<?php
declare(strict_types=1);

/* Current momentum height for the footer. */
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$response = chain_query('ledger.getFrontierMomentum', [], 'ledger/get-frontier-momentum', []);
$momentum = $response['data'] ?? null;

if (!is_array($momentum) || !isset($momentum['height'])) {
    // Status 200 on purpose: nginx replaces error statuses with its HTML error pages.
    echo json_encode(['error' => 'The current momentum is not available.']);
    exit;
}

echo json_encode([
    'height'    => (int)$momentum['height'],
    'timestamp' => isset($momentum['timestamp']) ? (int)$momentum['timestamp'] : null,
]);
