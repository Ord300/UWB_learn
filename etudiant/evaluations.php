<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_login();
if ($u['role'] === 'admin') { header('Location: ../admin/dashboard.php'); exit; }
$flash = flash();
// Migration auto : colonnes ajoutées après la création initiale
try { if (!db()->query("SHOW COLUMNS FROM evaluations LIKE 'filiere'")->fetch()) db()->exec("ALTER TABLE evaluations ADD COLUMN filiere VARCHAR(100) NOT NULL DEFAULT 'Informatique'"); } catch (Throwable $t) {}
try { if (!db()->query("SHOW COLUMNS FROM evaluations LIKE 'niveau'")->fetch()) db()->exec("ALTER TABLE evaluations ADD COLUMN niveau VARCHAR(10) NOT NULL DEFAULT 'L3'"); } catch (Throwable $t) {}
// Étudiant : seulement les évaluations de sa filière + niveau (frais depuis la base)
$scope = '';
$sql = 'SELECT ev.*, c.titre AS cours_titre FROM evaluations ev LEFT JOIN cours c ON c.id=ev.cours_id WHERE 1=1'; $ep = [];
if ($u['role'] === 'etudiant') {
    $me = null;
    try { $s = db()->prepare('SELECT filiere,niveau FROM users WHERE id=?'); $s->execute([$u['id']]); $me = $s->fetch(); } catch (Throwable $t) {}
    $myFil = trim((string)($me['filiere'] ?? $u['filiere'] ?? ''));
    $myNiv = trim((string)($me['niveau'] ?? $u['niveau'] ?? ''));
    if ($myFil !== '' && $myFil !== '—') {
        $sql .= ' AND ev.filiere=?'; $ep[] = $myFil; $scope = $myFil;
        if ($myNiv !== '' && $myNiv !== '—' && $myNiv !== 'Autre') { $sql .= ' AND ev.niveau=?'; $ep[] = $myNiv; $scope .= ' • ' . $myNiv; }
    }
}
$sql .= ' ORDER BY ev.id';
$st = db()->prepare($sql); $st->execute($ep); $evals = $st->fetchAll();
$selId = (int)($_GET['eval'] ?? ($evals[0]['id'] ?? 0));
$ev = null; $questions = [];
foreach ($evals as $x) if ((int)$x['id'] === $selId) $ev = $x;
if ($ev) { $q = db()->prepare('SELECT * FROM questions WHERE evaluation_id=? ORDER BY id'); $q->execute([$ev['id']]); $questions = $q->fetchAll(); }
$totalPts = array_sum(array_column($questions, 'points'));
// déjà soumise ?
$dej = null;
if ($ev && $u['role'] === 'etudiant') {
    $c = db()->prepare('SELECT * FROM soumissions WHERE evaluation_id=? AND etudiant_id=? LIMIT 1');
    $c->execute([$ev['id'], $u['id']]); $dej = $c->fetch();
}
// file validation (prof)
$att = [];
if ($u['role'] !== 'etudiant') {
    $sql = 'SELECT s.*, e.titre AS eval_titre FROM soumissions s JOIN evaluations e ON e.id=s.evaluation_id WHERE s.statut=?';
    $p = ['proposee-IA'];
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
<span class="badge-uwb"><i class="bi bi-stopwatch"></i> <?= (int)$ev['duree'] ?> min</span><?php if ($scope): ?><span class="badge-uwb"><i class="bi bi-funnel"></i> <?= e($scope) ?></span><?php endif; ?></div><?php endif; ?>
</form>
<?php if ($ev && $u['role'] === 'etudiant'): ?>
<?php if ($dej): ?>
<div class="alert alert-info"><i class="bi bi-check-circle"></i> Copie déjà soumise — IA : <strong><?= (int)$dej['note_ia'] ?>/20</strong>. Suivez la validation dans <a href="resultats.php">Résultats</a>.</div>
<?php else: ?>
<form method="post" action="../actions/submit_eval.php" id="examLive">
<input type="hidden" name="evaluation_id" value="<?= (int)$ev['id'] ?>">
<?php $letters=['A','B','C','D','E','F']; foreach ($questions as $i => $qq): $choix = json_decode($qq['choix'] ?? '[]', true) ?: []; shuffle($choix); ?>
<div class="exam-qcard mb-3">
<div class="d-flex align-items-center gap-2 flex-wrap"><span class="exam-qnum"><?= $i+1 ?></span><strong class="flex-fill"><?= e($qq['enonce']) ?></strong>
<span class="badge-uwb"><i class="bi bi-123"></i> <?= (int)$qq['points'] ?> pts</span>
<?= $qq['type']==='qcm' ? '<span class="badge-uwb"><i class="bi bi-list-check"></i> QCM auto</span>' : '<span class="badge-ia"><i class="bi bi-robot"></i> Corrigée par IA</span>' ?></div>
<div class="mt-1">
<?php if ($qq['type'] === 'qcm'): foreach ($choix as $j => $ch): ?>
<label class="exam-opt"><input type="radio" name="<?= e($qq['qkey']) ?>" value="<?= e($ch) ?>" required><span class="opt-letter"><?= $letters[$j] ?? '•' ?></span><span><?= e($ch) ?></span></label>
<?php endforeach; else: ?>
<textarea name="<?= e($qq['qkey']) ?>" class="form-control mt-2" rows="3" required placeholder="Rédigez votre réponse avec vos propres mots…"></textarea>
<?php endif; ?></div></div>
<?php endforeach; ?>
<div class="exam-bar"><strong class="small"><?= count($questions) ?> question(s) • <?= $totalPts ?> pts</strong>
<button class="btn-uwb-gold" onclick="return confirm('Envoyer définitivement pour correction IA ?')"><i class="bi bi-robot"></i> Soumettre → correction IA</button></div>
</form>
<?php endif; ?>
<?php elseif ($ev): ?>
<div class="alert alert-secondary">Sujet : <strong><?= e($ev['titre']) ?></strong> — <?= count($questions) ?> questions, <?= $totalPts ?> pts, <?= (int)$ev['duree'] ?> min. Les étudiants répondent ici ; validez dans <a href="../prof/validation-ia.php">Validation IA</a>.</div>
<?php else: ?><p class="text-muted"><?= $scope ? 'Aucun sujet pour votre filière / niveau pour le moment.' : 'Aucun sujet publié.' ?></p><?php endif; ?>
</div></section>
<?php if ($u['role'] !== 'etudiant'): ?>
<section class="why-us section"><div class="container section-title" data-aos="fade-up"><h2><i class="bi bi-check2-circle"></i> Vérification professeur</h2><p><?= count($att) ?> copie(s) en attente</p></div>
<div class="container">
<?php foreach ($att as $x): $det = json_decode($x['details'] ?? '[]', true) ?: []; ?>
<div class="uwb-list-item mb-2"><strong><?= e($x['etudiant_nom']) ?></strong> — <?= e($x['eval_titre']) ?> — <span class="badge-ia">IA : <?= (int)$x['note_ia'] ?>/20</span>
<pre class="small mt-2 mb-2" style="white-space:pre-wrap"><?= e($x['feedback']) ?></pre>
<form method="post" action="../actions/validate.php" class="row g-2"><input type="hidden" name="id" value="<?= (int)$x['id'] ?>">
<div class="col-md-2"><label class="form-label">Note finale</label><input name="note_finale" type="number" min="0" max="20" value="<?= (int)$x['note_ia'] ?>" class="form-control"></div>
<div class="col-md-7"><label class="form-label">Commentaire</label><input name="commentaire" class="form-control" value="Validé après contrôle."></div>
<div class="col-md-3 d-flex align-items-end"><button class="btn-uwb w-100"><i class="bi bi-check2-circle"></i> Valider</button></div></form></div>
<?php endforeach; if (!count($att)): ?><p class="text-muted text-center">Rien à valider.</p><?php endif; ?>
</div></section>
<?php endif; ?>
</main>
<?php uwb_footer('UWB E-learning — Évaluations & IA.'); ?>
