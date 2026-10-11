<?php
// Endpoint AJAX (prof) : vérifie les clés de correction avant publication.
// Répond en JSON : { ok, moteur, alertes:[{i,enonce,cle,suggestion,explication,source}] }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/eval_check.php';
header('Content-Type: application/json; charset=utf-8');
$u = require_login(['professeur','admin']);

$types  = $_POST['qtype']   ?? [];
$qs = [];
foreach ((array)$types as $i => $type) {
    if ((string)$type !== 'qcm') continue;
    $enonce = trim((string)($_POST['qenonce'][$i] ?? ''));
    $bonne  = trim((string)($_POST['qbonne'][$i]  ?? ''));
    $choix  = array_values(array_filter(array_map('trim', explode(',', (string)($_POST['qchoix'][$i] ?? ''))), fn($x) => $x !== ''));
    if ($enonce === '' || $bonne === '') continue;
    $qs[] = ['i' => (int)$i, 'enonce' => $enonce, 'bonne' => $bonne, 'choix' => $choix];
}

$res = ec_check($qs);
echo json_encode(['ok' => true, 'moteur' => $res['moteur'], 'alertes' => $res['alertes']], JSON_UNESCAPED_UNICODE);
