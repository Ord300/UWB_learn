<?php
// Notifications : marquer comme lu (individuel ou tout)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
$u = require_login();
$action = $_GET['action'] ?? '';
$back = $_SERVER['HTTP_REFERER'] ?? '../notifications.php';
if (strpos($back, 'notifications.php') === false) $back = '../notifications.php';

if ($action === 'read_all') {
    try { db()->prepare('UPDATE notifications SET lu=1 WHERE user_id=?')->execute([(int)$u['id']]); } catch (Throwable $t) {}
    header('Location: ../notifications.php'); exit;
}
if ($action === 'read' && isset($_GET['id'])) {
    $s = db()->prepare('SELECT * FROM notifications WHERE id=? AND user_id=?');
    $s->execute([(int)$_GET['id'], (int)$u['id']]);
    $n = $s->fetch();
    if ($n) {
        try { db()->prepare('UPDATE notifications SET lu=1 WHERE id=? AND user_id=?')->execute([(int)$n['id'], (int)$u['id']]); } catch (Throwable $t) {}
        if ($n['lien'] !== '') { header('Location: ' . $n['lien']); exit; }
    }
    header('Location: ../notifications.php'); exit;
}
header('Location: ../notifications.php');
