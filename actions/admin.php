<?php
// Admin : facultés + utilisateurs + notifications
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
$kind = $_POST['kind'] ?? $_GET['kind'] ?? '';
// kind=profil : toute personne connectée peut mettre à jour son propre profil
$u = $kind === 'profil' ? require_login() : require_login(['admin']);

if (($kind === 'faculte_delete' || $kind === 'filiere_delete') && isset($_GET['id'])) {
    db()->prepare('DELETE FROM facultes WHERE id=?')->execute([(int)$_GET['id']]);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../admin/facultes.php')); exit;
}
if ($kind === 'user_delete' && isset($_GET['id'])) {
    if ((int)$_GET['id'] !== (int)$u['id']) db()->prepare('DELETE FROM users WHERE id=?')->execute([(int)$_GET['id']]);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../admin/dashboard.php')); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($kind === 'faculte' || $kind === 'filiere')) {
    $id = (int)($_POST['id'] ?? 0);
    $nom = trim($_POST['nom'] ?? ''); $code = trim($_POST['code'] ?? ''); $desc = trim($_POST['description'] ?? '');
    if ($nom === '' || $code === '') { flash('Nom et code requis.'); header('Location: ../admin/facultes.php'); exit; }
    try {
        if ($id > 0) db()->prepare('UPDATE facultes SET nom=?,code=?,description=? WHERE id=?')->execute([$nom,$code,$desc,$id]);
        else db()->prepare('INSERT INTO facultes (nom,code,description) VALUES (?,?,?)')->execute([$nom,$code,$desc]);
        flash('Faculté enregistrée.', 'success');
    } catch (PDOException $e) { flash('Code déjà utilisé.'); }
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../admin/facultes.php')); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $kind === 'role') {
    db()->prepare('UPDATE users SET role=? WHERE id=?')->execute([$_POST['role'] ?? 'etudiant', (int)($_POST['id'] ?? 0)]);
    header('Location: ../admin/dashboard.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $kind === 'user') {
    $nom = trim($_POST['nom'] ?? ''); $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'etudiant'; $pass = (string)($_POST['password'] ?? '1234');
    $fil = trim($_POST['faculte'] ?? ($_POST['filiere'] ?? '—'));
    if (!in_array($role, ['etudiant','professeur','admin'], true)) $role = 'etudiant';
    if ($nom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('Nom/email invalides.'); header('Location: ../admin/dashboard.php'); exit; }
    try {
        db()->prepare('INSERT INTO users (nom,email,password_hash,role,faculte) VALUES (?,?,?,?,?)')
          ->execute([$nom,$email,password_hash($pass, PASSWORD_DEFAULT),$role,$fil]);
        $newId = (int)db()->lastInsertId();
        flash('Compte créé.', 'success');
        // Notification : compte créé par l'administration
        require_once __DIR__ . '/../includes/notifications.php';
        $libRole = $role === 'professeur' ? 'Professeur' : ($role === 'admin' ? 'Administrateur' : 'Étudiant');
        notifier($newId, 'Votre compte ' . $libRole . ' est créé', 'Identifiant : ' . $email . ' — faculté : ' . $fil . '. Contactez l\'administration pour votre mot de passe.', $role === 'etudiant' ? 'etudiant/dashboard.php' : ($role === 'professeur' ? 'prof/dashboard.php' : 'admin/dashboard.php'));
    } catch (PDOException $e) { flash('Email déjà utilisé.'); }
    header('Location: ../admin/dashboard.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $kind === 'notif') {
    require_once __DIR__ . '/../includes/notifications.php';
    $titre = trim($_POST['titre'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $lien = trim($_POST['lien'] ?? '');
    $portee = $_POST['portee'] ?? 'tous';
    if ($titre === '') { flash('Titre requis.'); header('Location: ../admin/dashboard.php#notifs'); exit; }
    $role = null; $faculte = null;
    if ($portee === 'etudiants') $role = 'etudiant';
    elseif ($portee === 'professeurs') $role = 'professeur';
    elseif ($portee === 'admins') $role = 'admin';
    elseif (str_starts_with($portee, 'faculte:')) { $role = 'etudiant'; $faculte = substr($portee, 8); }
    $n = notifier_groupe($titre, $message, $lien, $role, $faculte);
    flash($n . ' notification(s) envoyée(s).', 'success');
    header('Location: ../admin/dashboard.php#notifs'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $kind === 'profil') {
    $nom = trim($_POST['nom'] ?? $u['nom']); $fil = trim($_POST['faculte'] ?? ($_POST['filiere'] ?? $u['faculte']));
    db()->prepare('UPDATE users SET nom=?,faculte=? WHERE id=?')->execute([$nom,$fil,$u['id']]);
    $_SESSION['user']['nom'] = $nom; $_SESSION['user']['faculte'] = $fil; $_SESSION['user']['filiere'] = $fil;
    flash('Profil mis à jour.', 'success');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../etudiant/dashboard.php')); exit;
}
header('Location: ../admin/dashboard.php');
