<?php
// ══════════════════════════════════════════
//  BlueRoute — Login Page + API
//  login.php
// ══════════════════════════════════════════

// session_start() MUST be first before any output or includes
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

// ── Handle POST (JSON API) ────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $raw  = file_get_contents('php://input');
    $body = !empty($raw) ? (json_decode($raw, true) ?? []) : $_POST;

    // CSRF
    if (!verifyCsrf($body['csrf_token'] ?? '')) {
        jsonResponse(['success'=>false,'error'=>'Invalid security token. Please refresh.'], 403);
    }

    $login    = strtolower(trim($body['login']    ?? ''));
    $password =            $body['password'] ?? '';
    $remember = !empty($body['remember']);

    if (!$login || !$password) {
        jsonResponse(['success'=>false,'error'=>'Username and password are required.'], 422);
    }

    $pdo = getDB();

    // Rate limiting — 10 attempts per 15 min per IP
    if (!checkRateLimit($pdo, 'login', 10, 900)) {
        jsonResponse(['success'=>false,'error'=>'Too many login attempts. Please wait 15 minutes.'], 429);
    }

    // Find user by email OR username
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=:a OR username=:b LIMIT 1");
    $stmt->execute([':a'=>$login, ':b'=>$login]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        jsonResponse(['success'=>false,'error'=>'Invalid username or password.'], 401);
    }

    if ($user['status'] === 'suspended') {
        jsonResponse(['success'=>false,'error'=>'Your account has been suspended. Please contact support.'], 403);
    }

    // ── Success — build session ───────────
    session_regenerate_id(true);

    // Store full user array so any page can access role, name, etc.
    $_SESSION['user'] = [
        'id'         => (int) $user['id'],
        'first_name' => $user['first_name'],
        'last_name'  => $user['last_name'],
        'username'   => $user['username'],
        'email'      => $user['email'],
        'role'       => $user['role'],   // 'admin' or 'client' — from DB, never hardcoded
    ];

    // Update last login
    $pdo->prepare("UPDATE users SET last_login=NOW(), login_attempts=0 WHERE id=:id")
        ->execute([':id' => $user['id']]);

    // Reset rate limit on success
    resetRateLimit($pdo, 'login');

    // Log activity
    logActivity($pdo, 'user', "User <strong>{$user['username']}</strong> logged in as <strong>{$user['role']}</strong>");

    // ── Route by role ─────────────────────
    $redirect = $user['role'] === 'admin' ? 'admin.php' : 'client-dashboard.php';

    jsonResponse(['success'=>true, 'redirect'=> $redirect]);
}

// ── GET — render login page ───────────────
// Redirect if already logged in
if (!empty($_SESSION['user'])) {
    $redirect = ($_SESSION['user']['role'] ?? 'client') === 'admin' ? 'admin.php' : 'client-dashboard.php';
    header('Location: ' . $redirect);
    exit;
}

$token = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Login | BlueRoute Security &amp; Shipping</title>
  <meta name="robots" content="noindex"/>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --navy:      #0d1f3c;
      --navy-l:    #1a3260;
      --gold:      #b8942a;
      --red:       #e63946;
      --red-dark:  #c1121f;
      --blue:      #2563eb;
      --white:     #ffffff;
      --gray-l:    #f5f7fa;
      --gray-mid:  #e2e8f0;
      --gray:      #94a3b8;
      --text:      #1e293b;
      --text-l:    #64748b;
      --green:     #10b981;
    }

    html, body {
      font-family: 'Outfit', sans-serif;
      height: 100%; color: var(--text);
    }

    /* ── Two-panel layout ── */
    .login-wrap {
      display: flex;
      min-height: 100vh;
    }

    /* ── Left form panel ── */
    .form-panel {
      flex: 0 0 600px;
      max-width: 600px;
      background: var(--white);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 48px 64px;
      position: relative;
    }

    /* Logo */
    .login-logo {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 32px;
      text-decoration: none;
    }

    .logo-hex {
      width: 52px; height: 52px;
      background: var(--navy);
      clip-path: polygon(50% 0%,100% 25%,100% 75%,50% 100%,0% 75%,0% 25%);
      display: flex; align-items: center; justify-content: center;
      color: var(--gold); font-weight: 700; font-size: 9px;
      text-align: center; line-height: 1.2;
    }

    .logo-text h1 {
      font-size: 1.05rem; font-weight: 700; color: var(--navy); letter-spacing: .3px;
    }

    .logo-text p { font-size: .7rem; color: var(--gray); letter-spacing: .2px; }

    /* Headings */
    .login-heading {
      text-align: center;
      margin-bottom: 30px;
      width: 100%;
    }

    .login-heading h2 {
      font-size: 1.6rem; font-weight: 700; color: var(--text); margin-bottom: 6px;
    }

    .login-heading p { font-size: .88rem; color: var(--text-l); line-height: 1.5; }
    .login-heading p a { color: var(--blue); text-decoration: none; }

    /* Form */
    .login-form { width: 100%; }

    .form-group {
      margin-bottom: 16px;
    }

    .form-group label {
      display: block;
      font-size: .78rem; font-weight: 600; color: var(--text);
      margin-bottom: 6px;
    }

    .form-group label .req { color: var(--red); }

    .input-wrap {
      position: relative;
    }

    .input-wrap .i-icon {
      position: absolute; left: 12px; top: 50%;
      transform: translateY(-50%);
      color: var(--gray); font-size: .82rem; pointer-events: none;
    }

    .input-wrap input {
      width: 100%;
      padding: 11px 12px 11px 36px;
      border: 1px solid var(--gray-mid);
      border-radius: 7px;
      font-family: 'Outfit', sans-serif;
      font-size: .88rem; color: var(--text);
      outline: none;
      transition: border-color .2s, box-shadow .2s;
      background: var(--white);
    }

    .input-wrap input:focus {
      border-color: var(--navy);
      box-shadow: 0 0 0 3px rgba(13,31,60,.07);
    }

    .input-wrap input.error {
      border-color: var(--red);
      box-shadow: 0 0 0 3px rgba(230,57,70,.07);
    }

    /* Password toggle */
    .pw-eye {
      position: absolute; right: 10px; top: 50%;
      transform: translateY(-50%);
      background: none; border: none;
      color: var(--gray); cursor: pointer; font-size: .85rem;
      padding: 4px; transition: color .2s;
    }

    .pw-eye:hover { color: var(--navy); }

    /* Remember + forgot row */
    .form-meta {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 22px;
    }

    .remember-row {
      display: flex; align-items: center; gap: 7px;
      font-size: .83rem; color: var(--text-l); cursor: pointer;
    }

    .remember-row input[type="checkbox"] {
      width: 15px; height: 15px; accent-color: var(--navy);
      cursor: pointer;
    }

    .forgot-link {
      font-size: .83rem; color: var(--text-l);
      text-decoration: none; transition: color .2s;
    }

    .forgot-link:hover { color: var(--navy); text-decoration: underline; }

    /* Alert */
    .login-alert {
      display: none;
      padding: 11px 14px;
      border-radius: 7px;
      font-size: .84rem;
      margin-bottom: 16px;
      align-items: center; gap: 9px;
    }

    .login-alert.visible { display: flex; }
    .login-alert.error   { background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; }
    .login-alert.success { background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; }

    /* Submit button */
    .login-btn {
      width: 100%;
      padding: 13px;
      background: var(--red);
      color: var(--white);
      border: none; border-radius: 7px;
      font-family: 'Outfit', sans-serif;
      font-size: .92rem; font-weight: 700;
      letter-spacing: .8px; text-transform: uppercase;
      cursor: pointer;
      transition: background .25s, transform .15s;
      margin-bottom: 20px;
    }

    .login-btn:hover   { background: var(--red-dark); }
    .login-btn:active  { transform: scale(.98); }
    .login-btn:disabled { opacity: .65; cursor: not-allowed; }

    /* Signup row */
    .signup-row {
      text-align: center;
      font-size: .84rem; color: var(--text-l);
      padding-bottom: 24px;
      border-bottom: 1px solid var(--gray-mid);
      margin-bottom: 24px;
    }

    .signup-row a {
      color: var(--blue); font-weight: 600; text-decoration: none;
    }

    .signup-row a:hover { text-decoration: underline; }

    /* Track shipment section */
    .track-section { width: 100%; text-align: center; }

    .track-section p {
      font-size: .84rem; color: var(--text-l); margin-bottom: 10px;
    }

    .track-btn {
      display: flex; align-items: center; justify-content: center; gap: 8px;
      width: 100%; padding: 12px;
      background: var(--gray-l);
      border: 1px solid var(--gray-mid);
      border-radius: 7px;
      font-family: 'Outfit', sans-serif;
      font-size: .86rem; font-weight: 500; color: var(--text-l);
      text-decoration: none;
      transition: all .2s;
    }

    .track-btn:hover {
      background: var(--navy);
      color: var(--white);
      border-color: var(--navy);
    }

    /* ── Right image panel ── */
    .image-panel {
      flex: 1;
      background: var(--navy)
        url('https://images.unsplash.com/photo-1578575437130-527eed3abbec?w=1400&auto=format&fit=crop&q=80')
        center/cover no-repeat;
      position: relative;
      min-height: 100vh;
    }

    .image-panel::after {
      content: '';
      position: absolute; inset: 0;
      background: linear-gradient(
        135deg,
        rgba(13,31,60,.55) 0%,
        rgba(26,50,96,.35) 60%,
        rgba(0,0,0,.15) 100%
      );
    }

    /* Expand arrow bottom-right */
    .expand-btn {
      position: absolute; bottom: 24px; right: 24px; z-index: 2;
      width: 42px; height: 42px; border-radius: 50%;
      background: rgba(255,255,255,.2);
      backdrop-filter: blur(4px);
      display: flex; align-items: center; justify-content: center;
      color: var(--white); font-size: .9rem;
      cursor: pointer; border: none;
      transition: background .2s;
    }

    .expand-btn:hover { background: rgba(255,255,255,.35); }

    /* ── Responsive ── */
    @media (max-width: 860px) {
      .image-panel { display: none; }
      .form-panel  { flex: 1; max-width: 100%; padding: 40px 28px; }
    }

    @media (max-width: 420px) {
      .form-panel { padding: 32px 20px; }
    }
  </style>
</head>
<body>

<div class="login-wrap">

  <!-- ══════════ LEFT: FORM ══════════ -->
  <div class="form-panel">

    <!-- Logo -->
    <a href="index.html" class="login-logo">
      <div class="logo-hex">BLUE<br>ROUTE</div>
      <div class="logo-text">
        <h1>BLUEROUTE</h1>
        <p>Security &amp; Shipping Company</p>
      </div>
    </a>

    <!-- Heading -->
    <div class="login-heading">
      <h2>Welcome to BlueRoute</h2>
      <p>Log in to your account and <a href="#">start your adventure</a>.</p>
    </div>

    <!-- Alert -->
    <div class="login-alert" id="login-alert" role="alert">
      <i class="fa-solid fa-circle-exclamation"></i>
      <span id="alert-msg"></span>
    </div>

    <!-- Form -->
    <form class="login-form" id="loginForm" novalidate>
      <input type="hidden" id="csrf_token" value="<?= htmlspecialchars($token) ?>" />

      <div class="form-group">
        <label for="login">User <span class="req">*</span></label>
        <div class="input-wrap">
          <i class="i-icon fa-solid fa-user"></i>
          <input type="text" id="login" name="login"
                 placeholder="Username or email"
                 autocomplete="username" maxlength="180" />
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password <span class="req">*</span></label>
        <div class="input-wrap">
          <i class="i-icon fa-solid fa-lock"></i>
          <input type="password" id="password" name="password"
                 placeholder="Password"
                 autocomplete="current-password" />
          <button type="button" class="pw-eye" id="pw-eye" aria-label="Show password">
            <i class="fa-regular fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="form-meta">
        <label class="remember-row">
          <input type="checkbox" id="remember" name="remember" />
          Remember me
        </label>
        <a href="#" class="forgot-link">Forgot your password ?</a>
      </div>

      <button type="submit" class="login-btn" id="login-btn">ENTER</button>

    </form>

    <div class="signup-row">
      Don't have an account?&nbsp; <a href="signup.php">Sign up</a>
    </div>

    <div class="track-section">
      <p>Track your shipment</p>
      <a href="tracking.php" class="track-btn">
        <i class="fa-solid fa-magnifying-glass"></i> Tracking
      </a>
    </div>

  </div><!-- /form-panel -->

  <!-- ══════════ RIGHT: IMAGE ══════════ -->
  <div class="image-panel">
    <button class="expand-btn" title="View fullscreen">
      <i class="fa-solid fa-chevron-right"></i>
    </button>
  </div>

</div><!-- /login-wrap -->

<script>
// ── Password toggle ──
document.getElementById('pw-eye').addEventListener('click', function() {
  const inp  = document.getElementById('password');
  const icon = this.querySelector('i');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.className = 'fa-regular fa-eye-slash';
  } else {
    inp.type = 'password';
    icon.className = 'fa-regular fa-eye';
  }
});

// ── Alert helpers ──
function showAlert(msg, type = 'error') {
  const el = document.getElementById('login-alert');
  el.className = `login-alert visible ${type}`;
  document.getElementById('alert-msg').textContent = msg;
}

function clearAlert() {
  document.getElementById('login-alert').className = 'login-alert';
}

// ── Form submit ──
document.getElementById('loginForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  clearAlert();

  const login    = document.getElementById('login').value.trim();
  const password = document.getElementById('password').value;
  const remember = document.getElementById('remember').checked;
  const btn      = document.getElementById('login-btn');

  // Basic client validation
  if (!login) {
    document.getElementById('login').classList.add('error');
    showAlert('Please enter your username or email.');
    return;
  }
  if (!password) {
    document.getElementById('password').classList.add('error');
    showAlert('Please enter your password.');
    return;
  }

  document.getElementById('login').classList.remove('error');
  document.getElementById('password').classList.remove('error');

  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Signing in…';

  try {
    const res  = await fetch('login.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({
        csrf_token: document.getElementById('csrf_token').value,
        login,
        password,
        remember,
      }),
    });

    const data = await res.json();

    if (data.success) {
      btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Success! Redirecting…';
      showAlert('Login successful! Redirecting…', 'success');
      setTimeout(() => window.location.href = data.redirect, 800);
    } else {
      showAlert(data.error || 'Login failed. Please try again.');
      btn.disabled = false;
      btn.innerHTML = 'ENTER';
    }

  } catch (err) {
    showAlert('Connection error. Please check your connection and try again.');
    btn.disabled = false;
    btn.innerHTML = 'ENTER';
  }
});

// Clear error state on input
['login','password'].forEach(id => {
  document.getElementById(id).addEventListener('input', function() {
    this.classList.remove('error');
    clearAlert();
  });
});

// Auto-focus username
document.getElementById('login').focus();
</script>
</body>
</html>