<?php
// ══════════════════════════════════════════
//  BlueRoute — Security Helpers
//  includes/security.php
// ══════════════════════════════════════════

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false, // set true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ── CSRF Token ─────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '" />';
}

// ── Rate Limiting ──────────────────────────
function checkRateLimit(PDO $pdo, string $action, int $maxAttempts = 5, int $windowSeconds = 300): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Clean old records
    $pdo->prepare("DELETE FROM rate_limits WHERE last_attempt < DATE_SUB(NOW(), INTERVAL :sec SECOND)")
        ->execute([':sec' => $windowSeconds]);

    $stmt = $pdo->prepare("SELECT attempts, last_attempt FROM rate_limits WHERE ip_address=:ip AND action=:action");
    $stmt->execute([':ip' => $ip, ':action' => $action]);
    $row = $stmt->fetch();

    if ($row && $row['attempts'] >= $maxAttempts) {
        return false; // rate limited
    }

    // Upsert
    $pdo->prepare("
        INSERT INTO rate_limits (ip_address, action, attempts, last_attempt)
        VALUES (:ip, :action, 1, NOW())
        ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()
    ")->execute([':ip' => $ip, ':action' => $action]);

    return true;
}

function resetRateLimit(PDO $pdo, string $action): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $pdo->prepare("DELETE FROM rate_limits WHERE ip_address=:ip AND action=:action")
        ->execute([':ip' => $ip, ':action' => $action]);
}

// ── Input Validation ───────────────────────
function validateEmail(string $email): bool {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone(string $phone): bool {
    return (bool) preg_match('/^\+?[\d\s\-\(\)]{7,20}$/', $phone);
}

function validateUsername(string $username): bool {
    return (bool) preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username);
}

function validatePassword(string $password): array {
    $errors = [];
    if (strlen($password) < 8)                             $errors[] = 'At least 8 characters';
    if (!preg_match('/[A-Z]/', $password))                 $errors[] = 'At least one uppercase letter';
    if (!preg_match('/[a-z]/', $password))                 $errors[] = 'At least one lowercase letter';
    if (!preg_match('/[0-9]/', $password))                 $errors[] = 'At least one number';
    if (!preg_match('/[\W_]/', $password))                 $errors[] = 'At least one special character';
    return $errors;
}

function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost'   => 4,
        'threads'     => 1,
    ]);
}

function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

// ── Sanitise ───────────────────────────────
function sanitise(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}