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
<?php include __DIR__ . '/inc/head.php'; ?>
</head>
<body data-category="<?= e(current_tool()['category']) ?>">
<?php include __DIR__ . '/inc/topbar.php'; ?>

<main id="content" class="container page">
  <?php tool_intro(); ?>
  <div class="lookup">
    <form method="GET" class="lookup-form" id="searchForm">
        <input type="search" name="address" class="form-control lookup-input" placeholder="z1q… address" aria-label="Zenon address" value="<?php echo isset($_GET['address']) && is_string($_GET['address']) ? htmlspecialchars($_GET['address']) : ''; ?>">
        <div class="lookup-filters">
          <select name="type" class="form-select lookup-select" aria-label="Reward type" onchange="this.form.submit();">
            <?php foreach (['pillar', 'sentinel', 'stake', 'liquidity'] as $reward_type): ?>
              <option value="<?= $reward_type ?>"<?= $type === $reward_type ? ' selected' : '' ?>><?= ucfirst($reward_type) ?></option>
            <?php endforeach; ?>
          </select>
          <select name="per_page" class="form-select lookup-select" aria-label="Results per page" onchange="this.form.submit();">
            <?php foreach ([10, 25, 50, 100, 200] as $page_size): ?>
              <option value="<?= $page_size ?>"<?= $per_page === $page_size ? ' selected' : '' ?>><?= $page_size ?> per page</option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn lookup-btn" type="submit"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Look up</span></button>
      </form>
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
  
</main>
<?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
