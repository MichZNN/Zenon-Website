<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="img/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="img/favicon.svg" />
    <link rel="shortcut icon" href="img/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="img/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="Zenon Tools" />
    <link rel="manifest" href="img/site.webmanifest" />
    <link href="lib/bootstrap@5.3.6/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/index.css" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zenon Network &bull; Network of Momentum</title>
</head>
<body class="homepage">

<nav class="navbar navbar-expand-lg navbar-custom fixed-top">
  <div class="container">
    <a class="navbar-brand" href="index.php" aria-label="Home"></a>

    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarNav"
            aria-controls="navbarNav"
            aria-expanded="false"
            aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav">

        <li class="nav-item">
          <a class="nav-link" href="https://zenon.network/">Zenon Network</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="https://github.com/zenon-network/zenon.network/releases/tag/whitepaper">Whitepaper</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="https://www.satoshisl1.com/">A Revolution</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="https://app.uniswap.org/tokens/ethereum/0xb2e96a63479c2edd2fd62b382c89d5ca79f572d3">Buy Zenon</a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="toolsDropdown"
             role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Tools
          </a>

          <ul class="dropdown-menu" aria-labelledby="toolsDropdown">
            <li><a class="dropdown-item" href="uncollected-rewards.php">Uncollected rewards</a></li>
            <li><a class="dropdown-item" href="frontier-reward.php">Frontier rewards</a></li>
            <li><a class="dropdown-item" href="balance-by-address.php">Balance by address</a></li>
            <li><a class="dropdown-item" href="sentinel-revocation.php">Sentinel revocation</a></li>
            <li><a class="dropdown-item" href="liquidity-stake-entries.php">Liquidity stake entries</a></li>
            <li><a class="dropdown-item" href="unwrap-token-requests.php">Unwrap token requests by address</a></li>
            <li><a class="dropdown-item" href="all-unwrap-token-requests.php">All unwrap token requests</a></li>
            <li><a class="dropdown-item" href="all-unsigned-wrap-token-requests.php">All unsigned wrap token requests</a></li>
          </ul>
        </li>

        <li class="nav-item">
          <span class="nav-link navbar-price" id="navbarPrices" role="status">ZNN - · QSR -</span>
        </li>

      </ul>
    </div>
  </div>
</nav>


    <main class="homepage-hero">
        <div class="container">
            <div class="logo-wrap">
                <img class="logo" src="img/zn.svg" alt="ZN logo">
                <p class="homepage-kicker"><span>Network of Momentum</span></p>
            </div>
        </div>
    </main>

<script>
const homeLogo = document.querySelector('.homepage-hero .logo');
const homeTagline = document.querySelector('.homepage-kicker span');
if (homeLogo && homeTagline) {
    const fitHomeTagline = () => {
        homeTagline.style.fontSize = '100px';
        const textWidth = homeTagline.getBoundingClientRect().width;
        const logoWidth = homeLogo.getBoundingClientRect().width;
        if (textWidth > 0 && logoWidth > 0) {
            homeTagline.style.fontSize = `${100 * logoWidth / textWidth}px`;
        }
    };
    fitHomeTagline();
    new ResizeObserver(fitHomeTagline).observe(homeLogo);
    window.addEventListener('resize', fitHomeTagline);
    document.fonts.ready.then(fitHomeTagline);
}

const navbarPrices = document.getElementById('navbarPrices');
if (navbarPrices) {
    fetch('api/prices.php')
        .then(response => {
            if (!response.ok) throw new Error('Price request failed');
            return response.json();
        })
        .then(payload => {
            const prices = Array.isArray(payload.data) ? payload.data : [];
            navbarPrices.textContent = ['ZNN', 'QSR'].map(symbol => {
                const item = prices.find(price => price.symbol === symbol);
                const value = item ? Number(item.price) : NaN;
                return Number.isFinite(value) && value > 0
                    ? `${symbol} $${value.toLocaleString('en-US', { maximumSignificantDigits: 5 })}`
                    : `${symbol} -`;
            }).join(' · ');
            navbarPrices.title = prices.some(item => item.source === 'stale-cache')
                ? 'Last available prices; the price provider could not be refreshed.'
                : 'USD prices. ZNN may be calculated from the QSR/ZNN market.';
        })
        .catch(() => {
            navbarPrices.textContent = 'ZNN - · QSR -';
            navbarPrices.title = 'The price provider is currently unavailable.';
        });
}
</script>
<script src="lib/bootstrap@5.3.6/js/bootstrap.bundle.min.js"></script>

</body>
</html>
