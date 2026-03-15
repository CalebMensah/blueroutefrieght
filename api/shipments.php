<?php
// ══════════════════════════════════════════
//  BlueRoute — Shipments API
//  api/shipments.php
//
//  GET    ?id=1          → single shipment  (public)
//  GET    (no params)    → all shipments    (public)
//  POST                  → create shipment  (admin only)
//  PUT    ?id=1          → update shipment  (admin only)
//  DELETE ?id=1          → delete shipment  (admin only)
// ══════════════════════════════════════════

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../includes/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Read JSON body for PUT/POST
$body = [];
$raw  = file_get_contents('php://input');
if (!empty($raw)) {
    $body = json_decode($raw, true) ?? [];
}
// Also support form-encoded
if (empty($body)) $body = array_merge($_POST, []);

$pdo = getDB();

// ── Auth helper ───────────────────────────
// Returns the current user array from session, or null if not logged in.
function getCurrentUser(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['user'] ?? null;
}

// Aborts with 401/403 if the current user is not an admin.
function requireAdmin(): void {
    $user = getCurrentUser();
    if (!$user) {
        jsonResponse(['success' => false, 'error' => 'Authentication required.'], 401);
    }
    if (($user['role'] ?? '') !== 'admin') {
        jsonResponse(['success' => false, 'error' => 'Access denied. Admins only.'], 403);
    }
}

// ── Tracking number generator ─────────────
// Format: BLR-YYYYMMDD-XXXXX  (e.g. BLR-20250612-A3F7K)
// Guarantees uniqueness against the shipments table.
function generateTrackingNumber(PDO $pdo): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no I, O, 0, 1 to avoid confusion
    $date  = date('Ymd');

    do {
        $suffix = '';
        for ($i = 0; $i < 5; $i++) {
            $suffix .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $number = "BLR-{$date}-{$suffix}";

        $chk = $pdo->prepare("SELECT id FROM shipments WHERE tracking_number = :tn");
        $chk->execute([':tn' => $number]);
    } while ($chk->fetch()); // retry on the extremely rare collision

    return $number;
}

switch ($method) {

    // ── GET all or single ──────────────────
    case 'GET':
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM shipments WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            if (!$row) { jsonResponse(['success'=>false,'error'=>'Not found'], 404); }

            // attach timeline events
            $ev = $pdo->prepare("SELECT * FROM tracking_events WHERE shipment_id=:id ORDER BY sort_order ASC");
            $ev->execute([':id' => $id]);
            $row['events'] = $ev->fetchAll();
            jsonResponse(['success'=>true,'data'=>$row]);
        } else {
            $stmt = $pdo->query("
                SELECT s.*,
                       (SELECT COUNT(*) FROM tracking_events WHERE shipment_id=s.id) AS event_count
                FROM shipments s
                ORDER BY s.created_at DESC
            ");
            jsonResponse(['success'=>true,'data'=>$stmt->fetchAll()]);
        }
        break;

    // ── POST create ────────────────────────
    case 'POST':
        requireAdmin();

        $required = ['client_name','origin','destination'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                jsonResponse(['success'=>false,'error'=>"Field '{$field}' is required."], 422);
            }
        }

        // Auto-generate unique tracking number
        $trackingNumber = generateTrackingNumber($pdo);

        $stmt = $pdo->prepare("
            INSERT INTO shipments
                (tracking_number, client_name, client_email, origin, destination,
                 service_type, status, current_location, weight_kg, description, notes, eta)
            VALUES
                (:tn, :client, :email, :origin, :dest,
                 :service, :status, :location, :weight, :desc, :notes, :eta)
        ");
        $stmt->execute([
            ':tn'       => $trackingNumber,
            ':client'   => clean($body['client_name'] ?? ''),
            ':email'    => clean($body['client_email'] ?? ''),
            ':origin'   => clean($body['origin'] ?? ''),
            ':dest'     => clean($body['destination'] ?? ''),
            ':service'  => clean($body['service_type'] ?? 'Secure Air Freight'),
            ':status'   => clean($body['status'] ?? 'processing'),
            ':location' => clean($body['current_location'] ?? ''),
            ':weight'   => is_numeric($body['weight_kg'] ?? '') ? $body['weight_kg'] : null,
            ':desc'     => clean($body['description'] ?? ''),
            ':notes'    => clean($body['notes'] ?? ''),
            ':eta'      => !empty($body['eta']) ? $body['eta'] : null,
        ]);
        $newId = (int)$pdo->lastInsertId();

        // Save timeline events if provided
        if (!empty($body['events']) && is_array($body['events'])) {
            saveEvents($pdo, $newId, $body['events']);
        }

        logActivity($pdo, 'ship', "Shipment <strong>{$trackingNumber}</strong> created for {$body['client_name']}");
        jsonResponse([
            'success'         => true,
            'message'         => 'Shipment created.',
            'id'              => $newId,
            'tracking_number' => $trackingNumber,
        ], 201);
        break;

    // ── PUT update ─────────────────────────
    case 'PUT':
        requireAdmin();

        if (!$id) { jsonResponse(['success'=>false,'error'=>'ID required for update.'], 400); }

        // Check exists
        $chk = $pdo->prepare("SELECT id, tracking_number FROM shipments WHERE id=:id");
        $chk->execute([':id'=>$id]);
        $existing = $chk->fetch();
        if (!$existing) { jsonResponse(['success'=>false,'error'=>'Shipment not found.'], 404); }

        $stmt = $pdo->prepare("
            UPDATE shipments SET
                client_name      = COALESCE(:client,    client_name),
                client_email     = COALESCE(:email,     client_email),
                origin           = COALESCE(:origin,    origin),
                destination      = COALESCE(:dest,      destination),
                service_type     = COALESCE(:service,   service_type),
                status           = COALESCE(:status,    status),
                current_location = COALESCE(:location,  current_location),
                weight_kg        = COALESCE(:weight,    weight_kg),
                description      = COALESCE(:desc,      description),
                notes            = COALESCE(:notes,     notes),
                eta              = COALESCE(:eta,       eta),
                updated_at       = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':client'   => isset($body['client_name'])      ? clean($body['client_name'])      : null,
            ':email'    => isset($body['client_email'])     ? clean($body['client_email'])      : null,
            ':origin'   => isset($body['origin'])           ? clean($body['origin'])            : null,
            ':dest'     => isset($body['destination'])      ? clean($body['destination'])       : null,
            ':service'  => isset($body['service_type'])     ? clean($body['service_type'])      : null,
            ':status'   => isset($body['status'])           ? clean($body['status'])            : null,
            ':location' => isset($body['current_location']) ? clean($body['current_location'])  : null,
            ':weight'   => isset($body['weight_kg']) && is_numeric($body['weight_kg']) ? $body['weight_kg'] : null,
            ':desc'     => isset($body['description'])      ? clean($body['description'])       : null,
            ':notes'    => isset($body['notes'])            ? clean($body['notes'])             : null,
            ':eta'      => !empty($body['eta'])             ? $body['eta']                      : null,
            ':id'       => $id,
        ]);

        // Replace timeline events if provided
        if (isset($body['events']) && is_array($body['events'])) {
            $pdo->prepare("DELETE FROM tracking_events WHERE shipment_id=:id")->execute([':id'=>$id]);
            saveEvents($pdo, $id, $body['events']);
        }

        $tn        = $existing['tracking_number'];
        $newStatus = $body['status'] ?? 'updated';
        logActivity($pdo, 'ship', "Shipment <strong>{$tn}</strong> updated — status: <strong>{$newStatus}</strong>");
        jsonResponse(['success'=>true,'message'=>'Shipment updated.']);
        break;

    // ── DELETE ─────────────────────────────
    case 'DELETE':
        requireAdmin();

        if (!$id) { jsonResponse(['success'=>false,'error'=>'ID required for delete.'], 400); }

        $chk = $pdo->prepare("SELECT tracking_number FROM shipments WHERE id=:id");
        $chk->execute([':id'=>$id]);
        $row = $chk->fetch();
        if (!$row) { jsonResponse(['success'=>false,'error'=>'Not found.'], 404); }

        // tracking_events deleted via CASCADE
        $pdo->prepare("DELETE FROM shipments WHERE id=:id")->execute([':id'=>$id]);
        logActivity($pdo, 'del', "Shipment <strong>{$row['tracking_number']}</strong> deleted");
        jsonResponse(['success'=>true,'message'=>'Shipment deleted.']);
        break;

    default:
        jsonResponse(['success'=>false,'error'=>'Method not allowed.'], 405);
}

// ── Helper: save timeline events ──────────
function saveEvents(PDO $pdo, int $shipmentId, array $events): void {
    $stmt = $pdo->prepare("
        INSERT INTO tracking_events
            (shipment_id, event_title, location, event_time, is_done, is_active, sort_order)
        VALUES
            (:sid, :title, :loc, :time, :done, :active, :order)
    ");
    foreach ($events as $i => $ev) {
        $stmt->execute([
            ':sid'    => $shipmentId,
            ':title'  => clean($ev['event_title'] ?? $ev['event'] ?? ''),
            ':loc'    => clean($ev['location'] ?? ''),
            ':time'   => !empty($ev['event_time']) ? $ev['event_time'] : date('Y-m-d H:i:s'),
            ':done'   => isset($ev['is_done'])   ? (int)$ev['is_done']   : 0,
            ':active' => isset($ev['is_active']) ? (int)$ev['is_active'] : 0,
            ':order'  => $i + 1,
        ]);
    }
}