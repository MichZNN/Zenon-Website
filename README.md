# Momentum Tools

A website for looking up balances, rewards, Sentinel status, bridge requests and momentums. The home page lists every tool as a card, grouped by whether it needs an address, and the top bar shows ZNN and QSR prices in USD.

## Tools

| Tool | Description |
| --- | --- |
| [Uncollected rewards](uncollected-rewards.php) | Pending ZNN and QSR rewards from Pillars, Sentinels, staking and liquidity for an address. |
| [Frontier rewards](frontier-reward.php) | Rewards per epoch, with reward-type filters and pagination. |
| [Balance by address](balance-by-address.php) | Token balances for multiple addresses and their combined totals. Addresses can be separated by commas, spaces or new lines. |
| [Sentinel revocation](sentinel-revocation.php) | Registration time in UTC, revocation eligibility, cooldown and active status for multiple Sentinel owners. |
| [Liquidity stake entries](liquidity-stake-entries.php) | Liquidity staking entries for an address. |
| [Unwrap token requests by address](unwrap-token-requests.php) | Bridge unwrap requests for a destination address. |
| [All unwrap token requests](all-unwrap-token-requests.php) | Paginated overview of bridge unwrap requests. |
| [All unsigned wrap token requests](all-unsigned-wrap-token-requests.php) | Paginated overview of bridge wrap requests awaiting signatures. |

The [Detailed momentums by height](detailed-momentums-by-height.php) page displays detailed ledger momentums starting at a specified height.

## Data and behavior

Network data comes from Zenon nodes over JSON-RPC: our own node (`http://127.0.0.1:36097`) first, then the public nodes `node.atsocy.com` and `my.hc1node.com` as backup. Set `ZNN_NODE_RPC_URLS` (comma separated) to use other nodes. Search results use URL parameters, allowing queries to be bookmarked or shared. Multi-address lookups retain successful results when another address fails.

The Sentinel revocation page displays status; it does not submit revocation transactions. Registration timestamps use ISO 8601 UTC, and cooldowns are shown in weeks, days, hours and minutes.

Prices are retrieved from DexScreener and cached for ten minutes. When the direct ZNN price source is unavailable, ZNN/USD is calculated from the QSR/ZNN pair. Cached prices can be displayed when the provider cannot be refreshed.

## Structure

Shared parts live in `inc/`: `tools.php` is the single list of tools (title, description, category, input) used by the home page, the menu and each page's title; `head.php`, `topbar.php` and `footer.php` are included by every page. Styling is in `css/site.css`, and `js/site.js` handles the menu, prices and phone-friendly tables. To add a tool, add a PHP page and an entry in `inc/tools.php`.

## Palette

Each tool category has its own colour, used for its card, page accent and buttons.

| Role | Hex |
| --- | --- |
| Background | `#0E1420` |
| Panel | `#151D2B` |
| Text | `#E9EEF5` |
| Muted text | `#94A0B4` |
| Rewards | `#5EE6A8` |
| Balances & staking | `#7CB7FF` |
| Sentinels | `#C4A3FF` |
| Bridge | `#FFB86B` |
| Ledger | `#A9B4C6` |

Fonts: Bricolage Grotesque and Inter, both under the SIL Open Font License, self-hosted in `fonts/`.

## License

See [LICENSE](LICENSE).
