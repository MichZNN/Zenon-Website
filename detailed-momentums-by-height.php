<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once 'functions.php';

$page     = isset($_GET['page']) && (int)$_GET['page'] >= 1 ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
$type     = isset($_GET['type']) ? trim($_GET['type']) : 'pillar';

$data_valid  = false;
$output      = '';
$has_next_page = false;
$total_pages = 1;

if (!empty($_GET['height'])) {
    $height = trim($_GET['height']);
    // $count = !empty($_GET['count']) ? (int)$_GET['count'] : 25;

    // if (strlen($address) >= 40 && preg_match('/^[a-z0-9]+$/i', $address)) {
        $data_array = detailed_momentums_by_height($height, 1);

        // Debug output
        // echo "<pre>"; var_dump($data_array); echo "</pre>";
        
        if (isset($data_array['data']['count'])) {
            $total_records = (int)$data_array['data']['count'];
            $total_pages = ($total_records > 0) ? (int) ceil($total_records / $per_page) : 1;
            $has_next_page = $page < $total_pages;
        }
        
        if (isset($data_array['data']['list']) && is_array($data_array['data']['list'])) {
            foreach ($data_array['data']['list'] as $data) {
                
                $momentum = $data['momentum'];
                
                if (isset($momentum)) {

                    $output .= '<tr>' . PHP_EOL .
                       '<td>' . (isset($momentum['hash']) ? str_shorten($momentum['hash']) : '') . '</td>' . PHP_EOL .
                       '<td>' . (isset($momentum['previousHash']) ? htmlspecialchars($momentum['previousHash']) : '') . '</td>' . PHP_EOL .
                       '<td>' . (isset($momentum['height']) ? htmlspecialchars($momentum['height']) : '') . '</td>' . PHP_EOL .
                       '<td>' . (isset($momentum['timestamp']) ? (new DateTime('@' . $momentum['timestamp']))->format('Y-m-d H:i:s') : '') . '</td>' . PHP_EOL .
                       '<td>' . (isset($momentum['data']) ? htmlspecialchars($momentum['data']) : '') . '</td>' . PHP_EOL .
                       '<td>' . (isset($momentum['producer']) ? htmlspecialchars($momentum['producer']) : '') . '</td>' . PHP_EOL .
                    '</tr>' . PHP_EOL;
                }
            }
            $data_valid = true;
        } else {
            if (isset($data_array['error'])) {
                $output = htmlspecialchars($data_array['error']);
            } else {
                $output = 'No valid data received.';
            }
        }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta property="og:title" content=" - Zenon Network">
    <meta property="og:url" content="https://zenon.turmin.com/detailed-momentums-by-height.php">
    <meta property="og:description" content="">
    <meta property="og:locale" content="en_EN">
    <title> - Zenon Network</title>
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
      <input type="search" name="height" class="form-control custom-input" placeholder="Height" aria-label="Search" value="<?php echo isset($_GET['height']) ? htmlspecialchars($_GET['height']) : ''; ?>">
        &nbsp;
      <!-- <input type="search" name="count" class="form-control custom-input" placeholder="Count" aria-label="Search" value="<?php echo isset($_GET['count']) ? htmlspecialchars($_GET['count']) : ''; ?>"> -->

      <button class="btn btn-outline-secondary ms-2 custom-btn" type="submit" aria-label="Search">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>
    </form>

  </div>
</header>

<div class="container mt-2">
  <div class="tool-intro">
    <h1>Detailed momentums by height</h1>
    <p>Explore detailed ledger momentums starting at a specified height.</p>
  </div>
  <?php
    if (!$data_valid && $output) {
        echo '<div class="alert alert-warning">' . $output . '</div>' . PHP_EOL;
    }
  ?>
  <table class="table">
      <thead>
          <tr>
              <th scope="col">Hash</th>
              <th scope="col">Previous Hash</th>
              <th scope="col">Height</th>
              <th scope="col">Timestamp</th>
              <th scope="col">Data</th>
              <th scope="col">Producer</th>
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
