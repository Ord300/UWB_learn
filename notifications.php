<?php
require_once __DIR__ . '/includes/layout.php';
$u = require_login();
require_once __DIR__ . '/includes/notifications.php';
$flash = flash();
$nb = nb_notifs_non_lues((int)$u['id']);
$list = mes_notifs((int)$u['id']);
uwb_head('Notifications', 'Centre de notifications UWB.');
uwb_header_nav('notifs', $u['role'] === 'admin' ? nav_admin() : ($u['role'] === 'professeur' ? nav_prof() : nav_etudiant()));
?>
<main class="main">
<section class="section pt-4"><div class="container" data-aos="fade-up">
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
<div class="role-hero role-etudiant" data-aos="fade-up" data-aos-delay="100">
<div class="d-flex align-items-center gap-3 flex-wrap">
<span class="role-avatar"><i class="bi bi-bell-fill"></i></span>
<div><span class="hero-pill"><i class="bi bi-bell"></i> Centre de notifications</span>
<h2>Notifications</h2>
<p class="mb-0"><?= $nb ?> non lue(s) sur <?= count($list) ?> — cours, évaluations, bibliothèque, validations.</p></div>
<div class="ms-auto"><?php if ($list): ?><a class="btn-uwb text-decoration-none" href="actions/notifications.php?action=read_all"><i class="bi bi-check-all"></i> Tout marquer comme lu</a><?php endif; ?></div>
</div></div>
<div class="panel-pro mt-4" data-aos="fade-up" data-aos-delay="150">
<div class="panel-head"><strong><i class="bi bi-bell"></i> Ma boîte de réception</strong><small><?= count($list) ?> notification(s)</small></div>
<?php foreach ($list as $n): $estNouvelle = !(int)$n['lu']; ?>
<div class="mini-row" style="<?= $estNouvelle ? 'background:rgba(10,42,107,.04)' : '' ?>">
<div class="me-2" style="flex-shrink:0"><i class="bi bi-<?= $estNouvelle ? 'bell-fill text-primary' : 'bell' ?>"></i></div>
<div style="min-width:0;flex:1">
<strong><?= e($n['titre']) ?></strong> <?php if ($estNouvelle): ?><span class="mini-badge">Nouveau</span><?php endif; ?><br>
<small><?= e($n['message']) ?></small><br>
<small class="text-muted"><i class="bi bi-clock"></i> <?= e(date('d/m/Y H:i', strtotime($n['created_at']))) ?></small>
</div>
<div class="mini-actions">
<?php if ($n['lien']): ?><a class="icon-btn ok" href="actions/notifications.php?action=read&id=<?= (int)$n['id'] ?>" title="Ouvrir"><i class="bi bi-box-arrow-in-right"></i></a><?php endif; ?>
<?php if ($estNouvelle): ?><a class="icon-btn" href="actions/notifications.php?action=read&id=<?= (int)$n['id'] ?>" title="Marquer comme lu"><i class="bi bi-check2"></i></a><?php endif; ?>
</div></div>
<?php endforeach; if (!count($list)): ?><p class="text-muted small mb-0"><i class="bi bi-inbox"></i> Aucune notification pour le moment.</p><?php endif; ?>
</div>
</div></section>
</main>
<?php uwb_footer('UWB E-learning — Notifications.'); ?>
