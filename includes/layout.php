<?php
// UWB.Learn — Layout partagé (même design Orbit/UWB, zéro changement visuel)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/ia.php';

function uwb_head(string $title, string $desc = ''): void {
    $b = base_url(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8"><meta content="width=device-width, initial-scale=1.0" name="viewport">
<title><?= e($title) ?> — UWB Learn</title><link href="<?= $b ?>assets/css/style.css?v=6" rel="stylesheet">
<meta name="description" content="<?= e($desc) ?>">
<link href="<?= $b ?>forms/uwbfin.webp" rel="icon">
<link href="https://fonts.googleapis.com" rel="preconnect"><link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&family=Lato:wght@400;700;900&family=Raleway:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="<?= $b ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= $b ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
<link href="<?= $b ?>assets/vendor/aos/aos.css" rel="stylesheet">
<link href="<?= $b ?>assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
<link href="<?= $b ?>assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
<style>.header-actions{display:flex;align-items:center;gap:10px}.notif-btn{position:relative;display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:50%;background:#fff;color:#253a54;border:1px solid rgba(0,0,0,.1);box-shadow:0 2px 8px rgba(0,0,0,.1);font-size:1.1rem;transition:.3s}.notif-btn:hover,.notif-btn:focus{background:var(--accent-color);color:var(--contrast-color);border-color:transparent}.notif-btn .notif-badge{position:absolute;top:-6px;right:-6px;min-width:18px;height:18px;line-height:18px;padding:0 5px;border-radius:9px;background:#e63946;color:#fff!important;font-size:.68rem;font-weight:700;text-align:center}</style>
</head>
<body class="index-page">
<?php
}

function uwb_header_nav(string $active = '', array $links = []): void {
    $b = base_url();
    $nbNotif = 0; $me = current_user();
    if ($me) {
        try { require_once __DIR__ . '/notifications.php'; $nbNotif = nb_notifs_non_lues((int)$me['id']); } catch (Throwable $t) {}
    } ?>
<header id="header" class="header d-flex align-items-center sticky-top">
<div class="container position-relative d-flex align-items-center justify-content-between">
<a href="<?= $b ?>index.php" class="logo d-flex align-items-center me-auto me-xl-0"><img src="<?= $b ?>forms/uwbfin.webp" alt="UWB"></a>
<nav id="navmenu" class="navmenu"><ul>
<?php foreach ($links as [$href, $icon, $label, $key]): ?>
<li><a href="<?= e($href) ?>" class="<?= $active === $key ? 'active' : '' ?>"><i class="bi <?= e($icon) ?>"></i> <?= e($label) ?></a></li>
<?php endforeach; ?>
</ul><i class="mobile-nav-toggle d-lg-none bi bi-list"></i></nav>
<div class="header-actions">
<?php if ($me): ?>
<a class="notif-btn" href="<?= $b ?>notifications.php" title="Notifications" aria-label="Notifications"><i class="bi bi-bell"></i><?php if ($nbNotif): ?><span class="notif-badge"><?= $nbNotif ?></span><?php endif; ?></a>
<?php endif; ?>
<a class="btn-getstarted" href="<?= $b ?>actions/logout.php">Déconnexion</a>
</div>
</div></header>
<?php
}

function uwb_public_header(string $active = 'accueil'): void {
    $b = base_url(); ?>
<header id="header" class="header d-flex align-items-center sticky-top">
<div class="container position-relative d-flex align-items-center justify-content-between">
<a href="<?= $b ?>index.php" class="logo d-flex align-items-center me-auto me-xl-0"><img src="<?= $b ?>forms/uwbfin.webp" alt="UWB"></a>
<nav id="navmenu" class="navmenu"><ul>
<li><a href="<?= $b ?>index.php#hero" class="<?= $active==='accueil'?'active':'' ?>">Accueil</a></li>
<li><a href="<?= $b ?>index.php#about">Projet</a></li>
<li><a href="<?= $b ?>index.php#cours">Cours</a></li>
<li><a href="<?= $b ?>index.php#modules">Modules</a></li>
</ul><i class="mobile-nav-toggle d-lg-none bi bi-list"></i></nav>
<?php if (current_user()): ?>
<a class="btn-getstarted" href="<?= home_by_role(current_user()['role']) ?>">Mon espace</a>
<?php else: ?>
<a class="btn-getstarted" href="<?= $b ?>login.php">Se connecter</a>
<?php endif; ?>
</div></header>
<?php
}

function uwb_footer(string $note = 'UWB E-learning.'): void {
    $b = base_url(); ?>
<footer id="footer" class="footer light-background"><div class="container footer-bottom"><div class="copyright"><p>© <?= e($note) ?> Design : <a href="https://bootstrapmade.com/">BootstrapMade (Orbit)</a>.</p></div></div></footer>
<a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a><div id="preloader"></div>
<script src="<?= $b ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= $b ?>assets/vendor/aos/aos.js"></script><script src="<?= $b ?>assets/vendor/glightbox/js/glightbox.min.js"></script>
<script src="<?= $b ?>assets/vendor/isotope-layout/isotope.pkgd.min.js"></script><script src="<?= $b ?>assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
<script src="<?= $b ?>assets/vendor/purecounter/purecounter_vanilla.js"></script><script src="<?= $b ?>assets/vendor/swiper/swiper-bundle.min.js"></script>
<script src="<?= $b ?>assets/js/app.js"></script>
</body></html>
<?php
}

// Carte cours — même HTML que uwbCoursCard() JS, design strictement identique
function uwb_cours_card(array $x, int $i = 0): string {
    $ms = meet_state($x['seance_date'] ?? '');
    $fs = fenetre_seance($x);
    $estud = (current_user()['role'] ?? '') === 'etudiant';
    $rejoindre = !$estud || $fs['etat'] === 'live'; // étudiant : accès à la caméra uniquement dans la fenêtre début→fin
    $libEtat = ['avant' => 'Séance à venir', 'live' => 'En direct', 'fini' => 'Séance terminée', 'aucune' => 'Séance à programmer'][$fs['etat']];
    $quand = $fs['etat'] === 'aucune'
        ? (string)($x['seance_date'] ?: '—')
        : ($ms ? $ms['jour'] . ' ' . $ms['mois'] . ' • ' : '') . $fs['heure_debut'] . ($fs['heure_fin'] ? ' → ' . $fs['heure_fin'] : '');
    $d = (($i) % 3) * 100;
    $sup = json_decode($x['supports'] ?? '[]', true) ?: [];
    if (!is_array($sup)) $sup = [$sup];
    $supTxt = count($sup) . ' support(s)' . (count($sup) ? ' — ' . e(implode(' • ', array_slice($sup, 0, 2))) . (count($sup) > 2 ? ' …' : '') : '');
    ob_start(); ?>
<div class="col-lg-4 col-md-6 portfolio-item" data-aos="fade-up" data-aos-delay="<?= $d ?>"><div class="course-min h-100" data-cat="<?= e($x['categorie']) ?>"><div class="cat-strip"></div><div class="course-min-body">
<div class="d-flex gap-1 align-items-center flex-wrap mb-2"><span class="mini-badge"><?= e($x['categorie']) ?></span><span class="mini-badge ghost"><?= e($x['niveau']) ?></span><span class="ms-auto mini-muted"><i class="bi bi-people"></i> <?= (int)$x['inscrits'] ?> inscrits</span></div>
<h3><?= e($x['titre']) ?></h3>
<div class="teacher-mini"><span class="avatar-initials xs"><?= e(uwb_initials($x['enseignant_nom'])) ?></span><span><?= e($x['enseignant_nom']) ?></span></div>
<p class="course-desc"><?= e($x['description']) ?></p>
<div class="supports-mini"><i class="bi bi-paperclip"></i> <?= $supTxt ?></div>
<div class="meet-strip <?= $fs['etat'] === 'fini' ? 'past' : '' ?>"><span class="dot"></span>
<div class="flex-fill" style="min-width:0"><small class="text-muted"><?= e($libEtat) ?></small><br>
<strong class="small"><?= e($quand) ?></strong></div>
<?php if ($rejoindre && $x['meet_link']): ?>
<a href="<?= e($x['meet_link'] ?: '#') ?>" target="_blank" class="btn-join" title="Rejoindre sur Google Meet"><i class="bi bi-camera-video-fill"></i> Rejoindre</a>
<button class="btn-copy" title="Copier le lien" onclick="navigator.clipboard&&navigator.clipboard.writeText('<?= e($x['meet_link']) ?>')"><i class="bi bi-link-45deg"></i></button>
<?php else: ?>
<span class="btn-join" style="opacity:.55;cursor:not-allowed" title="<?= $estud ? 'Accès à la séance réservé à la fenêtre début → fin' : '' ?>"><i class="bi bi-lock"></i> <?= e($libEtat) ?></span>
<?php endif; ?>
</div></div></div></div>
<?php return ob_get_clean();
}

function nav_etudiant(): array {
    $b = base_url();
    return [
        [$b . 'etudiant/dashboard.php', 'bi-speedometer2', 'Mon espace', 'espace'],
        [$b . 'etudiant/cours.php', 'bi-mortarboard', 'Cours & Meet', 'cours'],
        [$b . 'etudiant/bibliotheque.php', 'bi-book', 'Bibliothèque', 'biblio'],
        [$b . 'etudiant/evaluations.php', 'bi-patch-check', 'Évaluations', 'evals'],
        [$b . 'etudiant/resultats.php', 'bi-bar-chart', 'Résultats', 'resultats'],
    ];
}
function nav_prof(): array {
    $b = base_url();
    return [
        [$b . 'prof/dashboard.php', 'bi-speedometer2', 'Mon espace', 'espace'],
        [$b . 'prof/mes-cours.php', 'bi-mortarboard', 'Mes cours & Meet', 'cours'],
        [$b . 'prof/creer-evaluation.php', 'bi-journal-plus', 'Créer une évaluation', 'creer'],
        [$b . 'prof/validation-ia.php', 'bi-robot', 'Validation IA', 'valid'],
    ];
}
function nav_admin(): array {
    $b = base_url();
    return [
        [$b . 'admin/dashboard.php', 'bi-speedometer2', 'Administration', 'admin'],
        [$b . 'etudiant/cours.php', 'bi-mortarboard', 'Cours & Meet', 'cours'],
        [$b . 'etudiant/bibliotheque.php', 'bi-book', 'Bibliothèque', 'biblio'],
        [$b . 'admin/facultes.php', 'bi-diagram-3', 'Facultés', 'facultes'],
    ];
}
