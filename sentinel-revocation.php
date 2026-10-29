<?php
require_once __DIR__ . '/functions.php';

$input = isset($_GET['address']) && is_string($_GET['address']) ? trim($_GET['address']) : '';
$addresses = array_values(array_unique(array_filter(preg_split('/[,\s]+/', $input))));
$normalized_input = implode("\n", $addresses);
$results = [];
foreach ($addresses as $address) {
    $result = ['address' => $address, 'error' => '', 'data' => null];
    if (!preg_match('/^z1[a-z0-9]{38}$/', $address)) {
        $result['error'] = 'Invalid Zenon address.';
    } else {
        $response = sentinel_by_owner($address);
        if (isset($response['error']) || isset($response['title'])) {
            $result['error'] = (string)($response['title'] ?? $response['error']);
        } elseif (!array_key_exists('data', $response)) {
            $result['error'] = 'No valid data received.';
        } elseif ($response['data'] === null || $response['data'] === []) {
            $result['error'] = 'No Sentinel registered for this address.';
        } elseif (!is_array($response['data'])) {
            $result['error'] = 'No valid data received.';
        } else {
            $result['data'] = $response['data'];
        }
    }
    $results[] = $result;
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
    <form method="GET" class="lookup-form">
              <textarea name="address" class="form-control lookup-input lookup-textarea" rows="3" placeholder="Addresses separated by commas, spaces or new lines" aria-label="Sentinel owner addresses" required><?= htmlspecialchars($normalized_input, ENT_QUOTES, 'UTF-8') ?></textarea>
              <button class="btn lookup-btn" type="submit"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Look up</span></button>
          </form>
  </div>

    <?php foreach ($results as $result): ?>
    <section class="address-result">
        <?php if ($result['error'] !== ''): ?>
            <p class="alert alert-warning mt-2"><strong title="<?= htmlspecialchars($result['address'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(str_shorten($result['address'], 6, 6), ENT_QUOTES, 'UTF-8') ?></strong>: <?= htmlspecialchars($result['error'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php else:
            $data = $result['data'];
            // Accept the display keys and the native node API field names.
            $registered = $data['Registration timestamp'] ?? $data['registrationTimestamp'] ?? null;
            $revocable = $data['Is Revocable'] ?? $data['isRevocable'] ?? null;
            $cooldown = $data['Revoke cooldown'] ?? $data['revokeCooldown'] ?? null;
            $active = $data['active'] ?? $data['Active'] ?? $data['isActive'] ?? null;
        ?>
            <table class="table sentinel-status-table">
                <caption class="visually-hidden">Sentinel status for <?= htmlspecialchars($result['address'], ENT_QUOTES, 'UTF-8') ?></caption>
                <colgroup><col><col><col><col><col><col></colgroup>
                <tbody>
                    <tr>
                        <td colspan="3"><span class="sentinel-field-label">Address</span><span title="<?= htmlspecialchars($result['address'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(str_shorten($result['address'], 6, 6), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td colspan="3"><span class="sentinel-field-label">Registered (UTC)</span><?= is_numeric($registered) && (float)$registered >= 0 ? gmdate('Y-m-d\TH:i:s\Z', (int)$registered) : 'Unknown' ?></td>
                    </tr>
                    <tr>
                        <td colspan="2"><span class="sentinel-field-label">Revocable</span><?= status_badge($revocable) ?></td>
                        <td colspan="2"><span class="sentinel-field-label">Cooldown</span><?= format_cooldown($cooldown) ?></td>
                        <td colspan="2"><span class="sentinel-field-label">Active</span><?= status_badge($active) ?></td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
    <?php endforeach; ?>
</main>
<?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
