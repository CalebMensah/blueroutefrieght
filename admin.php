<?php
// ══════════════════════════════════════════
//  BlueRoute — Admin Dashboard
//  admin.php
// ══════════════════════════════════════════
if (session_status() === PHP_SESSION_NONE) session_start();

// Auth gate — redirect to login if not admin
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php?error=unauthorized');
    exit;
}

$admin = $_SESSION['user'];
$adminInitials = strtoupper(substr($admin['first_name'] ?? 'A', 0, 1) . substr($admin['last_name'] ?? 'D', 0, 1));
$adminName     = htmlspecialchars(($admin['first_name'] ?? 'Admin') . ' ' . ($admin['last_name'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard | BlueRoute</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <style>
    /* ═══════════════════════════════════════
       RESET & TOKENS
    ═══════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --navy:       #0a1628;
      --navy-mid:   #0f2040;
      --navy-soft:  #162a50;
      --blue:       #1e4fd8;
      --blue-light: #2d63f5;
      --gold:       #c9a84c;
      --gold-light: #e4c46e;
      --red:        #dc3545;
      --green:      #198754;
      --amber:      #e07b00;
      --surface:    #f4f6fb;
      --card:       #ffffff;
      --border:     #e2e8f3;
      --text:       #1a2540;
      --text-soft:  #5a6a8a;
      --text-muted: #8a9ab8;
      --sidebar-w:  260px;
      --topbar-h:   64px;
      --radius:     10px;
      --shadow:     0 2px 12px rgba(10,22,40,.08);
      --shadow-md:  0 4px 24px rgba(10,22,40,.12);
    }

    html, body { height: 100%; font-family: 'DM Sans', sans-serif; background: var(--surface); color: var(--text); font-size: 14px; line-height: 1.5; }

    /* ═══════════════════════════════════════
       LAYOUT
    ═══════════════════════════════════════ */
    .admin-wrap { display: flex; height: 100vh; overflow: hidden; }

    /* ═══════════════════════════════════════
       SIDEBAR
    ═══════════════════════════════════════ */
    .sidebar {
      width: var(--sidebar-w);
      background: var(--navy);
      display: flex; flex-direction: column;
      height: 100vh; flex-shrink: 0;
      transition: transform .3s ease;
      z-index: 100; overflow-y: auto; overflow-x: hidden;
    }
    .sidebar::-webkit-scrollbar { width: 4px; }
    .sidebar::-webkit-scrollbar-track { background: transparent; }
    .sidebar::-webkit-scrollbar-thumb { background: var(--navy-soft); border-radius: 4px; }

    .sidebar-logo {
      display: flex; align-items: center; gap: 12px;
      padding: 22px 20px 18px;
      text-decoration: none;
      border-bottom: 1px solid rgba(255,255,255,.07);
      flex-shrink: 0;
    }
    .s-logo-icon {
      width: 40px; height: 40px; background: var(--blue); border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Syne', sans-serif; font-weight: 800; font-size: .52rem;
      color: #fff; letter-spacing: .5px; line-height: 1.1; text-align: center;
    }
    .s-logo-text h2 { font-family: 'Syne', sans-serif; font-size: .95rem; font-weight: 800; color: #fff; letter-spacing: 1.5px; }
    .s-logo-text p  { font-size: .68rem; color: var(--gold); letter-spacing: .8px; text-transform: uppercase; }

    .sidebar-nav { flex: 1; padding: 14px 0; }

    .nav-section-label {
      font-size: .62rem; font-weight: 700; letter-spacing: 1.2px;
      text-transform: uppercase; color: rgba(255,255,255,.28);
      padding: 16px 20px 6px; font-family: 'Syne', sans-serif;
    }

    .s-nav-item {
      display: flex; align-items: center; gap: 11px;
      width: 100%; padding: 10px 20px;
      background: none; border: none; cursor: pointer; text-decoration: none;
      color: rgba(255,255,255,.62); font-size: .84rem; font-family: 'DM Sans', sans-serif;
      transition: all .18s; border-radius: 0; position: relative;
      text-align: left;
    }
    .s-nav-item:hover { color: #fff; background: rgba(255,255,255,.06); }
    .s-nav-item.active {
      color: #fff; background: rgba(30,79,216,.35);
      font-weight: 600;
    }
    .s-nav-item.active::before {
      content: ''; position: absolute; left: 0; top: 0; bottom: 0;
      width: 3px; background: var(--gold); border-radius: 0 3px 3px 0;
    }
    .s-nav-item i { width: 18px; text-align: center; font-size: .9rem; }

    .s-badge {
      margin-left: auto; background: var(--blue-light); color: #fff;
      font-size: .65rem; font-weight: 700; padding: 2px 7px; border-radius: 20px;
      font-family: 'Syne', sans-serif;
    }
    .s-badge.red { background: var(--red); }

    .nav-divider { height: 1px; background: rgba(255,255,255,.07); margin: 10px 20px; }

    /* Sidebar footer */
    .sidebar-footer {
      border-top: 1px solid rgba(255,255,255,.07);
      padding: 14px 16px; flex-shrink: 0;
    }
    .admin-profile { display: flex; align-items: center; gap: 10px; }
    .admin-avatar {
      width: 36px; height: 36px; border-radius: 50%;
      background: var(--blue); color: #fff;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Syne', sans-serif; font-size: .75rem; font-weight: 700; flex-shrink: 0;
    }
    .admin-info h4 { font-size: .8rem; font-weight: 600; color: #fff; }
    .admin-info p  { font-size: .68rem; color: rgba(255,255,255,.4); }

    .btn-logout {
      display: flex; align-items: center; gap: 9px;
      width: 100%; padding: 9px 14px; margin-top: 10px;
      background: rgba(220,53,69,.15); border: 1px solid rgba(220,53,69,.25);
      color: #ff7a87; font-size: .8rem; font-family: 'DM Sans', sans-serif;
      border-radius: 7px; cursor: pointer; transition: all .18s;
    }
    .btn-logout:hover { background: rgba(220,53,69,.28); color: #fff; }
    .btn-logout i { font-size: .85rem; }

    /* ═══════════════════════════════════════
       TOPBAR
    ═══════════════════════════════════════ */
    .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }

    .topbar {
      height: var(--topbar-h); background: var(--card);
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 24px; flex-shrink: 0; gap: 16px;
    }
    .topbar-left { display: flex; align-items: center; gap: 14px; }
    .topbar-right { display: flex; align-items: center; gap: 10px; }

    .sidebar-toggle {
      background: none; border: 1px solid var(--border); border-radius: 7px;
      width: 36px; height: 36px; display: none; align-items: center; justify-content: center;
      cursor: pointer; color: var(--text-soft); transition: all .18s;
    }
    .sidebar-toggle:hover { background: var(--surface); color: var(--text); }

    .topbar-title-block h3 { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700; color: var(--text); }
    .breadcrumb { display: flex; align-items: center; gap: 6px; font-size: .72rem; color: var(--text-muted); }
    .breadcrumb i { font-size: .55rem; }

    .topbar-btn {
      background: none; border: 1px solid var(--border); border-radius: 7px;
      width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
      cursor: pointer; color: var(--text-soft); transition: all .18s;
    }
    .topbar-btn:hover { background: var(--surface); color: var(--blue); }

    .topbar-avatar {
      width: 36px; height: 36px; border-radius: 50%;
      background: var(--blue); color: #fff;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Syne', sans-serif; font-size: .72rem; font-weight: 700;
    }

    /* ═══════════════════════════════════════
       PAGE BODY / PANELS
    ═══════════════════════════════════════ */
    .page-body { flex: 1; overflow-y: auto; padding: 24px; }
    .page-body::-webkit-scrollbar { width: 6px; }
    .page-body::-webkit-scrollbar-track { background: transparent; }
    .page-body::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

    .panel { display: none; }
    .panel.active { display: block; animation: fadeIn .22s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

    /* ═══════════════════════════════════════
       STATS ROW
    ═══════════════════════════════════════ */
    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }

    .stat-card {
      background: var(--card); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 18px 20px;
      display: flex; align-items: center; gap: 16px;
      transition: box-shadow .18s;
    }
    .stat-card:hover { box-shadow: var(--shadow-md); }
    .stat-icon {
      width: 46px; height: 46px; border-radius: 10px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem;
    }
    .stat-icon.navy  { background: rgba(10,22,40,.08);  color: var(--navy); }
    .stat-icon.blue  { background: rgba(30,79,216,.1);  color: var(--blue); }
    .stat-icon.green { background: rgba(25,135,84,.1);  color: var(--green); }
    .stat-icon.gold  { background: rgba(201,168,76,.1); color: var(--gold); }
    .stat-icon.red   { background: rgba(220,53,69,.1);  color: var(--red); }
    .stat-icon.amber { background: rgba(224,123,0,.1);  color: var(--amber); }

    .stat-val   { font-family: 'Syne', sans-serif; font-size: 1.6rem; font-weight: 800; color: var(--text); line-height: 1; }
    .stat-label { font-size: .72rem; color: var(--text-muted); margin-top: 4px; font-weight: 500; }

    /* ═══════════════════════════════════════
       CARDS
    ═══════════════════════════════════════ */
    .card {
      background: var(--card); border: 1px solid var(--border);
      border-radius: var(--radius); margin-bottom: 20px;
      overflow: hidden;
    }
    .card-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 16px 20px; border-bottom: 1px solid var(--border);
      gap: 12px; flex-wrap: wrap;
    }
    .card-header h3 { font-family: 'Syne', sans-serif; font-size: .88rem; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 8px; }
    .card-header h3 i { color: var(--blue); }
    .card-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

    /* ═══════════════════════════════════════
       TABLE
    ═══════════════════════════════════════ */
    .table-wrap { overflow-x: auto; }
    .data-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
    .data-table thead tr { background: var(--surface); }
    .data-table th {
      padding: 11px 16px; text-align: left;
      font-family: 'Syne', sans-serif; font-size: .65rem; font-weight: 700;
      text-transform: uppercase; letter-spacing: .6px; color: var(--text-muted);
      white-space: nowrap;
    }
    .data-table td { padding: 12px 16px; border-top: 1px solid var(--border); vertical-align: middle; }
    .data-table tbody tr:hover { background: rgba(30,79,216,.025); }
    .td-ref { font-family: 'Syne', sans-serif; font-size: .75rem; font-weight: 700; color: var(--blue); }
    .td-mono { font-family: 'Syne', sans-serif; font-size: .72rem; color: var(--text); background: var(--surface); padding: 3px 8px; border-radius: 5px; display: inline-block; }

    /* ═══════════════════════════════════════
       BADGES
    ═══════════════════════════════════════ */
    .badge {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 4px 10px; border-radius: 20px;
      font-size: .67rem; font-weight: 600; font-family: 'Syne', sans-serif;
      letter-spacing: .3px; white-space: nowrap;
    }
    .badge-transit    { background: rgba(30,79,216,.1);  color: var(--blue); }
    .badge-delivered  { background: rgba(25,135,84,.1);  color: var(--green); }
    .badge-processing { background: rgba(224,123,0,.1);  color: var(--amber); }
    .badge-pending    { background: rgba(90,106,138,.1); color: var(--text-soft); }
    .badge-cancelled  { background: rgba(220,53,69,.1);  color: var(--red); }
    .badge-verified   { background: rgba(25,135,84,.1);  color: var(--green); }
    .badge-review     { background: rgba(201,168,76,.1); color: #8a6c00; }
    .badge-admin      { background: rgba(30,79,216,.12); color: var(--blue); }
    .badge-client     { background: rgba(90,106,138,.1); color: var(--text-soft); }

    /* ═══════════════════════════════════════
       ROW ACTIONS
    ═══════════════════════════════════════ */
    .row-actions { display: flex; gap: 6px; }
    .act-btn {
      width: 30px; height: 30px; border-radius: 6px; border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; background: var(--card); font-size: .75rem;
      transition: all .15s; color: var(--text-soft);
    }
    .act-btn:hover       { border-color: var(--blue);  color: var(--blue);  background: rgba(30,79,216,.06); }
    .act-btn.del:hover   { border-color: var(--red);   color: var(--red);   background: rgba(220,53,69,.06); }
    .act-btn.view:hover  { border-color: var(--green); color: var(--green); background: rgba(25,135,84,.06); }

    /* ═══════════════════════════════════════
       SEARCH & BUTTONS
    ═══════════════════════════════════════ */
    .search-box {
      display: flex; align-items: center; gap: 8px;
      background: var(--surface); border: 1px solid var(--border);
      border-radius: 7px; padding: 0 12px; height: 34px;
    }
    .search-box i { color: var(--text-muted); font-size: .78rem; }
    .search-box input { border: none; background: none; outline: none; font-size: .8rem; color: var(--text); width: 160px; font-family: 'DM Sans', sans-serif; }

    .btn-add {
      display: flex; align-items: center; gap: 7px;
      padding: 0 14px; height: 34px; border-radius: 7px;
      background: var(--blue); color: #fff; border: none; cursor: pointer;
      font-size: .78rem; font-weight: 600; font-family: 'DM Sans', sans-serif;
      transition: all .18s;
    }
    .btn-add:hover { background: var(--blue-light); }

    .btn-export {
      display: flex; align-items: center; gap: 7px;
      padding: 0 12px; height: 34px; border-radius: 7px;
      background: none; color: var(--text-soft); border: 1px solid var(--border);
      cursor: pointer; font-size: .78rem; font-family: 'DM Sans', sans-serif;
      transition: all .18s;
    }
    .btn-export:hover { background: var(--surface); color: var(--text); }

    /* ═══════════════════════════════════════
       ACTIVITY LIST
    ═══════════════════════════════════════ */
    .activity-list { padding: 8px 0; }
    .activity-item { display: flex; gap: 14px; align-items: flex-start; padding: 12px 20px; border-bottom: 1px solid var(--border); }
    .activity-item:last-child { border-bottom: none; }
    .activity-icon {
      width: 34px; height: 34px; border-radius: 8px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center; font-size: .78rem;
    }
    .activity-icon.ship  { background: rgba(30,79,216,.1);  color: var(--blue); }
    .activity-icon.vault { background: rgba(201,168,76,.1); color: var(--gold); }
    .activity-icon.user  { background: rgba(25,135,84,.1);  color: var(--green); }
    .activity-icon.del   { background: rgba(220,53,69,.1);  color: var(--red); }
    .activity-text p { font-size: .8rem; color: var(--text); }
    .activity-text p strong { color: var(--navy); }
    .activity-time { font-size: .7rem; color: var(--text-muted); margin-top: 3px; display: flex; align-items: center; gap: 5px; }

    /* ═══════════════════════════════════════
       MODALS
    ═══════════════════════════════════════ */
    .modal-overlay {
      position: fixed; inset: 0; background: rgba(10,22,40,.55);
      display: none; align-items: center; justify-content: center;
      z-index: 1000; padding: 20px; backdrop-filter: blur(3px);
    }
    .modal-overlay.open { display: flex; }

    .modal {
      background: var(--card); border-radius: 14px;
      width: 100%; max-width: 680px; max-height: 90vh;
      overflow-y: auto; box-shadow: 0 20px 60px rgba(10,22,40,.25);
      animation: modalIn .22s ease;
    }
    @keyframes modalIn { from { opacity: 0; transform: scale(.96) translateY(10px); } to { opacity: 1; transform: none; } }

    .modal-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 18px 22px; border-bottom: 1px solid var(--border); position: sticky; top: 0; background: var(--card); z-index: 1;
    }
    .modal-header h3 { font-family: 'Syne', sans-serif; font-size: .95rem; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 9px; }
    .modal-header h3 i { color: var(--blue); }
    .modal-close { background: none; border: 1px solid var(--border); border-radius: 6px; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-soft); transition: all .15s; }
    .modal-close:hover { background: var(--surface); color: var(--text); }

    .modal-body { padding: 22px; }

    .modal-footer {
      display: flex; align-items: center; justify-content: flex-end; gap: 10px;
      padding: 16px 22px; border-top: 1px solid var(--border); background: var(--surface);
    }

    /* ═══════════════════════════════════════
       FORMS
    ═══════════════════════════════════════ */
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
    .form-row.full { grid-template-columns: 1fr; }
    .form-row.three { grid-template-columns: 1fr 1fr 1fr; }

    .form-group { display: flex; flex-direction: column; gap: 5px; }
    .form-group label { font-size: .71rem; font-weight: 700; color: var(--text); text-transform: uppercase; letter-spacing: .4px; font-family: 'Syne', sans-serif; }

    .form-group input,
    .form-group select,
    .form-group textarea {
      padding: 9px 12px; border: 1px solid var(--border); border-radius: 7px;
      font-size: .82rem; font-family: 'DM Sans', sans-serif; color: var(--text);
      background: var(--surface); transition: border-color .15s;
      outline: none;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus { border-color: var(--blue); background: #fff; }
    .form-group input:disabled { opacity: .55; cursor: not-allowed; }
    .form-group textarea { resize: vertical; min-height: 70px; }

    .info-banner {
      background: rgba(30,79,216,.06); border: 1px solid rgba(30,79,216,.15);
      border-radius: 8px; padding: 12px 14px; margin-bottom: 18px;
      font-size: .8rem; color: var(--navy); display: flex; align-items: center; gap: 10px;
    }
    .info-banner i { color: var(--gold); font-size: .9rem; flex-shrink: 0; }

    /* Tracking number display */
    .tn-display {
      background: var(--navy); border-radius: 8px; padding: 14px 18px;
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 18px;
    }
    .tn-display .label { font-size: .68rem; color: rgba(255,255,255,.5); text-transform: uppercase; letter-spacing: .8px; font-family: 'Syne', sans-serif; }
    .tn-display .value { font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 800; color: var(--gold-light); letter-spacing: 1px; margin-top: 2px; }

    /* Timeline editor */
    .timeline-editor { display: flex; flex-direction: column; gap: 8px; margin-bottom: 10px; }
    .tl-row {
      display: flex; gap: 8px; align-items: center;
      background: var(--surface); border: 1px solid var(--border);
      border-radius: 8px; padding: 10px 12px; flex-wrap: wrap;
    }
    .tl-row input[type=text], .tl-row input[type=datetime-local] {
      border: 1px solid var(--border); border-radius: 6px; padding: 6px 9px;
      font-size: .76rem; font-family: 'DM Sans', sans-serif; background: #fff;
      color: var(--text); outline: none; min-width: 0;
    }
    .tl-row input[type=text]:focus, .tl-row input[type=datetime-local]:focus { border-color: var(--blue); }
    .tl-remove { background: none; border: 1px solid rgba(220,53,69,.3); border-radius: 5px; width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--red); font-size: .72rem; flex-shrink: 0; transition: all .15s; }
    .tl-remove:hover { background: rgba(220,53,69,.08); }

    .btn-add-step {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 6px 12px; background: none; border: 1px dashed var(--border);
      border-radius: 7px; cursor: pointer; color: var(--text-muted); font-size: .76rem;
      font-family: 'DM Sans', sans-serif; transition: all .15s;
    }
    .btn-add-step:hover { border-color: var(--blue); color: var(--blue); background: rgba(30,79,216,.04); }

    /* Modal footer buttons */
    .btn-cancel { background: none; border: 1px solid var(--border); color: var(--text-soft); padding: 0 16px; height: 36px; border-radius: 7px; cursor: pointer; font-size: .8rem; font-family: 'DM Sans', sans-serif; transition: all .15s; }
    .btn-cancel:hover { background: var(--surface); color: var(--text); }
    .btn-save { background: var(--blue); color: #fff; border: none; padding: 0 18px; height: 36px; border-radius: 7px; cursor: pointer; font-size: .8rem; font-weight: 600; font-family: 'DM Sans', sans-serif; display: flex; align-items: center; gap: 7px; transition: all .15s; }
    .btn-save:hover { background: var(--blue-light); }
    .btn-save:disabled { opacity: .6; cursor: not-allowed; }
    .btn-danger { background: var(--red); color: #fff; border: none; padding: 0 18px; height: 36px; border-radius: 7px; cursor: pointer; font-size: .8rem; font-weight: 600; font-family: 'DM Sans', sans-serif; transition: all .15s; }
    .btn-danger:hover { background: #c0392b; }

    /* Delete confirm */
    .delete-confirm { text-align: center; padding: 28px 22px !important; }
    .delete-confirm .del-icon { font-size: 2.5rem; color: var(--red); margin-bottom: 14px; }
    .delete-confirm p { font-size: .88rem; color: var(--text-soft); margin-bottom: 8px; }
    .delete-confirm p strong { color: var(--text); }

    /* ═══════════════════════════════════════
       TOASTS
    ═══════════════════════════════════════ */
    .toast-container { position: fixed; bottom: 24px; right: 24px; display: flex; flex-direction: column; gap: 8px; z-index: 9999; }
    .toast {
      display: flex; align-items: center; gap: 10px;
      padding: 12px 16px; border-radius: 9px; font-size: .8rem; font-weight: 500;
      box-shadow: var(--shadow-md); animation: toastIn .22s ease;
      max-width: 320px; font-family: 'DM Sans', sans-serif;
    }
    @keyframes toastIn { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: none; } }
    .toast.success { background: #fff; border-left: 4px solid var(--green); color: var(--text); }
    .toast.error   { background: #fff; border-left: 4px solid var(--red);   color: var(--text); }
    .toast.info    { background: #fff; border-left: 4px solid var(--blue);  color: var(--text); }
    .toast i { font-size: .9rem; }
    .toast.success i { color: var(--green); }
    .toast.error   i { color: var(--red); }
    .toast.info    i { color: var(--blue); }

    /* ═══════════════════════════════════════
       USERS TABLE EXTRAS
    ═══════════════════════════════════════ */
    .user-cell { display: flex; align-items: center; gap: 10px; }
    .user-mini-avatar {
      width: 30px; height: 30px; border-radius: 50%; background: var(--blue);
      color: #fff; display: flex; align-items: center; justify-content: center;
      font-family: 'Syne', sans-serif; font-size: .65rem; font-weight: 700; flex-shrink: 0;
    }
    .user-mini-name { font-size: .8rem; font-weight: 500; color: var(--text); }
    .user-mini-email { font-size: .7rem; color: var(--text-muted); }

    /* ═══════════════════════════════════════
       EMPTY STATE
    ═══════════════════════════════════════ */
    .empty-state { text-align: center; padding: 48px 24px; color: var(--text-muted); }
    .empty-state i { font-size: 2rem; margin-bottom: 12px; display: block; opacity: .4; }
    .empty-state p { font-size: .82rem; }

    /* ═══════════════════════════════════════
       RESPONSIVE
    ═══════════════════════════════════════ */
    @media (max-width: 768px) {
      .sidebar { position: fixed; left: 0; top: 0; bottom: 0; transform: translateX(-100%); }
      .sidebar.open { transform: none; box-shadow: 4px 0 20px rgba(0,0,0,.3); }
      .sidebar-toggle { display: flex; }
      .main-content { width: 100%; }
      .stats-row { grid-template-columns: 1fr 1fr; }
      .form-row { grid-template-columns: 1fr; }
      .form-row.three { grid-template-columns: 1fr; }
      .page-body { padding: 16px; }
    }
  </style>
</head>
<body>
<div class="admin-wrap">

<!-- ══════════════ SIDEBAR ══════════════ -->
<aside class="sidebar" id="sidebar">
  <a href="index.html" class="sidebar-logo">
    <div class="s-logo-icon">BLUE<br>ROUTE</div>
    <div class="s-logo-text"><h2>BLUEROUTE</h2><p>Admin Portal</p></div>
  </a>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Overview</div>
    <button class="s-nav-item active" data-panel="dashboard"><i class="fa-solid fa-gauge-high"></i> Dashboard</button>

    <div class="nav-section-label">Shipping</div>
    <button class="s-nav-item" data-panel="shipments"><i class="fa-solid fa-truck-fast"></i> Shipments <span class="s-badge" id="sb-shipments">0</span></button>
    <button class="s-nav-item" data-panel="tracking"><i class="fa-solid fa-map-location-dot"></i> Tracking Updates</button>

    <div class="nav-section-label">Storage</div>
    <button class="s-nav-item" data-panel="vaults"><i class="fa-solid fa-vault"></i> Vault Records</button>

    <div class="nav-section-label">Users</div>
    <button class="s-nav-item" data-panel="users"><i class="fa-solid fa-users"></i> All Users <span class="s-badge" id="sb-users">0</span></button>

    <div class="nav-section-label">Reports</div>
    <button class="s-nav-item" data-panel="reports"><i class="fa-solid fa-chart-bar"></i> Reports & Stats</button>

    <div class="nav-section-label">System</div>
    <button class="s-nav-item" data-panel="activity"><i class="fa-solid fa-clock-rotate-left"></i> Activity Log</button>
    <button class="s-nav-item" data-panel="settings"><i class="fa-solid fa-gear"></i> Settings</button>

    <div class="nav-divider"></div>
    <a href="tracking.php" class="s-nav-item" target="_blank"><i class="fa-solid fa-magnifying-glass"></i> Track & Trace</a>
    <a href="index.html" class="s-nav-item"><i class="fa-solid fa-arrow-left"></i> Back to Site</a>
  </nav>

  <div class="sidebar-footer">
    <div class="admin-profile">
      <div class="admin-avatar"><?= $adminInitials ?></div>
      <div class="admin-info">
        <h4><?= $adminName ?></h4>
        <p>Administrator</p>
      </div>
    </div>
    <form method="POST" action="logout.php">
      <button type="submit" class="btn-logout">
        <i class="fa-solid fa-right-from-bracket"></i> Sign Out
      </button>
    </form>
  </div>
</aside>

<!-- ══════════════ MAIN ══════════════ -->
<div class="main-content">
  <div class="topbar">
    <div class="topbar-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
      <div class="topbar-title-block">
        <h3 id="topbar-title">Dashboard</h3>
        <div class="breadcrumb"><span>BlueRoute Admin</span><i class="fa-solid fa-chevron-right"></i><span id="topbar-crumb">Overview</span></div>
      </div>
    </div>
    <div class="topbar-right">
      <button class="topbar-btn" id="refreshBtn" title="Refresh data"><i class="fa-solid fa-rotate-right"></i></button>
      <div class="topbar-avatar"><?= $adminInitials ?></div>
    </div>
  </div>

  <div class="page-body">

    <!-- ══ DASHBOARD ══ -->
    <div class="panel active" id="panel-dashboard">
      <div class="stats-row">
        <div class="stat-card"><div class="stat-icon blue"><i class="fa-solid fa-truck-fast"></i></div><div class="stat-info"><div class="stat-val" id="stat-active">—</div><div class="stat-label">In Transit</div></div></div>
        <div class="stat-card"><div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div><div class="stat-info"><div class="stat-val" id="stat-delivered">—</div><div class="stat-label">Delivered</div></div></div>
        <div class="stat-card"><div class="stat-icon gold"><i class="fa-solid fa-vault"></i></div><div class="stat-info"><div class="stat-val" id="stat-vaults">—</div><div class="stat-label">Vault Records</div></div></div>
        <div class="stat-card"><div class="stat-icon amber"><i class="fa-solid fa-clock"></i></div><div class="stat-info"><div class="stat-val" id="stat-pending">—</div><div class="stat-label">Pending</div></div></div>
        <div class="stat-card"><div class="stat-icon navy"><i class="fa-solid fa-users"></i></div><div class="stat-info"><div class="stat-val" id="stat-users">—</div><div class="stat-label">Total Users</div></div></div>
        <div class="stat-card"><div class="stat-icon red"><i class="fa-solid fa-ban"></i></div><div class="stat-info"><div class="stat-val" id="stat-cancelled">—</div><div class="stat-label">Cancelled</div></div></div>
      </div>
      <div class="card">
        <div class="card-header">
          <h3><i class="fa-solid fa-truck-fast"></i> Recent Shipments</h3>
          <button class="btn-add" onclick="switchPanel('shipments')"><i class="fa-solid fa-arrow-right"></i> View All</button>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Tracking #</th><th>Client</th><th>Route</th><th>Status</th><th>ETA</th><th>Actions</th></tr></thead>
            <tbody id="dash-recent-body"><tr><td colspan="6" class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i> Loading…</td></tr></tbody>
          </table>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><h3><i class="fa-solid fa-clock-rotate-left"></i> Recent Activity</h3></div>
        <div class="activity-list" id="dash-activity"><div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i> Loading…</div></div>
      </div>
    </div>

    <!-- ══ SHIPMENTS ══ -->
    <div class="panel" id="panel-shipments">
      <div class="card">
        <div class="card-header">
          <h3><i class="fa-solid fa-truck-fast"></i> All Shipments</h3>
          <div class="card-actions">
            <div class="search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="text" id="ship-search" placeholder="Search…" oninput="filterTable('shipments-body','ship-search')" /></div>
            <button class="btn-export" onclick="exportCSV()"><i class="fa-solid fa-file-csv"></i> Export</button>
            <button class="btn-add" onclick="openShipmentModal()"><i class="fa-solid fa-plus"></i> New Shipment</button>
          </div>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Tracking #</th><th>Client</th><th>Origin</th><th>Destination</th><th>Service</th><th>Status</th><th>ETA</th><th>Actions</th></tr></thead>
            <tbody id="shipments-body"><tr><td colspan="8" class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i> Loading…</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══ TRACKING ══ -->
    <div class="panel" id="panel-tracking">
      <div class="card">
        <div class="card-header">
          <h3><i class="fa-solid fa-map-location-dot"></i> Tracking Updates</h3>
          <div class="card-actions">
            <div class="search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="text" id="track-search" placeholder="Search…" oninput="filterTable('tracking-body','track-search')" /></div>
          </div>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Tracking #</th><th>Client</th><th>Current Location</th><th>Status</th><th>ETA</th><th>Last Updated</th><th>Actions</th></tr></thead>
            <tbody id="tracking-body"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══ VAULTS ══ -->
    <div class="panel" id="panel-vaults">
      <div class="card">
        <div class="card-header">
          <h3><i class="fa-solid fa-vault"></i> Vault Records</h3>
          <div class="card-actions">
            <div class="search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="text" id="vault-search" placeholder="Search…" oninput="filterTable('vaults-body','vault-search')" /></div>
            <button class="btn-add" onclick="openVaultModal()"><i class="fa-solid fa-plus"></i> Add Record</button>
          </div>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Vault Ref</th><th>Client</th><th>Facility</th><th>Contents</th><th>Status</th><th>Last Audit</th><th>Next Audit</th><th>Actions</th></tr></thead>
            <tbody id="vaults-body"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══ USERS ══ -->
    <div class="panel" id="panel-users">
      <div class="card">
        <div class="card-header">
          <h3><i class="fa-solid fa-users"></i> All Users</h3>
          <div class="card-actions">
            <div class="search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="text" id="users-search" placeholder="Search…" oninput="filterTable('users-body','users-search')" /></div>
          </div>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>User</th><th>Username</th><th>Phone</th><th>Country</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody id="users-body"><tr><td colspan="8" class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i> Loading…</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══ REPORTS ══ -->
    <div class="panel" id="panel-reports">
      <div class="stats-row">
        <div class="stat-card"><div class="stat-icon blue"><i class="fa-solid fa-boxes-stacked"></i></div><div class="stat-info"><div class="stat-val" id="rpt-total">—</div><div class="stat-label">Total Shipments</div></div></div>
        <div class="stat-card"><div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div><div class="stat-info"><div class="stat-val" id="rpt-delivered">—</div><div class="stat-label">Delivered</div></div></div>
        <div class="stat-card"><div class="stat-icon amber"><i class="fa-solid fa-truck-fast"></i></div><div class="stat-info"><div class="stat-val" id="rpt-transit">—</div><div class="stat-label">In Transit</div></div></div>
        <div class="stat-card"><div class="stat-icon red"><i class="fa-solid fa-ban"></i></div><div class="stat-info"><div class="stat-val" id="rpt-cancelled">—</div><div class="stat-label">Cancelled</div></div></div>
      </div>
      <div class="card">
        <div class="card-header"><h3><i class="fa-solid fa-chart-bar"></i> Status Breakdown</h3></div>
        <div style="padding:24px" id="rpt-breakdown"></div>
      </div>
    </div>

    <!-- ══ ACTIVITY ══ -->
    <div class="panel" id="panel-activity">
      <div class="card">
        <div class="card-header"><h3><i class="fa-solid fa-clock-rotate-left"></i> Full Activity Log</h3></div>
        <div class="activity-list" id="full-activity"></div>
      </div>
    </div>

    <!-- ══ SETTINGS ══ -->
    <div class="panel" id="panel-settings">
      <div class="card">
        <div class="card-header"><h3><i class="fa-solid fa-gear"></i> System Settings</h3></div>
        <div style="padding:32px;text-align:center;color:var(--text-muted)">
          <i class="fa-solid fa-screwdriver-wrench" style="font-size:2rem;margin-bottom:12px;display:block;opacity:.3"></i>
          <p style="font-size:.85rem">Settings panel coming soon.</p>
        </div>
      </div>
    </div>

  </div><!-- /page-body -->
</div><!-- /main-content -->
</div><!-- /admin-wrap -->

<!-- ══════════════ MODAL: SHIPMENT ══════════════ -->
<div class="modal-overlay" id="modal-shipment">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fa-solid fa-truck-fast"></i> <span id="modal-ship-title">New Shipment</span></h3>
      <button class="modal-close" onclick="closeModal('modal-shipment')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="ship-edit-id" />

      <!-- Shown only when editing: display current tracking number -->
      <div class="tn-display" id="tn-display-wrap" style="display:none">
        <div><div class="label">Tracking Number</div><div class="value" id="tn-display-val">—</div></div>
        <i class="fa-solid fa-barcode" style="color:rgba(255,255,255,.2);font-size:1.4rem"></i>
      </div>

      <div class="form-row">
        <div class="form-group"><label>Client Name *</label><input type="text" id="ship-client" placeholder="John Smith" /></div>
        <div class="form-group"><label>Client Email</label><input type="email" id="ship-email" placeholder="client@example.com" /></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Origin *</label><input type="text" id="ship-origin" placeholder="London, UK" /></div>
        <div class="form-group"><label>Destination *</label><input type="text" id="ship-dest" placeholder="Dubai, UAE" /></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Service Type</label>
          <select id="ship-service">
            <option>Secure Air Freight</option>
            <option>Armored Ground Transport</option>
            <option>Ocean Cargo Security</option>
          </select>
        </div>
        <div class="form-group"><label>Status</label>
          <select id="ship-status">
            <option value="processing">Processing</option>
            <option value="transit">In Transit</option>
            <option value="delivered">Delivered</option>
            <option value="pending">Pending</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Est. Delivery Date</label><input type="date" id="ship-eta" /></div>
        <div class="form-group"><label>Weight (kg)</label><input type="number" id="ship-weight" placeholder="0.00" step="0.01" /></div>
      </div>
      <div class="form-row full">
        <div class="form-group"><label>Current Location</label><input type="text" id="ship-location" placeholder="Frankfurt Hub, Germany" /></div>
      </div>
      <div class="form-row full">
        <div class="form-group"><label>Description</label><input type="text" id="ship-desc" placeholder="e.g. Gold bullion bars" /></div>
      </div>
      <div class="form-row full">
        <div class="form-group"><label>Internal Notes</label><textarea id="ship-notes" rows="2" placeholder="Optional internal notes…"></textarea></div>
      </div>
      <div>
        <label style="font-size:.71rem;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:.4px;font-family:'Syne',sans-serif;display:block;margin-bottom:10px">Tracking Timeline</label>
        <div class="timeline-editor" id="ship-timeline"></div>
        <button class="btn-add-step" type="button" onclick="addTimelineStep('ship-timeline')"><i class="fa-solid fa-plus"></i> Add Step</button>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal('modal-shipment')">Cancel</button>
      <button class="btn-save" id="btn-save-shipment" onclick="saveShipment()"><i class="fa-solid fa-floppy-disk"></i> Save Shipment</button>
    </div>
  </div>
</div>

<!-- ══════════════ MODAL: TRACKING UPDATE ══════════════ -->
<div class="modal-overlay" id="modal-tracking">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fa-solid fa-map-location-dot"></i> Update Tracking</h3>
      <button class="modal-close" onclick="closeModal('modal-tracking')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="track-edit-id" />
      <div class="info-banner">
        <i class="fa-solid fa-circle-info"></i>
        <span>Tracking: <strong id="track-edit-ref">—</strong> &nbsp;·&nbsp; Client: <strong id="track-edit-client">—</strong></span>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Status</label>
          <select id="track-status">
            <option value="processing">Processing</option>
            <option value="transit">In Transit</option>
            <option value="delivered">Delivered</option>
            <option value="pending">Pending</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div class="form-group"><label>Current Location</label><input type="text" id="track-location" placeholder="Frankfurt Hub, Germany" /></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Est. Delivery Date</label><input type="date" id="track-eta" /></div>
        <div class="form-group"><label>Internal Notes</label><input type="text" id="track-notes" placeholder="Optional note…" /></div>
      </div>
      <div>
        <label style="font-size:.71rem;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:.4px;font-family:'Syne',sans-serif;display:block;margin-bottom:10px">Timeline Steps</label>
        <div class="timeline-editor" id="track-timeline"></div>
        <button class="btn-add-step" type="button" onclick="addTimelineStep('track-timeline')"><i class="fa-solid fa-plus"></i> Add Step</button>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal('modal-tracking')">Cancel</button>
      <button class="btn-save" id="btn-save-tracking" onclick="saveTracking()"><i class="fa-solid fa-floppy-disk"></i> Save Update</button>
    </div>
  </div>
</div>

<!-- ══════════════ MODAL: VAULT ══════════════ -->
<div class="modal-overlay" id="modal-vault">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fa-solid fa-vault"></i> <span id="modal-vault-title">Add Vault Record</span></h3>
      <button class="modal-close" onclick="closeModal('modal-vault')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="vault-edit-id" />
      <div class="form-row">
        <div class="form-group"><label>Vault Reference</label><input type="text" id="vault-ref" placeholder="VLT-UK-00123" /></div>
        <div class="form-group"><label>Client Name</label><input type="text" id="vault-client" placeholder="John Smith" /></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Facility</label>
          <select id="vault-facility">
            <option>United Kingdom (HQ) — Newmarket</option>
            <option>Dubai, UAE — DIFC</option>
            <option>Frankfurt, Germany</option>
            <option>Singapore — Raffles Place</option>
          </select>
        </div>
        <div class="form-group"><label>Status</label>
          <select id="vault-status">
            <option value="verified">Verified</option>
            <option value="review">Under Review</option>
            <option value="pending">Pending Audit</option>
          </select>
        </div>
      </div>
      <div class="form-row full"><div class="form-group"><label>Contents Description</label><input type="text" id="vault-contents" placeholder="e.g. Gold bars, jewelry…" /></div></div>
      <div class="form-row">
        <div class="form-group"><label>Last Audit Date</label><input type="date" id="vault-last-audit" /></div>
        <div class="form-group"><label>Next Audit Date</label><input type="date" id="vault-next-audit" /></div>
      </div>
      <div class="form-row full"><div class="form-group"><label>Notes</label><textarea id="vault-notes" rows="2"></textarea></div></div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal('modal-vault')">Cancel</button>
      <button class="btn-save" onclick="saveVault()"><i class="fa-solid fa-floppy-disk"></i> Save</button>
    </div>
  </div>
</div>

<!-- ══════════════ MODAL: DELETE CONFIRM ══════════════ -->
<div class="modal-overlay" id="modal-delete">
  <div class="modal" style="max-width:420px">
    <div class="modal-header">
      <h3><i class="fa-solid fa-triangle-exclamation" style="color:var(--red)"></i> Confirm Delete</h3>
      <button class="modal-close" onclick="closeModal('modal-delete')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body delete-confirm">
      <div class="del-icon"><i class="fa-solid fa-trash-can"></i></div>
      <p>You are about to permanently delete <strong id="delete-label">this record</strong>.</p>
      <p style="margin-top:6px">This action <strong>cannot be undone</strong>.</p>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal('modal-delete')">Cancel</button>
      <button class="btn-danger" id="btn-confirm-delete"><i class="fa-solid fa-trash"></i> Delete</button>
    </div>
  </div>
</div>

<div class="toast-container" id="toast-container"></div>

<script>
/* ═══════════════════════════════════════════════
   CONFIG
═══════════════════════════════════════════════ */
const API = {
  shipments: 'api/shipments.php',
  vaults:    'api/vaults.php',
  activity:  'api/activity.php',
  users:     'api/users.php',
};

const PANEL_NAMES = {
  dashboard: 'Dashboard',
  shipments: 'Shipments',
  tracking:  'Tracking Updates',
  vaults:    'Vault Records',
  users:     'All Users',
  reports:   'Reports & Stats',
  activity:  'Activity Log',
  settings:  'Settings',
};

/* ═══════════════════════════════════════════════
   PANEL SWITCHING
═══════════════════════════════════════════════ */
function switchPanel(id) {
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.s-nav-item[data-panel]').forEach(b => b.classList.remove('active'));
  document.getElementById('panel-' + id)?.classList.add('active');
  document.querySelector(`[data-panel="${id}"]`)?.classList.add('active');
  document.getElementById('topbar-title').textContent = PANEL_NAMES[id] || id;
  document.getElementById('topbar-crumb').textContent = PANEL_NAMES[id] || id;
  document.getElementById('sidebar').classList.remove('open');

  if (id === 'activity') loadActivity('full-activity', 50);
  if (id === 'tracking') renderTrackingPanel();
  if (id === 'users')    loadUsers();
  if (id === 'reports')  renderReports();
}

document.querySelectorAll('[data-panel]').forEach(btn =>
  btn.addEventListener('click', () => switchPanel(btn.dataset.panel))
);
document.getElementById('sidebarToggle').addEventListener('click', () =>
  document.getElementById('sidebar').classList.toggle('open')
);
document.getElementById('refreshBtn').addEventListener('click', () => {
  loadAllData(); showToast('Data refreshed', 'success');
});

/* ═══════════════════════════════════════════════
   API HELPERS
═══════════════════════════════════════════════ */
async function apiFetch(url, options = {}) {
  try {
    const res = await fetch(url, { headers: { 'Content-Type': 'application/json' }, ...options });
    return res.json();
  } catch (e) {
    showToast('Network error', 'error');
    return { success: false, error: 'Network error' };
  }
}

/* ═══════════════════════════════════════════════
   DATA CACHE
═══════════════════════════════════════════════ */
let allShipments = [];
let allVaults    = [];
let allUsers     = [];

async function loadAllData() {
  await Promise.all([loadShipments(), loadVaults(), loadActivity('dash-activity', 6)]);
}

/* ═══════════════════════════════════════════════
   SHIPMENTS
═══════════════════════════════════════════════ */
async function loadShipments() {
  const res = await apiFetch(API.shipments);
  if (!res.success) { showToast('Failed to load shipments', 'error'); return; }
  allShipments = res.data;
  renderShipmentsTable();
  updateDashStats();
}

function renderShipmentsTable() {
  const tbody = document.getElementById('shipments-body');
  if (!allShipments.length) {
    tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><i class="fa-solid fa-truck-fast"></i><p>No shipments yet.</p></div></td></tr>`;
    return;
  }
  tbody.innerHTML = allShipments.map(s => `
    <tr>
      <td><span class="td-mono">${s.tracking_number || '—'}</span></td>
      <td>${s.client_name}</td>
      <td>${s.origin}</td>
      <td>${s.destination}</td>
      <td>${s.service_type}</td>
      <td>${badge(s.status)}</td>
      <td>${s.eta ? formatDate(s.eta) : '—'}</td>
      <td>
        <div class="row-actions">
          <button class="act-btn edit" title="Edit shipment"   onclick="openShipmentModal(${s.id})"><i class="fa-solid fa-pen"></i></button>
          <button class="act-btn view" title="Update tracking" onclick="openTrackingModal(${s.id})"><i class="fa-solid fa-map-location-dot"></i></button>
          <button class="act-btn del"  title="Delete"          onclick="confirmDelete('shipment',${s.id},'${escHtml(s.tracking_number || s.id)}')"><i class="fa-solid fa-trash"></i></button>
        </div>
      </td>
    </tr>`).join('');

  // Dashboard recent (first 6)
  document.getElementById('dash-recent-body').innerHTML = allShipments.slice(0, 6).map(s => `
    <tr>
      <td><span class="td-mono">${s.tracking_number || '—'}</span></td>
      <td>${s.client_name}</td>
      <td style="font-size:.75rem;color:var(--text-soft)">${s.origin} → ${s.destination}</td>
      <td>${badge(s.status)}</td>
      <td>${s.eta ? formatDate(s.eta) : '—'}</td>
      <td>
        <div class="row-actions">
          <button class="act-btn view" title="Update tracking" onclick="openTrackingModal(${s.id})"><i class="fa-solid fa-map-location-dot"></i></button>
        </div>
      </td>
    </tr>`).join('');
}

function renderTrackingPanel() {
  const tbody = document.getElementById('tracking-body');
  if (!allShipments.length) {
    tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><i class="fa-solid fa-map-location-dot"></i><p>No shipments.</p></div></td></tr>`;
    return;
  }
  tbody.innerHTML = allShipments.map(s => `
    <tr>
      <td><span class="td-mono">${s.tracking_number || '—'}</span></td>
      <td>${s.client_name}</td>
      <td>${s.current_location || '—'}</td>
      <td>${badge(s.status)}</td>
      <td>${s.eta ? formatDate(s.eta) : '—'}</td>
      <td style="font-size:.75rem;color:var(--text-muted)">${s.updated_at ? s.updated_at.substring(0,16) : '—'}</td>
      <td>
        <div class="row-actions">
          <button class="act-btn edit" title="Update tracking" onclick="openTrackingModal(${s.id})">
            <i class="fa-solid fa-map-location-dot"></i>
          </button>
        </div>
      </td>
    </tr>`).join('');
}

function updateDashStats() {
  document.getElementById('stat-active').textContent    = allShipments.filter(s => s.status === 'transit').length;
  document.getElementById('stat-delivered').textContent = allShipments.filter(s => s.status === 'delivered').length;
  document.getElementById('stat-pending').textContent   = allShipments.filter(s => s.status === 'pending').length;
  document.getElementById('stat-cancelled').textContent = allShipments.filter(s => s.status === 'cancelled').length;
  document.getElementById('sb-shipments').textContent   = allShipments.filter(s => ['transit','processing'].includes(s.status)).length;
}

/* ── Open shipment modal (add or edit) ── */
async function openShipmentModal(id) {
  document.getElementById('modal-ship-title').textContent = id ? 'Edit Shipment' : 'New Shipment';
  document.getElementById('ship-timeline').innerHTML = '';
  document.getElementById('tn-display-wrap').style.display = 'none';

  if (id) {
    const res = await apiFetch(`${API.shipments}?id=${id}`);
    if (!res.success) { showToast('Failed to load shipment', 'error'); return; }
    const s = res.data;
    document.getElementById('ship-edit-id').value  = s.id;
    document.getElementById('ship-client').value   = s.client_name;
    document.getElementById('ship-email').value    = s.client_email  || '';
    document.getElementById('ship-origin').value   = s.origin;
    document.getElementById('ship-dest').value     = s.destination;
    document.getElementById('ship-service').value  = s.service_type;
    document.getElementById('ship-status').value   = s.status;
    document.getElementById('ship-eta').value      = s.eta || '';
    document.getElementById('ship-location').value = s.current_location || '';
    document.getElementById('ship-desc').value     = s.description || '';
    document.getElementById('ship-notes').value    = s.notes || '';
    document.getElementById('ship-weight').value   = s.weight_kg || '';
    // Show tracking number display
    if (s.tracking_number) {
      document.getElementById('tn-display-val').textContent = s.tracking_number;
      document.getElementById('tn-display-wrap').style.display = 'flex';
    }
    (s.events || []).forEach(ev => addTimelineStep('ship-timeline', ev));
  } else {
    document.getElementById('ship-edit-id').value = '';
    ['ship-client','ship-email','ship-origin','ship-dest','ship-location','ship-desc','ship-notes','ship-weight'].forEach(i => document.getElementById(i).value = '');
    document.getElementById('ship-status').value  = 'processing';
    document.getElementById('ship-service').value = 'Secure Air Freight';
    document.getElementById('ship-eta').value     = '';
  }
  openModal('modal-shipment');
}

async function saveShipment() {
  const editId = document.getElementById('ship-edit-id').value;
  const client = document.getElementById('ship-client').value.trim();
  const origin = document.getElementById('ship-origin').value.trim();
  const dest   = document.getElementById('ship-dest').value.trim();

  if (!client) { showToast('Client name is required', 'error'); return; }
  if (!origin)  { showToast('Origin is required', 'error'); return; }
  if (!dest)    { showToast('Destination is required', 'error'); return; }

  const payload = {
    client_name:      client,
    client_email:     document.getElementById('ship-email').value,
    origin:           origin,
    destination:      dest,
    service_type:     document.getElementById('ship-service').value,
    status:           document.getElementById('ship-status').value,
    current_location: document.getElementById('ship-location').value,
    eta:              document.getElementById('ship-eta').value,
    description:      document.getElementById('ship-desc').value,
    notes:            document.getElementById('ship-notes').value,
    weight_kg:        document.getElementById('ship-weight').value,
    events:           getTimeline('ship-timeline'),
  };

  const btn = document.getElementById('btn-save-shipment');
  btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';

  const res = editId
    ? await apiFetch(`${API.shipments}?id=${editId}`, { method: 'PUT',  body: JSON.stringify(payload) })
    : await apiFetch(API.shipments,                   { method: 'POST', body: JSON.stringify(payload) });

  btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Shipment';

  if (!res.success) { showToast(res.error || 'Save failed', 'error'); return; }

  if (!editId && res.tracking_number) {
    showToast(`Shipment created · Tracking: ${res.tracking_number}`, 'success');
  } else {
    showToast(editId ? 'Shipment updated ✓' : 'Shipment created ✓', 'success');
  }

  closeModal('modal-shipment');
  await loadShipments();
}

/* ── Tracking update modal ── */
async function openTrackingModal(id) {
  const res = await apiFetch(`${API.shipments}?id=${id}`);
  if (!res.success) { showToast('Failed to load shipment', 'error'); return; }
  const s = res.data;

  document.getElementById('track-edit-id').value           = s.id;
  document.getElementById('track-edit-ref').textContent    = s.tracking_number || s.id;
  document.getElementById('track-edit-client').textContent = s.client_name;
  document.getElementById('track-status').value            = s.status;
  document.getElementById('track-location').value          = s.current_location || '';
  document.getElementById('track-eta').value               = s.eta || '';
  document.getElementById('track-notes').value             = s.notes || '';
  document.getElementById('track-timeline').innerHTML      = '';
  (s.events || []).forEach(ev => addTimelineStep('track-timeline', ev));

  openModal('modal-tracking');
}

async function saveTracking() {
  const id = document.getElementById('track-edit-id').value;
  if (!id) return;

  const payload = {
    status:           document.getElementById('track-status').value,
    current_location: document.getElementById('track-location').value,
    eta:              document.getElementById('track-eta').value,
    notes:            document.getElementById('track-notes').value,
    events:           getTimeline('track-timeline'),
  };

  const btn = document.getElementById('btn-save-tracking');
  btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';

  const res = await apiFetch(`${API.shipments}?id=${id}`, { method: 'PUT', body: JSON.stringify(payload) });

  btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Update';

  if (!res.success) { showToast(res.error || 'Update failed', 'error'); return; }
  showToast('Tracking updated ✓', 'success');
  closeModal('modal-tracking');
  await loadShipments();
  if (document.getElementById('panel-tracking').classList.contains('active')) renderTrackingPanel();
}

/* ═══════════════════════════════════════════════
   VAULTS
═══════════════════════════════════════════════ */
async function loadVaults() {
  const res = await apiFetch(API.vaults);
  if (!res.success) return;
  allVaults = res.data;
  renderVaultsTable();
  document.getElementById('stat-vaults').textContent = allVaults.length;
}

function renderVaultsTable() {
  const tbody = document.getElementById('vaults-body');
  if (!allVaults.length) {
    tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><i class="fa-solid fa-vault"></i><p>No vault records.</p></div></td></tr>`;
    return;
  }
  tbody.innerHTML = allVaults.map(v => `
    <tr>
      <td class="td-ref">${v.reference}</td>
      <td>${v.client_name}</td>
      <td>${v.facility}</td>
      <td>${v.contents || '—'}</td>
      <td>${badge(v.status)}</td>
      <td>${v.last_audit || '—'}</td>
      <td>${v.next_audit || '—'}</td>
      <td>
        <div class="row-actions">
          <button class="act-btn edit" onclick="openVaultModal(${v.id})"><i class="fa-solid fa-pen"></i></button>
          <button class="act-btn del"  onclick="confirmDelete('vault',${v.id},'${escHtml(v.reference)}')"><i class="fa-solid fa-trash"></i></button>
        </div>
      </td>
    </tr>`).join('');
}

async function openVaultModal(id) {
  document.getElementById('modal-vault-title').textContent = id ? 'Edit Vault Record' : 'Add Vault Record';
  if (id) {
    const res = await apiFetch(`${API.vaults}?id=${id}`);
    if (!res.success) { showToast('Failed to load vault', 'error'); return; }
    const v = res.data;
    document.getElementById('vault-edit-id').value    = v.id;
    document.getElementById('vault-ref').value        = v.reference;
    document.getElementById('vault-ref').disabled     = true;
    document.getElementById('vault-client').value     = v.client_name;
    document.getElementById('vault-facility').value   = v.facility;
    document.getElementById('vault-contents').value   = v.contents || '';
    document.getElementById('vault-status').value     = v.status;
    document.getElementById('vault-last-audit').value = v.last_audit || '';
    document.getElementById('vault-next-audit').value = v.next_audit || '';
    document.getElementById('vault-notes').value      = v.notes || '';
  } else {
    document.getElementById('vault-edit-id').value = '';
    document.getElementById('vault-ref').disabled  = false;
    ['vault-ref','vault-client','vault-contents','vault-last-audit','vault-next-audit','vault-notes'].forEach(i => document.getElementById(i).value = '');
    document.getElementById('vault-status').value   = 'verified';
    document.getElementById('vault-facility').selectedIndex = 0;
  }
  openModal('modal-vault');
}

async function saveVault() {
  const editId = document.getElementById('vault-edit-id').value;
  const ref    = document.getElementById('vault-ref').value.trim();
  const client = document.getElementById('vault-client').value.trim();
  if (!ref || !client) { showToast('Reference and client are required', 'error'); return; }

  const payload = {
    reference:   ref,
    client_name: client,
    facility:    document.getElementById('vault-facility').value,
    contents:    document.getElementById('vault-contents').value,
    status:      document.getElementById('vault-status').value,
    last_audit:  document.getElementById('vault-last-audit').value,
    next_audit:  document.getElementById('vault-next-audit').value,
    notes:       document.getElementById('vault-notes').value,
  };

  const btn = document.querySelector('#modal-vault .btn-save');
  btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';

  const res = editId
    ? await apiFetch(`${API.vaults}?id=${editId}`, { method: 'PUT',  body: JSON.stringify(payload) })
    : await apiFetch(API.vaults,                   { method: 'POST', body: JSON.stringify(payload) });

  btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save';

  if (!res.success) { showToast(res.error || 'Save failed', 'error'); return; }
  showToast(editId ? 'Vault updated ✓' : 'Vault record created ✓', 'success');
  closeModal('modal-vault');
  await loadVaults();
}

/* ═══════════════════════════════════════════════
   USERS
═══════════════════════════════════════════════ */
async function loadUsers() {
  const res = await apiFetch(API.users);
  if (!res.success) { showToast('Failed to load users', 'error'); return; }
  allUsers = res.data;
  renderUsersTable();
  document.getElementById('stat-users').textContent    = allUsers.length;
  document.getElementById('sb-users').textContent      = allUsers.length;
}

function renderUsersTable() {
  const tbody = document.getElementById('users-body');
  if (!allUsers.length) {
    tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><i class="fa-solid fa-users"></i><p>No users found.</p></div></td></tr>`;
    return;
  }
  tbody.innerHTML = allUsers.map(u => {
    const initials = ((u.first_name?.[0] || '') + (u.last_name?.[0] || '')).toUpperCase() || 'U';
    return `<tr>
      <td>
        <div class="user-cell">
          <div class="user-mini-avatar">${initials}</div>
          <div>
            <div class="user-mini-name">${escHtml(u.first_name + ' ' + u.last_name)}</div>
            <div class="user-mini-email">${escHtml(u.email)}</div>
          </div>
        </div>
      </td>
      <td class="td-ref">@${escHtml(u.username)}</td>
      <td>${escHtml(u.phone || '—')}</td>
      <td>${escHtml(u.country || '—')}</td>
      <td>${badge(u.role || 'client')}</td>
      <td>${badge(u.status || 'active')}</td>
      <td style="font-size:.75rem;color:var(--text-muted)">${u.created_at ? u.created_at.substring(0,10) : '—'}</td>
      <td>
        <div class="row-actions">
          <button class="act-btn del" title="Delete user" onclick="confirmDelete('user',${u.id},'${escHtml(u.username)}')"><i class="fa-solid fa-trash"></i></button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

/* ═══════════════════════════════════════════════
   REPORTS
═══════════════════════════════════════════════ */
function renderReports() {
  const total     = allShipments.length;
  const delivered = allShipments.filter(s => s.status === 'delivered').length;
  const transit   = allShipments.filter(s => s.status === 'transit').length;
  const cancelled = allShipments.filter(s => s.status === 'cancelled').length;
  const processing= allShipments.filter(s => s.status === 'processing').length;
  const pending   = allShipments.filter(s => s.status === 'pending').length;

  document.getElementById('rpt-total').textContent     = total;
  document.getElementById('rpt-delivered').textContent = delivered;
  document.getElementById('rpt-transit').textContent   = transit;
  document.getElementById('rpt-cancelled').textContent = cancelled;

  const pct = v => total ? Math.round((v / total) * 100) : 0;
  const bar = (label, val, cls) => `
    <div style="margin-bottom:16px">
      <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:.78rem">
        <span style="font-weight:600;color:var(--text)">${label}</span>
        <span style="color:var(--text-muted)">${val} <span style="opacity:.6">(${pct(val)}%)</span></span>
      </div>
      <div style="background:var(--surface);border-radius:20px;height:8px;overflow:hidden">
        <div style="height:100%;width:${pct(val)}%;background:${cls};border-radius:20px;transition:width .6s ease"></div>
      </div>
    </div>`;

  document.getElementById('rpt-breakdown').innerHTML =
    bar('Delivered',  delivered,  'var(--green)') +
    bar('In Transit', transit,    'var(--blue)') +
    bar('Processing', processing, 'var(--amber)') +
    bar('Pending',    pending,    'var(--text-muted)') +
    bar('Cancelled',  cancelled,  'var(--red)');
}

/* ═══════════════════════════════════════════════
   ACTIVITY LOG
═══════════════════════════════════════════════ */
async function loadActivity(containerId, limit = 10) {
  const res = await apiFetch(`${API.activity}?limit=${limit}`);
  if (!res.success) return;
  const iconMap = { ship: 'ship fa-truck-fast', vault: 'vault fa-vault', user: 'user fa-user', del: 'del fa-trash' };
  document.getElementById(containerId).innerHTML = res.data.length
    ? res.data.map(a => {
        const [cls, icon] = (iconMap[a.type] || 'user fa-circle').split(' ');
        return `<div class="activity-item">
          <div class="activity-icon ${cls}"><i class="fa-solid fa-${icon}"></i></div>
          <div class="activity-text">
            <p>${a.message}</p>
            <div class="activity-time"><i class="fa-regular fa-clock"></i> ${a.created_at}</div>
          </div>
        </div>`;
      }).join('')
    : `<div class="empty-state"><i class="fa-solid fa-clock-rotate-left"></i><p>No activity yet.</p></div>`;
}

/* ═══════════════════════════════════════════════
   DELETE
═══════════════════════════════════════════════ */
function confirmDelete(type, id, label) {
  document.getElementById('delete-label').textContent = label;
  document.getElementById('btn-confirm-delete').onclick = async () => {
    let url;
    if (type === 'vault')    url = `${API.vaults}?id=${id}`;
    else if (type === 'user') url = `${API.users}?id=${id}`;
    else                     url = `${API.shipments}?id=${id}`;

    const res = await apiFetch(url, { method: 'DELETE' });
    if (!res.success) { showToast(res.error || 'Delete failed', 'error'); return; }
    showToast(`"${label}" deleted`, 'info');
    closeModal('modal-delete');
    if (type === 'vault')    await loadVaults();
    else if (type === 'user') await loadUsers();
    else                     await loadShipments();
  };
  openModal('modal-delete');
}

/* ═══════════════════════════════════════════════
   TIMELINE EDITOR
═══════════════════════════════════════════════ */
function addTimelineStep(containerId, step = {}) {
  const el  = document.getElementById(containerId);
  const row = document.createElement('div');
  row.className = 'tl-row';
  const eventVal = escAttr(step.event_title || step.event || '');
  const timeVal  = step.event_time ? step.event_time.substring(0, 16) : (step.time || '');
  const locVal   = escAttr(step.location || '');
  const isDone   = step.is_done   == 1 ? 'checked' : '';
  const isActive = step.is_active == 1 ? 'checked' : '';
  row.innerHTML = `
    <input type="text"          class="tl-event"  placeholder="Event description" value="${eventVal}" style="flex:2;min-width:120px" />
    <input type="datetime-local" class="tl-time"   value="${timeVal}"             style="flex:1.5;min-width:140px" />
    <input type="text"          class="tl-loc"    placeholder="Location"          value="${locVal}"   style="flex:1.5;min-width:100px" />
    <label style="display:flex;align-items:center;gap:4px;font-size:.73rem;color:var(--text-muted);white-space:nowrap;cursor:pointer">
      <input type="checkbox" class="tl-done"   ${isDone}   /> Done
    </label>
    <label style="display:flex;align-items:center;gap:4px;font-size:.73rem;color:var(--blue);white-space:nowrap;cursor:pointer">
      <input type="checkbox" class="tl-active" ${isActive} /> Active
    </label>
    <button class="tl-remove" onclick="this.closest('.tl-row').remove()" type="button"><i class="fa-solid fa-xmark"></i></button>`;
  el.appendChild(row);
}

function getTimeline(containerId) {
  return [...document.querySelectorAll(`#${containerId} .tl-row`)].map(r => ({
    event_title: r.querySelector('.tl-event')?.value  || '',
    event_time:  r.querySelector('.tl-time')?.value   || '',
    location:    r.querySelector('.tl-loc')?.value    || '',
    is_done:     r.querySelector('.tl-done')?.checked  ? 1 : 0,
    is_active:   r.querySelector('.tl-active')?.checked ? 1 : 0,
  })).filter(e => e.event_title.trim());
}

/* ═══════════════════════════════════════════════
   HELPERS
═══════════════════════════════════════════════ */
const STATUS_MAP = {
  transit:    ['badge-transit',    'fa-truck',              'In Transit'],
  delivered:  ['badge-delivered',  'fa-circle-check',       'Delivered'],
  processing: ['badge-processing', 'fa-gear',               'Processing'],
  pending:    ['badge-pending',    'fa-clock',              'Pending'],
  cancelled:  ['badge-cancelled',  'fa-xmark-circle',       'Cancelled'],
  verified:   ['badge-verified',   'fa-shield-halved',      'Verified'],
  review:     ['badge-review',     'fa-magnifying-glass',   'Under Review'],
  active:     ['badge-verified',   'fa-circle-check',       'Active'],
  admin:      ['badge-admin',      'fa-shield-halved',      'Admin'],
  client:     ['badge-client',     'fa-user',               'Client'],
};

function badge(status) {
  const [cls, ico, label] = STATUS_MAP[status] || ['badge-pending','fa-circle',status];
  return `<span class="badge ${cls}"><i class="fa-solid ${ico}"></i> ${label}</span>`;
}

function filterTable(tbodyId, inputId) {
  const q = document.getElementById(inputId).value.toLowerCase();
  document.querySelectorAll(`#${tbodyId} tr`).forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

function exportCSV() {
  const headers = ['Tracking Number','Client','Origin','Destination','Service','Status','ETA'];
  const rows    = allShipments.map(s => [s.tracking_number||'', s.client_name, s.origin, s.destination, s.service_type, s.status, s.eta||'']);
  const csv     = [headers, ...rows].map(r => r.map(c => `"${c}"`).join(',')).join('\n');
  const a = document.createElement('a');
  a.href     = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
  a.download = `blueroute_shipments_${new Date().toISOString().slice(0,10)}.csv`;
  a.click();
  showToast('CSV exported', 'success');
}

function formatDate(d) {
  if (!d) return '—';
  try { return new Date(d).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }); }
  catch { return d; }
}

function escHtml(str) {
  return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escAttr(str) {
  return String(str ?? '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function openModal(id)  { document.getElementById(id).classList.add('open');    }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('.modal-overlay').forEach(o =>
  o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); })
);

function showToast(msg, type = 'success') {
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  const icon = type === 'success' ? 'circle-check' : type === 'error' ? 'circle-xmark' : 'circle-info';
  t.innerHTML = `<i class="fa-solid fa-${icon}"></i> ${msg}`;
  document.getElementById('toast-container').appendChild(t);
  setTimeout(() => t.classList.add('fade-out'), 3000);
  setTimeout(() => t.remove(), 3500);
}

/* ═══════════════════════════════════════════════
   INIT
═══════════════════════════════════════════════ */
loadAllData();
</script>
</body>
</html>