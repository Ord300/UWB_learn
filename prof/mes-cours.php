<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_login(['professeur','admin']);
$flash = flash();
if ($u['role'] === 'admin') $list = db()->query('SELECT * FROM cours ORDER BY id DESC')->fetchAll();
else { $s = db()->prepare('SELECT * FROM cours WHERE enseignant_nom=? ORDER BY id DESC'); $s->execute([$u['nom']]); $list = $s->fetchAll(); }
$inscrits = array_sum(array_column($list, 'inscrits'));
try { $fils = array_column(db()->query('SELECT nom FROM filieres ORDER BY nom')->fetchAll(), 'nom'); }
catch (Throwable $t) { $fils = []; }
if (!$fils) $fils = ['Informatique','Gestion','Méthodologie'];
$edit = null;
if (isset($_GET['edit'])) {
    $s = db()->prepare('SELECT * FROM cours WHERE id=?'); $s->execute([(int)$_GET['edit']]); $edit = $s->fetch();
    if ($edit && $u['role'] !== 'admin' && $edit['enseignant_nom'] !== $u['nom']) $edit = null;
}
$editJour = $editHeure = '';
if ($edit && preg_match('/^(\d{4}-\d{2}-\d{2})(?: (\d{2}:\d{2}))?/', (string)$edit['seance_date'], $m)) {
    $editJour = $m[1]; $editHeure = $m[2] ?? '';
}
uwb_head('Mes cours & séances Meet', 'Cours du professeur.');
uwb_header_nav('cours', nav_prof());
?>
<main class="main">
<section class="section pt-4"><div class="container" data-aos="fade-up">
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
<div class="role-hero role-professeur" data-aos="fade-up" data-aos-delay="100">
<div class="d-flex align-items-center gap-3 flex-wrap">
<span class="role-avatar"><i class="bi bi-journals"></i></span>
<div><span class="hero-pill"><i class="bi bi-mortarboard"></i> Mes cours & séances Meet</span>
<h2>Mes cours, <?= e($u['nom']) ?></h2>
<p class="mb-0"><?= e($u['filiere']) ?> • <?= e($u['email']) ?></p></div>
</div></div>
<div class="stats-grid mt-4" data-aos="fade-up" data-aos-delay="150">
<div class="stat-card stat-card-primary"><div class="stat-icon-wrap"><i class="bi bi-journals"></i></div><div class="stat-info"><span class="stat-value"><?= count($list) ?></span><span class="stat-title">Cours publiés</span></div></div>
<div class="stat-card"><div class="stat-icon-wrap"><i class="bi bi-people"></i></div><div class="stat-info"><span class="stat-value"><?= (int)$inscrits ?></span><span class="stat-title">Étudiants inscrits</span></div></div>
<div class="stat-card stat-card-accent"><div class="stat-icon-wrap"><i class="bi bi-camera-video"></i></div><div class="stat-info"><span class="stat-value"><?= count(array_filter($list, fn($c) => !empty($c['meet_link']))) ?></span><span class="stat-title">Séances Meet</span></div></div>
</div>
</div></section>
<section class="portfolio section light-background">
<div class="container"><div class="d-flex justify-content-end mb-3" data-aos="fade-up"><button class="btn-uwb" data-bs-toggle="modal" data-bs-target="#coursModal"><i class="bi bi-plus-circle"></i> Ajouter un cours</button></div>
<div class="panel-pro" data-aos="fade-up"><div class="panel-head"><strong><i class="bi bi-collection"></i> Cours publiés</strong><small><?= count($list) ?> cours</small></div>
<?php foreach ($list as $c): ?>
<div class="mini-row"><div style="min-width:0"><strong><?= e($c['titre']) ?></strong><br><small><?= e($c['categorie']) ?> • <?= e($c['filiere'] ?? '—') ?> • <?= e($c['niveau']) ?> • <?= (int)$c['inscrits'] ?> inscrits • <?= e($c['seance_date']) ?></small></div>
<div class="mini-actions"><a class="icon-btn ok" target="_blank" href="<?= e($c['meet_link']) ?>"><i class="bi bi-camera-video"></i></a>
<a class="icon-btn" href="?edit=<?= (int)$c['id'] ?>"><i class="bi bi-pencil"></i></a>
<a class="icon-btn danger" href="../actions/cours.php?action=delete&id=<?= (int)$c['id'] ?>" onclick="return confirm('Supprimer ce cours ?')"><i class="bi bi-trash"></i></a></div></div>
<?php endforeach; if (!count($list)): ?><p class="text-muted small mb-0">Aucun cours. Publiez le premier ci-dessus.</p><?php endif; ?>
</div></div></section>
<div class="modal fade <?= $edit ? 'show' : '' ?>" id="coursModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content" style="border-radius:18px;overflow:hidden"><div class="p-3 p-md-4"><div class="d-flex justify-content-between align-items-center mb-2"><strong><i class="bi bi-<?= $edit ? 'pencil' : 'plus-circle' ?>"></i> <?= $edit ? 'Modifier : ' . e($edit['titre']) : 'Nouveau cours' ?></strong><a class="icon-btn danger" href="mes-cours.php"><i class="bi bi-x-lg"></i></a></div>
<form method="post" action="../actions/cours.php" class="row g-3 form-min">
<input type="hidden" name="id" value="<?= $edit ? (int)$edit['id'] : 0 ?>">
<div class="col-12"><label class="form-label">Titre *</label><input name="titre" class="form-control" value="<?= e($edit['titre'] ?? '') ?>" placeholder="Ex : Algorithmes L1" required></div>
<div class="col-md-4"><label class="form-label">Catégorie *</label><input name="categorie" class="form-control" value="<?= e($edit['categorie'] ?? 'Informatique') ?>" required></div>
<div class="col-md-4"><label class="form-label">Filière *</label><select name="filiere" class="form-control"><?php foreach ($fils as $f): ?><option <?= ($edit['filiere'] ?? 'Informatique')===$f?'selected':'' ?>><?= e($f) ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Niveau</label><select name="niveau" class="form-control"><?php foreach (['L1','L2','L3','M1','M2','Autre'] as $n): ?><option <?= ($edit['niveau'] ?? 'L3')===$n?'selected':'' ?>><?= $n ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">Date séance</label><input name="seance_jour" type="date" value="<?= e($editJour) ?>" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Heure séance</label><input name="seance_heure" type="time" value="<?= e($editHeure) ?>" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Lien Google Meet</label><input name="meet_link" class="form-control" value="<?= e($edit['meet_link'] ?? 'https://meet.google.com/uwb-') ?>"></div>
<div class="col-md-6"><label class="form-label">Description + supports ( ; )</label><input name="description" class="form-control" value="<?= e($edit && $edit['description'] !== '—' ? $edit['description'] : '') ?>" placeholder="Intro…"><input name="supports" class="form-control mt-2" value="<?= e(implode(' ; ', json_decode($edit['supports'] ?? '[]', true) ?: [])) ?>" placeholder="Support PDF ; TP.zip"></div>
<div class="col-12 d-flex gap-2"><button class="btn-uwb"><i class="bi bi-<?= $edit ? 'check' : 'send' ?>"></i> <?= $edit ? 'Enregistrer' : 'Publier le cours' ?></button><a class="btn btn-outline-secondary" href="mes-cours.php">Fermer</a></div></form>
</div></div></div></div>
<?php if ($edit): ?><script>document.addEventListener('DOMContentLoaded',()=>{new bootstrap.Modal(document.getElementById('coursModal')).show();});</script><?php endif; ?>
</main>
<?php uwb_footer('UWB E-learning — Mes cours.'); ?>
