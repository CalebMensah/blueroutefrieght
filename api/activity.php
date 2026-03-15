<?php
// ══════════════════════════════════════════
//  BlueRoute — Activity Log API
//  api/activity.php
// ══════════════════════════════════════════

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/helpers.php';

$pdo   = getDB();
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 20;
$stmt  = $pdo->prepare("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT :lim");
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->execute();
jsonResponse(['success'=>true,'data'=>$stmt->fetchAll()]);