<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once 'functions.php';

$data_valid  = false;
$output      = '';

if (!empty($_GET['address'])) {
    $address = trim($_GET['address']);

    if (strlen($address) >= 40 && preg_match('/^[a-z0-9]+$/i', $address)) {

        $data_array = liquidity_stake_entries($address);

        if(isset($data_array['data'])) {
            $count = isset($data_array['data']['count']) ? $data_array['data']['count'] : 0;
        }

        if (isset($data_array['data']['list']) && is_array($data_array['data']['list'])) {
            $data_list = $data_array['data']['list'];

            $total_amount = 0;

            foreach($data_list as $data) {
                if(isset($data['tokenStandard'])) {
                    $token_data = token_by_zts($data['tokenStandard']);
                    if(isset($token_data['title'])) {
                        $token_name = $token_symbol = 'Unknown';
                        $token_decimals = 0;
                        $output = $token_data['title'];
                    } else {
                        $token_name = $token_data['data']['name'];
                        $token_symbol = $token_data['data']['symbol'];
                        $token_decimals = $token_data['data']['decimals'];
                    }
                }

                $total_amount = add_amount($total_amount, divisor($data['amount'], $token_decimals));

                $output .= '<tr>' . PHP_EOL .
                '<td>' . divisor($data['amount'], $token_decimals) . '</td>' . PHP_EOL .
                '<td>' . htmlspecialchars($token_symbol) . '</td>' . PHP_EOL .
                // '<td>' . divisor($data['weightedAmount'], $token_decimals) . '</td>' . PHP_EOL .
                '<td>' . (new DateTime('@' . $data['startTime']))->format('Y-m-d H:i:s') . '</td>' . PHP_EOL .
                '<td>' . htmlspecialchars($data['revokeTime']) . '</td>' . PHP_EOL .
                '<td>' . (new DateTime('@' . $data['expirationTime']))->format('Y-m-d H:i:s') . '</td>' . PHP_EOL .
                // '<td>' . htmlspecialchars($data['id']) . '</td>' . PHP_EOL .
                '</tr>' . PHP_EOL;
            }
            $output .= '<tr>' . PHP_EOL .
            '<td>Total amount: ' . $total_amount . '</td>' . PHP_EOL .
            '</tr>' . PHP_EOL;

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
<?php include __DIR__ . '/inc/head.php'; ?>
</head>
<body data-category="<?= e(current_tool()['category']) ?>">
<?php include __DIR__ . '/inc/topbar.php'; ?>

<main id="content" class="container page">
  <?php tool_intro(); ?>
  <div class="lookup">
    <form method="GET" class="lookup-form" id="searchForm">
        <input type="search" name="address" class="form-control lookup-input" placeholder="z1q… address" aria-label="Search" value="<?php echo isset($_GET['address']) ? htmlspecialchars($_GET['address']) : ''; ?>">
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
  <table class="table">
      <thead>
          <tr>
              <th scope="col">Amount</th>
              <th scope="col">Token Standard</th>
              <th scope="col">Start Time</th>
              <th scope="col">Revoke Time</th>
              <th scope="col">Expiration Time</th>
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
