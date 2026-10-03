<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once 'functions.php';

$data_valid  = false;
$output      = '';
$errors = [];
$address = isset($_GET['address']) && is_string($_GET['address']) ? trim($_GET['address']) : '';

if (!empty($_GET['address'])) {


    if (strlen($address) >= 40 && preg_match('/^[a-z0-9]+$/i', $address)) {

        $types = ['pillar', 'sentinel', 'stake', 'liquidity'];

        foreach ($types as $type) {
            $data_array = uncollected_rewards($address, $type);

            if (isset($data_array['data']['znnAmount'], $data_array['data']['qsrAmount'])) {
                $data = $data_array['data'];
                $output .= '<tr>' . PHP_EOL .
                            '<td>' . ucwords($type) . '</td>' . PHP_EOL .
                            '<td>' . divisor($data['znnAmount'], 8) . '</td>' . PHP_EOL .
                            '<td>' . divisor($data['qsrAmount'], 8) . '</td>' . PHP_EOL .
                            '</tr>' . PHP_EOL;
                $data_valid = true;
            } else {
                if (isset($data_array['title'])) {
                    $errors[] = ucwords($type) . ': ' . htmlspecialchars($data_array['title']);
                } else {
                    $errors[] = ucwords($type) . ': No valid data received.';
                }
            }
        }
    } else {
        $errors[] = 'Invalid address';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Uncollected rewards by address">
    <meta property="og:title" content="Uncollected Rewards - Zenon Network">
    <meta property="og:url" content="https://zenon.turmin.com/uncollected-rewards.php">
    <meta property="og:description" content="Uncollected rewards by address">
    <meta property="og:locale" content="en_EN">
    <title>Uncollected Rewards - Zenon Network</title>
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
      <input type="search" name="address" class="form-control custom-input" placeholder="Type address" aria-label="Search" value="<?php echo isset($_GET['address']) ? htmlspecialchars($address) : ''; ?>">
      
      <button class="btn btn-outline-secondary ms-2 custom-btn" type="submit" aria-label="Search">
        <i class="fas fa-search"></i>
      </button>
    </form>

  </div>
</header>

<main class="container mt-2">
  <div class="tool-intro">
    <h1>Uncollected rewards</h1>
    <p>View pending ZNN and QSR rewards from Pillars, Sentinels, staking and liquidity for an address.</p>
  </div>
  <?php
  if ($data_valid) {
      echo '<h1 class="responsive-title">' . htmlspecialchars($address) . '</h1>' . PHP_EOL;
  }
  foreach ($errors as $error) {
      echo '<div class="alert alert-warning">' . $error . '</div>' . PHP_EOL;
  }
  ?>
  <table class="table">
      <thead>
          <tr>
              <th scope="col">Type</th>
              <th scope="col">ZNN Amount</th>
              <th scope="col">QSR Amount</th>
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

</main>
<script src="lib/bootstrap@5.3.6/js/bootstrap.bundle.min.js"></script>
</body>
</html>