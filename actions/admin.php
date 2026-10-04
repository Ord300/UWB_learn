<?php
// Admin : filières + utilisateurs
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
$u = require_login(['admin']);
$kind = $_POST['kind'] ?? $_GET['kind'] ?? '';

if ($kind === 'filiere_delete' && isset($_GET['id'])) {
    db()->prepare('DELETE FROM filieres WHERE id=?')->execute([(int)$_GET['id']]);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../admin/filieres.php')); exit;
}
if ($kind === 'user_delete' && isset($_GET['id'])) {
    if ((int)$_GET['id'] !== (int)$u['id']) db()->prepare('DELETE FROM users WHERE id=?')->execute([(int)$_GET['id']]);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../admin/dashboard.php')); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $kind === 'filiere') {
    $id = (int)($_POST['id'] ?? 0);
    $nom = trim($_POST['nom'] ?? ''); $code = trim($_POST['code'] ?? ''); $desc = trim($_POST['description'] ?? '');
    if ($nom === '' || $code === '') { flash('Nom et code requis.'); header('Location: ../admin/filieres.php'); exit; }
    try {
        if ($id > 0) db()->prepare('UPDATE filieres SET nom=?,code=?,description=? WHERE id=?')->execute([$nom,$code,$desc,$id]);
        else db()->prepare('INSERT INTO filieres (nom,code,description) VALUES (?,?,?)')->execute([$nom,$code,$desc]);
        flash('Filière enregistrée.', 'success');
    } catch (PDOException $e) { flash('Code déjà utilisé.'); }
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../admin/filieres.php')); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $kind === 'role') {
    db()->prepare('UPDATE users SET role=? WHERE id=?')->execute([$_POST['role'] ?? 'etudiant', (int)($_POST['id'] ?? 0)]);
    header('Location: ../admin/dashboard.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $kind === 'user') {
    $nom = trim($_POST['nom'] ?? ''); $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'etudiant'; $pass = (string)($_POST['password'] ?? '1234');
    $fil = trim($_POST['filiere'] ?? '—');
    if (!in_array($role, ['etudiant','professeur','admin'], true)) $role = 'etudiant';
    if ($nom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('Nom/email invalides.'); header('Location: ../admin/dashboard.php'); exit; }
    try {
        db()->prepare('INSERT INTO users (nom,email,password_hash,role,filiere) VALUES (?,?,?,?,?)')
          ->execute([$nom,$email,password_hash($pass, PASSWORD_DEFAULT),$role,$fil]);
        flash('Compte créé.', 'success');
    } catch (PDOException $e) { flash('Email déjà utilisé.'); }
    header('Location: ../admin/dashboard.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $kind === 'profil') {
    $nom = trim($_POST['nom'] ?? $u['nom']); $fil = trim($_POST['filiere'] ?? $u['filiere']);
    db()->prepare('UPDATE users SET nom=?,filiere=? WHERE id=?')->execute([$nom,$fil,$u['id']]);
    $_SESSION['user']['nom'] = $nom; $_SESSION['user']['filiere'] = $fil;
    flash('Profil mis à jour.', 'success');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../etudiant/dashboard.php')); exit;
}
header('Location: ../admin/dashboard.php');
