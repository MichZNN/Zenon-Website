<?php
/* DIVISOR */
function divisor(string $raw, int $decimals): string
{
    if (function_exists('bcdiv')) {
        $divisor = bcpow('10', (string)$decimals, 0);
        $out     = bcdiv($raw, $divisor, $decimals);
        return strpos($out, '.') !== false ? rtrim(rtrim($out, '0'), '.') : $out;
    }

    $val = (float)$raw / pow(10, $decimals);
    $out = number_format($val, $decimals, '.', '');
    return strpos($out, '.') !== false ? rtrim(rtrim($out, '0'), '.') : $out;
}

/* ADD AMOUNTS
 * Adds two decimal amounts as strings, so totals never turn into floats
 * (which print small numbers as 6.96E-5 and lose precision on large ones). */
function add_amount($total, $amount, int $scale = 18): string
{
    $total  = (string)$total;
    $amount = (string)$amount;

    if (function_exists('bcadd')) {
        $sum = bcadd($total, $amount, $scale);
    } else {
        // Without bcmath, round to the precision of the inputs to hide float noise.
        $places = max(strlen(strrchr($total, '.') ?: '.') - 1, strlen(strrchr($amount, '.') ?: '.') - 1);
        $sum = number_format((float)$total + (float)$amount, min($places, $scale), '.', '');
    }
    return strpos($sum, '.') !== false ? rtrim(rtrim($sum, '0'), '.') : $sum;
}

/* STRING SHORTEN */
function str_shorten(string $str, int $frontLen = 5, int $backLen = 5, string $ellipsis = '...'): string
{
    if (mb_strlen($str) <= $frontLen + $backLen) {
        return $str;
    }

    $prefix = mb_substr($str, 0, $frontLen);
    $suffix = mb_substr($str, -$backLen);

    return $prefix . $ellipsis . $suffix;
}

/* API GET */
function api_get(string $url, int $timeout = 30): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_FAILONERROR    => true,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['error' => $err];
    }

    curl_close($ch);
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        return ['error' => 'The API returned an invalid JSON response.'];
    }
    return $data;
}

/* BUILD URL */
function build_url(string $base, array $query = []): string
{
    return $base . ($query ? '?' . http_build_query($query) : '');
}

/* NODE RPC
 * Data comes from our own Zenon node over JSON-RPC. If the node can't be reached
 * or returns an error, the same question goes to the Zenon Hub API instead.
 * Both return the same shape, wrapped here as ['data' => ...]. */
const ZENONHUB_API = 'https://zenonhub.io/api/nom/';

function node_rpc_url(): string
{
    $url = getenv('ZNN_NODE_RPC_URL');
    return is_string($url) && $url !== '' ? $url : 'http://127.0.0.1:36097';
}

function node_rpc(string $method, array $params = []): array
{
    $payload = json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params]);
    $ch = curl_init(node_rpc_url());
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    $status   = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($response === false || $status !== 200) {
        return ['error' => $error !== '' ? $error : "Node returned HTTP $status"];
    }
    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['error' => 'The node returned an invalid JSON response.'];
    }
    if (isset($decoded['error'])) {
        return ['error' => (string)($decoded['error']['message'] ?? 'Node RPC error')];
    }
    if (!array_key_exists('result', $decoded)) {
        return ['error' => 'The node returned no result.'];
    }
    return ['data' => $decoded['result'], 'source' => 'node'];
}

/* Ask the node first; fall back to Zenon Hub with the equivalent request. */
function chain_query(string $method, array $params, string $hub_path, array $hub_query): array
{
    $result = node_rpc($method, $params);
    if (!isset($result['error'])) {
        return $result;
    }
    $fallback = api_get(build_url(ZENONHUB_API . $hub_path, $hub_query));
    if (is_array($fallback) && !isset($fallback['error']) && !isset($fallback['title'])) {
        $fallback['source'] = 'zenonhub';
    }
    return $fallback;
}

/*  FRONTIER REWARD  */
function frontier_reward(string $address,
                         string $type      = 'pillar',
                         int    $page      = 1,
                         int    $per_page  = 25): array
{
    $page_index = max(0, $page - 1);
    return chain_query(
        "embedded.{$type}.getFrontierRewardByPage", [$address, $page_index, $per_page],
        "{$type}/get-frontier-reward-by-page", ['address' => $address, 'page' => $page_index, 'per_page' => $per_page]
    );
}

/*  UNCOLLECTED REWARDS  */
function uncollected_rewards(string $address,
                             string $type = 'pillar'): array
{
    return chain_query(
        "embedded.{$type}.getUncollectedReward", [$address],
        "{$type}/get-uncollected-reward", ['address' => $address]
    );
}

/*  LIQUIDITY STAKE ENTRIES  */
function liquidity_stake_entries(string $address,
                                 int $page_index = 1,
                                 int $page_size  = 1000): array
{
    $page_index = max(0, $page_index - 1);
    return chain_query(
        'embedded.liquidity.getLiquidityStakeEntriesByAddress', [$address, $page_index, $page_size],
        'liquidity/get-liquidity-stake-entries-by-address', ['address' => $address, 'page_index' => $page_index, 'page_size' => $page_size]
    );
}

/*  TOKEN BY ZTS  */
function token_by_zts(string $token): array
{
    return chain_query(
        'embedded.token.getByZts', [$token],
        'token/get-by-zts', ['token' => $token]
    );
}

/*  UNWRAP TOKEN REQUESTS  */
function unwrap_token_requests(string $to_address,
                               int $page_index = 1,
                               int $page_size  = 1000): array
{
    $page_index = max(0, $page_index - 1);
    return chain_query(
        'embedded.bridge.getAllUnwrapTokenRequestsByToAddress', [$to_address, $page_index, $page_size],
        'bridge/get-all-unwrap-token-requests-by-to-address', ['to_address' => $to_address, 'page_index' => $page_index, 'page_size' => $page_size]
    );
}

/* ALL UNSIGNED WRAP TOKEN REQUESTS */
function all_unsigned_wrap_token_requests(int $page_index = 1,
                               int $page_size  = 1000): array
{
    $page_index = max(0, $page_index - 1);
    return chain_query(
        'embedded.bridge.getAllUnsignedWrapTokenRequests', [$page_index, $page_size],
        'bridge/get-all-unsigned-wrap-token-requests', ['page_index' => $page_index, 'page_size' => $page_size]
    );
}

/* ALL UNWRAP TOKEN REQUESTS */
function all_unwrap_token_requests(int $page_index = 1,
                               int $page_size  = 1000): array
{
    $page_index = max(0, $page_index - 1);
    return chain_query(
        'embedded.bridge.getAllUnwrapTokenRequests', [$page_index, $page_size],
        'bridge/get-all-unwrap-token-requests', ['page_index' => $page_index, 'page_size' => $page_size]
    );
}

/* MOMENTUMS BY HEIGHT */
function momentums_by_height(int $height = 1,
                               int $count  = 10): array
{
    return chain_query(
        'ledger.getMomentumsByHeight', [$height, $count],
        'ledger/get-momentums-by-height', ['height' => $height, 'count' => $count]
    );
}

/* DETAILED MOMENTUMS BY HEIGHT */
function detailed_momentums_by_height(int $height = 1,
                               int $count  = 10): array
{
    return chain_query(
        'ledger.getDetailedMomentumsByHeight', [$height, $count],
        'ledger/get-detailed-momentums-by-height', ['height' => $height, 'count' => $count]
    );
}

/* ACCOUNT BLOCKS
 * Transactions of an address, newest first. page_index starts at 0. */
function account_blocks_by_page(string $address, int $page_index = 0, int $page_size = 1000): array
{
    $page_index = max(0, $page_index);
    return chain_query(
        'ledger.getAccountBlocksByPage', [$address, $page_index, $page_size],
        'ledger/get-account-blocks-by-page', ['address' => $address, 'page_index' => $page_index, 'page_size' => $page_size]
    );
}

/* ACCOUNT BALANCES */
function account_info_by_address(string $address): array
{
    return chain_query(
        'ledger.getAccountInfoByAddress', [$address],
        'ledger/get-account-info-by-address', ['address' => $address]
    );
}

/* SENTINEL REGISTRATION AND REVOCATION STATUS */
function sentinel_by_owner(string $address): array
{
    return chain_query(
        'embedded.sentinel.getByOwner', [$address],
        'sentinel/get-by-owner', ['address' => $address]
    );
}

function format_cooldown($seconds): string
{
    if (!is_numeric($seconds) || (float)$seconds < 0) {
        return 'Unknown';
    }
    $remaining = (int)$seconds;
    $weeks = intdiv($remaining, 604800);
    $remaining %= 604800;
    $days = intdiv($remaining, 86400);
    $remaining %= 86400;
    $hours = intdiv($remaining, 3600);
    $minutes = intdiv($remaining % 3600, 60);
    return $weeks . ' ' . ($weeks === 1 ? 'week' : 'weeks') . ', '
        . $days . ' ' . ($days === 1 ? 'day' : 'days') . ', '
        . $hours . ' ' . ($hours === 1 ? 'hour' : 'hours') . ', '
        . $minutes . ' ' . ($minutes === 1 ? 'minute' : 'minutes');
}

function status_badge($value): string
{
    $boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($value === null || $boolean === null) {
        return '<span class="status-unknown">Unknown</span>';
    }
    $label = $boolean ? 'true' : 'false';
    $icon = $boolean ? 'circle-check' : 'circle-xmark';
    return '<span class="status-' . $label . '"><i class="fa-solid fa-' . $icon . '" aria-hidden="true"></i> ' . $label . '</span>';
}
