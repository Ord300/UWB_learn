<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_login(['admin']);
$flash = flash();
$users = db()->query('SELECT * FROM users ORDER BY id')->fetchAll();
$students = array_values(array_filter($users, fn($x) => ($x['role'] ?? '') === 'etudiant'));
$profs    = array_values(array_filter($users, fn($x) => ($x['role'] ?? '') === 'professeur'));
$admins   = array_values(array_filter($users, fn($x) => ($x['role'] ?? '') === 'admin' || ($x['role'] ?? '') === 'superadmin'));
$facultes = db()->query('SELECT * FROM facultes ORDER BY nom')->fetchAll();
$nbCours = (int)db()->query('SELECT COUNT(*) c FROM cours')->fetch()['c'];
$nbLivres = (int)db()->query('SELECT COUNT(*) c FROM livres')->fetch()['c'];
uwb_head('Espace admin', 'Administration UWB.');
uwb_header_nav('admin', nav_admin());
?>
<main class="main">
<section class="section pt-4"><div class="container" data-aos="fade-up">
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
<div class="role-hero role-admin" data-aos="fade-up" data-aos-delay="100">
<div class="d-flex align-items-center gap-3 flex-wrap">
<span class="role-avatar"><i class="bi bi-shield-lock-fill"></i></span>
<div><span class="hero-pill"><i class="bi bi-gear"></i> Espace administrateur</span>
<h2>Bienvenue, <?= e($u['nom']) ?></h2>
<p class="mb-0"><?= e($u['email']) ?> • Super-admin plateforme</p></div>
</div></div>
<div class="stats-grid mt-4" data-aos="fade-up" data-aos-delay="150">
<div class="stat-card stat-card-primary"><div class="stat-icon-wrap"><i class="bi bi-people"></i></div><div class="stat-info"><span class="stat-value"><?= count($users) ?></span><span class="stat-title">Utilisateurs</span></div></div>
<div class="stat-card"><div class="stat-icon-wrap"><i class="bi bi-mortarboard"></i></div><div class="stat-info"><span class="stat-value"><?= $nbCours ?></span><span class="stat-title">Cours</span></div></div>
<div class="stat-card"><div class="stat-icon-wrap"><i class="bi bi-book"></i></div><div class="stat-info"><span class="stat-value"><?= $nbLivres ?></span><span class="stat-title">Ressources</span></div></div>
<div class="stat-card stat-card-accent"><div class="stat-icon-wrap"><i class="bi bi-diagram-3"></i></div><div class="stat-info"><span class="stat-value"><?= count($facultes) ?></span><span class="stat-title">Facultés</span></div></div>
</div>
</div></section>
<section class="services section" id="users"><div class="container section-title" data-aos="fade-up"><h2><i class="bi bi-people"></i> Comptes étudiants & enseignants</h2><p>Créer un compte, changer un rôle, supprimer un accès</p></div>
<div class="container" data-aos="fade-up"><div class="d-flex justify-content-end mb-3 gap-2 flex-wrap">
<button class="btn-uwb" onclick="uwbOpenUserModal('etudiant')"><i class="bi bi-mortarboard"></i> Ajouter un étudiant</button>
<button class="btn-uwb-gold" onclick="uwbOpenUserModal('professeur')"><i class="bi bi-journals"></i> Ajouter un professeur</button>
</div>
<div class="panel-pro"><div class="panel-head"><strong><i class="bi bi-mortarboard"></i> Étudiants</strong><small><?= count($students) ?> compte(s)</small></div>
<?php foreach ($students as $x): ?>
<div class="mini-row"><div style="min-width:0;flex:1"><strong><?= e($x['nom']) ?></strong><br><small><?= e($x['email']) ?> • <?= e($x['faculte']) ?></small><br><?php if (!empty($x['matricule'])): ?><span class="mini-badge"><i class="bi bi-person-vcard"></i> <?= e($x['matricule']) ?></span><?php endif; ?></div>
<div class="mini-actions"><form method="post" action="../actions/admin.php" class="d-flex gap-1"><input type="hidden" name="kind" value="role"><input type="hidden" name="id" value="<?= (int)$x['id'] ?>">
<div class="select-uwb sm"><i class="bi bi-person-badge"></i><select name="role" onchange="this.form.submit()"><?php foreach (['etudiant','professeur','admin'] as $r): ?><option <?= $x['role']===$r?'selected':'' ?>><?= e($r) ?></option><?php endforeach; ?></select></div></form>
<a class="icon-btn danger" href="../actions/admin.php?kind=user_delete&id=<?= (int)$x['id'] ?>" onclick="return confirm('Supprimer ?')"><i class="bi bi-trash"></i></a></div></div>
<?php endforeach; if (!count($students)): ?><p class="text-muted small mb-0">Aucun étudiant pour le moment. Utilisez « Ajouter un étudiant ».</p><?php endif; ?>
</div>
<div class="panel-pro mt-3"><div class="panel-head"><strong><i class="bi bi-journals"></i> Professeurs</strong><small><?= count($profs) ?> compte(s)</small></div>
<?php foreach ($profs as $x): ?>
<div class="mini-row"><div style="min-width:0;flex:1"><strong><?= e($x['nom']) ?></strong><br><small><?= e($x['email']) ?> • <?= e($x['faculte']) ?></small></div>
<div class="mini-actions"><form method="post" action="../actions/admin.php" class="d-flex gap-1"><input type="hidden" name="kind" value="role"><input type="hidden" name="id" value="<?= (int)$x['id'] ?>">
<div class="select-uwb sm"><i class="bi bi-person-badge"></i><select name="role" onchange="this.form.submit()"><?php foreach (['etudiant','professeur','admin'] as $r): ?><option <?= $x['role']===$r?'selected':'' ?>><?= e($r) ?></option><?php endforeach; ?></select></div></form>
<a class="icon-btn danger" href="../actions/admin.php?kind=user_delete&id=<?= (int)$x['id'] ?>" onclick="return confirm('Supprimer ?')"><i class="bi bi-trash"></i></a></div></div>
<?php endforeach; if (!count($profs)): ?><p class="text-muted small mb-0">Aucun professeur pour le moment. Utilisez « Ajouter un professeur ».</p><?php endif; ?>
</div>
<div class="panel-pro mt-3"><div class="panel-head"><strong><i class="bi bi-shield-lock"></i> Administrateurs</strong><small><?= count($admins) ?> compte(s)</small></div>
<?php foreach ($admins as $x): ?>
<div class="mini-row"><div style="min-width:0;flex:1"><strong><?= e($x['nom']) ?></strong><br><small><?= e($x['email']) ?> • <?= e($x['faculte']) ?></small></div>
<div class="mini-actions"><form method="post" action="../actions/admin.php" class="d-flex gap-1"><input type="hidden" name="kind" value="role"><input type="hidden" name="id" value="<?= (int)$x['id'] ?>">
<div class="select-uwb sm"><i class="bi bi-person-badge"></i><select name="role" onchange="this.form.submit()"><?php foreach (['etudiant','professeur','admin'] as $r): ?><option <?= $x['role']===$r?'selected':'' ?>><?= e($r) ?></option><?php endforeach; ?></select></div></form>
<a class="icon-btn danger" href="../actions/admin.php?kind=user_delete&id=<?= (int)$x['id'] ?>" onclick="return confirm('Supprimer ?')"><i class="bi bi-trash"></i></a></div></div>
<?php endforeach; if (!count($admins)): ?><p class="text-muted small mb-0">Aucun administrateur.</p><?php endif; ?>
</div>
</div></section>
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content" style="border-radius:18px;overflow:hidden"><div class="p-3 p-md-4">
<div class="d-flex justify-content-between align-items-center mb-2"><strong><i class="bi bi-person-plus"></i> Créer un compte</strong><button type="button" class="icon-btn danger" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button></div>
<form method="post" action="../actions/admin.php" class="row g-3 form-min"><input type="hidden" name="kind" value="user">
<div class="col-md-3"><label class="form-label">Nom *</label><input name="nom" class="form-control" required></div>
<div class="col-md-3"><label class="form-label">Email *</label><input name="email" type="email" class="form-control" required></div>
<div class="col-md-3"><label class="form-label">Rôle</label><select name="role" id="roleSel" class="form-control"><option value="etudiant">Étudiant</option><option value="professeur">Professeur</option><option value="admin">Admin</option></select></div>
<div class="col-md-4"><label class="form-label">Matricule (étudiant)</label><input name="matricule" class="form-control" placeholder="UWB-INFO-2024-0001"></div>
<div class="col-md-3"><label class="form-label">Mot de passe *</label><input name="password" id="up" class="form-control" value="1234" required></div>
<div class="col-12"><label class="form-label">Faculté</label><select name="faculte" class="form-control"><option value="—">— Sans faculté —</option><?php foreach ($facultes as $f): ?><option value="<?= e($f['nom']) ?>"><?= e($f['nom']) ?> (<?= e($f['code']) ?>)</option><?php endforeach; ?></select></div>
<div class="col-12 d-flex gap-2"><button class="btn-uwb"><i class="bi bi-check"></i> Créer</button></div></form>
</div></div></div></div>
<section class="portfolio section light-background"><div class="container section-title" data-aos="fade-up"><h2><i class="bi bi-diagram-3"></i> Facultés</h2><p>Créez et gérez les facultés utilisées pour les comptes</p></div>
<div class="container" data-aos="fade-up"><div class="d-flex justify-content-end gap-2 mb-3 flex-wrap"><a href="facultes.php" class="btn-simple"><i class="bi bi-arrow-right"></i> Ouvrir la page Facultés</a><button class="btn-uwb" data-bs-toggle="modal" data-bs-target="#faculteModal"><i class="bi bi-plus-circle"></i> Ajouter une faculté</button></div>
<div class="panel-pro"><div class="panel-head"><strong><i class="bi bi-collection"></i> Facultés</strong><small><?= count($facultes) ?> faculté(s)</small></div>
<?php foreach ($facultes as $fl): $nb = 0; foreach ($users as $uu) if ($uu['faculte'] === $fl['nom']) $nb++; ?>
<div class="mini-row"><div style="min-width:0;flex:1"><strong><?= e($fl['nom']) ?></strong> <span class="mini-badge"><?= e($fl['code']) ?></span><br><small><?= e($fl['description']) ?></small><br><small><?= $nb ?> compte(s) rattaché(s)</small></div>
<div class="mini-actions"><a class="icon-btn danger" href="../actions/admin.php?kind=faculte_delete&id=<?= (int)$fl['id'] ?>" onclick="return confirm('Supprimer ?')"><i class="bi bi-trash"></i></a></div></div>
<?php endforeach; ?></div></div></section>
<section class="services section" id="notifs"><div class="container section-title" data-aos="fade-up"><h2><i class="bi bi-bell"></i> Envoyer une notification</h2><p>Diffusez un message aux étudiants, professeurs ou à toute une faculté</p></div>
<div class="container" data-aos="fade-up"><div class="panel-pro"><div class="panel-head"><strong><i class="bi bi-send"></i> Nouvelle diffusion</strong><small>cible au choix</small></div>
<form method="post" action="../actions/admin.php" class="row g-3 form-min"><input type="hidden" name="kind" value="notif">
<div class="col-md-4"><label class="form-label">Titre *</label><input name="titre" class="form-control" placeholder="Ex : Réunion de fac" required></div>
<div class="col-md-5"><label class="form-label">Message *</label><input name="message" class="form-control" placeholder="Contenu du message…" required></div>
<div class="col-md-3"><label class="form-label">Cible</label><select name="portee" class="form-control">
<option value="tous">Tous les utilisateurs</option>
<option value="etudiants">Étudiants</option>
<option value="professeurs">Professeurs</option>
<option value="admins">Administrateurs</option>
<?php foreach ($facultes as $f): ?><option value="faculte:<?= e($f['nom']) ?>">Étudiants — <?= e($f['nom']) ?></option><?php endforeach; ?>
</select></div>
<div class="col-md-8"><label class="form-label">Lien (optionnel)</label><input name="lien" class="form-control" placeholder="Ex : etudiant/cours.php"></div>
<div class="col-md-4 d-flex align-items-end"><button class="btn-uwb w-100"><i class="bi bi-send"></i> Envoyer</button></div></form>
</div></div></section>
<div class="modal fade" id="faculteModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border-radius:18px;overflow:hidden"><div class="p-3 p-md-4">
<div class="d-flex justify-content-between align-items-center mb-2"><strong><i class="bi bi-diagram-3"></i> Ajouter une faculté</strong><button type="button" class="icon-btn danger" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button></div>
<form method="post" action="../actions/admin.php" class="row g-3 form-min"><input type="hidden" name="kind" value="faculte"><input type="hidden" name="id" value="0">
<div class="col-md-7"><label class="form-label">Nom *</label><input name="nom" class="form-control" required></div>
<div class="col-md-5"><label class="form-label">Code *</label><input name="code" class="form-control" required></div>
<div class="col-12"><label class="form-label">Description</label><input name="description" class="form-control"></div>
<div class="col-12 d-flex gap-2"><button class="btn-uwb"><i class="bi bi-check"></i> Enregistrer</button></div></form>
</div></div></div></div>
<script>function uwbOpenUserModal(role){const sel=document.getElementById('roleSel');if(sel)sel.value=role;const m=document.getElementById('userModal');if(m)new bootstrap.Modal(m).show();}</script>
</main>
<?php uwb_footer('UWB E-learning — Administration.'); ?>
