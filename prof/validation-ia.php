<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_login(['professeur']);
$flash = flash();
$fNom = trim($_GET['nom'] ?? ''); $fEval = (int)($_GET['eval'] ?? 0); $fDate = trim($_GET['date'] ?? '');
$sql = 'SELECT s.*, e.titre AS eval_titre FROM soumissions s JOIN evaluations e ON e.id=s.evaluation_id WHERE e.prof_nom=?';
$p = [$u['nom']];
if ($fNom !== '') { $sql .= ' AND s.etudiant_nom LIKE ?'; $p[] = "%$fNom%"; }
if ($fEval) { $sql .= ' AND s.evaluation_id=?'; $p[] = $fEval; }
if ($fDate !== '') { $sql .= ' AND s.date_soumission=?'; $p[] = $fDate; }
$sql .= ' ORDER BY s.id DESC';
$st = db()->prepare($sql); $st->execute($p); $rows = $st->fetchAll();
$att = array_filter($rows, fn($x) => $x['statut'] === 'proposee-IA');
$done = array_filter($rows, fn($x) => $x['statut'] === 'validee');
$myEvals = db()->prepare('SELECT * FROM evaluations WHERE prof_nom=?'); $myEvals->execute([$u['nom']]); $myEvals = $myEvals->fetchAll();
uwb_head('File de validation IA', 'Validation copies.');
uwb_header_nav('valid', nav_prof());
?>
<main class="main">
<section class="section pt-4"><div class="container" data-aos="fade-up">
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
<div class="role-hero role-professeur" data-aos="fade-up" data-aos-delay="100">
<div class="d-flex align-items-center gap-3 flex-wrap">
<span class="role-avatar"><i class="bi bi-person-check-fill"></i></span>
<div><span class="hero-pill"><i class="bi bi-robot"></i> Validation finale : vous décidez</span>
<h2>File de validation IA</h2>
<p class="mb-0">L'IA propose la note et le feedback — vérifiez, ajustez, commentez.</p></div>
</div></div>
<div class="stats-grid cols-3 mt-4" data-aos="fade-up" data-aos-delay="150">
<div class="stat-card stat-card-accent"><div class="stat-icon-wrap"><i class="bi bi-hourglass-split"></i></div><div class="stat-info"><span class="stat-value"><?= count($att) ?></span><span class="stat-title">En attente</span></div></div>
<div class="stat-card stat-card-primary"><div class="stat-icon-wrap"><i class="bi bi-check2-all"></i></div><div class="stat-info"><span class="stat-value"><?= count($done) ?></span><span class="stat-title">Validées</span></div></div>
<div class="stat-card"><div class="stat-icon-wrap"><i class="bi bi-robot"></i></div><div class="stat-info"><span class="stat-value"><?= count($rows) ?></span><span class="stat-title">Total mes copies</span></div></div>
</div>
</div></section>
<section class="portfolio section light-background">
<div class="container"><form method="get" class="search-pro mb-4" data-aos="fade-up">
<div class="pro-row">
<div class="pro-input"><i class="bi bi-search"></i><input name="nom" value="<?= e($fNom) ?>" placeholder="Nom de l'étudiant…"></div>
<div class="select-uwb pro-select"><i class="bi bi-file-earmark-text"></i><select name="eval" onchange="this.form.submit()"><option value="0">Tous les sujets</option><?php foreach ($myEvals as $ev2): ?><option value="<?= (int)$ev2['id'] ?>" <?= $fEval===$ev2['id']?'selected':'' ?>><?= e($ev2['titre']) ?></option><?php endforeach; ?></select></div>
<div class="pro-input" style="flex:1 1 200px"><i class="bi bi-calendar"></i><input name="date" type="date" value="<?= e($fDate) ?>" onchange="this.form.submit()"></div>
</div>
<div class="pro-row pro-tags"><span><?= count($rows) ?> rapport(s) trouvé(s)</span><a class="pro-tag text-decoration-none" href="validation-ia.php">Tout afficher</a><button class="pro-tag">Filtrer</button></div>
</form>
<div class="panel-pro" data-aos="fade-up"><div class="panel-head"><strong><i class="bi bi-hourglass-split"></i> Copies en attente</strong><small><?= count($att) ?> copie(s)</small></div>
<?php foreach ($att as $x): ?>
<div class="mini-row" style="flex-wrap:wrap;align-items:flex-start"><div style="min-width:0;flex:1"><strong><?= e($x['etudiant_nom']) ?></strong> <span class="text-muted">— <?= e($x['eval_titre']) ?></span><br><small><?= e($x['date_soumission']) ?> • Proposition IA : <?= (int)$x['note_ia'] ?>/20</small></div>
<div class="w-100"><details class="copy-details mt-1"><summary>Voir le rapport IA</summary>
<pre class="small p-2" style="white-space:pre-wrap"><?= e($x['feedback']) ?></pre>
<form method="post" action="../actions/validate.php" class="row g-3 form-min mt-2"><input type="hidden" name="id" value="<?= (int)$x['id'] ?>">
<div class="col-md-2"><label class="form-label">Note /20</label><input name="note_finale" type="number" min="0" max="20" value="<?= (int)$x['note_ia'] ?>" class="form-control"></div>
<div class="col-md-7"><label class="form-label">Commentaire</label><input name="commentaire" class="form-control" value="Validé après contrôle."></div>
<div class="col-md-3 d-flex align-items-end"><button class="btn-uwb w-100"><i class="bi bi-check2-circle"></i> Valider</button></div></form></details></div></div>
<?php endforeach; if (!count($att)): ?><p class="text-muted small mb-0">File vide.</p><?php endif; ?>
</div></div></section>
<section class="portfolio section light-background"><div class="container"><div class="panel-pro" data-aos="fade-up"><div class="panel-head"><strong><i class="bi bi-check2-all"></i> Copies validées</strong><small><?= count($done) ?> copie(s)</small></div>
<?php foreach (array_reverse($done) as $x): ?>
<div class="mini-row" style="flex-wrap:wrap"><div style="min-width:0;flex:1"><strong><?= e($x['etudiant_nom']) ?></strong><br><small><?= e($x['eval_titre']) ?> • <?= e($x['date_soumission']) ?> • Finale : <?= (int)$x['note_finale'] ?>/20 (IA : <?= (int)$x['note_ia'] ?>)</small></div></div>
<?php endforeach; if (!count($done)): ?><p class="text-muted small mb-0">Aucune copie validée.</p><?php endif; ?>
</div></div></section>
</main>
<?php uwb_footer('UWB E-learning — Validation IA.'); ?>
