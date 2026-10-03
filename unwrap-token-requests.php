<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once 'functions.php';

$data_valid  = false;
$output      = '';

if (!empty($_GET['address'])) {
    $address = trim($_GET['address']);

    if (strlen($address) >= 40 && preg_match('/^[a-z0-9]+$/i', $address)) {

        $data_array = unwrap_token_requests($address);

        if(isset($data_array['data'])) {
            $count = isset($data_array['data']['count']) ? $data_array['data']['count'] : 0;
        }

        if (isset($data_array['data']['list']) && is_array($data_array['data']['list'])) {
            $data_list = $data_array['data']['list'];

            foreach($data_list as $data) {

                $output .= '<tr>' . PHP_EOL .
                '<td>' . divisor($data['amount'], 8) . '</td>' . PHP_EOL .
                '<td>' . $data['token']['symbol'] . '</td>' . PHP_EOL .
                '<td>' . ($data['redeemed'] ? 'Yes' : 'No') . '</td>' . PHP_EOL .
                '</tr>' . PHP_EOL;
            }
            $data_valid = true;
        } else {
            $output = 'No valid data received.';
        }
    } else {
        $output = 'Invalid address';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Unwrap Token Requests by address">
    <meta property="og:title" content="Unwrap Token Requests - Zenon Network">
    <meta property="og:url" content="https://zenon.turmin.com/unwrap-token-requests.php">
    <meta property="og:description" content="Unwrap Token Requests by address">
    <meta property="og:locale" content="en_EN">
    <title>Unwrap Token Requests - Zenon Network</title>
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
    <form method="GET" class="tool-search-form" id="searchForm">
      <input type="search" name="address" class="form-control custom-input" placeholder="Type address" aria-label="Search" value="<?php echo isset($_GET['address']) ? htmlspecialchars($_GET['address']) : ''; ?>">
      
      <button class="btn btn-outline-secondary ms-2 custom-btn" type="submit" aria-label="Search">
        <i class="fas fa-search"></i>
      </button>
    </form>

  </div>
</header>

<div class="container mt-2">
  <div class="tool-intro">
    <h1>Unwrap token requests by address</h1>
    <p>View bridge unwrap requests for a destination address.</p>
  </div>
  <?php
  if ($data_valid) {
      echo '<h1 class="responsive-title">' . htmlspecialchars($address) . '</h1>' . PHP_EOL;
  } elseif ($output) {
      echo '<div class="alert alert-warning">' . $output . '</div>' . PHP_EOL;
  }
  ?>
  <table class="table">
      <thead>
          <tr>
              <th scope="col">Amount</th>
              <th scope="col">Symbol</th>
              <th scope="col">Redeemed</th>
          </tr>
      </thead>
      <tbody>
          <?php
          if ($data_valid) {
              echo $output;
          }
          ?>
      </tbody>
  </table>

</div>
<script src="lib/bootstrap@5.3.6/js/bootstrap.bundle.min.js"></script>
</body>
</html>
