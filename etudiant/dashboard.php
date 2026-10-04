<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_login(['etudiant']);
$flash = flash();
// KPIs
$nbCours = (int)db()->query('SELECT COUNT(*) c FROM cours')->fetch()['c'];
$mesSoum = db()->prepare('SELECT s.*, e.titre FROM soumissions s LEFT JOIN evaluations e ON e.id=s.evaluation_id WHERE s.etudiant_id=? ORDER BY s.id DESC');
$mesSoum->execute([$u['id']]); $mesSoum = $mesSoum->fetchAll();
$vals = array_filter($mesSoum, fn($x) => $x['statut'] === 'validee');
$moy = count($vals) ? number_format(array_sum(array_map(fn($x) => $x['note_finale'] ?? $x['note_ia'], $vals)) / count($vals), 1) : '—';
$evals = db()->query('SELECT * FROM evaluations ORDER BY id')->fetchAll();
$idsSoumis = array_column($mesSoum, 'evaluation_id');
$aFaire = array_filter($evals, fn($e) => !in_array($e['id'], $idsSoumis));
$meets = db()->query('SELECT * FROM cours ORDER BY id LIMIT 3')->fetchAll();
$livres = db()->query('SELECT * FROM livres ORDER BY telechargements DESC')->fetchAll();
$sug = ia_suggestions($livres, $u['filiere'], 'Informatique', [$u['filiere']]);
uwb_head('Mon espace étudiant', 'Espace étudiant UWB.');
uwb_header_nav('espace', nav_etudiant());
?>
<main class="main">
<section class="section pt-4"><div class="container" data-aos="fade-up">
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
<div class="role-hero role-etudiant" data-aos="fade-up" data-aos-delay="100">
<div class="d-flex align-items-center gap-3 flex-wrap">
<span class="role-avatar"><i class="bi bi-mortarboard-fill"></i></span>
<div><span class="hero-pill"><i class="bi bi-person-badge"></i> Espace étudiant</span>
<h2>Bienvenue, <?= e($u['nom']) ?></h2>
<p class="mb-0"><?= e($u['filiere']) ?> • <?= e($u['email']) ?></p></div>
<div class="ms-auto d-flex gap-2"><a href="cours.php" class="btn-uwb-gold text-decoration-none"><i class="bi bi-play-circle"></i> Continuer mes cours</a>
<button class="btn-uwb text-decoration-none" style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.4)" data-bs-toggle="modal" data-bs-target="#profilModal"><i class="bi bi-pencil"></i> Profil</button></div>
</div></div>
<div class="stats-grid mt-4" data-aos="fade-up" data-aos-delay="150">
<div class="stat-card"><div class="stat-icon-wrap"><i class="bi bi-mortarboard"></i></div><div class="stat-info"><span class="stat-value"><?= $nbCours ?></span><span class="stat-title">Cours accessibles</span></div></div>
<div class="stat-card"><div class="stat-icon-wrap"><i class="bi bi-patch-check"></i></div><div class="stat-info"><span class="stat-value"><?= count($aFaire) ?></span><span class="stat-title">Évaluations à passer</span></div></div>
<div class="stat-card stat-card-accent"><div class="stat-icon-wrap"><i class="bi bi-award"></i></div><div class="stat-info"><span class="stat-value"><?= e($moy) ?>/20</span><span class="stat-title">Moyenne validée</span></div></div>
<div class="stat-card stat-card-primary"><div class="stat-icon-wrap"><i class="bi bi-send-check"></i></div><div class="stat-info"><span class="stat-value"><?= count($mesSoum) ?></span><span class="stat-title">Copies soumises</span></div></div>
</div>
</div></section>
<section class="portfolio section light-background pt-4"><div class="container">
<div class="container section-title" data-aos="fade-up"><h2><i class="bi bi-camera-video"></i> Mes séances Google Meet</h2><p>Rejoignez vos cours en visioconférence en un clic</p></div>
<div class="row g-4" data-aos="fade-up">
<?php foreach ($meets as $i => $c) echo uwb_cours_card($c, $i); ?>
</div>
<div class="text-center mt-4" data-aos="fade-up"><a href="cours.php" class="btn-uwb text-decoration-none"><i class="bi bi-grid"></i> Tout le catalogue des cours</a></div>
</div></section>
<section class="services section"><div class="container section-title" data-aos="fade-up"><h2>Mon parcours</h2><p>Évaluations, notes et lectures recommandées</p></div>
<div class="container"><div class="row g-4">
<div class="col-lg-4 col-md-6" data-aos="fade-up"><div class="parc-card h-100"><div class="parc-head"><span><i class="bi bi-pencil-square"></i> À passer</span><a href="evaluations.php">Tout voir <i class="bi bi-arrow-right"></i></a></div>
<?php foreach ($aFaire as $ev2): $tp = (int)db()->query('SELECT COALESCE(SUM(points),0) s FROM questions WHERE evaluation_id=' . (int)$ev2['id'])->fetch()['s']; ?>
<a class="parc-row" href="evaluations.php?eval=<?= (int)$ev2['id'] ?>"><div style="min-width:0"><strong><?= e($ev2['titre']) ?></strong><small><?= e($ev2['duree']) ?> min • <?= $tp ?> pts</small></div><span class="parc-go"><i class="bi bi-arrow-right-circle"></i></span></a>
<?php endforeach; if (!count($aFaire)): ?><p class="text-muted small mb-0">Tout est fait. Bravo !</p><?php endif; ?>
</div></div>
<div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100"><div class="parc-card h-100"><div class="parc-head"><span><i class="bi bi-award"></i> Dernières notes</span><a href="resultats.php">Historique <i class="bi bi-arrow-right"></i></a></div>
<?php foreach (array_slice($mesSoum, 0, 3) as $x): $n = $x['note_finale'] ?? $x['note_ia']; ?>
<div class="parc-row"><div style="min-width:0"><strong><?= e($x['titre'] ?? 'Évaluation') ?></strong><small><?= e($x['date_soumission']) ?></small></div><div class="parc-note"><strong><?= (int)$n ?>/20</strong><br><small class="text-muted"><?= $x['statut']==='validee' ? 'Finale' : 'IA • attente' ?></small></div></div>
<?php endforeach; if (!count($mesSoum)): ?><p class="text-muted small mb-0">Aucune note pour le moment.</p><?php endif; ?>
</div></div>
<div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200"><div class="parc-card h-100"><div class="parc-head"><span><i class="bi bi-book"></i> Lectures suggérées</span><a href="bibliotheque.php">Bibliothèque <i class="bi bi-arrow-right"></i></a></div>
<?php foreach ($sug as $l): ?>
<a class="parc-row" href="bibliotheque.php?q=<?= urlencode($l['titre']) ?>"><div style="min-width:0"><strong><?= e($l['titre']) ?></strong><small><?= e($l['auteur']) ?> • <?= e($l['categorie']) ?></small></div><span class="parc-go"><i class="bi bi-chevron-right"></i></span></a>
<?php endforeach; ?>
</div></div>
</div></div></section>
</main>
<div class="modal fade" id="profilModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border-radius:18px"><form method="post" action="../actions/admin.php" class="p-4">
<input type="hidden" name="kind" value="profil">
<h5><i class="bi bi-pencil"></i> Mon profil</h5>
<label class="form-label">Nom</label><input name="nom" class="form-control" value="<?= e($u['nom']) ?>" required>
<label class="form-label mt-2">Filière</label><input name="filiere" class="form-control" value="<?= e($u['filiere']) ?>">
<button class="btn-uwb mt-3 w-100">Enregistrer</button></form></div></div></div>
<?php uwb_footer('UWB E-learning — Espace étudiant.'); ?>
