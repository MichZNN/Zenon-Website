<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$cacheFile = __DIR__ . '/price_cache.json';
$cacheDuration = 10 * 60;
$apiBaseUrl = 'https://api.dexscreener.com/latest/dex/pairs';
$chain = 'ethereum';

$pairs = [
    'wznn_weth' => [
        'address' => '0xdac866a3796f85cb84a914d98faec052e3b5596d',
        'symbol' => 'ZNN',
    ],
    'wqsr_wznn' => [
        'address' => '0xe6c61425d0383c1cde02a49365945f48ebf0ea0c',
        'symbol' => 'QSR',
    ],
];

function utc_timestamp(int $timestamp): string
{
    return gmdate('Y-m-d\TH:i:s\Z', $timestamp);
}

function read_price_cache(string $cacheFile): array
{
    if (!is_file($cacheFile)) {
        return [];
    }

    $content = file_get_contents($cacheFile);
    if ($content === false) {
        return [];
    }

    $cache = json_decode($content, true);
    return is_array($cache) ? $cache : [];
}

function write_price_cache(string $cacheFile, array $cache): bool
{
    $cacheDir = dirname($cacheFile);
    if (!is_writable($cacheDir) || (file_exists($cacheFile) && !is_writable($cacheFile))) {
        return false;
    }

    return file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
}

function fetch_pair(string $apiBaseUrl, string $chain, string $address): array
{
    $url = implode('/', [$apiBaseUrl, $chain, $address]);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ZenonTools/1.0');

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['error' => $error];
        }

        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($status >= 400) {
            return ['error' => 'DexScreener returned HTTP ' . $status];
        }
    } elseif (in_array('https', stream_get_wrappers(), true)) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'header' => "User-Agent: ZenonTools/1.0\r\n",
            ],
        ]);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return ['error' => 'Unable to fetch pair data'];
        }
    } else {
        return ['error' => 'No HTTP client available'];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['error' => 'Invalid JSON response'];
    }

    $pair = $decoded['pair'] ?? ($decoded['pairs'][0] ?? null);
    if (!is_array($pair) || !isset($pair['priceUsd'])) {
        return ['error' => 'No valid pair price found'];
    }

    return ['pair' => $pair];
}

function normalize_pair(string $key, array $config, array $pair, int $timestamp, string $source): array
{
    return [
        'key' => $key,
        'address' => $config['address'],
        'name' => $pair['baseToken']['name'] ?? null,
        'symbol' => $config['symbol'],
        'price' => $pair['priceUsd'],
        'timestamp' => $timestamp,
        'timestamp_human' => utc_timestamp($timestamp),
        'source' => $source,
        'pricing_method' => $pair['pricingMethod'] ?? 'direct',
    ];
}

$cache = read_price_cache($cacheFile);
$cacheUpdated = false;
$results = [];
$errors = [];

foreach ($pairs as $key => $config) {
    $cached = $cache[$key] ?? null;
    $isFresh = is_array($cached)
        && isset($cached['timestamp'], $cached['pair'])
        && (time() - (int)$cached['timestamp'] < $cacheDuration);

    if ($isFresh) {
        $results[$key] = normalize_pair($key, $config, $cached['pair'], (int)$cached['timestamp'], 'cache');
        continue;
    }

    $fresh = fetch_pair($apiBaseUrl, $chain, $config['address']);
    if (!isset($fresh['error'])) {
        $timestamp = time();
        $cache[$key] = [
            'timestamp' => $timestamp,
            'pair' => $fresh['pair'],
        ];
        $cacheUpdated = true;
        $results[$key] = normalize_pair($key, $config, $fresh['pair'], $timestamp, 'api');
        continue;
    }

    $errors[$key] = $fresh['error'];
    if (is_array($cached) && isset($cached['timestamp'], $cached['pair'])) {
        $results[$key] = normalize_pair($key, $config, $cached['pair'], (int)$cached['timestamp'], 'stale-cache');
    }
}

// The QSR/ZNN pair quotes QSR in ZNN, so USD per ZNN is USD per QSR
// divided by ZNN per QSR. Use its timestamp to preserve cache freshness.
if (isset($errors['wznn_weth'], $cache['wqsr_wznn']['pair'])) {
    $quotePair = $cache['wqsr_wznn']['pair'];
    $usd = $quotePair['priceUsd'] ?? null;
    $native = $quotePair['priceNative'] ?? null;
    $quoteAddress = strtolower($quotePair['quoteToken']['address'] ?? '');
    if ($quoteAddress === '0xb2e96a63479c2edd2fd62b382c89d5ca79f572d3'
        && is_numeric($usd) && is_numeric($native) && (float)$usd > 0 && (float)$native > 0) {
        $timestamp = (int)$cache['wqsr_wznn']['timestamp'];
        $derivedPair = [
            'baseToken' => $quotePair['quoteToken'],
            'priceUsd' => rtrim(rtrim(number_format((float)$usd / (float)$native, 8, '.', ''), '0'), '.'),
            'pricingMethod' => 'derived-from-qsr-znn',
        ];
        $cache['wznn_weth'] = ['timestamp' => $timestamp, 'pair' => $derivedPair];
        $cacheUpdated = true;
        $source = time() - $timestamp < $cacheDuration ? 'derived' : 'stale-cache';
        $results['wznn_weth'] = normalize_pair('wznn_weth', $pairs['wznn_weth'], $derivedPair, $timestamp, $source);
    }
}

$cacheWritable = true;
if ($cacheUpdated) {
    $cacheWritable = write_price_cache($cacheFile, $cache);
}

$addressFilter = isset($_GET['address']) ? strtolower(trim((string)$_GET['address'])) : null;
$pairFilter = isset($_GET['pair']) ? trim((string)$_GET['pair']) : null;

if ($addressFilter !== null && $addressFilter !== '') {
    $results = array_filter($results, static function (array $item) use ($addressFilter): bool {
        return strtolower($item['address']) === $addressFilter;
    });
}

if ($pairFilter !== null && $pairFilter !== '') {
    $results = isset($results[$pairFilter]) ? [$pairFilter => $results[$pairFilter]] : [];
}

if (($addressFilter || $pairFilter) && count($results) === 0) {
    http_response_code(404);
}

echo json_encode([
    'data' => array_values($results),
    'errors' => $errors,
    'cache' => [
        'duration' => $cacheDuration,
        'writable' => $cacheWritable,
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
