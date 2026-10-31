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

/* Readable text hidden in a momentum's base64 data field, or '' when there is none. */
function decoded_momentum_data(array $momentum): string
{
    if (isset($momentum['dataDecoded'])) {
        return (string)$momentum['dataDecoded'];
    }
    $raw = base64_decode((string)($momentum['data'] ?? ''), true);
    if ($raw === false || $raw === '' || !mb_check_encoding($raw, 'UTF-8') || preg_match('/[\x00-\x08\x0E-\x1F\x7F]/', $raw)) {
        return '';
    }
    return $raw;
}

const BLOCK_TYPE_NAMES = [
    1 => 'Genesis',
    2 => 'Send',
    3 => 'Receive',
    4 => 'Contract send',
    5 => 'Contract receive',
];
const BLOCKS_PER_PAGE = 100;

/* Page links for the account blocks, keeping the height in the link. */
function blocks_page_url(string $height, int $page): string
{
    $query = ['height' => $height];
    if ($page > 1) {
        $query['page'] = $page;
    }
    return '?' . http_build_query($query) . '#account-blocks-title';
}

/* Amount and token of an account block; a receive takes them from the send it receives. */
function block_transfer(array $block): array
{
    $source = $block;
    if (in_array((int)($block['blockType'] ?? 0), [3, 5], true) && is_array($block['pairedAccountBlock'] ?? null)) {
        $source = $block['pairedAccountBlock'];
    }
    $raw = (string)($source['amount'] ?? '0');
    if (!preg_match('/^[0-9]+$/', $raw) || ltrim($raw, '0') === '') {
        return ['', ''];
    }
    $symbol   = (string)($source['token']['symbol'] ?? '');
    $decimals = $source['token']['decimals'] ?? null;
    return [is_numeric($decimals) ? divisor($raw, (int)$decimals) : $raw, $symbol];
}

$account_blocks = null;

// Without a height, show the genesis momentum: it is stored, so it loads instantly.
$height_input = isset($_GET['height']) && is_string($_GET['height']) ? trim($_GET['height']) : '';
$height_query = $height_input === '' ? '1' : $height_input;

if (!ctype_digit($height_query) || (int)$height_query < 1) {
    $output = 'Enter a momentum height: a whole number from 1 upwards.';
} else {
    $height = (string)(int)$height_query;
    // $count = !empty($_GET['count']) ? (int)$_GET['count'] : 25;

    // if (strlen($address) >= 40 && preg_match('/^[a-z0-9]+$/i', $address)) {
        // The genesis momentum (height 1) is too large to fetch in time; it never changes, so it is stored.
        $genesis_file = __DIR__ . '/data/genesis-momentum.json';
        if ($height === '1' && is_file($genesis_file)) {
            $genesis = json_decode((string)file_get_contents($genesis_file), true);
            $genesis_blocks_file = __DIR__ . '/data/genesis-accountblocks.json';
            $genesis_blocks = is_file($genesis_blocks_file)
                ? json_decode((string)file_get_contents($genesis_blocks_file), true)
                : null;
            $data_array = ['data' => ['count' => 1, 'list' => [['momentum' => $genesis, 'accountBlocks' => $genesis_blocks]]]];
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
                $account_blocks = $data['accountBlocks'] ?? null;
                
                if (isset($momentum)) {

                    $output .= '<tr>' . PHP_EOL .
                       '<td>' . (isset($momentum['hash']) ? htmlspecialchars($momentum['hash']) : '') . '</td>' . PHP_EOL .
                       '<td>' . (isset($momentum['previousHash']) ? htmlspecialchars($momentum['previousHash']) : '') . '</td>' . PHP_EOL .
                       '<td>' . (isset($momentum['height']) ? htmlspecialchars($momentum['height']) : '') . '</td>' . PHP_EOL .
                       '<td>' . (isset($momentum['timestamp']) ? (new DateTime('@' . $momentum['timestamp']))->format('Y-m-d H:i:s') : '') . '</td>' . PHP_EOL .
                       '<td><span class="cell-stack">' . (isset($momentum['data']) ? htmlspecialchars($momentum['data']) : '')
                            . (decoded_momentum_data($momentum) !== '' ? '<span class="data-decoded"><span>Decoded</span>' . htmlspecialchars(decoded_momentum_data($momentum)) . '</span>' : '')
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
        <input type="search" name="height" class="form-control lookup-input" placeholder="1" aria-label="Momentum height" inputmode="numeric" value="<?php echo htmlspecialchars($height_input); ?>">
        <button class="btn lookup-btn" type="submit"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Look up</span></button>
      </form>
  </div>
  <?php
    if (!$data_valid && $output) {
        echo '<div class="alert alert-warning">' . $output . '</div>' . PHP_EOL;
    }
  ?>
  <table class="table record-table">
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

  <?php if ($data_valid): ?>
    <section class="account-blocks" aria-labelledby="account-blocks-title">
      <h2 id="account-blocks-title" class="section-title">
        Account blocks
        <?php if (is_array($account_blocks)): ?><span class="section-count"><?= number_format(count($account_blocks)) ?></span><?php endif; ?>
      </h2>
      <?php if (!is_array($account_blocks)): ?>
        <p class="tx-note">The account blocks of this momentum are not available.</p>
      <?php elseif (!$account_blocks): ?>
        <p class="tx-note">This momentum contains no account blocks.</p>
      <?php else: ?>
        <?php
          $block_pages = max(1, (int)ceil(count($account_blocks) / BLOCKS_PER_PAGE));
          $block_page  = min(max(1, $page), $block_pages);
          $first_block = ($block_page - 1) * BLOCKS_PER_PAGE;
          $block_rows  = [];
          foreach (array_slice($account_blocks, $first_block, BLOCKS_PER_PAGE) as $block) {
              if (!is_array($block)) continue;
              [$block_amount, $block_symbol] = block_transfer($block);
              $block_type = (int)($block['blockType'] ?? 0);
              $block_rows[] = [
                  'type'    => BLOCK_TYPE_NAMES[$block_type] ?? ('Type ' . $block_type),
                  'address' => (string)($block['address'] ?? ''),
                  'to'      => in_array($block_type, [2, 4], true) ? (string)($block['toAddress'] ?? '') : '',
                  'amount'  => $block_amount,
                  'symbol'  => $block_symbol,
                  'hash'    => (string)($block['hash'] ?? ''),
              ];
          }
          // Leave out columns that are empty for every block on this page (genesis blocks have no recipient or amount).
          $show_to     = (bool)array_filter(array_column($block_rows, 'to'));
          $show_amount = (bool)array_filter(array_column($block_rows, 'amount'));
          // With only type, address and hash there is room for the full values.
          $short = fn(string $value): string => ($show_to || $show_amount) ? str_shorten($value, 6, 6) : $value;
        ?>
        <table class="table">
          <thead>
            <tr>
              <th scope="col">Type</th>
              <th scope="col">Address</th>
              <?php if ($show_to): ?><th scope="col">To</th><?php endif; ?>
              <?php if ($show_amount): ?><th scope="col">Amount</th><th scope="col">Token</th><?php endif; ?>
              <th scope="col">Hash</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($block_rows as $row): ?>
              <tr>
                <td class="nowrap"><?= htmlspecialchars($row['type']) ?></td>
                <td class="nowrap-wide"><span title="<?= htmlspecialchars($row['address']) ?>"><?= htmlspecialchars($short($row['address'])) ?></span></td>
                <?php if ($show_to): ?><td><?php if ($row['to'] !== ''): ?><span title="<?= htmlspecialchars($row['to']) ?>"><?= htmlspecialchars(str_shorten($row['to'], 6, 6)) ?></span><?php endif; ?></td><?php endif; ?>
                <?php if ($show_amount): ?><td><?= htmlspecialchars($row['amount']) ?></td><td><?= htmlspecialchars($row['symbol']) ?></td><?php endif; ?>
                <td><span title="<?= htmlspecialchars($row['hash']) ?>"><?= htmlspecialchars($short($row['hash'])) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php if ($block_pages > 1):
          $link_height = (string)($height ?? '1');
          $window = array_unique(array_filter(
              [1, $block_page - 2, $block_page - 1, $block_page, $block_page + 1, $block_page + 2, $block_pages],
              fn($n) => $n >= 1 && $n <= $block_pages
          ));
          sort($window); ?>
          <p class="tx-note">Account blocks <?= number_format($first_block + 1) ?>–<?= number_format(min($first_block + BLOCKS_PER_PAGE, count($account_blocks))) ?> of <?= number_format(count($account_blocks)) ?></p>
          <nav aria-label="Account block pages">
            <ul class="pagination justify-content-center">
              <li class="page-item page-mobile<?= $block_page <= 1 ? ' disabled' : '' ?>">
                <a class="page-link" href="<?= $block_page <= 1 ? '#' : e(blocks_page_url($link_height, 1)) ?>" aria-label="First page"><i class="bi bi-chevron-double-left fs-6" aria-hidden="true"></i></a>
              </li>
              <li class="page-item<?= $block_page <= 1 ? ' disabled' : '' ?>">
                <a class="page-link" href="<?= $block_page <= 1 ? '#' : e(blocks_page_url($link_height, $block_page - 1)) ?>" aria-label="Previous page"><i class="bi bi-chevron-left fs-6" aria-hidden="true"></i></a>
              </li>
              <?php $previous = 0; foreach ($window as $n): ?>
                <?php if ($n > $previous + 1): ?><li class="page-item page-number disabled"><span class="page-link">…</span></li><?php endif; ?>
                <li class="page-item page-number<?= $n === $block_page ? ' active' : '' ?>">
                  <a class="page-link" href="<?= e(blocks_page_url($link_height, $n)) ?>"<?= $n === $block_page ? ' aria-current="page"' : '' ?>><?= $n ?></a>
                </li>
              <?php $previous = $n; endforeach; ?>
              <li class="page-item page-mobile page-status" aria-hidden="true"><span class="page-link"><?= $block_page ?> / <?= $block_pages ?></span></li>
              <li class="page-item<?= $block_page >= $block_pages ? ' disabled' : '' ?>">
                <a class="page-link" href="<?= $block_page >= $block_pages ? '#' : e(blocks_page_url($link_height, $block_page + 1)) ?>" aria-label="Next page"><i class="bi bi-chevron-right fs-6" aria-hidden="true"></i></a>
              </li>
              <li class="page-item page-mobile<?= $block_page >= $block_pages ? ' disabled' : '' ?>">
                <a class="page-link" href="<?= $block_page >= $block_pages ? '#' : e(blocks_page_url($link_height, $block_pages)) ?>" aria-label="Last page"><i class="bi bi-chevron-double-right fs-6" aria-hidden="true"></i></a>
              </li>
            </ul>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
