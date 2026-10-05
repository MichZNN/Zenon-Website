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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Check Sentinel registration, revocation eligibility and cooldowns for multiple Zenon addresses.">
    <title>Sentinel Revocation - Zenon Network</title>
    <link rel="icon" type="image/svg+xml" href="img/favicon.svg">
    <link href="lib/bootstrap@5.3.6/css/bootstrap.min.css" rel="stylesheet">
    <link href="lib/fontawesome@6.7.2/css/all.min.css" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet">
</head>
<body>
<header class="py-3 custom-header tool-header">
    <div class="container tool-header-inner">
        <a class="btn home-btn" href="index.php" aria-label="Home"><i class="fa-solid fa-house" aria-hidden="true"></i></a>
        <form method="GET" class="tool-search-form">
            <textarea name="address" class="form-control custom-textarea" rows="3" placeholder="Addresses separated by commas, spaces or new lines" aria-label="Sentinel owner addresses" required><?= htmlspecialchars($normalized_input, ENT_QUOTES, 'UTF-8') ?></textarea>
            <button class="btn custom-btn" type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
        </form>
    </div>
</header>
<main class="container mt-2">
  <div class="tool-intro">
    <h1>Sentinel revocation</h1>
    <p>Check registration, revocation eligibility, cooldown and active status for multiple Sentinel owners. Registration times are shown in UTC.</p>
  </div>

    <?php foreach ($results as $result): ?>
    <section class="address-result">
        <?php if ($result['error'] !== ''): ?>
            <p class="alert alert-warning mt-2"><strong title="<?= htmlspecialchars($result['address'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(str_shorten($result['address'], 3, 3), ENT_QUOTES, 'UTF-8') ?></strong>: <?= htmlspecialchars($result['error'], ENT_QUOTES, 'UTF-8') ?></p>
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
                        <td colspan="3"><span class="sentinel-field-label">Address</span><span title="<?= htmlspecialchars($result['address'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(str_shorten($result['address'], 3, 3), ENT_QUOTES, 'UTF-8') ?></span></td>
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
</body>
</html>
