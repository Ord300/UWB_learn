<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_login(['professeur','admin']);
$flash = flash();
if ($u['role'] === 'admin') { $allCours = db()->query('SELECT * FROM cours')->fetchAll(); $mesEvals = db()->query('SELECT * FROM evaluations')->fetchAll(); }
else {
    $s = db()->prepare('SELECT * FROM cours WHERE enseignant_nom=?'); $s->execute([$u['nom']]); $allCours = $s->fetchAll();
    $s = db()->prepare('SELECT * FROM evaluations WHERE prof_nom=?'); $s->execute([$u['nom']]); $mesEvals = $s->fetchAll();
}
if ($u['role'] === 'admin') $att = db()->query("SELECT COUNT(*) c FROM soumissions WHERE statut='proposee-IA'")->fetch()['c'];
else { $s = db()->prepare("SELECT COUNT(*) c FROM soumissions s JOIN evaluations e ON e.id=s.evaluation_id WHERE s.statut='proposee-IA' AND e.prof_nom=?"); $s->execute([$u['nom']]); $att = $s->fetch()['c']; }
$nbEtu = (int)db()->query("SELECT COUNT(*) c FROM users WHERE role='etudiant'")->fetch()['c'];
uwb_head('Espace professeur', 'Espace prof UWB.');
uwb_header_nav('espace', nav_prof());
?>
<main class="main">
<section class="section pt-4"><div class="container" data-aos="fade-up">
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
<div class="role-hero role-professeur" data-aos="fade-up" data-aos-delay="100">
<div class="d-flex align-items-center gap-3 flex-wrap">
<span class="role-avatar"><i class="bi bi-easel-fill"></i></span>
<div><span class="hero-pill"><i class="bi bi-person-video3"></i> Espace professeur</span>
<h2>Bienvenue, <?= e($u['nom']) ?></h2>
<p class="mb-0"><?= e($u['filiere']) ?> • <?= e($u['email']) ?></p></div>
<div class="ms-auto d-flex gap-2 flex-wrap"><a href="creer-evaluation.php" class="btn-uwb-gold text-decoration-none"><i class="bi bi-journal-plus"></i> Créer une évaluation</a><a href="validation-ia.php" class="btn-uwb text-decoration-none" style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.4)"><i class="bi bi-robot"></i> File de validation IA</a></div>
</div></div>
<div class="stats-grid mt-4" data-aos="fade-up" data-aos-delay="150">
<div class="stat-card stat-card-primary"><div class="stat-icon-wrap"><i class="bi bi-journals"></i></div><div class="stat-info"><span class="stat-value"><?= count($allCours) ?></span><span class="stat-title">Mes cours publiés</span></div></div>
<div class="stat-card"><div class="stat-icon-wrap"><i class="bi bi-patch-check"></i></div><div class="stat-info"><span class="stat-value"><?= count($mesEvals) ?></span><span class="stat-title">Mes évaluations</span></div></div>
<div class="stat-card stat-card-accent"><div class="stat-icon-wrap"><i class="bi bi-robot"></i></div><div class="stat-info"><span class="stat-value"><?= (int)$att ?></span><span class="stat-title">Copies IA à valider</span></div></div>
<div class="stat-card"><div class="stat-icon-wrap"><i class="bi bi-people"></i></div><div class="stat-info"><span class="stat-value"><?= $nbEtu ?></span><span class="stat-title">Étudiants inscrits</span></div></div>
</div>
</div></section>
<section class="services section"><div class="container section-title" data-aos="fade-up"><h2><i class="bi bi-grid"></i> Mes modules</h2><p>Chaque module a sa page dédiée</p></div>
<div class="container"><div class="row g-4">
<div class="col-lg-4 col-md-6" data-aos="fade-up"><div class="service-card h-100"><div class="icon-wrapper"><i class="bi bi-mortarboard"></i></div><h3>Mes cours & Meet</h3><p><strong><?= count($allCours) ?></strong> cours publiés — créez, programmez le Meet, publiez les supports.</p><a href="mes-cours.php" class="service-link"><span>Gérer mes cours</span><i class="bi bi-arrow-right"></i></a></div></div>
<div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100"><div class="service-card h-100"><div class="icon-wrapper"><i class="bi bi-journal-plus"></i></div><h3>Créer une évaluation</h3><p><strong><?= count($mesEvals) ?></strong> sujet(s) publié(s) — QCM + questions ouvertes IA.</p><a href="creer-evaluation.php" class="service-link"><span>Construire un sujet</span><i class="bi bi-arrow-right"></i></a></div></div>
<div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200"><div class="service-card featured h-100"><div class="featured-badge"><i class="bi bi-robot"></i><span><?= (int)$att ?> en attente</span></div><div class="icon-wrapper"><i class="bi bi-person-check"></i></div><h3>Validation IA</h3><p>Vérifiez les corrections proposées par l'IA et validez définitivement.</p><a href="validation-ia.php" class="service-link"><span>Ouvrir la file</span><i class="bi bi-arrow-right"></i></a></div></div>
</div></div></section>
</main>
<?php uwb_footer('UWB E-learning — Espace professeur.'); ?>
