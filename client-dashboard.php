<?php
// ══════════════════════════════════════════
//  BlueRoute — Client Dashboard
//  client-dashboard.php
//  FIXES:
//   1. Session key corrected: $_SESSION['user']['id'] (was $_SESSION['user_id'])
//   2. Role guard added — only 'client' role may access; admins redirected to admin.php
//   3. Removed ?user_id= GET override (clients must never view other users' data)
// ══════════════════════════════════════════

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/db.php';

// ── Auth guard ────────────────────────────
// Must be logged in
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Admins go to their own panel — never the client dashboard
if ($_SESSION['user']['role'] === 'admin') {
    header('Location: admin.php');
    exit;
}

// Only 'client' role beyond this point
if ($_SESSION['user']['role'] !== 'client') {
    session_destroy();
    header('Location: login.php');
    exit;
}

// ── User ID comes from session only ──────
// Removed ?user_id= GET override — clients must never access other users' data
$userId = (int) $_SESSION['user']['id'];

if (!$userId) {
    header('Location: login.php');
    exit;
}

$pdo = getDB();

// Re-fetch fresh user data from DB (session may be stale)
$uStmt = $pdo->prepare("SELECT * FROM users WHERE id=:id");
$uStmt->execute([':id' => $userId]);
$user = $uStmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Double-check role from DB too (in case it was changed server-side)
if ($user['role'] !== 'client') {
    header('Location: ' . ($user['role'] === 'admin' ? 'admin.php' : 'login.php'));
    exit;
}

$shipStmt = $pdo->prepare("
    SELECT s.*,
        (SELECT te.event_title FROM tracking_events te WHERE te.shipment_id=s.id AND te.is_active=1 LIMIT 1) AS latest_event,
        (SELECT COUNT(*) FROM tracking_events te WHERE te.shipment_id=s.id) AS event_count,
        (SELECT COUNT(*) FROM tracking_events te WHERE te.shipment_id=s.id AND te.is_done=1) AS events_done
    FROM shipments s
    WHERE s.client_email=:email
    ORDER BY s.created_at DESC
");
$shipStmt->execute([':email' => $user['email']]);
$shipments = $shipStmt->fetchAll();

$total     = count($shipments);
$inTransit = count(array_filter($shipments, fn($s) => $s['status'] === 'transit'));
$delivered = count(array_filter($shipments, fn($s) => $s['status'] === 'delivered'));
$pending   = count(array_filter($shipments, fn($s) => in_array($s['status'], ['pending', 'processing'])));

$initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
$hour     = (int) date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

$sc = [
    'transit'    => ['label' => 'In Transit',  'cls' => 's-transit',    'icon' => 'fa-truck'],
    'delivered'  => ['label' => 'Delivered',   'cls' => 's-delivered',  'icon' => 'fa-circle-check'],
    'processing' => ['label' => 'Processing',  'cls' => 's-processing', 'icon' => 'fa-gear'],
    'pending'    => ['label' => 'Pending',     'cls' => 's-pending',    'icon' => 'fa-clock'],
    'cancelled'  => ['label' => 'Cancelled',   'cls' => 's-cancelled',  'icon' => 'fa-ban'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Dashboard | BlueRoute</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Serif+Display&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --navy:#0b1929;--navy-mid:#112240;--navy-l:#1a3260;
  --gold:#c9a84c;--gold-dim:rgba(201,168,76,.15);
  --sidebar-w:240px;--topbar-h:64px;
  --page:#f0f4f9;--card:#ffffff;--border:#e4eaf2;--bdr:rgba(255,255,255,.07);
  --th:#0b1929;--tb:#334155;--tm:#64748b;--ts:#94a3b8;
  --c-transit:#f59e0b;--c-deliv:#10b981;--c-proc:#3b82f6;--c-pend:#94a3b8;--c-cancel:#ef4444;
  --r:12px;--r-sm:8px;
  --sh-sm:0 1px 3px rgba(11,25,41,.06),0 1px 8px rgba(11,25,41,.04);
  --sh-md:0 4px 16px rgba(11,25,41,.08),0 1px 4px rgba(11,25,41,.05);
  --sh-lg:0 12px 40px rgba(11,25,41,.14);
}
html,body{font-family:'DM Sans',sans-serif;background:var(--page);color:var(--tb);min-height:100vh;-webkit-font-smoothing:antialiased}

/* LAYOUT */
.shell{display:flex;min-height:100vh}

/* SIDEBAR */
.sidebar{
  width:var(--sidebar-w);background:var(--navy);
  position:fixed;top:0;left:0;bottom:0;z-index:300;
  display:flex;flex-direction:column;overflow:hidden;
  transition:transform .3s cubic-bezier(.4,0,.2,1);
}
.sidebar::before{
  content:'';position:absolute;inset:0;pointer-events:none;
  background:repeating-linear-gradient(-45deg,rgba(255,255,255,.012) 0px,rgba(255,255,255,.012) 1px,transparent 1px,transparent 8px);
}
.sb-logo{display:flex;align-items:center;gap:11px;padding:22px 20px 18px;border-bottom:1px solid var(--bdr);text-decoration:none;flex-shrink:0}
.sb-hex{width:36px;height:36px;flex-shrink:0;clip-path:polygon(50% 0%,100% 25%,100% 75%,50% 100%,0% 75%,0% 25%);background:linear-gradient(135deg,var(--gold),#8a6820);display:flex;align-items:center;justify-content:center;font-size:7px;font-weight:800;color:#fff;text-align:center;line-height:1.2;letter-spacing:.3px}
.sb-brand-name{font-size:.82rem;font-weight:700;color:#fff;letter-spacing:.5px}
.sb-brand-sub{font-size:.62rem;color:rgba(255,255,255,.3);margin-top:1px}
.sb-user{display:flex;align-items:center;gap:10px;padding:14px 20px;border-bottom:1px solid var(--bdr);flex-shrink:0}
.sb-av{width:38px;height:38px;border-radius:50%;flex-shrink:0;background:linear-gradient(135deg,var(--gold),#8a6820);display:flex;align-items:center;justify-content:center;font-size:.82rem;font-weight:700;color:#fff;border:2px solid rgba(201,168,76,.3)}
.sb-uname{font-size:.84rem;font-weight:600;color:#fff;line-height:1.2}
.sb-email{font-size:.66rem;color:rgba(255,255,255,.35);margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px}
.sb-nav{flex:1;padding:10px 0;overflow-y:auto}
.sb-nav::-webkit-scrollbar{width:0}
.sb-sec{font-size:.58rem;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:rgba(255,255,255,.2);padding:14px 20px 5px}
.sb-item{display:flex;align-items:center;gap:10px;padding:10px 20px;color:rgba(255,255,255,.45);text-decoration:none;font-size:.84rem;font-weight:500;cursor:pointer;border:none;background:none;width:100%;text-align:left;transition:all .18s;border-left:2px solid transparent}
.sb-ic{width:30px;height:30px;border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;font-size:.78rem;flex-shrink:0;background:rgba(255,255,255,.05);transition:all .18s}
.sb-item:hover{color:#fff;background:rgba(255,255,255,.06)}
.sb-item:hover .sb-ic{background:rgba(201,168,76,.15);color:var(--gold)}
.sb-item.active{color:#fff;background:rgba(201,168,76,.1);border-left-color:var(--gold)}
.sb-item.active .sb-ic{background:var(--gold-dim);color:var(--gold)}
.sb-foot{padding:14px 20px;border-top:1px solid var(--bdr);flex-shrink:0}
.sb-out{display:flex;align-items:center;gap:9px;color:rgba(255,255,255,.3);font-size:.8rem;text-decoration:none;transition:color .18s;background:none;border:none;cursor:pointer;width:100%;font-family:'DM Sans',sans-serif}
.sb-out:hover{color:var(--c-cancel)}

/* TOPBAR */
.topbar{height:var(--topbar-h);background:var(--card);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 28px;position:sticky;top:0;z-index:200;box-shadow:var(--sh-sm)}
.topbar-l{display:flex;align-items:center;gap:14px}
.hbg{display:none;background:none;border:none;font-size:1.1rem;color:var(--tm);cursor:pointer;padding:6px;border-radius:6px;transition:background .15s}
.hbg:hover{background:var(--page)}
.bc{font-size:.82rem;color:var(--tm)}
.bc strong{color:var(--th);font-weight:600}
.bc span{color:var(--ts);margin:0 6px}
.topbar-r{display:flex;align-items:center;gap:10px}
.tb-track{display:flex;align-items:center;gap:7px;padding:8px 16px;background:var(--navy);color:#fff;border:none;border-radius:var(--r-sm);font-family:'DM Sans',sans-serif;font-size:.8rem;font-weight:600;cursor:pointer;text-decoration:none;transition:background .18s}
.tb-track:hover{background:var(--navy-l)}
.tb-av{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--gold),#8a6820);display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:700;color:#fff;cursor:pointer;border:2px solid var(--border)}

/* MAIN */
.main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh}
.page-body{padding:28px 28px 40px;flex:1}
.panel{display:none}
.panel.active{display:block;animation:fadeIn .22s ease forwards}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}

/* PAGE HEADING */
.ph{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.ph h1{font-family:'DM Serif Display',serif;font-size:1.65rem;color:var(--th);line-height:1.1}
.ph .sub{font-size:.82rem;color:var(--tm);margin-top:3px}

/* STAT CARDS */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
.scard{background:var(--card);border-radius:var(--r);border:1px solid var(--border);padding:20px 22px;box-shadow:var(--sh-sm);position:relative;overflow:hidden;transition:box-shadow .2s,transform .2s;cursor:default}
.scard:hover{box-shadow:var(--sh-md);transform:translateY(-2px)}
.scard::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;border-radius:3px 0 0 3px}
.scard.c1::before{background:var(--navy)}.scard.c2::before{background:var(--c-transit)}.scard.c3::before{background:var(--c-deliv)}.scard.c4::before{background:var(--c-pend)}
.slbl{font-size:.71rem;font-weight:600;color:var(--tm);text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px}
.sval{font-family:'DM Serif Display',serif;font-size:2.5rem;color:var(--th);line-height:1;margin-bottom:8px}
.sft{font-size:.72rem;color:var(--ts);display:flex;align-items:center;gap:5px}
.sbg{position:absolute;right:16px;top:50%;transform:translateY(-50%);font-size:2.8rem;opacity:.04;color:var(--th)}

/* CARD */
.card{background:var(--card);border-radius:var(--r);border:1px solid var(--border);box-shadow:var(--sh-sm);overflow:hidden;margin-bottom:20px}
.ch{display:flex;align-items:center;justify-content:space-between;padding:16px 22px;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:10px}
.ct{display:flex;align-items:center;gap:9px;font-size:.88rem;font-weight:700;color:var(--th)}
.cti{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.78rem}
.ci-navy{background:rgba(11,25,41,.08);color:var(--navy)}.ci-gold{background:var(--gold-dim);color:var(--gold)}.ci-green{background:rgba(16,185,129,.1);color:var(--c-deliv)}.ci-blue{background:rgba(59,130,246,.1);color:var(--c-proc)}

/* TABLE */
.tw{overflow-x:auto}
table.t{width:100%;border-collapse:collapse;font-size:.82rem}
table.t thead th{padding:11px 18px;text-align:left;font-size:.68rem;font-weight:700;color:var(--tm);text-transform:uppercase;letter-spacing:.7px;white-space:nowrap;background:#f8fafc;border-bottom:1px solid var(--border)}
table.t tbody tr{border-bottom:1px solid #f1f5f9;transition:background .12s}
table.t tbody tr:last-child{border-bottom:none}
table.t tbody tr:hover{background:#fafcff}
table.t tbody td{padding:13px 18px;vertical-align:middle}
.tref{font-weight:600;color:var(--navy);font-size:.79rem;white-space:nowrap;font-family:monospace}
.te{text-align:center;padding:48px !important;color:var(--ts);font-size:.84rem}
.te i{font-size:1.8rem;display:block;margin-bottom:10px;opacity:.3}

/* BADGES */
.badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:.69rem;font-weight:700;white-space:nowrap;letter-spacing:.2px}
.s-transit{background:#fef3c7;color:#92400e}.s-delivered{background:#d1fae5;color:#065f46}.s-processing{background:#dbeafe;color:#1e40af}.s-pending{background:#f1f5f9;color:#475569}.s-cancelled{background:#fee2e2;color:#991b1b}

/* PROGRESS */
.pgw{min-width:90px}
.pgb{height:5px;background:#e9eef4;border-radius:3px;overflow:hidden;margin-bottom:3px}
.pgf{height:100%;border-radius:3px;background:linear-gradient(90deg,var(--navy),var(--gold));transition:width .4s ease}
.pgl{font-size:.67rem;color:var(--ts)}

/* SEARCH */
.sw{display:flex;align-items:center;gap:8px;background:var(--page);border:1px solid var(--border);border-radius:var(--r-sm);padding:7px 13px;transition:border-color .18s}
.sw:focus-within{border-color:var(--navy)}
.sw i{color:var(--ts);font-size:.78rem}
.sw input{border:none;background:none;outline:none;font-family:'DM Sans',sans-serif;font-size:.82rem;color:var(--tb);width:200px}

/* BUTTONS */
.btn{display:inline-flex;align-items:center;gap:7px;padding:8px 18px;border-radius:var(--r-sm);font-family:'DM Sans',sans-serif;font-size:.82rem;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:all .18s;white-space:nowrap}
.b-navy{background:var(--navy);color:#fff}.b-navy:hover{background:var(--navy-l)}
.b-out{background:none;border:1px solid var(--border);color:var(--tm)}.b-out:hover{border-color:var(--navy);color:var(--navy);background:rgba(11,25,41,.03)}
.b-sm{padding:5px 12px;font-size:.76rem}
.tlink{display:inline-flex;align-items:center;gap:5px;color:var(--navy);font-size:.76rem;font-weight:600;text-decoration:none;padding:5px 11px;border:1px solid rgba(11,25,41,.15);border-radius:6px;transition:all .18s;white-space:nowrap}
.tlink:hover{background:var(--navy);color:#fff;border-color:transparent}

/* ADDRESS HERO */
.addr-hero{padding:22px;background:linear-gradient(135deg,var(--navy) 0%,var(--navy-l) 100%);border-radius:var(--r);margin-bottom:20px;position:relative;overflow:hidden}
.addr-hero::after,.addr-hero::before{content:'';position:absolute;border-radius:50%}
.addr-hero::after{right:-20px;top:-20px;width:120px;height:120px;background:rgba(201,168,76,.08)}
.addr-hero::before{right:30px;bottom:-30px;width:80px;height:80px;background:rgba(201,168,76,.05)}
.addr-lbl{font-size:.65rem;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:1px;margin-bottom:14px}
.arow{display:flex;align-items:baseline;gap:10px;margin-bottom:7px;font-size:.84rem}
.ak{color:rgba(255,255,255,.4);min-width:110px;font-size:.76rem}
.av{color:var(--gold);font-weight:600}
.acopy{margin-top:14px;display:inline-flex;align-items:center;gap:7px;padding:8px 16px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:var(--r-sm);color:rgba(255,255,255,.8);font-size:.78rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .18s}
.acopy:hover{background:rgba(201,168,76,.2);border-color:var(--gold);color:var(--gold)}

/* TRACK */
.tbar{display:flex;gap:10px;align-items:stretch;flex-wrap:wrap;padding:20px 22px;border-bottom:1px solid var(--border)}
.tbar input{flex:1;min-width:220px;padding:10px 16px;border:1px solid var(--border);border-radius:var(--r-sm);font-family:'DM Sans',sans-serif;font-size:.88rem;color:var(--tb);outline:none;transition:border-color .18s}
.tbar input:focus{border-color:var(--navy)}
.tr-card{border:1px solid var(--border);border-radius:var(--r);overflow:hidden;animation:fadeIn .2s ease}
.trh{background:var(--navy);padding:14px 20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px}
.tr-ref{font-weight:700;color:#fff;font-size:.88rem;letter-spacing:.5px}
.tr-name{font-size:.72rem;color:rgba(255,255,255,.45);margin-top:2px}
.trgrid{display:grid;grid-template-columns:1fr 1fr;border-bottom:1px solid var(--border)}
.trc{padding:13px 20px;border-right:1px solid var(--border);border-bottom:1px solid var(--border)}
.trc:nth-child(2n){border-right:none}.trc:nth-last-child(-n+2){border-bottom:none}
.trcl{font-size:.67rem;text-transform:uppercase;letter-spacing:.6px;color:var(--ts);margin-bottom:3px}
.trcv{font-size:.84rem;font-weight:600;color:var(--th)}
.trtl{padding:16px 20px}
.trtlt{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--tm);margin-bottom:14px}
.tli{display:flex;align-items:flex-start;gap:12px;margin-bottom:14px}
.tlidc{display:flex;flex-direction:column;align-items:center}
.tlidot{width:11px;height:11px;border-radius:50%;flex-shrink:0;margin-top:2px}
.tlidot.done{background:var(--gold)}.tlidot.active{background:var(--navy);box-shadow:0 0 0 3px rgba(11,25,41,.15)}.tlidot.future{background:#dde3ec}
.tliline{width:1px;flex:1;min-height:16px;background:linear-gradient(to bottom,#dde3ec,transparent);margin-top:3px}
.tlitl{font-size:.82rem;font-weight:600;color:var(--th);line-height:1.3}
.tlim{font-size:.71rem;color:var(--ts);margin-top:2px}

/* QUICK LIST */
.ql-item{display:flex;align-items:center;justify-content:space-between;padding:13px 22px;border-bottom:1px solid #f1f5f9;flex-wrap:wrap;gap:8px;transition:background .12s}
.ql-item:last-child{border-bottom:none}
.ql-item:hover{background:#fafcff}
.ql-ref{font-weight:600;color:var(--navy);font-size:.83rem}
.ql-rt{font-size:.73rem;color:var(--tm);margin-top:2px}

/* PROFILE */
.prof-h{display:flex;align-items:center;gap:18px;padding:22px 22px 18px;border-bottom:1px solid var(--border)}
.prof-av{width:64px;height:64px;border-radius:50%;flex-shrink:0;background:linear-gradient(135deg,var(--navy),var(--navy-l));display:flex;align-items:center;justify-content:center;font-family:'DM Serif Display',serif;font-size:1.5rem;color:var(--gold);border:3px solid var(--border)}
.prof-name{font-family:'DM Serif Display',serif;font-size:1.15rem;color:var(--th)}
.prof-un{font-size:.78rem;color:var(--tm);margin-top:2px}
.prof-row{display:flex;justify-content:space-between;align-items:center;padding:12px 22px;border-bottom:1px solid #f1f5f9;font-size:.84rem}
.prof-row:last-child{border-bottom:none}
.prl{color:var(--tm);font-weight:500}.prv{color:var(--th);font-weight:500;text-align:right}

/* HOWTO STEPS */
.step{display:flex;gap:12px;margin-bottom:14px}
.stepn{width:24px;height:24px;border-radius:50%;background:var(--navy);color:var(--gold);display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;flex-shrink:0}

/* TOAST */
.tst-wrap{position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px;pointer-events:none}
.tst{display:flex;align-items:center;gap:10px;background:var(--navy);color:#fff;padding:12px 16px;border-radius:var(--r);font-size:.82rem;min-width:240px;box-shadow:var(--sh-lg);animation:tIn .25s ease,tOut .3s ease 3.2s forwards;pointer-events:auto}
.tst.ts i{color:var(--c-deliv)}.tst.te i{color:var(--c-cancel)}.tst.ti i{color:var(--gold)}
@keyframes tIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
@keyframes tOut{from{opacity:1}to{opacity:0;pointer-events:none}}

.footer{text-align:center;padding:16px;font-size:.72rem;color:var(--ts);border-top:1px solid var(--border);background:var(--card)}

/* RESPONSIVE */
@media(max-width:960px){
  .sidebar{transform:translateX(-100%)}.sidebar.open{transform:translateX(0);box-shadow:var(--sh-lg)}
  .main{margin-left:0}.hbg{display:flex}.stats{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:540px){
  .stats{grid-template-columns:repeat(2,1fr);gap:12px}.page-body{padding:16px 14px 32px}
  .topbar{padding:0 14px}.trgrid{grid-template-columns:1fr}.trc{border-right:none}
}
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:299}
.overlay.on{display:block}
</style>
</head>
<body>
<div class="shell">

<aside class="sidebar" id="sidebar">
  <a href="index.html" class="sb-logo">
    <div class="sb-hex">BLUE<br>ROUTE</div>
    <div class="sb-brand"><div class="sb-brand-name">BLUEROUTE</div><div class="sb-brand-sub">Security &amp; Shipping</div></div>
  </a>
  <div class="sb-user">
    <div class="sb-av"><?= $initials ?></div>
    <div style="min-width:0">
      <div class="sb-uname"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
      <div class="sb-email"><?= htmlspecialchars($user['email']) ?></div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="sb-sec">Overview</div>
    <button class="sb-item active" data-panel="dashboard"><span class="sb-ic"><i class="fa-solid fa-gauge-high"></i></span>Dashboard</button>
    <button class="sb-item" data-panel="shipments"><span class="sb-ic"><i class="fa-solid fa-boxes-stacked"></i></span>My Shipments</button>
    <button class="sb-item" data-panel="tracking"><span class="sb-ic"><i class="fa-solid fa-map-location-dot"></i></span>Track Package</button>
    <div class="sb-sec">Account</div>
    <button class="sb-item" data-panel="address"><span class="sb-ic"><i class="fa-solid fa-location-dot"></i></span>My Address</button>
    <button class="sb-item" data-panel="profile"><span class="sb-ic"><i class="fa-solid fa-user"></i></span>Profile</button>
  </nav>
  <div class="sb-foot">
    <a href="logout.php" class="sb-out"><i class="fa-solid fa-arrow-right-from-bracket"></i>Sign out</a>
  </div>
</aside>
<div class="overlay" id="overlay"></div>

<div class="main">
  <div class="topbar">
    <div class="topbar-l">
      <button class="hbg" id="hbg"><i class="fa-solid fa-bars"></i></button>
      <div class="bc">BlueRoute <span>›</span> <strong id="tplbl">Dashboard</strong></div>
    </div>
    <div class="topbar-r">
      <a href="tracking.php" target="_blank" class="tb-track"><i class="fa-solid fa-magnifying-glass"></i> Track &amp; Trace</a>
      <div class="tb-av"><?= $initials ?></div>
    </div>
  </div>

  <div class="page-body">

    <!-- DASHBOARD -->
    <div class="panel active" id="panel-dashboard">
      <div class="ph">
        <div><h1><?= $greeting ?>, <?= htmlspecialchars($user['first_name']) ?></h1><div class="sub">Here's an overview of your shipments</div></div>
        <div style="font-size:.75rem;color:var(--ts)"><?= date('l, d F Y') ?></div>
      </div>
      <div class="stats">
        <div class="scard c1"><div class="slbl">Total Shipments</div><div class="sval"><?= $total ?></div><div class="sft"><i class="fa-solid fa-boxes-stacked"></i>All time</div><i class="fa-solid fa-boxes-stacked sbg"></i></div>
        <div class="scard c2"><div class="slbl">In Transit</div><div class="sval"><?= $inTransit ?></div><div class="sft"><i class="fa-solid fa-truck"></i>Currently moving</div><i class="fa-solid fa-truck sbg"></i></div>
        <div class="scard c3"><div class="slbl">Delivered</div><div class="sval"><?= $delivered ?></div><div class="sft"><i class="fa-solid fa-circle-check"></i>Successfully received</div><i class="fa-solid fa-circle-check sbg"></i></div>
        <div class="scard c4"><div class="slbl">Pending</div><div class="sval"><?= $pending ?></div><div class="sft"><i class="fa-regular fa-clock"></i>Awaiting dispatch</div><i class="fa-regular fa-clock sbg"></i></div>
      </div>

      <div class="card">
        <div class="ch">
          <div class="ct"><span class="cti ci-navy"><i class="fa-solid fa-truck-fast"></i></span>Recent Shipments</div>
          <button class="btn b-out b-sm" onclick="sp('shipments')">View all <i class="fa-solid fa-arrow-right"></i></button>
        </div>
        <div class="tw"><table class="t"><thead><tr><th>Reference</th><th>Route</th><th>Service</th><th>Status</th><th>Progress</th><th>ETA</th><th></th></tr></thead><tbody>
          <?php if (empty($shipments)): ?>
          <tr><td colspan="7" class="te"><i class="fa-regular fa-box-open"></i>No shipments yet. Contact us to get started.</td></tr>
          <?php else: foreach (array_slice($shipments, 0, 6) as $s): $cfg = $sc[$s['status']] ?? ['label' => $s['status'], 'cls' => 's-pending', 'icon' => 'fa-circle']; $pct = $s['event_count'] > 0 ? round(($s['events_done'] / $s['event_count']) * 100) : 0; ?>
          <tr>
            <td class="tref"><?= htmlspecialchars($s['reference']) ?></td>
            <td><div style="font-size:.82rem;font-weight:500;color:var(--th)"><?= htmlspecialchars($s['origin']) ?></div><div style="font-size:.72rem;color:var(--ts);display:flex;align-items:center;gap:4px"><i class="fa-solid fa-arrow-right" style="font-size:.55rem"></i><?= htmlspecialchars($s['destination']) ?></div></td>
            <td style="font-size:.76rem;color:var(--tm)"><?= htmlspecialchars($s['service_type']) ?></td>
            <td><span class="badge <?= $cfg['cls'] ?>"><i class="fa-solid <?= $cfg['icon'] ?>"></i> <?= $cfg['label'] ?></span></td>
            <td><div class="pgw"><div class="pgb"><div class="pgf" style="width:<?= $pct ?>%"></div></div><div class="pgl"><?= $pct ?>% complete</div></div></td>
            <td style="font-size:.8rem;color:var(--tm);white-space:nowrap"><?= htmlspecialchars($s['eta'] ?? '—') ?></td>
            <td><a class="tlink" href="tracking.php?tracking_number=<?= urlencode($s['reference']) ?>" target="_blank"><i class="fa-solid fa-magnifying-glass"></i> Track</a></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody></table></div>
      </div>

      <div class="card">
        <div class="ch">
          <div class="ct"><span class="cti ci-gold"><i class="fa-solid fa-location-dot"></i></span>Your BlueRoute Locker Address</div>
          <button class="btn b-out b-sm" onclick="sp('address')">View full address</button>
        </div>
        <div style="padding:16px 22px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
          <div>
            <div style="font-size:.9rem;font-weight:700;color:var(--th)">13 Exning Rd, Newmarket, CB8 0JD, UK</div>
            <div style="font-size:.76rem;color:var(--tm);margin-top:4px"><i class="fa-solid fa-box-archive" style="color:var(--gold)"></i> Locker #<?= $userId ?> — use this when shopping online</div>
          </div>
          <button class="btn b-navy b-sm" onclick="copyAddr()"><i class="fa-regular fa-copy"></i> Copy address</button>
        </div>
      </div>
    </div>

    <!-- SHIPMENTS -->
    <div class="panel" id="panel-shipments">
      <div class="ph">
        <div><h1>My Shipments</h1><div class="sub"><?= $total ?> shipment<?= $total !== 1 ? 's' : '' ?> on your account</div></div>
        <div class="sw"><i class="fa-solid fa-magnifying-glass"></i><input type="text" placeholder="Search reference, status…" oninput="filterS(this.value)"/></div>
      </div>
      <div class="card">
        <div class="tw"><table class="t" id="stbl"><thead><tr><th>Reference</th><th>Origin</th><th>Destination</th><th>Service</th><th>Current Location</th><th>Status</th><th>Progress</th><th>ETA</th><th></th></tr></thead><tbody>
          <?php if (empty($shipments)): ?>
          <tr><td colspan="9" class="te"><i class="fa-regular fa-box-open"></i>No shipments found.</td></tr>
          <?php else: foreach ($shipments as $s): $cfg = $sc[$s['status']] ?? ['label' => $s['status'], 'cls' => 's-pending', 'icon' => 'fa-circle']; $pct = $s['event_count'] > 0 ? round(($s['events_done'] / $s['event_count']) * 100) : 0; ?>
          <tr>
            <td class="tref"><?= htmlspecialchars($s['reference']) ?></td>
            <td style="font-size:.82rem"><?= htmlspecialchars($s['origin']) ?></td>
            <td style="font-size:.82rem"><?= htmlspecialchars($s['destination']) ?></td>
            <td style="font-size:.76rem;color:var(--tm)"><?= htmlspecialchars($s['service_type']) ?></td>
            <td style="font-size:.8rem;color:var(--tm);max-width:150px"><?= htmlspecialchars($s['current_location'] ?? '—') ?></td>
            <td><span class="badge <?= $cfg['cls'] ?>"><i class="fa-solid <?= $cfg['icon'] ?>"></i> <?= $cfg['label'] ?></span></td>
            <td><div class="pgw"><div class="pgb"><div class="pgf" style="width:<?= $pct ?>%"></div></div><div class="pgl"><?= $pct ?>%</div></div></td>
            <td style="font-size:.8rem;color:var(--tm);white-space:nowrap"><?= htmlspecialchars($s['eta'] ?? '—') ?></td>
            <td><a class="tlink" href="tracking.php?tracking_number=<?= urlencode($s['reference']) ?>" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i></a></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody></table></div>
      </div>
    </div>

    <!-- TRACKING -->
    <div class="panel" id="panel-tracking">
      <div class="ph"><div><h1>Track a Package</h1><div class="sub">Enter any reference for live status updates</div></div></div>
      <div class="card">
        <div class="tbar">
          <input type="text" id="tinput" placeholder="e.g. BLR100000001" onkeydown="if(event.key==='Enter')doTrack()"/>
          <button class="btn b-navy" onclick="doTrack()"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        </div>
        <div id="tresult">
          <div style="padding:36px 22px;text-align:center;color:var(--ts);font-size:.84rem">
            <i class="fa-solid fa-map-location-dot" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.25"></i>
            Enter a reference number above
          </div>
        </div>
      </div>
      <?php if (!empty($shipments)): ?>
      <div class="card">
        <div class="ch"><div class="ct"><span class="cti ci-blue"><i class="fa-solid fa-bolt"></i></span>Quick Track — Your Shipments</div></div>
        <?php foreach ($shipments as $s): $cfg = $sc[$s['status']] ?? ['label' => $s['status'], 'cls' => 's-pending', 'icon' => 'fa-circle']; ?>
        <div class="ql-item">
          <div><div class="ql-ref"><?= htmlspecialchars($s['reference']) ?></div><div class="ql-rt"><?= htmlspecialchars($s['origin']) ?> → <?= htmlspecialchars($s['destination']) ?></div></div>
          <div style="display:flex;align-items:center;gap:10px">
            <span class="badge <?= $cfg['cls'] ?>"><i class="fa-solid <?= $cfg['icon'] ?>"></i> <?= $cfg['label'] ?></span>
            <button class="btn b-out b-sm" onclick="qtrack('<?= htmlspecialchars($s['reference']) ?>');"><i class="fa-solid fa-magnifying-glass"></i> Track</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- ADDRESS -->
    <div class="panel" id="panel-address">
      <div class="ph"><div><h1>My Address</h1><div class="sub">Use this when ordering from any online merchant</div></div></div>
      <div style="max-width:560px">
        <div class="addr-hero">
          <div class="addr-lbl">Your BlueRoute Locker Address</div>
          <div class="arow"><span class="ak">Line 1 Address:</span><span class="av">13 Exning Rd, Newmarket, CB8 0JD, UK</span></div>
          <div class="arow"><span class="ak">Line 2 Address:</span><span class="av"><?= $userId ?></span></div>
          <div class="arow"><span class="ak">City:</span><span class="av">Newmarket</span></div>
          <div class="arow"><span class="ak">Postcode:</span><span class="av">CB8 0JD</span></div>
          <div class="arow"><span class="ak">Country:</span><span class="av">United Kingdom</span></div>
          <button class="acopy" onclick="copyAddr()"><i class="fa-regular fa-copy"></i> Copy full address</button>
          <div id="acopyok" style="display:none;margin-top:8px;font-size:.78rem;color:var(--gold)"><i class="fa-solid fa-circle-check"></i> Copied!</div>
        </div>
        <div class="card">
          <div class="ch"><div class="ct"><span class="cti ci-navy"><i class="fa-solid fa-circle-info"></i></span>How it works</div></div>
          <div style="padding:18px 22px;font-size:.84rem;color:var(--tm);line-height:1.85">
            <div class="step"><div class="stepn">1</div><div><strong style="color:var(--th)">Shop online</strong> — use your BlueRoute address at checkout on any merchant.</div></div>
            <div class="step"><div class="stepn">2</div><div><strong style="color:var(--th)">We receive it</strong> — packages arrive at our Newmarket facility, logged to your locker.</div></div>
            <div class="step"><div class="stepn">3</div><div><strong style="color:var(--th)">We forward it</strong> — contact us to arrange onward shipping anywhere in the world.</div></div>
          </div>
        </div>
      </div>
    </div>

    <!-- PROFILE -->
    <div class="panel" id="panel-profile">
      <div class="ph"><div><h1>My Profile</h1><div class="sub">Your account details</div></div></div>
      <div style="max-width:520px">
        <div class="card">
          <div class="prof-h">
            <div class="prof-av"><?= $initials ?></div>
            <div>
              <div class="prof-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
              <div class="prof-un">@<?= htmlspecialchars($user['username']) ?></div>
              <div style="margin-top:6px"><span class="badge s-delivered" style="font-size:.68rem"><i class="fa-solid fa-circle" style="font-size:.4rem"></i> <?= ucfirst($user['status']) ?></span></div>
            </div>
          </div>
          <?php foreach (['Email' => $user['email'], 'Phone' => $user['phone'] ?? '—', 'Country' => $user['country'] ?? '—', 'State' => $user['state'] ?? '—', 'City' => $user['city'] ?? '—', 'Zip Code' => $user['zip_code'] ?? '—', 'Address' => $user['address'] ?? '—', 'Member since' => date('F Y', strtotime($user['created_at']))] as $lbl => $val): ?>
          <div class="prof-row"><span class="prl"><?= $lbl ?></span><span class="prv"><?= htmlspecialchars($val) ?></span></div>
          <?php endforeach; ?>
          <div style="padding:14px 22px;display:flex;gap:10px;border-top:1px solid var(--border)">
            <a href="signup.php" class="btn b-out b-sm"><i class="fa-solid fa-pen"></i> Edit Profile</a>
            <a href="logout.php" class="btn b-sm" style="background:#fff0f0;color:var(--c-cancel);border:1px solid #fecaca"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out</a>
          </div>
        </div>
      </div>
    </div>

  </div>
  <footer class="footer">© 2026 BlueRoute Security &amp; Shipping — All rights reserved</footer>
</div>
</div>
<div class="tst-wrap" id="toasts"></div>

<script>
const panelLabels={dashboard:'Dashboard',shipments:'My Shipments',tracking:'Track Package',address:'My Address',profile:'Profile'};

function sp(id){
  document.querySelectorAll('.panel').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.sb-item').forEach(b=>b.classList.remove('active'));
  document.getElementById('panel-'+id)?.classList.add('active');
  document.querySelector('[data-panel="'+id+'"]')?.classList.add('active');
  document.getElementById('tplbl').textContent=panelLabels[id]||id;
  closeSb();
  window.scrollTo({top:0,behavior:'smooth'});
}

document.querySelectorAll('[data-panel]').forEach(b=>b.addEventListener('click',()=>sp(b.dataset.panel)));

function closeSb(){document.getElementById('sidebar').classList.remove('open');document.getElementById('overlay').classList.remove('on')}
document.getElementById('hbg').addEventListener('click',()=>{document.getElementById('sidebar').classList.toggle('open');document.getElementById('overlay').classList.toggle('on')});
document.getElementById('overlay').addEventListener('click',closeSb);

function filterS(q){document.querySelectorAll('#stbl tbody tr').forEach(r=>r.style.display=r.textContent.toLowerCase().includes(q.toLowerCase())?'':'none')}

function copyAddr(){
  const t='13 Exning Rd, Newmarket, CB8 0JD, UK\n<?= $userId ?>\nNewmarket\nCB8 0JD\nUnited Kingdom';
  navigator.clipboard.writeText(t).then(()=>{
    const ok=document.getElementById('acopyok');
    if(ok){ok.style.display='block';setTimeout(()=>ok.style.display='none',2500)}
    toast('Address copied!','s');
  });
}

function qtrack(ref){sp('tracking');document.getElementById('tinput').value=ref;doTrack()}

async function doTrack(){
  const ref=document.getElementById('tinput').value.trim();
  const out=document.getElementById('tresult');
  if(!ref)return;
  out.innerHTML='<div style="padding:32px 22px;text-align:center;color:var(--ts);font-size:.84rem"><i class="fa-solid fa-spinner fa-spin" style="font-size:1.5rem;display:block;margin-bottom:10px"></i>Searching…</div>';
  try{
    const res=await fetch('api/track.php?ref='+encodeURIComponent(ref));
    const d=await res.json();
    if(!d.success){out.innerHTML='<div style="padding:20px 22px"><div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:14px 18px;color:#b91c1c;font-size:.84rem;display:flex;align-items:center;gap:9px"><i class="fa-solid fa-circle-exclamation"></i>'+(d.error||'Not found.')+'</div></div>';return}
    const s=d.data;
    const slbl={transit:'🚛 In Transit',delivered:'✅ Delivered',processing:'⚙️ Processing',pending:'🕐 Pending',cancelled:'❌ Cancelled'};
    const evts=s.events||[];
    const done=evts.filter(e=>e.is_done==1).length;
    const tot=evts.length;
    const pct=tot>0?Math.round((done/tot)*100):0;
    const tl=evts.map((ev,i)=>{
      const dc=ev.is_done==1?'done':ev.is_active==1?'active':'future';
      return`<div class="tli"><div class="tlidc"><div class="tlidot ${dc}"></div>${i<evts.length-1?'<div class="tliline"></div>':''}</div><div><div class="tlitl">${ev.event_title}</div><div class="tlim">${(ev.event_time||'').substring(0,16)}${ev.location?' · '+ev.location:''}</div></div></div>`;
    }).join('');
    out.innerHTML=`<div style="padding:20px 22px"><div class="tr-card">
      <div class="trh">
        <div><div class="tr-ref">${s.reference}</div><div class="tr-name">${s.client_name}</div></div>
        <div style="display:flex;align-items:center;gap:10px">
          <span style="background:rgba(255,255,255,.15);padding:5px 12px;border-radius:20px;font-size:.72rem;font-weight:700;color:#fff">${slbl[s.status]||s.status}</span>
          <a href="tracking.php?tracking_number=${s.reference}" target="_blank" style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);padding:5px 12px;border-radius:6px;color:#fff;font-size:.73rem;font-weight:600;text-decoration:none"><i class="fa-solid fa-arrow-up-right-from-square"></i> Full page</a>
        </div>
      </div>
      ${tot>0?`<div style="padding:12px 20px;background:#fafcff;border-bottom:1px solid var(--border)"><div style="display:flex;justify-content:space-between;font-size:.71rem;color:var(--ts);margin-bottom:5px"><span>Shipment progress</span><span>${pct}%</span></div><div style="height:6px;background:#e9eef4;border-radius:3px;overflow:hidden"><div style="height:100%;width:${pct}%;background:linear-gradient(90deg,var(--navy),var(--gold));border-radius:3px"></div></div></div>`:''}
      <div class="trgrid">
        <div class="trc"><div class="trcl">Origin</div><div class="trcv">${s.origin}</div></div>
        <div class="trc"><div class="trcl">Destination</div><div class="trcv">${s.destination}</div></div>
        <div class="trc"><div class="trcl">Current Location</div><div class="trcv">${s.current_location||'—'}</div></div>
        <div class="trc"><div class="trcl">Est. Delivery</div><div class="trcv">${s.eta||'—'}</div></div>
      </div>
      ${tl?`<div class="trtl"><div class="trtlt">Tracking History</div>${tl}</div>`:''}
    </div></div>`;
  }catch(e){out.innerHTML='<div style="padding:20px 22px"><div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:14px 18px;color:#b91c1c;font-size:.84rem"><i class="fa-solid fa-circle-exclamation"></i> Connection error.</div></div>'}
}

function toast(msg,type='i'){
  const icons={s:'circle-check',e:'circle-xmark',i:'circle-info'};
  const el=document.createElement('div');
  el.className='tst t'+type;
  el.innerHTML='<i class="fa-solid fa-'+icons[type]+'"></i> '+msg;
  document.getElementById('toasts').appendChild(el);
  setTimeout(()=>el.remove(),3800);
}
</script>
</body>
</html>