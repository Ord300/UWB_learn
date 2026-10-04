<?php
require_once __DIR__ . '/includes/layout.php';
if (current_user()) { header('Location: ' . home_by_role(current_user()['role'])); exit; }
try { $filieres = db()->query('SELECT * FROM filieres ORDER BY nom')->fetchAll(); } catch (Throwable $t) { $filieres = []; }
$flash = flash();
uwb_head('Inscription');
?>
<style>body{background:#f6f8fc}.auth-wrap{min-height:calc(100vh - 90px);display:flex;align-items:center;justify-content:center;padding:2rem 1rem}.auth-card{width:100%;max-width:560px}.auth-logo{width:64px;height:64px;object-fit:contain;border-radius:14px}.role-pick{display:flex;gap:.6rem}.role-pick label{flex:1;display:flex;gap:.5rem;align-items:center;border:1.5px solid #e5e9f2;border-radius:12px;padding:.6rem .8rem;cursor:pointer;font-size:.9rem;transition:.2s}.role-pick input{accent-color:#0a2a6b}</style>
<header id="header" class="header d-flex align-items-center sticky-top"><div class="container d-flex align-items-center justify-content-between">
<a href="index.php" class="logo d-flex align-items-center"><img src="forms/uwbfin.webp" alt="UWB"></a>
<div class="d-flex gap-2"><a class="btn-simple" href="login.php"><i class="bi bi-box-arrow-in-right"></i> Se connecter</a><a class="btn-simple" href="index.php"><i class="bi bi-house"></i> Accueil</a></div></div></header>
<main class="auth-wrap"><div class="auth-card">
<div class="panel-pro"><div class="text-center mb-2"><img src="forms/uwbfin.webp" alt="UWB" class="auth-logo"><div class="mt-2"><span class="mini-badge">Création de compte</span></div><h2 class="mt-2 mb-0">Inscription</h2><p class="text-muted small mb-0">Étudiant ou professeur • 30 secondes</p></div>
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?> py-2 small mt-2"><?= e($flash['msg']) ?></div><?php endif; ?>
<form method="post" action="actions/register.php" class="form-min mt-3">
<div class="row g-3"><div class="col-md-6"><label class="form-label">Nom complet *</label><input name="nom" class="form-control" placeholder="Ex : Divine Mbuyi" required></div>
<div class="col-md-6"><label class="form-label">Filière *</label><select name="filiere" class="form-control" required>
<?php foreach ($filieres as $f): ?><option value="<?= e($f['nom']) ?>"><?= e($f['nom']) ?> (<?= e($f['code']) ?>)</option><?php endforeach; ?>
<?php if (!count($filieres)): ?><option value="—">— Aucune filière —</option><?php endif; ?>
</select></div>
<div class="col-md-6"><label class="form-label">Email académique *</label><input name="email" type="email" class="form-control" placeholder="vous@uwb.ac.cd" required></div>
<div class="col-md-6"><label class="form-label">Mot de passe *</label><div class="position-relative"><input name="password" id="pass" type="password" minlength="4" class="form-control pe-5" placeholder="Min. 4 caractères" required><button type="button" class="icon-btn" style="position:absolute;right:6px;top:50%;transform:translateY(-50%);width:28px;height:28px" onclick="togglePass('pass',this)"><i class="bi bi-eye"></i></button></div></div>
<div class="col-12"><label class="form-label">Je suis</label><div class="role-pick">
<label><input type="radio" name="role" value="etudiant" checked> <i class="bi bi-mortarboard-fill"></i> Étudiant</label>
<label><input type="radio" name="role" value="professeur"> <i class="bi bi-person-video3"></i> Professeur</label></div></div>
<div class="col-md-6"><label class="form-label">Niveau *</label><select name="niveau" class="form-control" required>
<option>L1</option><option>L2</option><option selected>L3</option><option>M1</option><option>M2</option><option>Autre</option>
</select></div>
<div class="col-md-6 d-flex align-items-end"><small class="text-muted">Étudiant : seuls les cours de <strong>votre filière + niveau</strong> s'afficheront.</small></div></div>
<button class="btn-simple primary w-100 justify-content-center mt-3" type="submit">Créer mon compte <i class="bi bi-arrow-right"></i></button></form>
<hr><p class="small text-center mb-0">Déjà inscrit ? <a href="login.php" class="auth-link"><strong>Se connecter</strong></a></p>
</div></div></main>
<?php uwb_footer('UWB E-learning.'); ?>
