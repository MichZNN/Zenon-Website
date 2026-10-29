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
<?php include __DIR__ . '/inc/head.php'; ?>
</head>
<body data-category="<?= e(current_tool()['category']) ?>">
<?php include __DIR__ . '/inc/topbar.php'; ?>

<main id="content" class="container page">
  <?php tool_intro(); ?>
  <div class="lookup">
    <form method="GET" class="lookup-form" id="searchForm">
        <input type="search" name="address" class="form-control lookup-input" placeholder="z1q… address" aria-label="Search" value="<?php echo isset($_GET['address']) ? htmlspecialchars($address) : ''; ?>">
        <button class="btn lookup-btn" type="submit"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Look up</span></button>
      </form>
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
<?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>