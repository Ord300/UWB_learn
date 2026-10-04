<?php
// Redirection intelligente par rôle (remplace dashboard.html + JS uwbSession)
require_once __DIR__ . '/includes/layout.php';
$u = current_user();
if (!$u) { header('Location: login.php'); exit; }
header('Location: ' . home_by_role($u['role']));
exit;
