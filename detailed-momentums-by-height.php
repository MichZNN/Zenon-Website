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
        // The genesis momentum (height 1) is too large to fetch in time; it never changes, so it is stored.
        $genesis_file = __DIR__ . '/data/genesis-momentum.json';
        if ($height === '1' && is_file($genesis_file)) {
            $genesis = json_decode((string)file_get_contents($genesis_file), true);
            $data_array = ['data' => ['count' => 1, 'list' => [['momentum' => $genesis]]]];
        } else {
            $data_array = detailed_momentums_by_height($height, 1);
        }

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
                       '<td><span class="cell-stack">' . (isset($momentum['data']) ? htmlspecialchars($momentum['data']) : '')
                            . (isset($momentum['dataDecoded']) ? '<span class="data-decoded"><span>Decoded</span>' . htmlspecialchars($momentum['dataDecoded']) . '</span>' : '')
                            . '</span></td>' . PHP_EOL .
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
<?php include __DIR__ . '/inc/head.php'; ?>
</head>
<body data-category="<?= e(current_tool()['category']) ?>">
<?php include __DIR__ . '/inc/topbar.php'; ?>

<main id="content" class="container page">
  <?php tool_intro(); ?>
  <div class="lookup">
    <form method="GET" class="lookup-form" id="searchForm">
        <input type="search" name="height" class="form-control lookup-input" placeholder="Momentum height, e.g. 1000000" aria-label="Search" value="<?php echo isset($_GET['height']) ? htmlspecialchars($_GET['height']) : ''; ?>">
        <button class="btn lookup-btn" type="submit"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Look up</span></button>
      </form>
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
  
</main>
<?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
