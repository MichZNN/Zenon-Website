<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once 'functions.php';

$page     = isset($_GET['page']) && (int)$_GET['page'] >= 1 ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
$type     = isset($_GET['type']) && is_string($_GET['type']) ? trim($_GET['type']) : 'pillar';
$per_page = in_array($per_page, [10, 25, 50, 100, 200], true) ? $per_page : 25;
$type = in_array($type, ['pillar', 'sentinel', 'stake', 'liquidity'], true) ? $type : 'pillar';

$data_valid  = false;
$output      = '';
$has_next_page = false;
$total_pages = 1;

if (!empty($_GET['address'])) {
    $address = trim($_GET['address']);

    if (strlen($address) >= 40 && preg_match('/^[a-z0-9]+$/i', $address)) {
        $data_array = frontier_reward($address, $type, $page, $per_page);

        // Debug output
        // echo "<pre>"; var_dump($data_array); echo "</pre>";

        if (isset($data_array['data']['count'])) {
            $total_records = (int)$data_array['data']['count'];
            $total_pages = ($total_records > 0) ? (int) ceil($total_records / $per_page) : 1;
            $has_next_page = $page < $total_pages;
        }

        if (isset($data_array['data']['list']) && is_array($data_array['data']['list'])) {
            foreach ($data_array['data']['list'] as $data) {
                $output .= '<tr>' . PHP_EOL .
                           '<td>' . htmlspecialchars($data['epoch']) . '</td>' . PHP_EOL .
                           '<td>' . divisor($data['znnAmount'], 8) . '</td>' . PHP_EOL .
                           '<td>' . divisor($data['qsrAmount'], 8) . '</td>' . PHP_EOL .
                           '</tr>' . PHP_EOL;
            }
            $data_valid = true;
        } else {
            if (isset($data_array['title'])) {
                $output = htmlspecialchars($data_array['title']);
            } else {
                $output = 'No valid data received.';
            }
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
    <meta name="description" content="Rewards per epoch by address">
    <meta property="og:title" content="Frontier Rewards - Zenon Network">
    <meta property="og:url" content="https://zenon.turmin.com/frontier-reward.php">
    <meta property="og:description" content="Rewards per epoch by address">
    <meta property="og:locale" content="en_EN">
    <title>Frontier Rewards - Zenon Network</title>
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
  <div class="container tool-header-inner frontier-header-inner">
    <a class="btn home-btn" href="index.php" aria-label="Home"><i class="fa-solid fa-house" aria-hidden="true"></i></a>
    <form method="GET" class="tool-search-form frontier-search-form" id="searchForm">
      <input type="search" name="address" class="form-control custom-input" placeholder="Type address" aria-label="Zenon address" value="<?php echo isset($_GET['address']) && is_string($_GET['address']) ? htmlspecialchars($_GET['address']) : ''; ?>">
      <div class="frontier-filters">
        <select name="type" class="form-select custom-select" aria-label="Reward type" onchange="this.form.submit();">
          <?php foreach (['pillar', 'sentinel', 'stake', 'liquidity'] as $reward_type): ?>
            <option value="<?= $reward_type ?>"<?= $type === $reward_type ? ' selected' : '' ?>><?= ucfirst($reward_type) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="per_page" class="form-select custom-select" aria-label="Results per page" onchange="this.form.submit();">
          <?php foreach ([10, 25, 50, 100, 200] as $page_size): ?>
            <option value="<?= $page_size ?>"<?= $per_page === $page_size ? ' selected' : '' ?>><?= $page_size ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="btn custom-btn" type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
    </form>
  </div>
</header>

<div class="container mt-2">
  <div class="tool-intro">
    <h1>Frontier rewards</h1>
    <p>Explore rewards per epoch for an address, filtered by reward type.</p>
  </div>
  <?php
  if ($data_valid) {
      echo '<h1 class="responsive-title">' . htmlspecialchars($address) . '</h1>' . PHP_EOL;
  } elseif ($output) {
      echo '<div class="alert alert-warning">' . $output . '</div>' . PHP_EOL;
  }
  ?>
  <div class="table-responsive frontier-results">
  <table class="table">
      <thead>
          <tr>
              <th scope="col">Epoch</th>
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
  </div>
  
  <?php if ($data_valid) : 
      $base_url = '?address=' . urlencode($address) . '&per_page=' . $per_page . '&type=' . urlencode($type);
  ?>
  <nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
      <?php 
      if ($page <= 1) {
          echo '<li class="page-item disabled"><a class="page-link" href="#"><i class="bi bi-chevron-left fs-6"></i></a></li>' . PHP_EOL;
      } else {
          echo '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . ($page - 1) . '"><i class="bi bi-chevron-left fs-6"></i></a></li>' . PHP_EOL;
      }
      
      $start = max(1, $page - 2);
      $end   = min($total_pages, $page + 2);

      for ($i = $start; $i <= $end; $i++) {
          if ($i == $page) {
              echo '<li class="page-item active"><a class="page-link" href="#">' . $i . '</a></li>' . PHP_EOL;
          } else {
              echo '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . $i . '">' . $i . '</a></li>' . PHP_EOL;
          }
      }
      
      if ($has_next_page) {
          echo '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . ($page + 1) . '"><i class="bi bi-chevron-right fs-6"></i></a></li>' . PHP_EOL;
      } else {
          echo '<li class="page-item disabled"><a class="page-link" href="#"><i class="bi bi-chevron-right fs-6"></i></a></li>' . PHP_EOL;
      }
      ?>
    </ul>
  </nav>
  <?php endif; ?>
  
</div>
<script src="lib/bootstrap@5.3.6/js/bootstrap.bundle.min.js"></script>
</body>
</html>
