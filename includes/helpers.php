<?php
// ══════════════════════════════════════════
//  BlueRoute — Helpers
//  includes/helpers.php
// ══════════════════════════════════════════
require_once __DIR__ . '/db.php';

/**
 * Return full shipment data + timeline by tracking_number
 * (previously used 'reference' column — now uses 'tracking_number')
 */
function getShipmentByRef(string $ref): ?array {
    $pdo = getDB();

    $stmt = $pdo->prepare("
        SELECT s.*,
               DATEDIFF(s.eta, CURDATE()) AS days_remaining
        FROM   shipments s
        WHERE  s.tracking_number = :ref
        LIMIT  1
    ");
    $stmt->execute([':ref' => strtoupper(trim($ref))]);
    $shipment = $stmt->fetch();

    if ($shipment) {
        $evStmt = $pdo->prepare("
            SELECT * FROM tracking_events
            WHERE  shipment_id = :id
            ORDER  BY sort_order ASC
        ");
        $evStmt->execute([':id' => $shipment['id']]);
        $shipment['events'] = $evStmt->fetchAll();
        $shipment['type']   = 'shipment';
        return $shipment;
    }

    return null;
}

/**
 * Return vault record by reference
 */
function getVaultByRef(string $ref): ?array {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM vaults WHERE reference = :ref LIMIT 1");
    $stmt->execute([':ref' => strtoupper(trim($ref))]);
    $vault = $stmt->fetch();

    if ($vault) {
        $vault['type'] = 'vault';
        return $vault;
    }

    return null;
}

/**
 * Status label & CSS class map
 */
function statusInfo(string $status): array {
    return match($status) {
        'processing' => ['label' => 'Processing',   'class' => 'status-processing', 'icon' => 'fa-gear'],
        'transit'    => ['label' => 'In Transit',   'class' => 'status-transit',    'icon' => 'fa-truck'],
        'delivered'  => ['label' => 'Delivered',    'class' => 'status-delivered',  'icon' => 'fa-circle-check'],
        'pending'    => ['label' => 'Pending',      'class' => 'status-pending',    'icon' => 'fa-clock'],
        'cancelled'  => ['label' => 'Cancelled',    'class' => 'status-cancelled',  'icon' => 'fa-xmark'],
        'verified'   => ['label' => 'Verified',     'class' => 'status-delivered',  'icon' => 'fa-shield-halved'],
        'review'     => ['label' => 'Under Review', 'class' => 'status-processing', 'icon' => 'fa-magnifying-glass'],
        default      => ['label' => ucfirst($status), 'class' => 'status-pending',  'icon' => 'fa-circle'],
    };
}

/**
 * Format a datetime string nicely
 */
function fmtDate(string $dt): string {
    $ts = strtotime($dt);
    return $ts ? date('d M Y, H:i', $ts) : $dt;
}

/**
 * JSON response helper
 */
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Sanitise input
 */
function clean(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

/**
 * Write to activity_log table
 */
function logActivity(PDO $pdo, string $type, string $message): void {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_log (type, message) VALUES (:type, :msg)");
        $stmt->execute([':type' => $type, ':msg' => $message]);
    } catch (Exception $e) {
        // non-fatal — don't break the request
    }
}