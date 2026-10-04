<?php
// UWB.Learn — Session + garde-fous par rôle (remplace localStorage uwbSession)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}
function require_login(?array $roles = null): array {
    $u = current_user();
    $base = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false
        || strpos($_SERVER['SCRIPT_NAME'] ?? '', '/etudiant/') !== false
        || strpos($_SERVER['SCRIPT_NAME'] ?? '', '/actions/') !== false
        || strpos($_SERVER['SCRIPT_NAME'] ?? '', '/prof/') !== false) ? '../' : '';
    if (!$u) {
        header('Location: ' . $base . 'login.php');
        exit;
    }
    if ($roles && !in_array($u['role'], $roles, true)) {
        header('Location: ' . home_by_role($u['role']));
        exit;
    }
    return $u;
}
function home_by_role(string $role): string {
    $b = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false
        || strpos($_SERVER['SCRIPT_NAME'] ?? '', '/etudiant/') !== false
        || strpos($_SERVER['SCRIPT_NAME'] ?? '', '/actions/') !== false
        || strpos($_SERVER['SCRIPT_NAME'] ?? '', '/prof/') !== false) ? '../' : '';
    if ($role === 'admin') return $b . 'admin/dashboard.php';
    if ($role === 'professeur') return $b . 'prof/dashboard.php';
    return $b . 'etudiant/dashboard.php';
}
function login_user(array $row): void {
    $_SESSION['user'] = [
        'id' => (int)$row['id'],
        'nom' => $row['nom'],
        'email' => $row['email'],
        'role' => $row['role'],
        'filiere' => $row['filiere'] ?? '—',
        'niveau' => $row['niveau'] ?? 'L3',
    ];
}
function logout_user(): void {
    $_SESSION = [];
    if (session_status() !== PHP_SESSION_NONE) session_destroy();
}
function flash(?string $msg = null, string $type = 'danger'): ?array {
    if ($msg !== null) { $_SESSION['_flash'] = ['msg' => $msg, 'type' => $type]; return null; }
    $f = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);
    return $f;
}
