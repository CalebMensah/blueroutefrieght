<?php
// ══════════════════════════════════════════
//  BlueRoute — Users API
//  api/users.php
// ══════════════════════════════════════════

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

// ── Admin-only guard ──────────────────────
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// ── Helper ────────────────────────────────
function jsonOut(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

// ══════════════════════════════════════════
//  GET — list all users  OR  single user
// ══════════════════════════════════════════
if ($method === 'GET') {

    if ($id) {
        // Single user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) jsonOut(['success' => false, 'error' => 'User not found'], 404);

        // Never expose password hash
        unset($user['password_hash']);

        jsonOut(['success' => true, 'data' => $user]);
    }

    // All users
    $stmt = $pdo->query("
        SELECT
            id, first_name, last_name, username, email,
            phone, country, state, city, address, zip_code,
            role, status, last_login, login_attempts, created_at
        FROM users
        ORDER BY created_at DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonOut(['success' => true, 'data' => $users]);
}

// ══════════════════════════════════════════
//  DELETE — remove a user
// ══════════════════════════════════════════
if ($method === 'DELETE') {

    if (!$id) jsonOut(['success' => false, 'error' => 'Missing user ID'], 400);

    // Prevent admin from deleting themselves
    if ($id === (int)$_SESSION['user']['id']) {
        jsonOut(['success' => false, 'error' => 'You cannot delete your own account'], 403);
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() === 0) jsonOut(['success' => false, 'error' => 'User not found'], 404);

    jsonOut(['success' => true, 'message' => 'User deleted']);
}

// ══════════════════════════════════════════
//  PATCH — update role or status
// ══════════════════════════════════════════
if ($method === 'PATCH') {

    if (!$id) jsonOut(['success' => false, 'error' => 'Missing user ID'], 400);

    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    $allowed_roles    = ['admin', 'client'];
    $allowed_statuses = ['active', 'suspended'];

    $fields = [];
    $params = [':id' => $id];

    if (isset($body['role'])) {
        if (!in_array($body['role'], $allowed_roles, true)) {
            jsonOut(['success' => false, 'error' => 'Invalid role'], 422);
        }
        $fields[]       = 'role = :role';
        $params[':role'] = $body['role'];
    }

    if (isset($body['status'])) {
        if (!in_array($body['status'], $allowed_statuses, true)) {
            jsonOut(['success' => false, 'error' => 'Invalid status'], 422);
        }
        $fields[]         = 'status = :status';
        $params[':status'] = $body['status'];
    }

    if (empty($fields)) jsonOut(['success' => false, 'error' => 'Nothing to update'], 422);

    $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $pdo->prepare($sql)->execute($params);

    jsonOut(['success' => true, 'message' => 'User updated']);
}

// Fallback
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);