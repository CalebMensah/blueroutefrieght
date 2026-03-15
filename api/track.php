<?php
// ══════════════════════════════════════════
//  BlueRoute — Tracking API
//  api/track.php
//  GET  ?ref=BLR100000001&type=shipment
//  POST { ref, type }
// ══════════════════════════════════════════

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../includes/helpers.php';

// Accept GET or POST
$ref  = clean($_GET['ref']  ?? $_POST['ref']  ?? '');
$type = clean($_GET['type'] ?? $_POST['type'] ?? 'shipment');

if (empty($ref)) {
    jsonResponse(['success' => false, 'error' => 'Tracking reference is required.'], 400);
}

if ($type === 'vault') {
    $record = getVaultByRef($ref);
} else {
    $record = getShipmentByRef($ref);
    // fallback: try vault if not found as shipment
    if (!$record) $record = getVaultByRef($ref);
}

if (!$record) {
    jsonResponse([
        'success' => false,
        'error'   => "No record found for reference <strong>{$ref}</strong>. Please check the number and try again."
    ], 404);
}

jsonResponse(['success' => true, 'data' => $record]);