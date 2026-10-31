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
 * Data comes from Zenon nodes over JSON-RPC: our own node first, then public
 * nodes as backup (the same list the Pillar Tracker uses). Override the list
 * with ZNN_NODE_RPC_URLS, comma separated. Results are wrapped as ['data' => ...]. */
const NODE_RPC_URLS = [
    'http://127.0.0.1:36097',
    'https://node.atsocy.com:35997',
    'https://my.hc1node.com:35997',
];

function node_rpc_urls(): array
{
    $configured = getenv('ZNN_NODE_RPC_URLS');
    if (is_string($configured) && trim($configured) !== '') {
        $urls = array_values(array_filter(array_map('trim', explode(',', $configured))));
        if ($urls) {
            return $urls;
        }
    }
    return NODE_RPC_URLS;
}

/* One call to one node. 'retry' says whether another node might do better. */
function node_rpc_call(string $url, string $method, array $params): array
{
    $payload = json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params]);
    $is_local = (bool)preg_match('#^https?://(127\.0\.0\.1|localhost)[:/]#', $url);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_CONNECTTIMEOUT => $is_local ? 2 : 5,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $response = curl_exec($ch);
    $status   = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($response === false || $status !== 200) {
        return ['error' => $error !== '' ? $error : "Node returned HTTP $status", 'retry' => true];
    }
    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['error' => 'The node returned an invalid JSON response.', 'retry' => true];
    }
    if (isset($decoded['error'])) {
        // A JSON-RPC error is the node's real answer (for example an invalid address): don't ask the others.
        return ['error' => (string)($decoded['error']['message'] ?? 'Node RPC error'), 'retry' => false];
    }
    if (!array_key_exists('result', $decoded)) {
        return ['error' => 'The node returned no result.', 'retry' => true];
    }
    return ['data' => $decoded['result'], 'source' => $url];
}

/* Ask each node in turn until one answers. */
function node_rpc(string $method, array $params = []): array
{
    $result = ['error' => 'No Zenon node is configured.'];
    foreach (node_rpc_urls() as $url) {
        $result = node_rpc_call($url, $method, $params);
        if (!isset($result['error']) || empty($result['retry'])) {
            break;
        }
    }
    if (isset($result['error']) && !empty($result['retry'])) {
        $result['error'] = 'The Zenon nodes could not be reached: ' . $result['error'];
    }
    unset($result['retry']);
    return $result;
}

/*  FRONTIER REWARD  */
function frontier_reward(string $address,
                         string $type      = 'pillar',
                         int    $page      = 1,
                         int    $per_page  = 25): array
{
    $page_index = max(0, $page - 1);
    return node_rpc("embedded.{$type}.getFrontierRewardByPage", [$address, $page_index, $per_page]);
}

/*  UNCOLLECTED REWARDS  */
function uncollected_rewards(string $address,
                             string $type = 'pillar'): array
{
    return node_rpc("embedded.{$type}.getUncollectedReward", [$address]);
}

/*  LIQUIDITY STAKE ENTRIES  */
function liquidity_stake_entries(string $address,
                                 int $page_index = 1,
                                 int $page_size  = 1000): array
{
    $page_index = max(0, $page_index - 1);
    return node_rpc('embedded.liquidity.getLiquidityStakeEntriesByAddress', [$address, $page_index, $page_size]);
}

/*  TOKEN BY ZTS  */
function token_by_zts(string $token): array
{
    return node_rpc('embedded.token.getByZts', [$token]);
}

/*  UNWRAP TOKEN REQUESTS  */
function unwrap_token_requests(string $to_address,
                               int $page_index = 1,
                               int $page_size  = 1000): array
{
    $page_index = max(0, $page_index - 1);
    return node_rpc('embedded.bridge.getAllUnwrapTokenRequestsByToAddress', [$to_address, $page_index, $page_size]);
}

/* ALL UNSIGNED WRAP TOKEN REQUESTS */
function all_unsigned_wrap_token_requests(int $page_index = 1,
                               int $page_size  = 1000): array
{
    $page_index = max(0, $page_index - 1);
    return node_rpc('embedded.bridge.getAllUnsignedWrapTokenRequests', [$page_index, $page_size]);
}

/* ALL UNWRAP TOKEN REQUESTS */
function all_unwrap_token_requests(int $page_index = 1,
                               int $page_size  = 1000): array
{
    $page_index = max(0, $page_index - 1);
    return node_rpc('embedded.bridge.getAllUnwrapTokenRequests', [$page_index, $page_size]);
}

/* MOMENTUMS BY HEIGHT */
function momentums_by_height(int $height = 1,
                               int $count  = 10): array
{
    return node_rpc('ledger.getMomentumsByHeight', [$height, $count]);
}

/* DETAILED MOMENTUMS BY HEIGHT */
function detailed_momentums_by_height(int $height = 1,
                               int $count  = 10): array
{
    return node_rpc('ledger.getDetailedMomentumsByHeight', [$height, $count]);
}

/* ACCOUNT BLOCKS
 * Transactions of an address, newest first. page_index starts at 0. */
function account_blocks_by_page(string $address, int $page_index = 0, int $page_size = 1000): array
{
    $page_index = max(0, $page_index);
    return node_rpc('ledger.getAccountBlocksByPage', [$address, $page_index, $page_size]);
}

/* ACCOUNT BALANCES */
function account_info_by_address(string $address): array
{
    return node_rpc('ledger.getAccountInfoByAddress', [$address]);
}

/* SENTINEL REGISTRATION AND REVOCATION STATUS */
function sentinel_by_owner(string $address): array
{
    return node_rpc('embedded.sentinel.getByOwner', [$address]);
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
