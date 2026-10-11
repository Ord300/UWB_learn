<?php
// Validation professeur : note finale + commentaire -> statut validee
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
$u = require_login(['professeur','admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../prof/validation-ia.php'); exit; }
$id = (int)($_POST['id'] ?? 0);
$note = $_POST['note_finale'] ?? '';
$note = ($note === '' || $note === null) ? (int)$row['note_ia'] : max(0, min(20, (int)$note));
$com = trim($_POST['commentaire'] ?? 'Validé après contrôle.');
$s = db()->prepare('SELECT * FROM soumissions WHERE id=?'); $s->execute([$id]); $row = $s->fetch();
if (!$row) { header('Location: ../prof/validation-ia.php'); exit; }
if ($u['role'] !== 'admin') {
    $e = db()->prepare('SELECT prof_nom, faculte FROM evaluations WHERE id=?'); $e->execute([$row['evaluation_id']]);
    $ev = $e->fetch();
    $ok = $ev && $ev['prof_nom'] === $u['nom'];
    $myfac = my_faculte();
    if ($ok && $myfac !== '' && fac_key($ev['faculte']) !== '' && fac_key($ev['faculte']) !== $myfac) $ok = false;
    if (!$ok) { flash('Vous ne pouvez valider que vos sujets, dans votre faculté.'); header('Location: ../prof/validation-ia.php'); exit; }
}
db()->prepare("UPDATE soumissions SET note_finale=?, commentaire_prof=?, statut='validee' WHERE id=?")->execute([$note, $com, $id]);
// Notification : l'étudiant apprend sa note finale
if (!empty($row['etudiant_id'])) {
    require_once __DIR__ . '/../includes/notifications.php';
    $titreEval = '';
    try { $e2 = db()->prepare('SELECT titre FROM evaluations WHERE id=?'); $e2->execute([$row['evaluation_id']]); $r2 = $e2->fetch(); $titreEval = $r2['titre'] ?? ''; } catch (Throwable $t) {}
    notifier((int)$row['etudiant_id'], 'Copie validée : ' . $note . '/20', 'Évaluation « ' . $titreEval . ' » — commentaire : ' . $com, 'etudiant/resultats.php');
}
flash('Copie validée : ' . $note . '/20.', 'success');
header('Location: ../prof/validation-ia.php');
exit;
