# Zenon Network tools

A website for exploring Zenon Network balances, rewards, Sentinel status and bridge requests. The home page provides links to network resources and displays ZNN and QSR prices in USD.

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

Network data is retrieved from Zenon Hub. Search results use URL parameters, allowing queries to be bookmarked or shared. Multi-address lookups retain successful results when another address fails.

The Sentinel revocation page displays status; it does not submit revocation transactions. Registration timestamps use ISO 8601 UTC, and cooldowns are shown in weeks, days and minutes.

Prices are retrieved from DexScreener and cached for ten minutes. When the direct ZNN price source is unavailable, ZNN/USD is calculated from the QSR/ZNN pair. Cached prices can be displayed when the provider cannot be refreshed.

## Brand palette

| Color | Hex |
| --- | --- |
| Intergalactic Black | `#151515` |
| Zenon Green | `#6FF34D` |
| Quasar Blue | `#0061EB` |
| Plasma Pink | `#F91690` |
| SYRIUS Green | `#00D557` |

## License

See [LICENSE](LICENSE).
