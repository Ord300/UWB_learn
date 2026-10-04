<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../inscription.php'); exit; }
$nom = trim($_POST['nom'] ?? '');
$email = trim($_POST['email'] ?? '');
$pass = (string)($_POST['password'] ?? $_POST['pass'] ?? '');
$role = $_POST['role'] ?? 'etudiant';
$filiere = trim($_POST['filiere'] ?? '—') ?: '—';
$niveau = trim($_POST['niveau'] ?? 'L3') ?: 'L3';
if (!in_array($niveau, ['L1','L2','L3','M1','M2','Autre'], true)) $niveau = 'L3';
if (!in_array($role, ['etudiant','professeur'], true)) $role = 'etudiant';
if ($nom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 4) {
    flash('Vérifiez les champs : nom, email valide, mot de passe (min. 4 caractères).');
    header('Location: ../inscription.php'); exit;
}
$chk = db()->prepare('SELECT id FROM users WHERE LOWER(email)=LOWER(?) LIMIT 1');
$chk->execute([$email]);
if ($chk->fetch()) { flash('Cet email est déjà utilisé. Connectez-vous.'); header('Location: ../inscription.php'); exit; }

// Migration auto : colonne niveau si base créée avant son ajout
if (!db()->query("SHOW COLUMNS FROM users LIKE 'niveau'")->fetch()) {
    db()->exec("ALTER TABLE users ADD COLUMN niveau VARCHAR(10) NOT NULL DEFAULT 'L3'");
}
$ins = db()->prepare('INSERT INTO users (nom,email,password_hash,role,filiere,niveau) VALUES (?,?,?,?,?,?)');
$ins->execute([$nom, $email, password_hash($pass, PASSWORD_DEFAULT), $role, $filiere, $niveau]);
$id = (int)db()->lastInsertId();
login_user(['id'=>$id,'nom'=>$nom,'email'=>$email,'role'=>$role,'filiere'=>$filiere,'niveau'=>$niveau]);
header('Location: ' . home_by_role($role));
exit;
