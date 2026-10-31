<?php
/*
 * Single list of tools, used by the home page cards, the menu and each page's <head>.
 * Category decides the colour; input tells visitors what they need before opening a tool.
 */

const SITE_NAME = 'Momentum Tools';

const TOOL_GROUPS = [
    'address' => 'Look up an address',
    'network' => 'Browse the network',
];

const TOOLS = [
    'uncollected-rewards' => [
        'title'    => 'Uncollected rewards',
        'summary'  => 'Pending ZNN and QSR from Pillars, Sentinels, staking and liquidity.',
        'intro'    => 'View pending ZNN and QSR rewards from Pillars, Sentinels, staking and liquidity for an address.',
        'category' => 'rewards',
        'input'    => 'One address',
        'group'    => 'address',
    ],
    'frontier-reward' => [
        'title'    => 'Frontier rewards',
        'summary'  => 'Rewards per epoch, filtered by reward type.',
        'intro'    => 'Explore rewards per epoch for an address, filtered by reward type.',
        'category' => 'rewards',
        'input'    => 'One address',
        'group'    => 'address',
    ],
    'balance-by-address' => [
        'title'    => 'Balance by address',
        'summary'  => 'Token balances per address, plus the combined total.',
        'intro'    => 'View token balances for multiple addresses and their combined totals.',
        'category' => 'balances',
        'input'    => 'One or more addresses',
        'group'    => 'address',
    ],
    'transaction-history' => [
        'title'    => 'Transaction history',
        'summary'  => 'Incoming and outgoing transfers for a period, as a table or CSV file.',
        'intro'    => 'View the incoming and outgoing transfers of an address for a period, and download them as a CSV file for your records or tax return. Dates and times are in UTC.',
        'category' => 'balances',
        'input'    => 'One address',
        'group'    => 'address',
    ],
    'liquidity-stake-entries' => [
        'title'    => 'Liquidity stake entries',
        'summary'  => 'Liquidity staking entries with start, revoke and expiry times.',
        'intro'    => 'View liquidity staking entries for an address.',
        'category' => 'balances',
        'input'    => 'One address',
        'group'    => 'address',
    ],
    'sentinel-revocation' => [
        'title'    => 'Sentinel revocation',
        'summary'  => 'Registration time, revocation window and cooldown per Sentinel owner.',
        'intro'    => 'Check registration, revocation eligibility, cooldown and active status for multiple Sentinel owners. Registration times are shown in UTC.',
        'category' => 'sentinel',
        'input'    => 'One or more addresses',
        'group'    => 'address',
    ],
    'unwrap-token-requests' => [
        'title'    => 'Unwrap requests by address',
        'summary'  => 'Bridge unwrap requests sent to one destination address.',
        'intro'    => 'View bridge unwrap requests for a destination address.',
        'category' => 'bridge',
        'input'    => 'One address',
        'group'    => 'address',
    ],
    'all-unwrap-token-requests' => [
        'title'    => 'All unwrap requests',
        'summary'  => 'Every bridge unwrap request, with redeemed and open totals.',
        'intro'    => 'Browse bridge unwrap requests across all destination addresses.',
        'category' => 'bridge',
        'input'    => 'No address needed',
        'group'    => 'network',
    ],
    'all-unsigned-wrap-token-requests' => [
        'title'    => 'Unsigned wrap requests',
        'summary'  => 'Bridge wrap requests still waiting for signatures.',
        'intro'    => 'Browse bridge wrap requests awaiting signatures.',
        'category' => 'bridge',
        'input'    => 'No address needed',
        'group'    => 'network',
    ],
    'detailed-momentums-by-height' => [
        'title'    => 'Momentums by height',
        'summary'  => 'Momentum details starting from a ledger height.',
        'intro'    => 'Explore detailed ledger momentums starting at a specified height.',
        'category' => 'ledger',
        'input'    => 'Momentum height',
        'group'    => 'network',
    ],
];

const CATEGORY_NAMES = [
    'rewards'  => 'Rewards',
    'balances' => 'Balances & staking',
    'sentinel' => 'Sentinels',
    'bridge'   => 'Bridge',
    'ledger'   => 'Ledger',
];

function current_tool_slug(): string
{
    return basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
}

function current_tool(): ?array
{
    return TOOLS[current_tool_slug()] ?? null;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/* Shared title and introduction for a tool page. */
function tool_intro(): void
{
    $tool = current_tool();
    if (!$tool) {
        return;
    }
    echo '<div class="tool-intro" data-category="' . e($tool['category']) . '">' . PHP_EOL
       . '  <p class="tool-category">' . e(CATEGORY_NAMES[$tool['category']]) . '</p>' . PHP_EOL
       . '  <h1>' . e($tool['title']) . '</h1>' . PHP_EOL
       . '  <p class="tool-lede">' . e($tool['intro']) . '</p>' . PHP_EOL
       . '</div>' . PHP_EOL;
}
