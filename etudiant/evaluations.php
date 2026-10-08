<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/eval_session.php';
$u = require_login();
if ($u['role'] === 'admin') { header('Location: ../admin/dashboard.php'); exit; }
$flash = flash();
evals_ensure_schema();
evals_finaliser_expirees(); // délai dépassé → copie soumise automatiquement
// Migration auto : colonnes ajoutées après la création initiale
try { if (!db()->query("SHOW COLUMNS FROM evaluations LIKE 'faculte'")->fetch()) db()->exec("ALTER TABLE evaluations ADD COLUMN faculte VARCHAR(150) NOT NULL DEFAULT 'Informatique'"); } catch (Throwable $t) {}
try { if (!db()->query("SHOW COLUMNS FROM evaluations LIKE 'niveau'")->fetch()) db()->exec("ALTER TABLE evaluations ADD COLUMN niveau VARCHAR(10) NOT NULL DEFAULT 'L3'"); } catch (Throwable $t) {}
// Étudiant : seulement les évaluations de sa faculté (« — » = toutes facultés)
$scope = '';
$sql = 'SELECT ev.*, c.titre AS cours_titre FROM evaluations ev LEFT JOIN cours c ON c.id=ev.cours_id WHERE 1=1'; $ep = [];
if ($u['role'] === 'etudiant') {
    $me = null;
    try { $s = db()->prepare('SELECT faculte FROM users WHERE id=?'); $s->execute([$u['id']]); $me = $s->fetch(); } catch (Throwable $t) {}
    $myFac = trim((string)($me['faculte'] ?? $u['faculte'] ?? $u['filiere'] ?? ''));
    if ($myFac !== '' && $myFac !== '—') {
        $sql .= " AND (ev.faculte=? OR ev.faculte='—' OR ev.faculte='')"; $ep[] = $myFac; $scope = $myFac;
    }
}
$sql .= ' ORDER BY ev.id';
$st = db()->prepare($sql); $st->execute($ep); $evals = $st->fetchAll();
$selId = (int)($_GET['eval'] ?? ($evals[0]['id'] ?? 0));
$ev = null; $questions = [];
foreach ($evals as $x) if ((int)$x['id'] === $selId) $ev = $x;
if ($ev) { $q = db()->prepare('SELECT * FROM questions WHERE evaluation_id=? ORDER BY id'); $q->execute([$ev['id']]); $questions = $q->fetchAll(); }
$totalPts = array_sum(array_column($questions, 'points'));
// Fenêtre d'accès définie par le professeur (horloge MySQL)
$ouvAt = null; $cloAt = null; $ouverte = true; $fermee = false;
if ($ev) {
    try {
        $f = db()->prepare('SELECT ouverture_at, cloture_at, (ouverture_at IS NULL OR ouverture_at <= NOW()) AS ouv, (cloture_at IS NOT NULL AND cloture_at <= NOW()) AS ferme FROM evaluations WHERE id=?');
        $f->execute([(int)$ev['id']]); $r = $f->fetch();
        if ($r) { $ouvAt = $r['ouverture_at']; $cloAt = $r['cloture_at']; $ouverte = (int)$r['ouv'] === 1; $fermee = (int)$r['ferme'] === 1; }
    } catch (Throwable $t) {}
}
// déjà soumise ?
$dej = null; $sess = null;
if ($ev && $u['role'] === 'etudiant') {
    $c = db()->prepare('SELECT * FROM soumissions WHERE evaluation_id=? AND etudiant_id=? LIMIT 1');
    $c->execute([$ev['id'], $u['id']]); $dej = $c->fetch();
    if (!$dej) $sess = eval_session_get((int)$ev['id'], (int)$u['id']);
}
// file validation (prof)
$att = [];
if ($u['role'] !== 'etudiant') {
    $sql = 'SELECT s.*, e.titre AS eval_titre FROM soumissions s JOIN evaluations e ON e.id=s.evaluation_id WHERE s.statut<>?';
    $p = ['validee'];
    if ($u['role'] !== 'admin') { $sql .= ' AND e.prof_nom=?'; $p[] = $u['nom']; }
    $sql .= ' ORDER BY s.id DESC';
    $s = db()->prepare($sql); $s->execute($p); $att = $s->fetchAll();
}
uwb_head('Évaluations & IA', 'Évaluations UWB.');
uwb_header_nav('evals', $u['role'] === 'professeur' ? nav_prof() : nav_etudiant());
?>
<main class="main">
<section class="section pt-4"><div class="container" data-aos="fade-up">
<div class="role-hero role-professeur" data-aos="fade-up" data-aos-delay="100">
<div class="d-flex align-items-center gap-3 flex-wrap">
<span class="role-avatar"><i class="bi bi-robot"></i></span>
<div><span class="hero-pill"><i class="bi bi-diagram-3"></i> Module 4 — Évaluations + IA</span>
<h2>Du sujet au résultat validé</h2>
<p class="mb-0">Répondez en ligne — l'IA corrige, le professeur valide.</p></div>
</div></div>
</div>
<section class="portfolio section light-background tight">
<div class="container" data-aos="fade-up">
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
<form method="get" class="search-pro mb-3">
<div class="pro-row"><div class="select-uwb pro-main"><i class="bi bi-journal-check"></i>
<select name="eval" onchange="this.form.submit()">
<?php foreach ($evals as $x): ?><option value="<?= (int)$x['id'] ?>" <?= $ev && $ev['id']==$x['id']?'selected':'' ?>><?= e($x['titre']) ?> — <?= e($x['prof_nom']) ?></option><?php endforeach; ?>
</select></div></div>
<?php if ($ev): ?><div class="pro-row pro-tags"><span>Détails :</span>
<span class="badge-uwb"><i class="bi bi-list-ol"></i> <?= count($questions) ?> questions</span>
<span class="badge-uwb"><i class="bi bi-123"></i> <?= $totalPts ?> pts</span>
<span class="badge-uwb"><i class="bi bi-stopwatch"></i> <?= (int)$ev['duree'] ?> min</span><?php if ($ouvAt): ?><span class="badge-uwb"><i class="bi bi-unlock"></i> Ouverture <?= e(dt_fr($ouvAt)) ?></span><?php endif; ?><?php if ($cloAt): ?><span class="badge-uwb"><i class="bi bi-lock"></i> Clôture <?= e(dt_fr($cloAt)) ?></span><?php endif; ?><?php if ($scope): ?><span class="badge-uwb"><i class="bi bi-funnel"></i> <?= e($scope) ?></span><?php endif; ?></div><?php endif; ?>
</form>
<?php if ($ev && $u['role'] === 'etudiant'): ?>
<?php if ($dej): ?>
<?php if ($dej['statut'] === 'triche'): ?>
<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <strong>Session interrompue</strong> — votre copie a été soumise automatiquement (sortie de page détectée). Elle est <strong>en attente de la validation du professeur</strong> : résultat et note dans <a href="resultats.php">Résultats</a>.</div>
<?php else: ?>
<div class="alert alert-info"><i class="bi bi-check-circle"></i> Copie soumise — <strong>en attente de la validation du professeur</strong>. Le résultat sera affiché et vous serez notifié(e) dans <a href="resultats.php">Résultats</a>.</div>
<?php endif; ?>
<?php elseif ($sess && $sess['etat'] === 'en_cours'): ?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
<span class="badge-uwb"><i class="bi bi-clock-history"></i> Début <strong><?= eval_heure($sess['started_at']) ?></strong> • Fin <strong><?= eval_heure($sess['deadline_at']) ?></strong></span>
<span class="small text-muted" id="saveState"><i class="bi bi-cloud-check"></i> Sauvegarde automatique active</span></div>
<div class="alert alert-danger py-2 small mb-3"><i class="bi bi-exclamation-triangle"></i> <strong>Attention :</strong> quitter cette page pendant la session soumet immédiatement votre copie (considéré comme une <strong>tentative de triche</strong> — note proposée 0/20, à valider par le professeur).</div>
<form method="post" action="../actions/submit_eval.php" id="examLive">
<input type="hidden" name="evaluation_id" value="<?= (int)$ev['id'] ?>">
<?php
$saved = json_decode((string)($sess['reponses'] ?? ''), true) ?: [];
$letters=['A','B','C','D','E','F']; foreach ($questions as $i => $qq): $choix = json_decode($qq['choix'] ?? '[]', true) ?: []; shuffle($choix); ?>
<div class="exam-qcard mb-3">
<div class="d-flex align-items-center gap-2 flex-wrap"><span class="exam-qnum"><?= $i+1 ?></span><strong class="flex-fill"><?= e($qq['enonce']) ?></strong>
<span class="badge-uwb"><i class="bi bi-123"></i> <?= (int)$qq['points'] ?> pts</span>
<?= $qq['type']==='qcm' ? '<span class="badge-uwb"><i class="bi bi-list-check"></i> QCM auto</span>' : '<span class="badge-ia"><i class="bi bi-robot"></i> Corrigée par IA</span>' ?></div>
<div class="mt-1">
<?php if ($qq['type'] === 'qcm'): foreach ($choix as $j => $ch): ?>
<label class="exam-opt"><input type="radio" name="<?= e($qq['qkey']) ?>" value="<?= e($ch) ?>" <?= ((string)($saved[$qq['qkey']] ?? '') === (string)$ch) ? 'checked' : '' ?> required><span class="opt-letter"><?= $letters[$j] ?? '•' ?></span><span><?= e($ch) ?></span></label>
<?php endforeach; else: ?>
<textarea name="<?= e($qq['qkey']) ?>" class="form-control mt-2" rows="3" required placeholder="Rédigez votre réponse avec vos propres mots…"><?= e($saved[$qq['qkey']] ?? '') ?></textarea>
<?php endif; ?></div></div>
<?php endforeach; ?>
<div class="exam-bar"><strong class="small"><?= count($questions) ?> question(s) • <?= $totalPts ?> pts</strong>
<button class="btn-uwb-gold" onclick="window.__submitting=true;return confirm('Envoyer définitivement votre copie ?')"><i class="bi bi-send"></i> Soumettre ma copie</button></div>
</form>
<script>
window.__evalId = <?= (int)$ev['id'] ?>;
function evalSerialiser(){ const o={}; new FormData(document.getElementById('examLive')).forEach((v,k)=>{ if(k!=='evaluation_id') o[k]=v; }); return o; }
let __t=null, __saving=false;
async function evalSave(){
  if (__saving) return; __saving=true;
  try {
    const fd=new FormData();
    fd.append('action','save');
    fd.append('evaluation_id', String(window.__evalId));
    fd.append('reponses', JSON.stringify(evalSerialiser()));
    const r=await fetch('../actions/eval_session.php',{method:'POST',body:fd,credentials:'same-origin'});
    const j=await r.json();
    document.getElementById('saveState').innerHTML = j.ok
      ? '<i class="bi bi-cloud-check"></i> Réponses enregistrées à '+String(j.at).slice(0,5)
      : '<i class="bi bi-exclamation-triangle"></i> Session expirée';
  } catch(e){}
  __saving=false;
}
setInterval(evalSave, 15000);
document.getElementById('examLive').addEventListener('input', ()=>{ clearTimeout(__t); __t=setTimeout(evalSave, 2000); });
// Sortie de page (fermeture, navigation, rafraîchissement) = copie soumise automatiquement (triche)
window.addEventListener('pagehide', ()=>{
  if (window.__submitting) return;
  try {
    const fd=new FormData();
    fd.append('action','leave');
    fd.append('evaluation_id', String(window.__evalId));
    fd.append('reponses', JSON.stringify(evalSerialiser()));
    navigator.sendBeacon('../actions/eval_session.php', fd);
  } catch(e) {}
});
</script>
<?php elseif ($fermee): ?>
<div class="alert alert-danger"><i class="bi bi-lock-fill"></i> <strong>Évaluation clôturée</strong> — la fenêtre d'accès fixée par le professeur s'est terminée le <strong><?= e(dt_fr($cloAt)) ?></strong>. Il n'est plus possible d'accéder au sujet. Contactez votre professeur pour toute question.</div>
<?php elseif (!$ouverte): ?>
<div class="alert alert-info"><i class="bi bi-clock-history"></i> <strong>Pas encore ouverte</strong> — cette évaluation sera accessible à partir du <strong><?= e(dt_fr($ouvAt)) ?></strong> (clôture <?= e(dt_fr($cloAt)) ?>). Revenez à ce moment-là pour commencer.</div>
<?php else: ?>
<div class="alert alert-warning">
<strong><i class="bi bi-play-circle"></i> Prêt à commencer ?</strong>
<ul class="mb-0 mt-2">
<?php if ($ouvAt || $cloAt): ?><li>Fenêtre d'accès : <strong><?= $ouvAt ? e(dt_fr($ouvAt)) : 'immédiat' ?></strong> → <strong><?= $cloAt ? e(dt_fr($cloAt)) : 'sans limite' ?></strong> — après la clôture, plus aucun accès.</li><?php endif; ?>
<li>Délai autorisé : <strong><?= (int)$ev['duree'] ?> min</strong> — la session démarre à votre clic et se termine automatiquement à l'heure de fin (jamais au-delà de la clôture).</li>
<li>Pas de compte à rebours : l'heure de <strong>début</strong> et l'heure de <strong>fin</strong> vous sont affichées pendant toute la session.</li>
<li>Vos réponses sont sauvegardées automatiquement.</li>
<li class="text-danger">⚠ Quitter la page pendant la session soumet votre copie immédiatement (tentative de triche).</li>
</ul>
<form method="post" action="../actions/eval_session.php" class="mt-2">
<input type="hidden" name="action" value="start">
<input type="hidden" name="evaluation_id" value="<?= (int)$ev['id'] ?>">
<button class="btn-uwb-gold"><i class="bi bi-play-fill"></i> Commencer l'évaluation</button>
</form></div>
<?php endif; ?>
<?php elseif ($ev): ?>
<div class="alert alert-secondary">Sujet : <strong><?= e($ev['titre']) ?></strong> — <?= count($questions) ?> questions, <?= $totalPts ?> pts, <?= (int)$ev['duree'] ?> min. Les étudiants répondent ici ; validez dans <a href="../prof/validation-ia.php">Validation IA</a>.</div>
<?php else: ?><p class="text-muted"><?= $scope ? 'Aucun sujet pour votre faculté pour le moment.' : 'Aucun sujet publié.' ?></p><?php endif; ?>
</div></section>
<?php if ($u['role'] !== 'etudiant'): ?>
<section class="why-us section"><div class="container section-title" data-aos="fade-up"><h2><i class="bi bi-check2-circle"></i> Vérification professeur</h2><p><?= count($att) ?> copie(s) en attente</p></div>
<div class="container">
<?php foreach ($att as $x): $triche = $x['statut'] === 'triche'; ?>
<div class="uwb-list-item mb-2"><strong><?= e($x['etudiant_nom']) ?></strong> — <?= e($x['eval_titre']) ?>
<?php if ($triche): ?> <span class="badge-ia" style="background:#dc2626;color:#fff"><i class="bi bi-exclamation-triangle"></i> Triche</span><?php endif; ?>
<span class="badge-ia">IA : <?= (int)$x['note_ia'] ?>/20</span><?= ia_ligne_alertes($x) ?>
<pre class="small mt-2 mb-2" style="white-space:pre-wrap"><?= e($x['feedback']) ?></pre>
<form method="post" action="../actions/validate.php" class="row g-2"><input type="hidden" name="id" value="<?= (int)$x['id'] ?>">
<div class="col-md-2"><label class="form-label">Note finale</label><input name="note_finale" type="number" min="0" max="20" value="<?= $triche ? 0 : (int)$x['note_ia'] ?>" class="form-control"></div>
<div class="col-md-7"><label class="form-label">Commentaire</label><input name="commentaire" class="form-control" value="Validé après contrôle."></div>
<div class="col-md-3 d-flex align-items-end"><button class="btn-uwb w-100"><i class="bi bi-check2-circle"></i> Valider</button></div></form></div>
<?php endforeach; if (!count($att)): ?><p class="text-muted text-center">Rien à valider.</p><?php endif; ?>
</div></section>
<?php endif; ?>
</main>
<?php uwb_footer('UWB E-learning — Évaluations & IA.'); ?>
