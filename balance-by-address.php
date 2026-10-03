<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once 'functions.php';

$data_valid = false;
$error_message = '';
$output_addresses = '';
$output_total = '';
$total_balances = [];
$normalized_input = '';

if (!empty($_GET['address']) && is_string($_GET['address'])) {
    $addresses_input = is_string($_GET['address']) ? trim($_GET['address']) : '';

    $addresses = preg_split('/[,\s\n\r\t]+/', $addresses_input);
    $addresses = array_map('trim', $addresses);
    $addresses = array_unique(array_filter($addresses));

    $normalized_input = implode("\n", $addresses);
    $url_format = implode(',', $addresses);

    $valid_addresses = 0;

    foreach ($addresses as $address) {
        if (strlen($address) >= 40 && preg_match('/^[a-z0-9]+$/i', $address)) {

            $data_array = account_info_by_address($address);

            if (isset($data_array['data']['balanceInfoMap']) && is_array($data_array['data']['balanceInfoMap'])) {

                $valid_addresses++;

                $output_addresses .= '<h1 class="responsive-title">' . htmlspecialchars($address) . '</h1>' . PHP_EOL;
                $output_addresses .= '<table class="table">' . PHP_EOL .
                '<thead>' . PHP_EOL .
                    '<tr>' . PHP_EOL .
                        '<th>Token</th>' . PHP_EOL .
                        '<th>Symbol</th>' . PHP_EOL .
                        '<th>Balance</th>' . PHP_EOL .
                    '</tr>' . PHP_EOL .
                '</thead>' . PHP_EOL .
                '<tbody>' . PHP_EOL;

                foreach($data_array['data']['balanceInfoMap'] as $balance_info_map) {

                    $name = htmlspecialchars($balance_info_map['token']['name']);
                    $symbol = htmlspecialchars($balance_info_map['token']['symbol']);
                    $decimals = (int) $balance_info_map['token']['decimals'];
                    $balance = $balance_info_map['balance'];
                    $formatted_balance = divisor($balance, $decimals);

                    if ($balance === '0') {
                        continue; // Skip zero balances
                    }

                    $output_addresses .= '<tr>' . PHP_EOL .
                                '<td>' . $name . '</td>' . PHP_EOL .
                                '<td>' . $symbol . '</td>' . PHP_EOL .
                                '<td>' . $formatted_balance . '</td>' . PHP_EOL .
                                '</tr>' . PHP_EOL;

                    $token_key = $balance_info_map['token']['tokenStandard'] ?? ($name . ':' . $symbol . ':' . $decimals);
                    if (!isset($total_balances[$token_key])) {
                        $total_balances[$token_key] = [
                            'name' => $name,
                            'symbol' => $symbol,
                            'raw_total' => '0',
                            'decimals' => $decimals
                        ];
                    }
                    if (function_exists('bcadd')) {
                        $total_balances[$token_key]['raw_total'] = bcadd($total_balances[$token_key]['raw_total'], $balance, 0);
                    } else {
                        $total_balances[$token_key]['raw_total'] = (string)((int)$total_balances[$token_key]['raw_total'] + (int)$balance);
                    }
                }

                $output_addresses .= '</tbody>' . PHP_EOL . '</table>' . PHP_EOL . '<br>' . PHP_EOL;

            } else {
                $error_message .= htmlspecialchars($address . ': ' . ($data_array['title'] ?? $data_array['error'] ?? 'No valid balance data received.')) . '<br>';
            }
        } else {
            $error_message .= htmlspecialchars($address) . ': Invalid address<br>';
        }
    }

    if (!empty($total_balances) && $valid_addresses > 1) {
        $data_valid = true;
        
        $output_total .= '<h1 class="responsive-title">Total balances</h1>' . PHP_EOL;
        $output_total .= '<table class="table">' . PHP_EOL .
        '<thead>' . PHP_EOL .
            '<tr>' . PHP_EOL .
                '<th>Token</th>' . PHP_EOL .
                '<th>Symbol</th>' . PHP_EOL .
                '<th>Balance</th>' . PHP_EOL .
            '</tr>' . PHP_EOL .
        '</thead>' . PHP_EOL .
        '<tbody>' . PHP_EOL;
        
        foreach ($total_balances as $token_data) {
            $output_total .= '<tr>' . PHP_EOL .
                       '<td>' . $token_data['name'] . '</td>' . PHP_EOL .
                       '<td>' . $token_data['symbol'] . '</td>' . PHP_EOL .
                       '<td>' . divisor($token_data['raw_total'], $token_data['decimals']) . '</td>' . PHP_EOL .
                       '</tr>' . PHP_EOL;
        }
        
        $output_total .= '</table>' . PHP_EOL;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Balance by address">
    <meta property="og:title" content="Balance - Zenon Network">
    <meta property="og:url" content="https://zenon.turmin.com/balance-by-address.php">
    <meta property="og:description" content="Balance by address">
    <meta property="og:locale" content="en_EN">
    <title>Balance - Zenon Network</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/img/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/img/favicon-16x16.png">
    <link href="lib/bootstrap@5.3.6/css/bootstrap.min.css" rel="stylesheet">
    <link href="lib/fontawesome@6.7.2/css/all.min.css" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet">
</head>
<body>

<header class="py-3 custom-header tool-header">
  <div class="container tool-header-inner">
    <a class="btn home-btn" href="index.php" aria-label="Home"><i class="fa-solid fa-house" aria-hidden="true"></i></a>
    <form method="GET" class="tool-search-form" id="searchForm">
      <textarea name="address" class="form-control custom-textarea" rows="4" placeholder="Multiple addresses (comma or space separated)" aria-label="Multiple addresses"><?php echo htmlspecialchars($normalized_input); ?></textarea>
      
      <button class="btn btn-outline-secondary ms-2 custom-btn" type="submit" aria-label="Search">
        <i class="fas fa-search"></i>
      </button>
    </form>

  </div>
</header>

<main class="container mt-2">
  <div class="tool-intro">
    <h1>Balance by address</h1>
    <p>View token balances for multiple addresses and their combined totals.</p>
  </div>
  <?php
  if ($error_message) {
      echo '<div class="alert alert-warning">' . $error_message . '</div>' . PHP_EOL;
  }
  if ($output_total) {
      echo $output_total;
  }
  if ($output_addresses) {
      echo $output_addresses;
  }
  ?>
</main>
<script src="lib/bootstrap@5.3.6/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('searchForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            const textarea = form.querySelector('textarea[name="address"]');
            if (!textarea || !textarea.value.trim()) {
                return;
            }
            const addresses = textarea.value.split(/[,\s\n\r\t]+/)
                                          .map(addr => addr.trim())
                                          .filter(addr => addr.length > 0);
            if (addresses.length > 0) {
                const urlFormat = addresses.join(',');
                const newUrl = window.location.pathname + '?address=' + encodeURIComponent(urlFormat);
                window.location.href = newUrl;
            }
        });
    }
});
</script>
</body>
</html>