<?php
// Soumission de copie (fin de session volontaire) -> correction IA réelle
// -> statut proposee-IA, en attente de validation du professeur
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/eval_session.php';
require_once __DIR__ . '/../includes/ia.php';
$u = require_login(['etudiant']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../etudiant/evaluations.php'); exit; }
evals_ensure_schema();
$evalId = (int)($_POST['evaluation_id'] ?? 0);
$ev = db()->prepare('SELECT * FROM evaluations WHERE id=?'); $ev->execute([$evalId]); $eval = $ev->fetch();
if (!$eval) { flash('Sujet introuvable.'); header('Location: ../etudiant/evaluations.php'); exit; }
// Garde-fou périmètre : faculté de l'étudiant (frais depuis la base)
try {
    $s = db()->prepare('SELECT faculte FROM users WHERE id=?'); $s->execute([$u['id']]); $me = $s->fetch();
    $myFac = trim((string)($me['faculte'] ?? $u['faculte'] ?? $u['filiere'] ?? ''));
    if ($myFac !== '' && $myFac !== '—' && ($eval['faculte'] ?? '') !== '' && ($eval['faculte'] ?? '—') !== $myFac) {
        flash('Ce sujet ne concerne pas votre faculté.'); header('Location: ../etudiant/evaluations.php'); exit;
    }
} catch (Throwable $t) {}
// Tentatives épuisées ? (aucune session en cours ne doit être acceptée au-delà du quota)
$enCours = eval_session_get($evalId, (int)$u['id']);
$reprise = $enCours && $enCours['etat'] === 'en_cours';
if (!$reprise && eval_tentatives_used($evalId, (int)$u['id']) >= eval_tentatives_max($evalId)) {
    flash('Nombre maximal de tentatives atteint pour ce sujet.');
    header('Location: ../etudiant/evaluations.php?eval=' . $evalId); exit;
}
// Session obligatoire : démarée depuis la page d'évaluation
$sess = $enCours;
if (!$sess || $sess['etat'] !== 'en_cours') {
    flash('Session non démarrée : cliquez d’abord sur « Commencer l’évaluation ».');
    header('Location: ../etudiant/evaluations.php?eval=' . $evalId);
    exit;
}
// Garde-fou fenêtre : clôture de l'évaluation dépassée → plus de soumission
try {
    $w = db()->prepare('SELECT (cloture_at IS NOT NULL AND cloture_at < NOW()) AS ferme, cloture_at FROM evaluations WHERE id=?');
    $w->execute([$evalId]); $fen = $w->fetch();
    if ($fen && (int)$fen['ferme'] === 1) {
        flash('Évaluation clôturée le ' . dt_fr($fen['cloture_at']) . ' : soumission impossible.');
        header('Location: ../etudiant/evaluations.php?eval=' . $evalId); exit;
    }
} catch (Throwable $t) {}
evals_finaliser_expirees((int)$u['id']);
// Réponses
$questions = db()->prepare('SELECT * FROM questions WHERE evaluation_id=? ORDER BY id');
$questions->execute([$evalId]); $questions = $questions->fetchAll();
$reponses = [];
foreach ($questions as $qq) $reponses[$qq['qkey']] = trim((string)($_POST[$qq['qkey']] ?? ''));
// Fin de délai (comparaison SQL : même horloge que MySQL) : tolérance 60 s
$late = db()->prepare('SELECT deadline_at < DATE_SUB(NOW(), INTERVAL 60 SECOND) AS late FROM eval_sessions WHERE id=?');
$late->execute([(int)$sess['id']]);
$isLate = (int)($late->fetch()['late'] ?? 0) === 1;
$type = $isLate ? 'expiree' : 'soumise';
eval_session_finaliser($sess, $type, $reponses, $isLate ? 'Soumission au-delà du délai autorisé' : '');
flash('Copie reçue ! En attente de la validation du professeur — vous serez notifié(e) de votre note dans Résultats.', 'success');
header('Location: ../etudiant/resultats.php');
exit;
