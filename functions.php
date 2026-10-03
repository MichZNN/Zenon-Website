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

/*  FRONTIER REWARD  */
function frontier_reward(string $address,
                         string $type      = 'pillar',
                         int    $page      = 1,
                         int    $per_page  = 25): array
{
    $url = build_url(
        "https://zenonhub.io/api/nom/{$type}/get-frontier-reward-by-page",
        [
            'address'   => $address,
            'page'      => max(0, $page - 1),
            'per_page'  => $per_page,
        ]
    );
    return api_get($url);
}

/*  UNCOLLECTED REWARDS  */
function uncollected_rewards(string $address,
                             string $type = 'pillar'): array
{
    $url = build_url(
        "https://zenonhub.io/api/nom/{$type}/get-uncollected-reward",
        ['address' => $address]
    );
    return api_get($url);
}

/*  LIQUIDITY STAKE ENTRIES  */
function liquidity_stake_entries(string $address,
                                 int $page_index = 1,
                                 int $page_size  = 1000): array
{
    $url = build_url(
        'https://zenonhub.io/api/nom/liquidity/get-liquidity-stake-entries-by-address',
        [
            'address'     => $address,
            'page_index'  => max(0, $page_index - 1),
            'page_size'   => $page_size,
        ]
    );
    return api_get($url);
}

/*  TOKEN BY ZTS  */
function token_by_zts(string $token): array
{
    $url = build_url(
        'https://zenonhub.io/api/nom/token/get-by-zts',
        ['token' => $token]
    );
    return api_get($url);
}

/*  UNWRAP TOKEN REQUESTS  */
function unwrap_token_requests(string $to_address,
                               int $page_index = 1,
                               int $page_size  = 1000): array
{
    $url = build_url(
        'https://zenonhub.io/api/nom/bridge/get-all-unwrap-token-requests-by-to-address',
        [
            'to_address'  => $to_address,
            'page_index'  => max(0, $page_index - 1),
            'page_size'   => $page_size,
        ]
    );
    return api_get($url);
}

/* ALL UNSIGNED WRAP TOKEN REQUESTS */
function all_unsigned_wrap_token_requests(int $page_index = 1,
                               int $page_size  = 1000): array
{
    $url = build_url(
        'https://zenonhub.io/api/nom/bridge/get-all-unsigned-wrap-token-requests',
        [
            'page_index'  => max(0, $page_index - 1),
            'page_size'   => $page_size,
        ]
    );
    return api_get($url);
}

/* ALL UNWRAP TOKEN REQUESTS */
function all_unwrap_token_requests(int $page_index = 1,
                               int $page_size  = 1000): array
{
    $url = build_url(
        'https://zenonhub.io/api/nom/bridge/get-all-unwrap-token-requests',
        [
            'page_index'  => max(0, $page_index - 1),
            'page_size'   => $page_size,
        ]
    );
    return api_get($url);
}

/* MOMENTUMS BY HEIGHT */
function momentums_by_height(int $height = 1,
                               int $count  = 10): array
{
    $url = build_url(
        'https://zenonhub.io/api/nom/ledger/get-momentums-by-height',
        [
            'height'  => $height,
            'count'   => $count,
        ]
    );
    return api_get($url);
}

/* DETAILED MOMENTUMS BY HEIGHT */
function detailed_momentums_by_height(int $height = 1,
                               int $count  = 10): array
{
    $url = build_url(
        'https://zenonhub.io/api/nom/ledger/get-detailed-momentums-by-height',
        [
            'height'  => $height,
            'count'   => $count,
        ]
    );
    return api_get($url);
}


/* ACCOUNT BALANCES */
function account_info_by_address(string $address): array
{
    return api_get(build_url('https://zenonhub.io/api/nom/ledger/get-account-info-by-address', ['address' => $address]));
}

/* SENTINEL REGISTRATION AND REVOCATION STATUS */
function sentinel_by_owner(string $address): array
{
    return api_get(build_url('https://zenonhub.io/api/nom/sentinel/get-by-owner', ['address' => $address]));
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
    $minutes = intdiv($remaining % 86400, 60);
    return $weeks . ' ' . ($weeks === 1 ? 'week' : 'weeks') . ', '
        . $days . ' ' . ($days === 1 ? 'day' : 'days') . ', '
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
