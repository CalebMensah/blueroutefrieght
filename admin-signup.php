<?php
// ══════════════════════════════════════════
//  BlueRoute — Admin Signup
//  admin-signup.php
//  Role is locked to 'admin' — not user-settable
// ══════════════════════════════════════════

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

// ── Handle POST ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $raw  = file_get_contents('php://input');
    $body = !empty($raw) ? (json_decode($raw, true) ?? []) : $_POST;

    // CSRF
    if (!verifyCsrf($body['csrf_token'] ?? '')) {
        jsonResponse(['success'=>false,'error'=>'Invalid security token. Please refresh.'], 403);
    }

    $pdo = getDB();

    // Rate limiting
    if (!checkRateLimit($pdo, 'signup', 5, 600)) {
        jsonResponse(['success'=>false,'error'=>'Too many attempts. Please wait 10 minutes.'], 429);
    }

    // Extract fields
    $firstName = sanitise($body['first_name'] ?? '');
    $lastName  = sanitise($body['last_name']  ?? '');
    $email     = strtolower(trim($body['email']    ?? ''));
    $username  = strtolower(trim($body['username'] ?? ''));
    $password  = $body['password']         ?? '';
    $confirm   = $body['confirm_password'] ?? '';
    $role      = 'admin'; // always locked — never taken from input

    // Validation
    $errors = [];

    if (strlen($firstName) < 2 || strlen($firstName) > 80)
        $errors['first_name'] = 'First name must be 2–80 characters.';

    if (strlen($lastName) < 2 || strlen($lastName) > 80)
        $errors['last_name'] = 'Last name must be 2–80 characters.';

    if (!validateEmail($email))
        $errors['email'] = 'Please enter a valid email address.';

    if (!validateUsername($username))
        $errors['username'] = 'Username must be 3–30 characters: letters, numbers, underscores only.';

    $pwErrors = validatePassword($password);
    if ($pwErrors)
        $errors['password'] = 'Password must have: ' . implode(', ', $pwErrors) . '.';

    if ($password !== $confirm)
        $errors['confirm_password'] = 'Passwords do not match.';

    if ($errors) {
        jsonResponse(['success'=>false,'errors'=>$errors,'error'=>'Please fix the errors below.'], 422);
    }

    // Duplicate checks
    $chk = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $chk->execute([':email' => $email]);
    if ($chk->fetch()) {
        jsonResponse(['success'=>false,'errors'=>['email'=>'This email is already registered.'],'error'=>'Email already in use.'], 409);
    }

    $chk = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $chk->execute([':username' => $username]);
    if ($chk->fetch()) {
        jsonResponse(['success'=>false,'errors'=>['username'=>'This username is already taken.'],'error'=>'Username unavailable.'], 409);
    }

    // Hash & insert
    $hash = hashPassword($password);

    $stmt = $pdo->prepare("
        INSERT INTO users
            (first_name, last_name, email, username, password_hash,
             role, email_verified, verify_token, status)
        VALUES
            (:fn, :ln, :email, :username, :hash,
             :role, 1, NULL, 'active')
    ");
    $stmt->execute([
        ':fn'       => $firstName,
        ':ln'       => $lastName,
        ':email'    => $email,
        ':username' => $username,
        ':hash'     => $hash,
        ':role'     => $role,
    ]);

    logActivity($pdo, 'user', "New admin registered: <strong>{$username}</strong> ({$email})");
    resetRateLimit($pdo, 'signup');

    jsonResponse(['success'=>true,'message'=>'Admin account created. You can now log in.'], 201);
}

// ── GET — redirect if already logged in ──
if (!empty($_SESSION['user'])) {
    header('Location: admin.php');
    exit;
}

$token = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Admin Registration | BlueRoute</title>
  <meta name="robots" content="noindex,nofollow"/>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --navy:     #0a1628;
      --navy-mid: #0f2040;
      --blue:     #1e4fd8;
      --gold:     #c9a84c;
      --red:      #dc3545;
      --green:    #198754;
      --surface:  #f4f6fb;
      --border:   #e2e8f3;
      --text:     #1a2540;
      --text-soft:#5a6a8a;
      --text-muted:#8a9ab8;
      --white:    #ffffff;
    }

    html, body {
      min-height: 100vh;
      font-family: 'DM Sans', sans-serif;
      background: var(--surface);
      color: var(--text);
    }

    /* ── Layout ── */
    .page-wrap {
      min-height: 100vh;
      display: flex;
    }

    /* ── Left accent panel ── */
    .accent-panel {
      width: 340px; flex-shrink: 0;
      background: var(--navy);
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      padding: 48px 36px;
      position: relative; overflow: hidden;
    }

    /* subtle grid lines decoration */
    .accent-panel::before {
      content: '';
      position: absolute; inset: 0;
      background-image:
        linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
      background-size: 32px 32px;
    }

    .accent-logo { position: relative; z-index: 1; text-align: center; }

    .accent-icon {
      width: 70px; height: 70px;
      background: var(--blue); border-radius: 16px;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 20px;
      font-family: 'Syne', sans-serif; font-size: .65rem; font-weight: 800;
      color: #fff; letter-spacing: .5px; line-height: 1.2;
    }

    .accent-logo h2 {
      font-family: 'Syne', sans-serif; font-size: 1.3rem; font-weight: 800;
      color: #fff; letter-spacing: 2px; margin-bottom: 6px;
    }

    .accent-logo p { font-size: .76rem; color: rgba(255,255,255,.4); letter-spacing: .5px; }

    .accent-badge {
      position: relative; z-index: 1;
      margin-top: 48px;
      background: rgba(201,168,76,.12);
      border: 1px solid rgba(201,168,76,.3);
      border-radius: 10px; padding: 16px 20px;
      text-align: center;
    }

    .accent-badge i { font-size: 1.4rem; color: var(--gold); margin-bottom: 10px; display: block; }
    .accent-badge h4 { font-family: 'Syne', sans-serif; font-size: .82rem; font-weight: 700; color: var(--gold); letter-spacing: .5px; margin-bottom: 6px; }
    .accent-badge p  { font-size: .74rem; color: rgba(255,255,255,.45); line-height: 1.5; }

    /* ── Right form panel ── */
    .form-panel {
      flex: 1;
      display: flex; align-items: center; justify-content: center;
      padding: 40px 24px;
    }

    .form-card {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 36px 40px;
      width: 100%; max-width: 520px;
      box-shadow: 0 4px 24px rgba(10,22,40,.08);
    }

    .form-card-header { margin-bottom: 28px; }
    .form-card-header h3 {
      font-family: 'Syne', sans-serif; font-size: 1.2rem; font-weight: 800;
      color: var(--text); margin-bottom: 5px;
    }
    .form-card-header p { font-size: .82rem; color: var(--text-soft); }

    /* Role locked banner */
    .role-banner {
      display: flex; align-items: center; gap: 12px;
      background: rgba(10,22,40,.04);
      border: 1px solid var(--border);
      border-radius: 9px; padding: 12px 16px;
      margin-bottom: 24px;
    }
    .role-icon {
      width: 36px; height: 36px; border-radius: 8px;
      background: var(--navy); color: var(--gold);
      display: flex; align-items: center; justify-content: center;
      font-size: .85rem; flex-shrink: 0;
    }
    .role-text { flex: 1; }
    .role-text strong { font-size: .8rem; color: var(--text); display: block; margin-bottom: 2px; }
    .role-text span   { font-size: .72rem; color: var(--text-muted); }
    .role-lock { color: var(--text-muted); font-size: .75rem; }

    /* Form rows */
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
    .form-row.full { grid-template-columns: 1fr; }

    .form-group { display: flex; flex-direction: column; gap: 5px; }
    .form-group label {
      font-size: .71rem; font-weight: 700; color: var(--text);
      text-transform: uppercase; letter-spacing: .4px;
      font-family: 'Syne', sans-serif;
      display: flex; align-items: center; gap: 4px;
    }
    .form-group label .req { color: var(--red); }

    .input-wrap { position: relative; }
    .input-wrap .i-icon {
      position: absolute; left: 11px; top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted); font-size: .78rem; pointer-events: none;
    }
    .input-wrap input {
      width: 100%; padding: 10px 12px 10px 34px;
      border: 1px solid var(--border); border-radius: 8px;
      font-family: 'DM Sans', sans-serif; font-size: .84rem; color: var(--text);
      background: var(--surface); outline: none;
      transition: border-color .15s, box-shadow .15s;
    }
    .input-wrap input:focus {
      border-color: var(--blue); background: #fff;
      box-shadow: 0 0 0 3px rgba(30,79,216,.08);
    }
    .input-wrap input.error {
      border-color: var(--red);
      box-shadow: 0 0 0 3px rgba(220,53,69,.08);
    }

    .pw-eye {
      position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
      background: none; border: none; color: var(--text-muted);
      cursor: pointer; font-size: .82rem; padding: 4px; transition: color .15s;
    }
    .pw-eye:hover { color: var(--navy); }

    .field-error {
      font-size: .71rem; color: var(--red);
      display: none; margin-top: 2px;
    }
    .field-error.visible { display: block; }

    /* Alert */
    .form-alert {
      display: none; padding: 11px 14px; border-radius: 8px;
      font-size: .82rem; margin-bottom: 18px;
      align-items: center; gap: 9px;
    }
    .form-alert.visible { display: flex; }
    .form-alert.error   { background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; }
    .form-alert.success { background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; }

    /* Submit */
    .submit-btn {
      width: 100%; padding: 13px;
      background: var(--navy); color: #fff;
      border: none; border-radius: 9px;
      font-family: 'Syne', sans-serif; font-size: .88rem; font-weight: 700;
      letter-spacing: .8px; text-transform: uppercase;
      cursor: pointer; transition: background .2s, transform .15s;
      margin-top: 6px; display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .submit-btn:hover   { background: var(--blue); }
    .submit-btn:active  { transform: scale(.98); }
    .submit-btn:disabled { opacity: .6; cursor: not-allowed; }

    .login-link {
      text-align: center; margin-top: 18px;
      font-size: .8rem; color: var(--text-soft);
    }
    .login-link a { color: var(--blue); font-weight: 600; text-decoration: none; }
    .login-link a:hover { text-decoration: underline; }

    /* Responsive */
    @media (max-width: 768px) {
      .accent-panel { display: none; }
      .form-card { padding: 28px 22px; }
      .form-row  { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
<div class="page-wrap">

  <!-- ── Left accent panel ── -->
  <div class="accent-panel">
    <div class="accent-logo">
      <div class="accent-icon">BLUE<br>ROUTE</div>
      <h2>BLUEROUTE</h2>
      <p>Admin Registration Portal</p>
    </div>
    <div class="accent-badge">
      <i class="fa-solid fa-shield-halved"></i>
      <h4>Administrator Access</h4>
      <p>This page is for creating admin accounts only. Admin credentials grant full system access.</p>
    </div>
  </div>

  <!-- ── Right form panel ── -->
  <div class="form-panel">
    <div class="form-card">

      <div class="form-card-header">
        <h3>Create Admin Account</h3>
        <p>Fill in the details below to register a new administrator.</p>
      </div>

      <!-- Role locked indicator -->
      <div class="role-banner">
        <div class="role-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <div class="role-text">
          <strong>Role: Administrator</strong>
          <span>This account will have full system access</span>
        </div>
        <i class="fa-solid fa-lock role-lock"></i>
      </div>

      <!-- Alert -->
      <div class="form-alert" id="form-alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span id="alert-msg"></span>
      </div>

      <form id="adminSignupForm" novalidate>
        <input type="hidden" id="csrf_token" value="<?= htmlspecialchars($token) ?>" />

        <div class="form-row">
          <div class="form-group">
            <label>First Name <span class="req">*</span></label>
            <div class="input-wrap">
              <i class="i-icon fa-solid fa-user"></i>
              <input type="text" id="first_name" placeholder="John" autocomplete="given-name" maxlength="80" />
            </div>
            <span class="field-error" id="err-first_name"></span>
          </div>
          <div class="form-group">
            <label>Last Name <span class="req">*</span></label>
            <div class="input-wrap">
              <i class="i-icon fa-solid fa-user"></i>
              <input type="text" id="last_name" placeholder="Smith" autocomplete="family-name" maxlength="80" />
            </div>
            <span class="field-error" id="err-last_name"></span>
          </div>
        </div>

        <div class="form-row full">
          <div class="form-group">
            <label>Email Address <span class="req">*</span></label>
            <div class="input-wrap">
              <i class="i-icon fa-solid fa-envelope"></i>
              <input type="email" id="email" placeholder="admin@blueroute.com" autocomplete="email" maxlength="180" />
            </div>
            <span class="field-error" id="err-email"></span>
          </div>
        </div>

        <div class="form-row full">
          <div class="form-group">
            <label>Username <span class="req">*</span></label>
            <div class="input-wrap">
              <i class="i-icon fa-solid fa-at"></i>
              <input type="text" id="username" placeholder="admin_john" autocomplete="username" maxlength="30" />
            </div>
            <span class="field-error" id="err-username"></span>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Password <span class="req">*</span></label>
            <div class="input-wrap">
              <i class="i-icon fa-solid fa-lock"></i>
              <input type="password" id="password" placeholder="Password" autocomplete="new-password" />
              <button type="button" class="pw-eye" id="pw-eye-1"><i class="fa-regular fa-eye"></i></button>
            </div>
            <span class="field-error" id="err-password"></span>
          </div>
          <div class="form-group">
            <label>Confirm Password <span class="req">*</span></label>
            <div class="input-wrap">
              <i class="i-icon fa-solid fa-lock"></i>
              <input type="password" id="confirm_password" placeholder="Repeat password" autocomplete="new-password" />
              <button type="button" class="pw-eye" id="pw-eye-2"><i class="fa-regular fa-eye"></i></button>
            </div>
            <span class="field-error" id="err-confirm_password"></span>
          </div>
        </div>

        <button type="submit" class="submit-btn" id="submit-btn">
          <i class="fa-solid fa-shield-halved"></i> Create Admin Account
        </button>
      </form>

      <div class="login-link">
        Already have an account? <a href="login.php">Sign in</a>
      </div>

    </div>
  </div>

</div>

<script>
// ── Password toggles ──
function pwToggle(btnId, inputId) {
  document.getElementById(btnId).addEventListener('click', function() {
    const inp  = document.getElementById(inputId);
    const icon = this.querySelector('i');
    if (inp.type === 'password') {
      inp.type = 'text';
      icon.className = 'fa-regular fa-eye-slash';
    } else {
      inp.type = 'password';
      icon.className = 'fa-regular fa-eye';
    }
  });
}
pwToggle('pw-eye-1', 'password');
pwToggle('pw-eye-2', 'confirm_password');

// ── Alert helpers ──
function showAlert(msg, type = 'error') {
  const el = document.getElementById('form-alert');
  el.className = `form-alert visible ${type}`;
  document.getElementById('alert-msg').textContent = msg;
  el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
function clearAlert() {
  document.getElementById('form-alert').className = 'form-alert';
}

// ── Field error helpers ──
function showFieldError(field, msg) {
  document.getElementById(field).classList.add('error');
  const el = document.getElementById('err-' + field);
  if (el) { el.textContent = msg; el.classList.add('visible'); }
}
function clearFieldErrors() {
  document.querySelectorAll('.input-wrap input').forEach(i => i.classList.remove('error'));
  document.querySelectorAll('.field-error').forEach(e => { e.textContent = ''; e.classList.remove('visible'); });
}

// ── Submit ──
document.getElementById('adminSignupForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  clearAlert();
  clearFieldErrors();

  const btn = document.getElementById('submit-btn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating…';

  const payload = {
    csrf_token:       document.getElementById('csrf_token').value,
    first_name:       document.getElementById('first_name').value.trim(),
    last_name:        document.getElementById('last_name').value.trim(),
    email:            document.getElementById('email').value.trim(),
    username:         document.getElementById('username').value.trim(),
    password:         document.getElementById('password').value,
    confirm_password: document.getElementById('confirm_password').value,
  };
  // Note: role is intentionally NOT sent — it is hardcoded to 'admin' server-side

  try {
    const res  = await fetch('admin-signup.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(payload),
    });
    const data = await res.json();

    if (data.success) {
      showAlert('Admin account created successfully! Redirecting to login…', 'success');
      btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Done!';
      setTimeout(() => window.location.href = 'login.php', 1500);
    } else {
      // Field-level errors
      if (data.errors) {
        Object.entries(data.errors).forEach(([field, msg]) => showFieldError(field, msg));
      }
      showAlert(data.error || 'Please fix the errors above.');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-shield-halved"></i> Create Admin Account';
    }
  } catch (err) {
    showAlert('Connection error. Please try again.');
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-shield-halved"></i> Create Admin Account';
  }
});

// Clear errors on input
document.querySelectorAll('input').forEach(input => {
  input.addEventListener('input', function() {
    this.classList.remove('error');
    const err = document.getElementById('err-' + this.id);
    if (err) { err.textContent = ''; err.classList.remove('visible'); }
    clearAlert();
  });
});

document.getElementById('first_name').focus();
</script>
</body>
</html>