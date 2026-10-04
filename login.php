<?php
require_once __DIR__ . '/includes/layout.php';
if (current_user()) { header('Location: ' . home_by_role(current_user()['role'])); exit; }
$flash = flash();
uwb_head('Connexion');
?>
<style>body{background:#f6f8fc}.auth-wrap{min-height:calc(100vh - 90px);display:flex;align-items:center;justify-content:center;padding:2rem 1rem}.auth-card{width:100%;max-width:480px}.auth-logo{width:64px;height:64px;object-fit:contain;border-radius:14px}</style>
<header id="header" class="header d-flex align-items-center sticky-top"><div class="container d-flex align-items-center justify-content-between">
<a href="index.php" class="logo d-flex align-items-center"><img src="forms/uwbfin.webp" alt="UWB"></a>
<div class="d-flex gap-2"><a class="btn-simple" href="inscription.php"><i class="bi bi-person-plus"></i> Créer un compte</a><a class="btn-simple" href="index.php"><i class="bi bi-house"></i> Accueil</a></div></div></header>
<main class="auth-wrap"><div class="auth-card">
<div class="panel-pro"><div class="text-center mb-2"><img src="forms/uwbfin.webp" alt="UWB" class="auth-logo"><div class="mt-2"><span class="mini-badge">Connexion par rôle</span></div><h2 class="mt-2 mb-0">Bon retour</h2><p class="text-muted small mb-0">Étudiant • Professeur • Admin</p></div>
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?> py-2 small"><?= e($flash['msg']) ?></div><?php endif; ?>
<form method="post" action="actions/login.php" class="form-min mt-3">
<div class="mb-2"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="etudiant@uwb.ac.cd" required></div>
<div class="mb-2"><label class="form-label">Mot de passe</label><div class="position-relative"><input name="password" id="pass" type="password" class="form-control pe-5" value="etu123" required><button type="button" class="icon-btn" style="position:absolute;right:6px;top:50%;transform:translateY(-50%);width:28px;height:28px" title="Voir / masquer" onclick="togglePass('pass',this)"><i class="bi bi-eye"></i></button></div></div>
<button class="btn-simple primary w-100 justify-content-center mt-2" type="submit">Se connecter <i class="bi bi-arrow-right"></i></button></form>
<hr><p class="small text-center mb-2">Pas de compte ? <a href="inscription.php" class="auth-link"><strong>Créer un compte</strong></a></p>
<div class="pro-row pro-tags justify-content-center"><span>Démo :</span><button type="button" class="pro-tag" onclick="document.querySelector('[name=email]').value='admin@uwb.ac.cd';document.querySelector('[name=password]').value='admin123'">Admin</button><button type="button" class="pro-tag" onclick="document.querySelector('[name=email]').value='prof@uwb.ac.cd';document.querySelector('[name=password]').value='prof123'">Prof</button><button type="button" class="pro-tag" onclick="document.querySelector('[name=email]').value='etudiant@uwb.ac.cd';document.querySelector('[name=password]').value='etu123'">Étudiant</button></div>
</div></div></main>
<?php uwb_footer('UWB E-learning.'); ?>
