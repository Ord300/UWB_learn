<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../login.php'); exit; }
$ident = trim($_POST['email'] ?? $_POST['matricule'] ?? '');
$pass  = (string)($_POST['password'] ?? $_POST['pass'] ?? '');

// Migration auto : colonne matricule
try { if (!db()->query("SHOW COLUMNS FROM users LIKE 'matricule'")->fetch()) db()->exec("ALTER TABLE users ADD COLUMN matricule VARCHAR(30) NULL, ADD UNIQUE KEY matricule (matricule)"); } catch (Throwable $t) {}

$row = null;
// 1) identification par matricule (défaut pour les étudiants)
if ($ident !== '') {
    $stmt = db()->prepare('SELECT * FROM users WHERE matricule = ? LIMIT 1');
    $stmt->execute([$ident]);
    $row = $stmt->fetch();
}
// 2) repli : email (casse insensible)
if (!$row) {
    $stmt = db()->prepare('SELECT * FROM users WHERE LOWER(email)=LOWER(?) LIMIT 1');
    $stmt->execute([$ident]);
    $row = $stmt->fetch();
}
if (!$row || !password_verify($pass, $row['password_hash'])) {
    flash('Matricule/email ou mot de passe incorrect.');
    header('Location: ../login.php');
    exit;
}
login_user($row);
header('Location: ' . home_by_role($row['role']));
exit;
