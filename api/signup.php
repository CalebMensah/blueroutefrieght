<?php
// ══════════════════════════════════════════
//  BlueRoute — Signup API
//  api/signup.php
//  POST { first_name, last_name, email, phone,
//         country, state, city, zip_code, address,
//         username, password, confirm_password,
//         role, terms, csrf_token }
// ══════════════════════════════════════════

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['success'=>false,'error'=>'Method not allowed']); exit; }

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/helpers.php';

// ── Parse body ────────────────────────────
$raw  = file_get_contents('php://input');
$body = !empty($raw) ? (json_decode($raw, true) ?? []) : $_POST;

// ── CSRF check ────────────────────────────
if (!verifyCsrf($body['csrf_token'] ?? '')) {
    jsonResponse(['success' => false, 'error' => 'Invalid security token. Please refresh the page.'], 403);
}

$pdo = getDB();

// ── Rate limiting (max 5 signups per IP per 10 min) ──
if (!checkRateLimit($pdo, 'signup', 5, 600)) {
    jsonResponse(['success' => false, 'error' => 'Too many signup attempts. Please wait 10 minutes.'], 429);
}

// ── Required fields ───────────────────────
$required = ['first_name','last_name','email','phone','country','username','password','confirm_password'];
$missing  = [];
foreach ($required as $field) {
    if (empty(trim($body[$field] ?? ''))) $missing[] = $field;
}
if ($missing) {
    jsonResponse(['success' => false, 'error' => 'Required fields missing: ' . implode(', ', $missing)], 422);
}

// ── Extract & sanitise ────────────────────
$firstName  = sanitise($body['first_name']);
$lastName   = sanitise($body['last_name']);
$email      = strtolower(trim($body['email']));
$phone      = sanitise($body['phone']);
$country    = sanitise($body['country']    ?? '');
$state      = sanitise($body['state']      ?? '');
$city       = sanitise($body['city']       ?? '');
$zipCode    = sanitise($body['zip_code']   ?? '');
$address    = sanitise($body['address']    ?? '');
$username   = strtolower(trim($body['username']));
$password   = $body['password'];
$confirm    = $body['confirm_password'];
$terms      = !empty($body['terms']);
$role       = strtolower(trim($body['role'] ?? 'client'));

// ── Validation ────────────────────────────
$errors = [];

if (strlen($firstName) < 2 || strlen($firstName) > 80)
    $errors['first_name'] = 'First name must be 2–80 characters.';

if (strlen($lastName) < 2 || strlen($lastName) > 80)
    $errors['last_name'] = 'Last name must be 2–80 characters.';

if (!validateEmail($email))
    $errors['email'] = 'Please enter a valid email address.';

if (!validatePhone($phone))
    $errors['phone'] = 'Please enter a valid phone number.';

if (!validateUsername($username))
    $errors['username'] = 'Username must be 3–30 characters: letters, numbers, underscores only.';

$pwErrors = validatePassword($password);
if ($pwErrors)
    $errors['password'] = 'Password must have: ' . implode(', ', $pwErrors) . '.';

if ($password !== $confirm)
    $errors['confirm_password'] = 'Passwords do not match.';

if (!$terms)
    $errors['terms'] = 'You must agree to the terms and conditions.';

// ── Role validation ───────────────────────
$allowedRoles = ['admin', 'client'];
if (!in_array($role, $allowedRoles, true))
    $errors['role'] = 'Role must be either "admin" or "client".';

if ($errors) {
    jsonResponse(['success' => false, 'errors' => $errors, 'error' => 'Please fix the errors below.'], 422);
}

// ── Check duplicates ──────────────────────
$chkEmail = $pdo->prepare("SELECT id FROM users WHERE email = :email");
$chkEmail->execute([':email' => $email]);
if ($chkEmail->fetch()) {
    jsonResponse(['success' => false, 'errors' => ['email' => 'This email is already registered.'], 'error' => 'Email already in use.'], 409);
}

$chkUser = $pdo->prepare("SELECT id FROM users WHERE username = :username");
$chkUser->execute([':username' => $username]);
if ($chkUser->fetch()) {
    jsonResponse(['success' => false, 'errors' => ['username' => 'This username is already taken.'], 'error' => 'Username unavailable.'], 409);
}

// ── Hash password ─────────────────────────
$hash = hashPassword($password);

// ── Insert user (auto-verified, active) ───
$stmt = $pdo->prepare("
    INSERT INTO users
        (first_name, last_name, email, phone, country, state, city,
         zip_code, address, username, password_hash, role, email_verified,
         verify_token, status)
    VALUES
        (:fn, :ln, :email, :phone, :country, :state, :city,
         :zip, :address, :username, :hash, :role, 1,
         NULL, 'active')
");

$stmt->execute([
    ':fn'       => $firstName,
    ':ln'       => $lastName,
    ':email'    => $email,
    ':phone'    => $phone,
    ':country'  => $country,
    ':state'    => $state,
    ':city'     => $city,
    ':zip'      => $zipCode,
    ':address'  => $address,
    ':username' => $username,
    ':hash'     => $hash,
    ':role'     => $role,
]);

$newUserId = (int) $pdo->lastInsertId();

// ── Log activity ──────────────────────────
logActivity($pdo, 'user', "New user registered: <strong>{$username}</strong> ({$email}) as <strong>{$role}</strong>");

// ── Reset rate limit on success ───────────
resetRateLimit($pdo, 'signup');

// ── Respond ───────────────────────────────
// In production: send verification email here using $verifyToken
// e.g. mail($email, "Verify your BlueRoute account", "Click: /verify.php?token={$verifyToken}");

jsonResponse([
    'success'  => true,
    'message'  => 'Account created successfully! You can now log in.',
    'username' => $username,
    'role'     => $role,
], 201);