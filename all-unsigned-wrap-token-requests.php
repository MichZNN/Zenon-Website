<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once 'functions.php';

$explorer_url = "https://zenonhub.io/explorer/account/";

$page     = isset($_GET['page']) && (int)$_GET['page'] >= 1 ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;

$data_valid  = false;
$output      = '';
$has_next_page = false;
$total_pages = 1;

$total_amount_znn_redeemed = 0;
$total_amount_znn_unredeemed = 0;

$total_amount_qsr_redeemed = 0;
$total_amount_qsr_unredeemed = 0;

$total_amount_znnethlp_redeemed = 0;
$total_amount_znnethlp_unredeemed = 0;

$data_array = all_unsigned_wrap_token_requests($page, $per_page);

// Debug output
// echo "<pre>"; var_dump($data_array); echo "</pre>";

/*
{
    "data": {
        "count": 1,
        "list": [
            {
                "networkClass": 2,
                "chainId": 1,
                "id": "77bf95dba9a37a8db67894e12b51b589a9a42635eb139ec1ee585f39ee3030d6",
                "toAddress": "0x0e9e393e145c98343bc13b29c1371412c1babc45",
                "tokenStandard": "zts1qsrxxxxxxxxxxxxxmrhjll",
                "tokenAddress": "0x96546afe4a21515a3a30cd3fd64a70eb478dc174",
                "amount": "440000000000",
                "fee": "13200000000",
                "signature": "",
                "creationMomentumHeight": 10578487,
                "token": {
                    "name": "QSR",
                    "symbol": "QSR",
                    "domain": "zenon.network",
                    "totalSupply": "3022726651228689",
                    "decimals": 8,
                    "owner": "z1qxemdeddedxt0kenxxxxxxxxxxxxxxxxh9amk0",
                    "tokenStandard": "zts1qsrxxxxxxxxxxxxxmrhjll",
                    "maxSupply": "9007199254740991",
                    "isBurnable": true,
                    "isMintable": true,
                    "isUtility": true
                },
                "confirmationsToFinality": 12
            }
        ]
    }
}
*/

if (isset($data_array['data']['count'])) {
    $total_records = (int)$data_array['data']['count'];
    $total_pages = ($total_records > 0) ? (int) ceil($total_records / $per_page) : 1;
    $has_next_page = $page < $total_pages;
}

if (isset($data_array['title'])) {
    $output      = htmlspecialchars($data_array['title']);
    $data_valid  = false;

} elseif (
    isset($data_array['data']['count']) &&
    (int)$data_array['data']['count'] === 0
) {
    $output      = 'No records found.';
    $data_valid  = true;

} elseif (
    isset($data_array['data']['list']) &&
    is_array($data_array['data']['list'])
) {

    foreach ($data_array['data']['list'] as $data) {

        $token_symbol = $data['token']['symbol'];
        $token_decimals = $data['token']['decimals'];
        $amount = divisor($data['amount'], $token_decimals);

        if (isset($data['redeemed'])) {
            if($token_symbol === 'ZNN') {
                $total_amount_znn_redeemed += $amount;
            } else if($token_symbol === 'QSR') {
                $total_amount_qsr_redeemed += $amount;
            } else if($token_symbol === 'ZNNETHLP') {
                $total_amount_znnethlp_redeemed += $amount;
            }
        } else {
            if($token_symbol === 'ZNN') {
                $total_amount_znn_unredeemed += $amount;
            } else if($token_symbol === 'QSR') {
                $total_amount_qsr_unredeemed += $amount;
            } else if($token_symbol === 'ZNNETHLP') {
                $total_amount_znnethlp_unredeemed += $amount;
            }
        }

        $to_address = htmlspecialchars($data['toAddress']);
        $creation_momentum_height = $data['creationMomentumHeight'];
        $momentums_by_height = momentums_by_height($creation_momentum_height, 1);
        $timestamp = isset($momentums_by_height["data"]["list"][0]["timestamp"]) ? $momentums_by_height["data"]["list"][0]["timestamp"] : null;
        $datetime = isset($timestamp) ? (new DateTime('@' . $timestamp))->format('Y-m-d H:i:s') : 'N/A';


        $output .= '<tr>' . PHP_EOL .
                    '<td>' . str_shorten($to_address) . '</td>' . PHP_EOL .
                    '<td>' . $datetime . '</td>' . PHP_EOL .
                    '<td>' . $amount . '</td>' . PHP_EOL .
                    '<td>' . htmlspecialchars($token_symbol) . '</td>' . PHP_EOL .
                    '</tr>' . PHP_EOL;
    }
    $data_valid = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="All unsigned wrap token requests">
    <meta property="og:title" content="All Unsigned Wrap Token Requests - Zenon Network">
    <meta property="og:url" content="https://zenon.turmin.com/all-unsigned-wrap-token-requests.php">
    <meta property="og:description" content="All Unsigned Wrap Token Requests">
    <meta property="og:image" content="https://zenon.turmin.com/card.php?t=All Unsigned Wrap Token Requests&dn1=Total ZNN Unredeemed&dv1=<?= $total_amount_znn_unredeemed; ?> &dn2=Total QSR Unredeemed&dv2=<?= $total_amount_qsr_unredeemed; ?>&ts=<?= time(); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="en_EN">
    <meta name="twitter:card" content="summary_large_image">
    <title>All Unsigned Wrap Token Requests - Zenon Network</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/img/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/img/favicon-16x16.png">
    <link href="lib/bootstrap@5.3.6/css/bootstrap.min.css" rel="stylesheet">
    <link href="lib/fontawesome@6.7.2/css/all.min.css" rel="stylesheet">
    <link href="lib/twbs@1.13.1/icons/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet">
</head>
<body>

<header class="py-3 custom-header tool-header">
  <div class="container tool-header-inner">
    <a class="btn home-btn" href="index.php" aria-label="Home">
      <i class="fa-solid fa-house"></i>
    </a>
  </div>
</header>

<div class="container mt-2">
  <div class="tool-intro">
    <h1>All unsigned wrap token requests</h1>
    <p>Browse bridge wrap requests awaiting signatures.</p>
  </div>
  <?php
    if (!$data_valid && $output) {
        echo '<div class="alert alert-warning">' . $output . '</div>' . PHP_EOL;
    }
    elseif ($data_valid && isset($data_array['data']['count']) && (int)$data_array['data']['count'] === 0) {
        echo '<div class="alert alert-info">' . $output . '</div>' . PHP_EOL;
    }
    if ($data_valid && (int)$data_array['data']['count'] > 0):
    ?>
    <table class="table">
        <tbody>
            <?php if($total_amount_znn_redeemed > 0): ?>
            <tr>
                <td>
                    Total ZNN Redeemed
                </td>
                <td>
                    <?php echo $total_amount_znn_redeemed; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if($total_amount_znn_unredeemed > 0): ?>
            <tr>
                <td>
                    Total ZNN Unredeemed
                </td>
                <td>
                    <?php echo $total_amount_znn_unredeemed; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if($total_amount_qsr_redeemed > 0): ?>
            <tr>
                <td>
                    Total QSR Redeemed
                </td>
                <td>
                    <?php echo $total_amount_qsr_redeemed; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if($total_amount_qsr_unredeemed > 0): ?>
            <tr>
                <td>
                    Total QSR Unredeemed
                </td>
                <td>
                    <?php echo $total_amount_qsr_unredeemed; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if($total_amount_znnethlp_redeemed > 0): ?>
            <tr>
                <td>
                    Total ZNNETHLP Redeemed
                </td>
                <td>
                    <?php echo $total_amount_znnethlp_redeemed; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if($total_amount_znnethlp_unredeemed > 0): ?>
            <tr>
                <td>
                    Total ZNNETHLP Unredeemed
                </td>
                <td>
                    <?php echo $total_amount_znnethlp_unredeemed; ?>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">To Address</th>
                <th scope="col">Creation</th>
                <th scope="col">Amount</th>
                <th scope="col">Token</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($data_valid && (int)$data_array['data']['count'] > 0) {
            echo $output;
        }
        ?>
        </tbody>
    </table>
</div>
<script src="lib/bootstrap@5.3.6/js/bootstrap.bundle.min.js"></script>
</body>
</html>
