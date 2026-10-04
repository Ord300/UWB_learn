<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_login();
if ($u['role'] === 'professeur') { header('Location: ../prof/dashboard.php'); exit; }
$flash = flash();
$q = trim($_GET['q'] ?? ''); $cat = trim($_GET['cat'] ?? '');
$sql = 'SELECT * FROM livres WHERE 1=1'; $p = [];
if ($cat !== '') { $sql .= ' AND categorie=?'; $p[] = $cat; }
if ($q !== '') { $sql .= ' AND (titre LIKE ? OR auteur LIKE ? OR resume LIKE ? OR mots LIKE ?)'; $p += []; $p[] = "%$q%"; $p[] = "%$q%"; $p[] = "%$q%"; $p[] = "%$q%"; }
$sql .= ' ORDER BY telechargements DESC';
$st = db()->prepare($sql); $st->execute($p); $list = $st->fetchAll();
$all = db()->query('SELECT * FROM livres')->fetchAll();
$sug = ia_suggestions($all, $q, '', [$cat ?: 'Informatique']);
$isAdmin = $u['role'] === 'admin';
$edit = null;
if ($isAdmin && isset($_GET['edit'])) {
    $s = db()->prepare('SELECT * FROM livres WHERE id=?'); $s->execute([(int)$_GET['edit']]); $edit = $s->fetch();
}
uwb_head('Bibliothèque intelligente', 'Ressources UWB.');
uwb_header_nav('biblio', $isAdmin ? nav_admin() : nav_etudiant());
?>
<main class="main">
<section class="section pt-4"><div class="container" data-aos="fade-up">
<div class="role-hero role-etudiant" data-aos="fade-up" data-aos-delay="100">
<div class="d-flex align-items-center gap-3 flex-wrap">
<span class="role-avatar"><i class="bi bi-library"></i></span>
<div><span class="hero-pill"><i class="bi bi-stars"></i> Module 3 — Bibliothèque intelligente</span>
<h2>Ressources & suggestions IA</h2>
<p class="mb-0">Recherchez un livre, un PDF, un mot-clé — la bibliothèque vous recommande le plus pertinent.</p></div>
</div></div>
</div></section>
<section class="services section pt-2">
<div class="container">
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
<form method="get" class="search-pro mb-4" data-aos="fade-up">
<div class="pro-row">
<div class="pro-input"><i class="bi bi-search"></i><input name="q" value="<?= e($q) ?>" placeholder="Titre, auteur, mot-clé…"><button class="pro-clear" title="Rechercher"><i class="bi bi-search"></i></button></div>
<div class="select-uwb pro-select"><i class="bi bi-funnel"></i><select name="cat" onchange="this.form.submit()"><option value="">Toutes catégories</option>
<?php foreach (['Informatique','Gestion','Méthodologie'] as $c): ?><option <?= $cat===$c?'selected':'' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
</div>
<div class="pro-row pro-tags"><span>Recherches fréquentes :</span>
<a class="pro-tag text-decoration-none" href="?q=sql">sql</a><a class="pro-tag text-decoration-none" href="?q=ohada">ohada</a><a class="pro-tag text-decoration-none" href="?q=ia">intelligence artificielle</a><a class="pro-tag text-decoration-none" href="?q=réseau">réseau</a><a class="pro-tag text-decoration-none" href="?q=tfe">méthodologie TFE</a></div>
</form>
<div class="ia-box p-3 mb-4" data-aos="fade-up"><strong><i class="bi bi-robot"></i> Suggestions pertinentes :</strong>
<?php foreach ($sug as $x): ?><span class="badge-ia me-1"><i class="bi bi-bookmark-star"></i> <?= e($x['titre']) ?></span><?php endforeach; ?></div>
<?php if ($isAdmin): ?>
<div class="d-flex justify-content-end mb-3" data-aos="fade-up"><button class="btn-uwb" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-circle"></i> <?= $edit ? 'Modifier la ressource' : 'Ajouter une ressource' ?></button></div>
<div class="panel-pro" data-aos="fade-up"><div class="panel-head"><strong><i class="bi bi-collection"></i> Ressources publiées</strong><small><?= count($list) ?> ressource(s)</small></div>
<?php foreach ($list as $l): ?>
<div class="mini-row"><img src="<?= e(livre_cover($l)) ?>" alt="" loading="lazy" style="width:48px;height:64px;object-fit:cover;border-radius:8px;border:1px solid #e5e9f2;flex-shrink:0">
<div style="min-width:0;flex:1"><strong><?= e($l['titre']) ?></strong><br><small><?= e($l['auteur']) ?> • <?= e($l['categorie']) ?> • <?= (int)$l['annee'] ?> • <i class="bi bi-download"></i> <?= (int)$l['telechargements'] ?></small></div>
<div class="mini-actions"><a class="icon-btn ok" href="../actions/livre.php?action=download&id=<?= (int)$l['id'] ?>"><i class="bi bi-download"></i></a>
<a class="icon-btn" href="?edit=<?= (int)$l['id'] ?>&q=<?= urlencode($q) ?>&cat=<?= urlencode($cat) ?>"><i class="bi bi-pencil"></i></a>
<a class="icon-btn danger" href="../actions/livre.php?action=delete&id=<?= (int)$l['id'] ?>" onclick="return confirm('Supprimer ?')"><i class="bi bi-trash"></i></a></div></div>
<?php endforeach; ?></div>
<?php else: ?>
<div class="row g-4" data-aos="fade-up">
<?php foreach ($list as $i => $l): ?>
<div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?= ($i%3)*100 ?>"><div class="res-card h-100">
<div class="res-cover"><img src="<?= e(livre_cover($l)) ?>" alt="" loading="lazy"><span class="res-cat"><i class="bi bi-tag-fill"></i> <?= e($l['categorie']) ?></span><span class="res-year"><i class="bi bi-calendar"></i> <?= (int)$l['annee'] ?></span></div>
<div class="res-body"><h3><?= e($l['titre']) ?></h3>
<p class="res-author"><i class="bi bi-person-circle"></i> <?= e($l['auteur']) ?> • <i class="bi bi-download"></i> <?= (int)$l['telechargements'] ?> téléchargements</p>
<p class="res-desc"><?= e($l['resume']) ?></p>
<div class="res-tags"><?php foreach (array_slice(array_filter(array_map('trim', explode(',', $l['mots'] ?? ''))), 0, 4) as $m): ?><span class="support-chip"><?= e($m) ?></span><?php endforeach; ?></div>
<div class="res-foot"><a class="btn-simple primary" style="flex:1;justify-content:center" href="lecture.php?id=<?= (int)$l['id'] ?>"><i class="bi bi-book-open"></i> Consulter</a> <a class="btn btn-outline-primary" href="../actions/livre.php?action=download&id=<?= (int)$l['id'] ?>"><i class="bi bi-download"></i> Télécharger</a></div></div></div></div>
<?php endforeach; ?>
<?php if (!count($list)): ?><div class="col-12"><p class="text-muted text-center">Aucune ressource. Essayez « sql », « ohada », « ia ».</p></div><?php endif; ?>
</div>
<?php endif; ?>
<?php if ($isAdmin): ?>
<div class="modal fade <?= $edit ? 'show' : '' ?>" id="addModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content" style="border-radius:18px;overflow:hidden"><div class="p-3 p-md-4">
<div class="d-flex justify-content-between align-items-center mb-2"><strong><i class="bi bi-cloud-plus"></i> <?= $edit ? 'Modifier : ' . e($edit['titre']) : 'Ajouter une ressource' ?></strong><a class="icon-btn danger" href="bibliotheque.php"><i class="bi bi-x-lg"></i></a></div>
<form method="post" action="../actions/livre.php" enctype="multipart/form-data" class="row g-3 form-min">
<input type="hidden" name="id" value="<?= $edit ? (int)$edit['id'] : 0 ?>">
<input type="hidden" name="fichier_exist" value="<?= e($edit['fichier'] ?? '') ?>">
<div class="col-md-4"><label class="form-label">Titre *</label><input name="titre" class="form-control" value="<?= e($edit['titre'] ?? '') ?>" required></div>
<div class="col-md-3"><label class="form-label">Auteur *</label><input name="auteur" class="form-control" value="<?= e($edit['auteur'] ?? '') ?>" required></div>
<div class="col-md-2"><label class="form-label">Catégorie</label><select name="categorie" class="form-control"><?php foreach (['Informatique','Gestion','Méthodologie'] as $c): ?><option <?= ($edit['categorie'] ?? '')===$c?'selected':'' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Année</label><input name="annee" type="number" min="1990" max="2030" value="<?= (int)($edit['annee'] ?? 2026) ?>" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Image (URL)</label><input name="cover_url" class="form-control" value="<?= e((!str_starts_with($edit['cover'] ?? '', 'uploads/') && !str_starts_with($edit['cover'] ?? '', 'data:')) ? ($edit['cover'] ?? '') : '') ?>" placeholder="https://…"></div>
<div class="col-md-4"><label class="form-label">Image (fichier)</label><input name="cover_file" type="file" accept="image/*" class="form-control">
<?php if (!empty($edit['cover'])): ?><div class="mt-2"><small class="text-muted">Actuelle :</small><br><img src="<?= e(livre_cover($edit)) ?>" alt="Couverture actuelle" style="height:90px;border-radius:8px;border:1px solid #e5e9f2"></div><?php endif; ?></div>
<div class="col-md-4"><label class="form-label">PDF (fichier)</label><input name="fichier" type="file" accept=".pdf,.zip,.doc,.docx,.ppt,.pptx" class="form-control">
<?php if (!empty($edit['fichier'])): ?><div class="mt-2"><small class="text-muted">Joint : <?= e(basename($edit['fichier'])) ?></small></div><?php else: ?><div class="mt-2"><small class="text-danger">Aucun fichier joint.</small></div><?php endif; ?></div>
<div class="col-md-8"><label class="form-label">Résumé</label><input name="resume" class="form-control" value="<?= e($edit['resume'] ?? '') ?>"></div>
<div class="col-md-4"><label class="form-label">Mots-clés (,)</label><input name="mots" class="form-control" value="<?= e($edit['mots'] ?? '') ?>"></div>
<div class="col-12 d-flex gap-2"><button class="btn-uwb"><i class="bi bi-check"></i> Enregistrer</button><a class="btn btn-outline-secondary" href="bibliotheque.php">Fermer</a></div></form>
</div></div></div></div>
<?php if ($edit): ?><script>document.addEventListener('DOMContentLoaded',()=>{new bootstrap.Modal(document.getElementById('addModal')).show();});</script><?php endif; ?>
<?php endif; ?>
</div></section>
</main>
<?php uwb_footer('UWB E-learning — Bibliothèque.'); ?>
