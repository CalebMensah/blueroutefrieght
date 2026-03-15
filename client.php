<?php
require_once __DIR__ . '/includes/security.php';

$userId = (int)($_GET['user_id'] ?? $_SESSION['user_id'] ?? 1);
if (!$userId) { header('Location: login.html'); exit; }

require_once __DIR__ . '/includes/db.php';
$pdo = getDB();

$uStmt = $pdo->prepare("SELECT * FROM users WHERE id=:id");
$uStmt->execute([':id' => $userId]);
$user = $uStmt->fetch();
if (!$user) { header('Location: login.html'); exit; }

$initials = strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1));
$hour     = (int)date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

// Load shipments linked to this user by email
$shipStmt = $pdo->prepare("
    SELECT s.*, 
           (SELECT te.event_title FROM tracking_events te WHERE te.shipment_id = s.id AND te.is_active=1 LIMIT 1) AS latest_event
    FROM shipments s
    WHERE s.client_email = :email OR s.user_id = :uid
    ORDER BY s.created_at DESC
");
$shipStmt->execute([':email' => $user['email'], ':uid' => $userId]);
$shipments = $shipStmt->fetchAll();

$total     = count($shipments);
$inTransit = count(array_filter($shipments, fn($s) => $s['status'] === 'transit'));
$delivered = count(array_filter($shipments, fn($s) => $s['status'] === 'delivered'));
$pending   = count(array_filter($shipments, fn($s) => in_array($s['status'], ['pending','processing'])));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>My Dashboard | BlueRoute</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --navy:       #0d1f3c;
      --navy-light: #1a3260;
      --sidebar-w:  220px;
      --topbar-h:   60px;
      --gold:       #b8942a;
      --blue:       #2563eb;
      --green:      #10b981;
      --amber:      #f59e0b;
      --red:        #ef4444;
      --white:      #ffffff;
      --gray-light: #f1f5f9;
      --gray-mid:   #e2e8f0;
      --gray:       #94a3b8;
      --text:       #1e293b;
      --text-light: #64748b;
    }

    html, body { font-family: 'Outfit', sans-serif; color: var(--text); background: var(--gray-light); min-height: 100vh; }

    /* ── Layout ── */
    .wrap { display: flex; min-height: 100vh; }

    /* ── Sidebar ── */
    .sidebar {
      width: var(--sidebar-w);
      background: var(--navy);
      position: fixed; top: 0; left: 0; bottom: 0;
      z-index: 200; display: flex; flex-direction: column;
      transition: transform .3s;
      overflow-y: auto;
    }

    .sb-logo {
      display: flex; align-items: center; gap: 10px;
      padding: 20px 18px 16px;
      border-bottom: 1px solid rgba(255,255,255,.08);
      text-decoration: none;
    }

    .sb-logo-hex {
      width: 38px; height: 38px; flex-shrink: 0;
      background: var(--navy-light);
      clip-path: polygon(50% 0%,100% 25%,100% 75%,50% 100%,0% 75%,0% 25%);
      display: flex; align-items: center; justify-content: center;
      color: var(--gold); font-weight: 700; font-size: 8px;
      text-align: center; line-height: 1.2;
    }

    .sb-logo-text { color: var(--white); font-size: .82rem; font-weight: 700; letter-spacing: .3px; }
    .sb-logo-text span { display: block; font-size: .65rem; font-weight: 400; color: rgba(255,255,255,.45); }

    /* Profile block */
    .sb-profile {
      padding: 18px 16px 14px;
      border-bottom: 1px solid rgba(255,255,255,.08);
      display: flex; align-items: center; gap: 11px;
    }

    .sb-avatar {
      width: 40px; height: 40px; border-radius: 50%;
      background: linear-gradient(135deg, var(--gold), #8a6b1a);
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-weight: 700; font-size: .9rem;
      flex-shrink: 0;
    }

    .sb-profile-info .sb-greeting { font-size: .68rem; color: rgba(255,255,255,.4); }
    .sb-profile-info .sb-name     { font-size: .85rem; font-weight: 600; color: #fff; line-height: 1.2; }
    .sb-profile-info .sb-email    { font-size: .68rem; color: rgba(255,255,255,.4); margin-top: 1px; }

    /* Nav */
    .sb-nav { padding: 12px 0; flex: 1; }

    .sb-nav-label {
      font-size: .62rem; font-weight: 700; color: rgba(255,255,255,.25);
      text-transform: uppercase; letter-spacing: 1px;
      padding: 12px 18px 4px;
    }

    .sb-item {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 18px;
      color: rgba(255,255,255,.55);
      text-decoration: none; font-size: .84rem; font-weight: 500;
      cursor: pointer; border: none; background: none; width: 100%;
      text-align: left; transition: all .2s;
      border-left: 3px solid transparent;
    }

    .sb-item i { width: 16px; font-size: .82rem; }
    .sb-item:hover  { color: #fff; background: rgba(255,255,255,.06); }
    .sb-item.active { color: #fff; background: rgba(255,255,255,.1); border-left-color: var(--gold); }

    .sb-footer {
      padding: 14px 18px;
      border-top: 1px solid rgba(255,255,255,.08);
    }

    .sb-logout {
      display: flex; align-items: center; gap: 8px;
      color: rgba(255,255,255,.4); font-size: .8rem;
      text-decoration: none; transition: color .2s;
    }

    .sb-logout:hover { color: var(--red); }

    /* ── Topbar ── */
    .topbar {
      height: var(--topbar-h);
      background: var(--white);
      border-bottom: 1px solid var(--gray-mid);
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 24px;
      position: sticky; top: 0; z-index: 100;
    }

    .topbar-left { display: flex; align-items: center; gap: 12px; }

    .hamburger {
      display: none; background: none; border: none;
      font-size: 1.1rem; color: var(--text-light); cursor: pointer; padding: 4px;
    }

    .page-breadcrumb { font-size: .82rem; color: var(--text-light); }
    .page-breadcrumb strong { color: var(--navy); }

    .topbar-right { display: flex; align-items: center; gap: 14px; }

    .topbar-avatar {
      width: 34px; height: 34px; border-radius: 50%;
      background: linear-gradient(135deg, var(--gold), #8a6b1a);
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-weight: 700; font-size: .78rem;
      cursor: pointer;
    }

    /* ── Main ── */
    .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; }

    .page { padding: 24px; flex: 1; }

    .panel { display: none; }
    .panel.active { display: block; animation: fadeUp .25s ease; }

    @keyframes fadeUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }

    /* ── Stat cards ── */
    .stats-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px; margin-bottom: 22px;
    }

    .stat-card {
      background: var(--white);
      border-radius: 10px;
      border: 1px solid var(--gray-mid);
      padding: 18px 20px;
      display: flex; align-items: center; gap: 14px;
      box-shadow: 0 1px 4px rgba(0,0,0,.05);
    }

    .stat-icon {
      width: 46px; height: 46px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem; flex-shrink: 0;
    }

    .si-blue   { background: rgba(37,99,235,.1);  color: var(--blue); }
    .si-amber  { background: rgba(245,158,11,.1); color: var(--amber); }
    .si-green  { background: rgba(16,185,129,.1); color: var(--green); }
    .si-navy   { background: rgba(13,31,60,.1);   color: var(--navy); }

    .stat-val   { font-size: 1.6rem; font-weight: 700; color: var(--navy); line-height: 1; }
    .stat-label { font-size: .75rem; color: var(--text-light); margin-top: 3px; }

    /* ── Card ── */
    .card {
      background: var(--white);
      border-radius: 10px;
      border: 1px solid var(--gray-mid);
      box-shadow: 0 1px 4px rgba(0,0,0,.05);
      margin-bottom: 20px;
      overflow: hidden;
    }

    .card-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 14px 20px;
      border-bottom: 1px solid var(--gray-mid);
      flex-wrap: wrap; gap: 10px;
    }

    .card-title {
      font-size: .9rem; font-weight: 600; color: var(--navy);
      display: flex; align-items: center; gap: 8px;
    }

    .card-title i { color: var(--gold); }

    /* ── Search box ── */
    .search-box {
      display: flex; align-items: center; gap: 8px;
      background: var(--gray-light); border: 1px solid var(--gray-mid);
      border-radius: 7px; padding: 7px 12px;
    }

    .search-box input {
      border: none; background: none; outline: none;
      font-family: 'Outfit', sans-serif; font-size: .83rem;
      color: var(--text); width: 200px;
    }

    .search-box i { color: var(--gray); font-size: .8rem; }

    /* ── Table ── */
    .table-wrap { overflow-x: auto; }

    table.tbl {
      width: 100%; border-collapse: collapse; font-size: .83rem;
    }

    table.tbl thead th {
      padding: 10px 16px; text-align: left;
      font-size: .71rem; font-weight: 700; color: var(--text-light);
      text-transform: uppercase; letter-spacing: .5px;
      background: var(--gray-light);
      border-bottom: 1px solid var(--gray-mid);
      white-space: nowrap;
    }

    table.tbl tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
    table.tbl tbody tr:last-child { border-bottom: none; }
    table.tbl tbody tr:hover { background: #fafbfc; }
    table.tbl tbody td { padding: 12px 16px; vertical-align: middle; }

    .td-ref { font-weight: 600; color: var(--navy); font-size: .8rem; font-family: monospace; }
    .td-empty { text-align: center; padding: 36px !important; color: var(--gray); font-size: .85rem; }

    /* ── Badges ── */
    .badge {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 3px 9px; border-radius: 20px;
      font-size: .7rem; font-weight: 600; white-space: nowrap;
    }

    .b-transit    { background: #fef3c7; color: #92400e; }
    .b-delivered  { background: #d1fae5; color: #065f46; }
    .b-processing { background: #dbeafe; color: #1e40af; }
    .b-pending    { background: #f3f4f6; color: #374151; }
    .b-cancelled  { background: #fee2e2; color: #991b1b; }

    /* ── Track link ── */
    .track-link {
      display: inline-flex; align-items: center; gap: 5px;
      color: var(--blue); font-size: .78rem; font-weight: 500;
      text-decoration: none; padding: 4px 10px;
      border: 1px solid rgba(37,99,235,.25);
      border-radius: 6px; transition: all .2s;
    }

    .track-link:hover { background: var(--blue); color: #fff; }

    /* ── Profile card ── */
    .profile-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 0;
    }

    .profile-row {
      display: flex; justify-content: space-between; align-items: center;
      padding: 11px 20px; border-bottom: 1px solid #f1f5f9; font-size: .85rem;
    }

    .profile-row:last-child { border-bottom: none; }
    .profile-row .pl { color: var(--text-light); font-weight: 500; }
    .profile-row .pv { color: var(--text); font-weight: 500; text-align: right; }

    /* ── Address box ── */
    .addr-box {
      background: var(--gray-light);
      border: 1px solid var(--gray-mid);
      border-radius: 8px; padding: 16px 20px;
      font-size: .86rem; line-height: 1.9;
      color: var(--navy); font-weight: 500;
    }

    .addr-row { display: flex; gap: 8px; }
    .addr-lbl { color: var(--text-light); min-width: 110px; font-size: .8rem; }
    .addr-val { color: #e63946; font-weight: 600; }

    .copy-btn {
      display: inline-flex; align-items: center; gap: 6px;
      margin-top: 12px; padding: 7px 14px;
      background: var(--navy); color: #fff;
      border: none; border-radius: 6px; cursor: pointer;
      font-size: .78rem; font-weight: 600; font-family: 'Outfit', sans-serif;
      transition: background .2s;
    }

    .copy-btn:hover { background: var(--navy-light); }

    /* ── Footer ── */
    .footer {
      text-align: center; padding: 14px;
      font-size: .74rem; color: var(--text-light);
      border-top: 1px solid var(--gray-mid);
      background: var(--white);
    }

    /* ── Responsive ── */
    @media (max-width: 900px) {
      .sidebar { transform: translateX(-100%); }
      .sidebar.open { transform: translateX(0); }
      .main { margin-left: 0; }
      .hamburger { display: block; }
      .stats-row { grid-template-columns: 1fr 1fr; }
    }

    @media (max-width: 480px) {
      .stats-row { grid-template-columns: 1fr 1fr; }
      .page { padding: 14px; }
    }
  </style>
</head>
<body>
<div class="wrap">

<!-- ══════════ SIDEBAR ══════════ -->
<aside class="sidebar" id="sidebar">

  <a href="index.html" class="sb-logo">
    <div class="sb-logo-hex">BLUE<br>ROUTE</div>
    <div class="sb-logo-text">BlueRoute <span>Security &amp; Shipping</span></div>
  </a>

  <div class="sb-profile">
    <div class="sb-avatar"><?= $initials ?></div>
    <div class="sb-profile-info">
      <div class="sb-greeting"><?= $greeting ?>,</div>
      <div class="sb-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
      <div class="sb-email"><?= htmlspecialchars($user['email']) ?></div>
    </div>
  </div>

  <nav class="sb-nav">
    <div class="sb-nav-label">Main</div>
    <button class="sb-item active" data-panel="dashboard">
      <i class="fa-solid fa-gauge"></i> Dashboard
    </button>
    <button class="sb-item" data-panel="shipments">
      <i class="fa-solid fa-truck-fast"></i> My Shipments
    </button>
    <button class="sb-item" data-panel="tracking">
      <i class="fa-solid fa-map-location-dot"></i> Track a Package
    </button>

    <div class="sb-nav-label">Account</div>
    <button class="sb-item" data-panel="address">
      <i class="fa-solid fa-location-dot"></i> My Address
    </button>
    <button class="sb-item" data-panel="profile">
      <i class="fa-solid fa-user"></i> Profile
    </button>
  </nav>

  <div class="sb-footer">
    <a href="login.html" class="sb-logout">
      <i class="fa-solid fa-arrow-right-from-bracket"></i> Log out
    </a>
  </div>

</aside>

<!-- ══════════ MAIN ══════════ -->
<div class="main">

  <div class="topbar">
    <div class="topbar-left">
      <button class="hamburger" id="hamburger"><i class="fa-solid fa-bars"></i></button>
      <div class="page-breadcrumb">
        BlueRoute &rsaquo; <strong id="topbar-page">Dashboard</strong>
      </div>
    </div>
    <div class="topbar-right">
      <a href="tracking.php" target="_blank" style="font-size:.8rem;color:var(--blue);text-decoration:none">
        <i class="fa-solid fa-magnifying-glass"></i> Track &amp; Trace
      </a>
      <div class="topbar-avatar"><?= $initials ?></div>
    </div>
  </div>

  <div class="page">

    <!-- ══ DASHBOARD ══ -->
    <div class="panel active" id="panel-dashboard">

      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-icon si-blue"><i class="fa-solid fa-boxes-stacked"></i></div>
          <div><div class="stat-val"><?= $total ?></div><div class="stat-label">Total Shipments</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-amber"><i class="fa-solid fa-truck"></i></div>
          <div><div class="stat-val"><?= $inTransit ?></div><div class="stat-label">In Transit</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-green"><i class="fa-solid fa-circle-check"></i></div>
          <div><div class="stat-val"><?= $delivered ?></div><div class="stat-label">Delivered</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-navy"><i class="fa-regular fa-clock"></i></div>
          <div><div class="stat-val"><?= $pending ?></div><div class="stat-label">Pending</div></div>
        </div>
      </div>

      <!-- Recent shipments -->
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fa-solid fa-truck-fast"></i> Recent Shipments</div>
          <button class="track-link" onclick="switchPanel('shipments')">
            View all <i class="fa-solid fa-arrow-right"></i>
          </button>
        </div>
        <div class="table-wrap">
          <table class="tbl">
            <thead>
              <tr><th>Reference</th><th>Route</th><th>Service</th><th>Status</th><th>ETA</th><th>Track</th></tr>
            </thead>
            <tbody>
              <?php if (empty($shipments)): ?>
              <tr><td colspan="6" class="td-empty"><i class="fa-regular fa-box-open"></i> No shipments yet. Contact us to get started.</td></tr>
              <?php else: foreach (array_slice($shipments, 0, 5) as $s):
                $statusMap = ['transit'=>['b-transit','fa-truck','In Transit'],'delivered'=>['b-delivered','fa-circle-check','Delivered'],'processing'=>['b-processing','fa-gear','Processing'],'pending'=>['b-pending','fa-clock','Pending'],'cancelled'=>['b-cancelled','fa-xmark','Cancelled']];
                [$bc,$ic,$lb] = $statusMap[$s['status']] ?? ['b-pending','fa-circle',$s['status']];
              ?>
              <tr>
                <td class="td-ref"><?= htmlspecialchars($s['reference']) ?></td>
                <td><?= htmlspecialchars($s['origin']) ?> → <?= htmlspecialchars($s['destination']) ?></td>
                <td><small style="color:var(--text-light)"><?= htmlspecialchars($s['service_type']) ?></small></td>
                <td><span class="badge <?= $bc ?>"><i class="fa-solid <?= $ic ?>"></i> <?= $lb ?></span></td>
                <td><?= htmlspecialchars($s['eta'] ?? '—') ?></td>
                <td><a class="track-link" href="tracking.php?tracking_number=<?= urlencode($s['reference']) ?>" target="_blank"><i class="fa-solid fa-magnifying-glass"></i> Track</a></td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Virtual address teaser -->
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fa-solid fa-location-dot"></i> Your BlueRoute Address</div>
          <button class="track-link" onclick="switchPanel('address')">View full address</button>
        </div>
        <div style="padding:16px 20px;font-size:.86rem;color:var(--text-light);line-height:1.8">
          <strong style="color:var(--navy)">13 Exning Rd, Newmarket, CB8 0JD, UK</strong> — Locker #<?= $userId ?><br>
          Use this address when shopping online to have packages received and forwarded securely.
        </div>
      </div>

    </div><!-- /dashboard -->

    <!-- ══ SHIPMENTS ══ -->
    <div class="panel" id="panel-shipments">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fa-solid fa-truck-fast"></i> All My Shipments</div>
          <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Search reference, status…" oninput="filterRows(this.value)" />
          </div>
        </div>
        <div class="table-wrap">
          <table class="tbl" id="ship-table">
            <thead>
              <tr><th>Reference</th><th>Origin</th><th>Destination</th><th>Service</th><th>Current Location</th><th>Status</th><th>ETA</th><th>Track</th></tr>
            </thead>
            <tbody>
              <?php if (empty($shipments)): ?>
              <tr><td colspan="8" class="td-empty"><i class="fa-regular fa-box-open"></i> No shipments found on your account.</td></tr>
              <?php else: foreach ($shipments as $s):
                $statusMap = ['transit'=>['b-transit','fa-truck','In Transit'],'delivered'=>['b-delivered','fa-circle-check','Delivered'],'processing'=>['b-processing','fa-gear','Processing'],'pending'=>['b-pending','fa-clock','Pending'],'cancelled'=>['b-cancelled','fa-xmark','Cancelled']];
                [$bc,$ic,$lb] = $statusMap[$s['status']] ?? ['b-pending','fa-circle',$s['status']];
              ?>
              <tr>
                <td class="td-ref"><?= htmlspecialchars($s['reference']) ?></td>
                <td><?= htmlspecialchars($s['origin']) ?></td>
                <td><?= htmlspecialchars($s['destination']) ?></td>
                <td><small style="color:var(--text-light)"><?= htmlspecialchars($s['service_type']) ?></small></td>
                <td><?= htmlspecialchars($s['current_location'] ?? '—') ?></td>
                <td><span class="badge <?= $bc ?>"><i class="fa-solid <?= $ic ?>"></i> <?= $lb ?></span></td>
                <td><?= htmlspecialchars($s['eta'] ?? '—') ?></td>
                <td><a class="track-link" href="tracking.php?tracking_number=<?= urlencode($s['reference']) ?>" target="_blank"><i class="fa-solid fa-magnifying-glass"></i> Track</a></td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div><!-- /shipments -->

    <!-- ══ TRACKING ══ -->
    <div class="panel" id="panel-tracking">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fa-solid fa-map-location-dot"></i> Track a Package</div>
        </div>
        <div style="padding:24px 20px">
          <p style="font-size:.86rem;color:var(--text-light);margin-bottom:16px">Enter your tracking number below to get real-time status and location updates.</p>
          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <input type="text" id="track-input" placeholder="e.g. BLR100000001"
              style="flex:1;min-width:220px;padding:10px 14px;border:1px solid var(--gray-mid);border-radius:7px;font-size:.88rem;outline:none;font-family:'Outfit',sans-serif"
              onkeydown="if(event.key==='Enter') doTrack()" />
            <button onclick="doTrack()" style="padding:10px 22px;background:var(--blue);color:#fff;border:none;border-radius:7px;font-family:'Outfit',sans-serif;font-size:.88rem;font-weight:600;cursor:pointer">
              <i class="fa-solid fa-magnifying-glass"></i> Search
            </button>
          </div>
          <div id="track-result" style="margin-top:20px"></div>
        </div>
      </div>

      <?php if (!empty($shipments)): ?>
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fa-solid fa-list"></i> Quick Track — My Shipments</div>
        </div>
        <div style="padding:0 16px 16px">
          <?php foreach ($shipments as $s):
            $statusMap = ['transit'=>['b-transit','fa-truck','In Transit'],'delivered'=>['b-delivered','fa-circle-check','Delivered'],'processing'=>['b-processing','fa-gear','Processing'],'pending'=>['b-pending','fa-clock','Pending'],'cancelled'=>['b-cancelled','fa-xmark','Cancelled']];
            [$bc,$ic,$lb] = $statusMap[$s['status']] ?? ['b-pending','fa-circle',$s['status']];
          ?>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid #f1f5f9;flex-wrap:wrap;gap:8px">
            <div>
              <div style="font-weight:600;color:var(--navy);font-size:.85rem;font-family:monospace"><?= htmlspecialchars($s['reference']) ?></div>
              <div style="font-size:.76rem;color:var(--text-light);margin-top:2px"><?= htmlspecialchars($s['origin']) ?> → <?= htmlspecialchars($s['destination']) ?></div>
            </div>
            <div style="display:flex;align-items:center;gap:10px">
              <span class="badge <?= $bc ?>"><i class="fa-solid <?= $ic ?>"></i> <?= $lb ?></span>
              <a class="track-link" href="tracking.php?tracking_number=<?= urlencode($s['reference']) ?>" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open</a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- /tracking -->

    <!-- ══ ADDRESS ══ -->
    <div class="panel" id="panel-address">
      <div class="card" style="max-width:560px">
        <div class="card-header">
          <div class="card-title"><i class="fa-solid fa-location-dot"></i> Your BlueRoute Address</div>
        </div>
        <div style="padding:20px">
          <p style="font-size:.84rem;color:var(--text-light);margin-bottom:16px;line-height:1.7">
            Use the address below when shopping from online merchants. Packages arrive at our secure Newmarket facility and are forwarded to you.
          </p>
          <div class="addr-box">
            <div class="addr-row"><span class="addr-lbl">Line 1 Address:</span><span class="addr-val">13 Exning Rd, Newmarket, CB8 0JD, UK</span></div>
            <div class="addr-row"><span class="addr-lbl">Line 2 Address:</span><span class="addr-val"><?= $userId ?></span></div>
            <div class="addr-row"><span class="addr-lbl">City:</span><span class="addr-val">Newmarket</span></div>
            <div class="addr-row"><span class="addr-lbl">Postcode:</span><span class="addr-val">CB8 0JD</span></div>
            <div class="addr-row"><span class="addr-lbl">Country:</span><span class="addr-val">United Kingdom</span></div>
          </div>
          <button class="copy-btn" onclick="copyAddress()">
            <i class="fa-regular fa-copy"></i> Copy full address
          </button>
          <div id="copy-confirm" style="display:none;margin-top:10px;font-size:.8rem;color:var(--green)">
            <i class="fa-solid fa-circle-check"></i> Address copied to clipboard!
          </div>
        </div>
      </div>
    </div><!-- /address -->

    <!-- ══ PROFILE ══ -->
    <div class="panel" id="panel-profile">
      <div class="card" style="max-width:520px">
        <div class="card-header">
          <div class="card-title"><i class="fa-solid fa-user"></i> My Profile</div>
        </div>
        <div style="padding:20px;display:flex;align-items:center;gap:14px;border-bottom:1px solid var(--gray-mid)">
          <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,var(--gold),#8a6b1a);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;font-weight:700"><?= $initials ?></div>
          <div>
            <div style="font-size:1rem;font-weight:700;color:var(--navy)"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
            <div style="font-size:.8rem;color:var(--text-light)">@<?= htmlspecialchars($user['username']) ?></div>
            <div style="margin-top:4px"><span class="badge b-delivered"><i class="fa-solid fa-circle" style="font-size:.4rem"></i> <?= ucfirst($user['status']) ?></span></div>
          </div>
        </div>
        <?php
        $profileFields = [
          'Email'     => $user['email'],
          'Phone'     => $user['phone'] ?? '—',
          'Country'   => $user['country'] ?? '—',
          'State'     => $user['state'] ?? '—',
          'City'      => $user['city'] ?? '—',
          'Zip Code'  => $user['zip_code'] ?? '—',
          'Address'   => $user['address'] ?? '—',
          'Member since' => date('d M Y', strtotime($user['created_at'])),
        ];
        foreach ($profileFields as $label => $value):
        ?>
        <div class="profile-row">
          <span class="pl"><?= $label ?></span>
          <span class="pv"><?= htmlspecialchars($value) ?></span>
        </div>
        <?php endforeach; ?>
        <div style="padding:14px 20px;border-top:1px solid var(--gray-mid)">
          <a href="signup.php" style="font-size:.82rem;color:var(--blue);text-decoration:none">
            <i class="fa-solid fa-pen-to-square"></i> Edit profile
          </a>
        </div>
      </div>
    </div><!-- /profile -->

  </div><!-- /page -->

  <footer class="footer">© 2026 BlueRoute Shipping — All rights reserved</footer>
</div><!-- /main -->
</div><!-- /wrap -->

<script>
// Panel switching
const pages = { dashboard:'Dashboard', shipments:'My Shipments', tracking:'Track a Package', address:'My Address', profile:'Profile' };

function switchPanel(id) {
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.sb-item').forEach(b => b.classList.remove('active'));
  document.getElementById('panel-' + id)?.classList.add('active');
  document.querySelector(`[data-panel="${id}"]`)?.classList.add('active');
  document.getElementById('topbar-page').textContent = pages[id] || id;
  document.getElementById('sidebar').classList.remove('open');
}

document.querySelectorAll('[data-panel]').forEach(btn =>
  btn.addEventListener('click', () => switchPanel(btn.dataset.panel))
);

document.getElementById('hamburger').addEventListener('click', () =>
  document.getElementById('sidebar').classList.toggle('open')
);

// Table search filter
function filterRows(q) {
  document.querySelectorAll('#ship-table tbody tr').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q.toLowerCase()) ? '' : 'none';
  });
}

// Copy address
function copyAddress() {
  const text = `13 Exning Rd, Newmarket, CB8 0JD, UK\n<?= $userId ?>\nNewmarket\nCB8 0JD\nUnited Kingdom`;
  navigator.clipboard.writeText(text).then(() => {
    const c = document.getElementById('copy-confirm');
    c.style.display = 'block';
    setTimeout(() => c.style.display = 'none', 3000);
  });
}

// Inline track & trace
async function doTrack() {
  const ref = document.getElementById('track-input').value.trim();
  const resultEl = document.getElementById('track-result');
  if (!ref) return;

  resultEl.innerHTML = `<div style="color:var(--text-light);font-size:.85rem"><i class="fa-solid fa-spinner fa-spin"></i> Searching…</div>`;

  try {
    const res  = await fetch(`api/track.php?ref=${encodeURIComponent(ref)}`);
    const data = await res.json();

    if (!data.success) {
      resultEl.innerHTML = `
        <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:14px 16px;color:#b91c1c;font-size:.85rem">
          <i class="fa-solid fa-circle-exclamation"></i> ${data.error}
        </div>`;
      return;
    }

    const s = data.data;
    const statusMap = {transit:'🚛 In Transit',delivered:'✅ Delivered',processing:'⚙️ Processing',pending:'🕐 Pending',cancelled:'❌ Cancelled'};
    const tlRows = (s.events || []).map(ev => {
      const dot = ev.is_done == 1 ? '#b8942a' : ev.is_active == 1 ? '#0d1f3c' : '#e2e8f0';
      return `<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:14px">
        <div style="width:12px;height:12px;border-radius:50%;background:${dot};flex-shrink:0;margin-top:3px"></div>
        <div>
          <div style="font-size:.84rem;font-weight:600;color:#1e293b">${ev.event_title}</div>
          <div style="font-size:.74rem;color:#94a3b8">${ev.event_time?.substring(0,16) || ''} ${ev.location ? '· ' + ev.location : ''}</div>
        </div>
      </div>`;
    }).join('');

    resultEl.innerHTML = `
      <div style="background:var(--white);border:1px solid var(--gray-mid);border-radius:10px;overflow:hidden">
        <div style="background:var(--navy);padding:14px 18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
          <div>
            <div style="font-weight:700;color:#fff;font-family:monospace">${s.reference}</div>
            <div style="font-size:.75rem;color:rgba(255,255,255,.6);margin-top:2px">${s.client_name}</div>
          </div>
          <div style="background:rgba(255,255,255,.15);padding:4px 12px;border-radius:20px;font-size:.75rem;font-weight:600;color:#fff">
            ${statusMap[s.status] || s.status}
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;padding:14px 18px;gap:10px;border-bottom:1px solid var(--gray-mid)">
          <div><div style="font-size:.7rem;color:var(--text-light);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px">Origin</div><div style="font-size:.84rem;font-weight:500">${s.origin}</div></div>
          <div><div style="font-size:.7rem;color:var(--text-light);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px">Destination</div><div style="font-size:.84rem;font-weight:500">${s.destination}</div></div>
          <div><div style="font-size:.7rem;color:var(--text-light);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px">Current Location</div><div style="font-size:.84rem;font-weight:500">${s.current_location || '—'}</div></div>
          <div><div style="font-size:.7rem;color:var(--text-light);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px">Est. Delivery</div><div style="font-size:.84rem;font-weight:500">${s.eta || '—'}</div></div>
        </div>
        ${tlRows ? `<div style="padding:14px 18px"><div style="font-size:.75rem;font-weight:700;color:var(--navy);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Tracking History</div><div style="padding-left:6px">${tlRows}</div></div>` : ''}
        <div style="padding:10px 18px;border-top:1px solid var(--gray-mid);text-align:right">
          <a href="tracking.php?tracking_number=${s.reference}" target="_blank" class="track-link">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> Full tracking page
          </a>
        </div>
      </div>`;
  } catch(e) {
    resultEl.innerHTML = `<div style="color:var(--red);font-size:.85rem"><i class="fa-solid fa-circle-exclamation"></i> Connection error. Please try again.</div>`;
  }
}
</script>
</body>
</html>