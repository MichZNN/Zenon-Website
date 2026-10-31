<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once 'functions.php';

const TX_PAGE_SIZE   = 1000;  // blocks per API call
const TX_MAX_PAGES   = 25;    // stop after this many calls (25,000 blocks)
const TX_SCREEN_ROWS = 250;   // rows shown on the page; the CSV always has all

/* Block types in go-zenon */
const BLOCK_USER_SEND        = 2;
const BLOCK_USER_RECEIVE     = 3;
const BLOCK_CONTRACT_SEND    = 4;
const BLOCK_CONTRACT_RECEIVE = 5;

$address  = isset($_GET['address']) && is_string($_GET['address']) ? trim($_GET['address']) : '';
$from_raw = isset($_GET['from']) && is_string($_GET['from']) ? trim($_GET['from']) : '';
$to_raw   = isset($_GET['to']) && is_string($_GET['to']) ? trim($_GET['to']) : '';
$format   = ($_GET['format'] ?? '') === 'csv' ? 'csv' : 'html';
$sep      = ($_GET['sep'] ?? '') === 'semicolon' ? 'semicolon' : 'comma';

$errors    = [];
$rows      = [];
$totals    = [];
$truncated = false;
$searched  = $address !== '';

function parse_day(string $value): ?DateTimeImmutable
{
    if ($value === '') {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
    return $date && $date->format('Y-m-d') === $value ? $date : null;
}

/* Token details, looked up once per token when a block doesn't include them. */
function token_details(array $source, array &$cache): array
{
    $standard = (string)($source['tokenStandard'] ?? '');
    if (isset($source['token']['decimals'], $source['token']['symbol'])) {
        return [(string)$source['token']['symbol'], (int)$source['token']['decimals'], $standard];
    }
    if (!isset($cache[$standard])) {
        $token = token_by_zts($standard);
        $cache[$standard] = isset($token['data']['symbol'], $token['data']['decimals'])
            ? [(string)$token['data']['symbol'], (int)$token['data']['decimals']]
            : [$standard, 8];
    }
    return [$cache[$standard][0], $cache[$standard][1], $standard];
}

/* One transfer per block: sends carry their own amount, receives take it from the paired send. */
function transfer_from_block(array $block, string $address, array &$token_cache): ?array
{
    $type = (int)($block['blockType'] ?? 0);
    $time = $block['confirmationDetail']['momentumTimestamp'] ?? null;
    if (!is_numeric($time)) {
        return null; // not confirmed yet
    }

    if (in_array($type, [BLOCK_USER_SEND, BLOCK_CONTRACT_SEND], true)) {
        $source    = $block;
        $direction = 'Out';
        $from      = $address;
        $to        = (string)($block['toAddress'] ?? '');
    } elseif (in_array($type, [BLOCK_USER_RECEIVE, BLOCK_CONTRACT_RECEIVE], true)) {
        $source = $block['pairedAccountBlock'] ?? null;
        if (!is_array($source)) {
            return null;
        }
        $direction = 'In';
        $from      = (string)($source['address'] ?? '');
        $to        = $address;
    } else {
        return null;
    }

    $raw_amount = (string)($source['amount'] ?? '0');
    if (!preg_match('/^[0-9]+$/', $raw_amount) || ltrim($raw_amount, '0') === '') {
        return null; // nothing was transferred
    }
    [$symbol, $decimals, $standard] = token_details($source, $token_cache);

    return [
        'time'      => (int)$time,
        'direction' => $direction,
        'amount'    => divisor($raw_amount, $decimals),
        'symbol'    => $symbol,
        'standard'  => $standard,
        'from'      => $from,
        'to'        => $to,
        'other'     => $direction === 'In' ? $from : $to,
        'hash'      => (string)($block['hash'] ?? ''),
        'momentum'  => (string)($block['confirmationDetail']['momentumHeight'] ?? ''),
    ];
}

$from_day = parse_day($from_raw);
$to_day   = parse_day($to_raw);

if ($searched) {
    if (!preg_match('/^z1[a-z0-9]{38}$/', $address)) {
        $errors[] = 'Enter a valid Zenon address. It starts with z1 and is 40 characters long.';
    }
    if ($from_raw !== '' && !$from_day) {
        $errors[] = 'The start date is not a valid date.';
    }
    if ($to_raw !== '' && !$to_day) {
        $errors[] = 'The end date is not a valid date.';
    }
    if ($from_day && $to_day && $from_day > $to_day) {
        $errors[] = 'The start date is after the end date.';
    }
}

if ($searched && !$errors) {
    $start = $from_day ? $from_day->getTimestamp() : null;
    $end   = $to_day ? $to_day->modify('+1 day')->getTimestamp() : null; // end date is inclusive
    $token_cache = [];

    for ($page = 0; $page < TX_MAX_PAGES; $page++) {
        $response = account_blocks_by_page($address, $page, TX_PAGE_SIZE);
        $list = $response['data']['list'] ?? null;
        if (!is_array($list)) {
            $errors[] = 'Could not load transactions: ' . ($response['title'] ?? $response['error'] ?? 'no valid data received.');
            break;
        }

        $reached_start = false;
        foreach ($list as $block) {
            if (!is_array($block)) {
                continue;
            }
            $row = transfer_from_block($block, $address, $token_cache);
            if (!$row) {
                continue;
            }
            if ($end !== null && $row['time'] >= $end) {
                continue;
            }
            if ($start !== null && $row['time'] < $start) {
                $reached_start = true; // newest first, so everything after this is older
                break;
            }
            $rows[] = $row;

            $key = $row['standard'] ?: $row['symbol'];
            $totals[$key] ??= ['symbol' => $row['symbol'], 'in' => '0', 'out' => '0', 'count' => 0];
            $side = $row['direction'] === 'In' ? 'in' : 'out';
            $totals[$key][$side] = add_amount($totals[$key][$side], $row['amount']);
            $totals[$key]['count']++;
        }

        if ($reached_start || count($list) < TX_PAGE_SIZE) {
            break;
        }
        if ($page === TX_MAX_PAGES - 1) {
            $truncated = true;
        }
    }
}

/* CSV download */
if ($format === 'csv' && $searched && !$errors) {
    $delimiter = $sep === 'semicolon' ? ';' : ',';
    $period = ($from_day ? $from_day->format('Y-m-d') : 'start') . '_' . ($to_day ? $to_day->format('Y-m-d') : 'now');
    $filename = 'transactions_' . substr($address, 0, 8) . '_' . $period . ($sep === 'semicolon' ? '_semicolon' : '') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store');

    $out = fopen('php://output', 'w');
    if ($sep === 'semicolon') {
        fwrite($out, "\xEF\xBB\xBF"); // lets Excel open the file as UTF-8
    }
    fputcsv($out, ['Date (UTC)', 'Direction', 'Amount', 'Token', 'Token standard', 'From', 'To', 'Transaction hash', 'Momentum height'], $delimiter, '"', '');
    foreach (array_reverse($rows) as $row) { // oldest first, as most tax tools expect
        fputcsv($out, [
            gmdate('Y-m-d H:i:s', $row['time']),
            $row['direction'],
            $sep === 'semicolon' ? str_replace('.', ',', $row['amount']) : $row['amount'],
            $row['symbol'],
            $row['standard'],
            $row['from'],
            $row['to'],
            $row['hash'],
            $row['momentum'],
        ], $delimiter, '"', '');
    }
    fclose($out);
    exit;
}

function csv_link(string $sep): string
{
    $query = $_GET;
    $query['format'] = 'csv';
    $query['sep'] = $sep;
    return '?' . http_build_query($query);
}

$this_year = (int)gmdate('Y');
$today     = new DateTimeImmutable('today', new DateTimeZone('UTC'));
$presets = [
    // Rolling periods up to and including today
    'Last 7 days'            => [$today->modify('-6 days')->format('Y-m-d'), $today->format('Y-m-d')],
    'Last 30 days'           => [$today->modify('-29 days')->format('Y-m-d'), $today->format('Y-m-d')],
    (string)($this_year - 1) => [($this_year - 1) . '-01-01', ($this_year - 1) . '-12-31'],
    (string)$this_year       => [$this_year . '-01-01', gmdate('Y-m-d')],
    'All time'               => ['', ''],
];
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
      <input type="search" name="address" class="form-control lookup-input" placeholder="z1q… address" aria-label="Zenon address" value="<?= e($address) ?>" required>
      <div class="lookup-filters">
        <label class="date-field">
          <span>From</span>
          <input type="date" name="from" class="form-control" value="<?= e($from_raw) ?>">
        </label>
        <label class="date-field">
          <span>To</span>
          <input type="date" name="to" class="form-control" value="<?= e($to_raw) ?>">
        </label>
      </div>
      <button class="btn lookup-btn" type="submit"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Look up</span></button>
    </form>
    <div class="period-presets" role="group" aria-label="Quick periods">
      <?php foreach ($presets as $label => [$preset_from, $preset_to]): ?>
        <button type="button" class="period-preset" data-from="<?= e($preset_from) ?>" data-to="<?= e($preset_to) ?>"><?= e($label) ?></button>
      <?php endforeach; ?>
    </div>
  </div>

  <?php foreach ($errors as $error): ?>
    <div class="alert alert-warning"><?= e($error) ?></div>
  <?php endforeach; ?>

  <?php if ($searched && !$errors): ?>
    <section class="tx-summary" aria-labelledby="tx-summary-title">
      <div class="tx-summary-head">
        <div>
          <h2 id="tx-summary-title" class="responsive-title"><?= e($address) ?></h2>
          <p class="tx-period">
            <?= count($rows) ?> <?= count($rows) === 1 ? 'transfer' : 'transfers' ?>
            <?php if ($from_day || $to_day): ?>
              from <?= $from_day ? e($from_day->format('j M Y')) : 'the first transfer' ?>
              to <?= $to_day ? e($to_day->format('j M Y')) : 'today' ?>
            <?php else: ?>
              in total
            <?php endif; ?>
          </p>
        </div>
        <?php if ($rows): ?>
          <div class="tx-downloads">
            <a class="btn lookup-btn" href="<?= e(csv_link('comma')) ?>"><i class="fa-solid fa-file-csv" aria-hidden="true"></i><span>CSV, comma</span></a>
            <a class="btn tx-download-alt" href="<?= e(csv_link('semicolon')) ?>"><i class="fa-solid fa-file-csv" aria-hidden="true"></i><span>CSV, semicolon</span></a>
          </div>
        <?php endif; ?>
      </div>
      <?php if ($rows): ?>
        <p class="tx-note">Comma: amounts with a decimal point, for most tax tools and Google Sheets. Semicolon: amounts with a decimal comma, for Excel with Dutch or other European settings.</p>
      <?php endif; ?>

      <?php if ($truncated): ?>
        <div class="alert alert-warning">This address has more than <?= number_format(TX_PAGE_SIZE * TX_MAX_PAGES) ?> transactions. Only the most recent ones are included. Choose a shorter period to see older transfers.</div>
      <?php endif; ?>

      <?php if ($totals): ?>
        <table class="table tx-totals">
          <thead>
            <tr>
              <th scope="col">Token</th>
              <th scope="col">Transfers</th>
              <th scope="col">Received</th>
              <th scope="col">Sent</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($totals as $total): ?>
              <tr>
                <td><?= e($total['symbol']) ?></td>
                <td><?= $total['count'] ?></td>
                <td><?= e($total['in']) ?></td>
                <td><?= e($total['out']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>

    <?php if ($rows): ?>
      <table class="table tx-table">
        <thead>
          <tr>
            <th scope="col">Date (UTC)</th>
            <th scope="col">Direction</th>
            <th scope="col">Amount</th>
            <th scope="col">Token</th>
            <th scope="col">Counterparty</th>
            <th scope="col">Hash</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_slice($rows, 0, TX_SCREEN_ROWS) as $row): ?>
            <tr>
              <td><?= gmdate('Y-m-d H:i', $row['time']) ?></td>
              <td><span class="tx-dir tx-dir-<?= strtolower($row['direction']) ?>"><?= $row['direction'] === 'In' ? '↓ In' : '↑ Out' ?></span></td>
              <td><?= e($row['amount']) ?></td>
              <td><?= e($row['symbol']) ?></td>
              <td><a href="https://zenonhub.io/explorer/account/<?= e($row['other']) ?>" title="<?= e($row['other']) ?>"><?= e(str_shorten($row['other'], 6, 6)) ?></a></td>
              <td><span title="<?= e($row['hash']) ?>"><?= e(str_shorten($row['hash'], 6, 6)) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (count($rows) > TX_SCREEN_ROWS): ?>
        <p class="tx-note">Showing the <?= TX_SCREEN_ROWS ?> most recent of <?= count($rows) ?> transfers. The CSV file contains all of them.</p>
      <?php endif; ?>
    <?php else: ?>
      <p class="tx-note">No transfers in this period.</p>
    <?php endif; ?>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/inc/footer.php'; ?>
<script>
document.querySelectorAll('.period-preset').forEach(function (button) {
    button.addEventListener('click', function () {
        var form = document.getElementById('searchForm');
        form.elements.from.value = button.dataset.from;
        form.elements.to.value = button.dataset.to;
        if (form.elements.address.value.trim()) {
            if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }
        } else {
            form.elements.address.focus();
        }
    });
});
</script>
</body>
</html>
