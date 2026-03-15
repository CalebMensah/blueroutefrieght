<?php
// ══════════════════════════════════════════
//  BlueRoute — Vaults API
//  api/vaults.php
// ══════════════════════════════════════════

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../includes/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

$body = [];
$raw  = file_get_contents('php://input');
if (!empty($raw)) $body = json_decode($raw, true) ?? [];
if (empty($body)) $body = $_POST;

$pdo = getDB();

switch ($method) {

    case 'GET':
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM vaults WHERE id=:id");
            $stmt->execute([':id'=>$id]);
            $row = $stmt->fetch();
            if (!$row) { jsonResponse(['success'=>false,'error'=>'Not found'], 404); }
            jsonResponse(['success'=>true,'data'=>$row]);
        } else {
            $stmt = $pdo->query("SELECT * FROM vaults ORDER BY created_at DESC");
            jsonResponse(['success'=>true,'data'=>$stmt->fetchAll()]);
        }
        break;

    case 'POST':
        foreach (['reference','client_name','facility'] as $f) {
            if (empty($body[$f])) jsonResponse(['success'=>false,'error'=>"Field '{$f}' required."], 422);
        }
        $chk = $pdo->prepare("SELECT id FROM vaults WHERE reference=:ref");
        $chk->execute([':ref'=>strtoupper(trim($body['reference']))]);
        if ($chk->fetch()) jsonResponse(['success'=>false,'error'=>'Reference already exists.'], 409);

        $stmt = $pdo->prepare("
            INSERT INTO vaults (reference,client_name,client_email,facility,contents,status,last_audit,next_audit,notes)
            VALUES (:ref,:client,:email,:facility,:contents,:status,:last,:next,:notes)
        ");
        $stmt->execute([
            ':ref'      => strtoupper(trim($body['reference'])),
            ':client'   => clean($body['client_name'] ?? ''),
            ':email'    => clean($body['client_email'] ?? ''),
            ':facility' => clean($body['facility'] ?? ''),
            ':contents' => clean($body['contents'] ?? ''),
            ':status'   => clean($body['status'] ?? 'verified'),
            ':last'     => !empty($body['last_audit'])  ? $body['last_audit']  : null,
            ':next'     => !empty($body['next_audit'])  ? $body['next_audit']  : null,
            ':notes'    => clean($body['notes'] ?? ''),
        ]);
        $newId = (int)$pdo->lastInsertId();
        logActivity($pdo, 'vault', "Vault <strong>{$body['reference']}</strong> created for {$body['client_name']}");
        jsonResponse(['success'=>true,'message'=>'Vault record created.','id'=>$newId], 201);
        break;

    case 'PUT':
        if (!$id) jsonResponse(['success'=>false,'error'=>'ID required.'], 400);
        $chk = $pdo->prepare("SELECT reference FROM vaults WHERE id=:id");
        $chk->execute([':id'=>$id]);
        $existing = $chk->fetch();
        if (!$existing) jsonResponse(['success'=>false,'error'=>'Not found.'], 404);

        $stmt = $pdo->prepare("
            UPDATE vaults SET
                client_name  = COALESCE(:client,    client_name),
                client_email = COALESCE(:email,     client_email),
                facility     = COALESCE(:facility,  facility),
                contents     = COALESCE(:contents,  contents),
                status       = COALESCE(:status,    status),
                last_audit   = COALESCE(:last,      last_audit),
                next_audit   = COALESCE(:next,      next_audit),
                notes        = COALESCE(:notes,     notes),
                updated_at   = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':client'   => isset($body['client_name'])  ? clean($body['client_name'])  : null,
            ':email'    => isset($body['client_email']) ? clean($body['client_email']) : null,
            ':facility' => isset($body['facility'])     ? clean($body['facility'])     : null,
            ':contents' => isset($body['contents'])     ? clean($body['contents'])     : null,
            ':status'   => isset($body['status'])       ? clean($body['status'])       : null,
            ':last'     => !empty($body['last_audit'])  ? $body['last_audit']          : null,
            ':next'     => !empty($body['next_audit'])  ? $body['next_audit']          : null,
            ':notes'    => isset($body['notes'])        ? clean($body['notes'])        : null,
            ':id'       => $id,
        ]);
        logActivity($pdo, 'vault', "Vault <strong>{$existing['reference']}</strong> updated");
        jsonResponse(['success'=>true,'message'=>'Vault record updated.']);
        break;

    case 'DELETE':
        if (!$id) jsonResponse(['success'=>false,'error'=>'ID required.'], 400);
        $chk = $pdo->prepare("SELECT reference FROM vaults WHERE id=:id");
        $chk->execute([':id'=>$id]);
        $row = $chk->fetch();
        if (!$row) jsonResponse(['success'=>false,'error'=>'Not found.'], 404);
        $pdo->prepare("DELETE FROM vaults WHERE id=:id")->execute([':id'=>$id]);
        logActivity($pdo, 'del', "Vault <strong>{$row['reference']}</strong> deleted");
        jsonResponse(['success'=>true,'message'=>'Vault deleted.']);
        break;

    default:
        jsonResponse(['success'=>false,'error'=>'Method not allowed.'], 405);
}