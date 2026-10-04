<?php
// Validation professeur : note finale + commentaire -> statut validee
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
$u = require_login(['professeur','admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../prof/validation-ia.php'); exit; }
$id = (int)($_POST['id'] ?? 0);
$note = max(0, min(20, (int)($_POST['note_finale'] ?? 0)));
$com = trim($_POST['commentaire'] ?? 'Validé après contrôle.');
$s = db()->prepare('SELECT * FROM soumissions WHERE id=?'); $s->execute([$id]); $row = $s->fetch();
if (!$row) { header('Location: ../prof/validation-ia.php'); exit; }
if ($u['role'] !== 'admin') {
    $e = db()->prepare('SELECT prof_nom FROM evaluations WHERE id=?'); $e->execute([$row['evaluation_id']]);
    $ev = $e->fetch();
    if (!$ev || $ev['prof_nom'] !== $u['nom']) { flash('Vous ne pouvez valider que vos sujets.'); header('Location: ../prof/validation-ia.php'); exit; }
}
db()->prepare("UPDATE soumissions SET note_finale=?, commentaire_prof=?, statut='validee' WHERE id=?")->execute([$note, $com, $id]);
flash('Copie validée : ' . $note . '/20.', 'success');
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../prof/validation-ia.php'));
exit;
