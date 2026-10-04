<?php
// Soumission copie étudiant -> correction IA (même algo que JS) -> statut proposee-IA
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
$u = require_login(['etudiant']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../etudiant/evaluations.php'); exit; }
$evalId = (int)($_POST['evaluation_id'] ?? 0);
$ev = db()->prepare('SELECT * FROM evaluations WHERE id=?'); $ev->execute([$evalId]); $eval = $ev->fetch();
if (!$eval) { flash('Sujet introuvable.'); header('Location: ../etudiant/evaluations.php'); exit; }
// Garde-fou périmètre : filière + niveau de l'étudiant (frais depuis la base)
try {
    $s = db()->prepare('SELECT filiere,niveau FROM users WHERE id=?'); $s->execute([$u['id']]); $me = $s->fetch();
    $myFil = trim((string)($me['filiere'] ?? $u['filiere'] ?? ''));
    $myNiv = trim((string)($me['niveau'] ?? $u['niveau'] ?? ''));
    if ($myFil !== '' && $myFil !== '—' && ($eval['filiere'] ?? '') !== '' && ($eval['filiere'] ?? '—') !== $myFil) {
        flash('Ce sujet ne concerne pas votre filière.'); header('Location: ../etudiant/evaluations.php'); exit;
    }
    if ($myNiv !== '' && !in_array($myNiv, ['—','Autre'], true) && ($eval['niveau'] ?? '') !== '' && ($eval['niveau'] ?? '') !== $myNiv) {
        flash('Ce sujet ne concerne pas votre niveau.'); header('Location: ../etudiant/evaluations.php'); exit;
    }
} catch (Throwable $t) {}
// Déjà rendue ?
$chk = db()->prepare('SELECT id FROM soumissions WHERE evaluation_id=? AND etudiant_id=? LIMIT 1');
$chk->execute([$evalId, $u['id']]);
if ($chk->fetch()) { flash('Copie déjà soumise pour ce sujet.'); header('Location: ../etudiant/resultats.php'); exit; }

$q = db()->prepare('SELECT * FROM questions WHERE evaluation_id=? ORDER BY id');
$q->execute([$evalId]); $questions = $q->fetchAll();
$reponses = [];
foreach ($questions as $qq) $reponses[$qq['qkey']] = trim((string)($_POST[$qq['qkey']] ?? ''));
// adapte au format ia_corriger
$qs = array_map(fn($r) => ['qkey'=>$r['qkey'],'type'=>$r['type'],'bonne'=>$r['bonne'],'mots_cles'=>$r['mots_cles'],'points'=>(int)$r['points']], $questions);
$res = ia_corriger($qs, $reponses);
$ins = db()->prepare("INSERT INTO soumissions (evaluation_id,etudiant_id,etudiant_nom,email,reponses,note_ia,note_finale,feedback,details,statut) VALUES (?,?,?,?,?,?,?,?,?,'proposee-IA')");
$ins->execute([$evalId, $u['id'], $u['nom'], $u['email'], json_encode($reponses, JSON_UNESCAPED_UNICODE), $res['noteIA'], null, $res['feedback'], json_encode($res['details'], JSON_UNESCAPED_UNICODE)]);
flash('Soumission envoyée ! IA propose : ' . $res['noteIA'] . '/20. En attente du professeur.', 'success');
header('Location: ../etudiant/resultats.php');
exit;
