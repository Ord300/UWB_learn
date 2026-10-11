<?php
require_once __DIR__ . '/includes/layout.php';
if (current_user()) { header('Location: ' . home_by_role(current_user()['role'])); exit; }
$flash = flash();
uwb_head('Inscription');
?>
<style>body{background:#f6f8fc}.auth-wrap{min-height:calc(100vh - 90px);display:flex;align-items:center;justify-content:center;padding:2rem 1rem}.auth-card{width:100%;max-width:560px}.auth-logo{width:64px;height:64px;object-fit:contain;border-radius:14px}</style>
<header id="header" class="header d-flex align-items-center sticky-top"><div class="container d-flex align-items-center justify-content-between">
<a href="index.php" class="logo d-flex align-items-center"><img src="forms/uwbfin.webp" alt="UWB"></a>
<div class="d-flex gap-2"><a class="btn-simple" href="login.php"><i class="bi bi-box-arrow-in-right"></i> Se connecter</a><a class="btn-simple" href="index.php"><i class="bi bi-house"></i> Accueil</a></div></div></header>
<main class="auth-wrap"><div class="auth-card">
<div class="panel-pro"><div class="text-center mb-2"><img src="forms/uwbfin.webp" alt="UWB" class="auth-logo"><div class="mt-2"><span class="mini-badge">Création de compte étudiant</span></div><h2 class="mt-2 mb-0">Compte créé par l'administration</h2><p class="text-muted small mb-0">Ce n'est pas l'étudiant qui créer son compte : l'administration le fait par défaut avec son matricule.</p></div>
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?> py-2 small mt-2"><?= e($flash['msg']) ?></div><?php endif; ?>
<div class="mt-3">
<div class="about-check"><i class="bi bi-1-circle"></i><span>L'administration crée votre compte avec votre <strong>matricule</strong>.</span></div>
<div class="about-check"><i class="bi bi-2-circle"></i><span>Vous recevez votre matricule et votre mot de passe (via vos responsables).</span></div>
<div class="about-check"><i class="bi bi-3-circle"></i><span>Vous vous connectez avec votre <strong>matricule</strong> et votre mot de passe.</span></div>
</div>
<a href="login.php" class="btn-simple primary w-100 justify-content-center mt-3"><i class="bi bi-box-arrow-in-right"></i> Se connecter avec mon matricule</a>
<hr><p class="small text-center mb-0">Les professeurs et administrateurs sont créés par l'administration.</p>
</div></div></main>
<?php uwb_footer('UWB E-learning.'); ?>