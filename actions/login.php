<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../login.php'); exit; }
$email = trim($_POST['email'] ?? '');
$pass  = (string)($_POST['password'] ?? $_POST['pass'] ?? '');

$stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
$stmt->execute([strtolower($email)]);
// emails stockés en casse originale : fallback insensible
$row = $stmt->fetch();
if (!$row) {
    $stmt = db()->prepare('SELECT * FROM users WHERE LOWER(email)=LOWER(?) LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
}
if (!$row || !password_verify($pass, $row['password_hash'])) {
    flash('Email ou mot de passe incorrect.');
    header('Location: ../login.php');
    exit;
}
login_user($row);
header('Location: ' . home_by_role($row['role']));
exit;
