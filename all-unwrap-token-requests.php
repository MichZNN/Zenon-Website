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

$data_array = all_unwrap_token_requests($page, $per_page);

// Debug output
// echo "<pre>"; var_dump($data_array); echo "</pre>";

if (isset($data_array['data']['count'])) {
    $total_records = (int)$data_array['data']['count'];
    $total_pages = ($total_records > 0) ? (int) ceil($total_records / $per_page) : 1;
    $has_next_page = $page < $total_pages;
}

$total_amount_znn_redeemed = 0;
$total_amount_znn_unredeemed = 0;

$total_amount_qsr_redeemed = 0;
$total_amount_qsr_unredeemed = 0;

$total_amount_znnethlp_redeemed = 0;
$total_amount_znnethlp_unredeemed = 0;

if (isset($data_array['data']['list']) && is_array($data_array['data']['list'])) {
    foreach ($data_array['data']['list'] as $data) {

        $token_symbol = $data['token']['symbol'];
        $token_decimals = $data['token']['decimals'];
        $amount = divisor($data['amount'], $token_decimals);

        if ($data['redeemed']) {
            if($token_symbol === 'ZNN') {
                $total_amount_znn_redeemed = add_amount($total_amount_znn_redeemed, $amount);
            } else if($token_symbol === 'QSR') {
                $total_amount_qsr_redeemed = add_amount($total_amount_qsr_redeemed, $amount);
            } else if($token_symbol === 'ZNNETHLP') {
                $total_amount_znnethlp_redeemed = add_amount($total_amount_znnethlp_redeemed, $amount);
            }
        } else {
            if($token_symbol === 'ZNN') {
                $total_amount_znn_unredeemed = add_amount($total_amount_znn_unredeemed, $amount);
            } else if($token_symbol === 'QSR') {
                $total_amount_qsr_unredeemed = add_amount($total_amount_qsr_unredeemed, $amount);
            } else if($token_symbol === 'ZNNETHLP') {
                $total_amount_znnethlp_unredeemed = add_amount($total_amount_znnethlp_unredeemed, $amount);
            }
        }

        $to_address = htmlspecialchars($data['toAddress']);

        $output .= '<tr>' . PHP_EOL .
                    '<td><a href="' . $explorer_url . $to_address . '">' . str_shorten($to_address) . '</a></td>' . PHP_EOL .
                    '<td>' . $amount . '</td>' . PHP_EOL .
                    '<td>' . htmlspecialchars($token_symbol) . '</td>' . PHP_EOL .
                    '<td>' . ($data['redeemed'] ? 'Yes' : 'No') . '</td>' . PHP_EOL .
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
  <?php
    if (!$data_valid && $output) {
        echo '<div class="alert alert-warning">' . $output . '</div>' . PHP_EOL;
    }
    if ($data_valid):
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
                <th scope="col">Amount</th>
                <th scope="col">Token</th>
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
</main>
<?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
