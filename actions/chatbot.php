<?php
// ============================================================
// UWB.Learn — Chatbot IA (assistant conversationnel via Groq)
// Reçoit : message (string) + historique (JSON [{role,content}])
// Renvoie : { ok:1, reply:"..." }
// ============================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ia.php';

header('Content-Type: application/json; charset=utf-8');
$u = require_login(); // tout utilisateur connecté

function _chat_out(array $d): void { echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

$message = trim((string)($_POST['message'] ?? ''));
$historyRaw = (string)($_POST['history'] ?? '');
$history = [];
if ($historyRaw !== '') {
    $hj = json_decode($historyRaw, true);
    if (is_array($hj)) $history = $hj;
}
if ($message === '') _chat_out(['ok' => 0, 'reply' => 'Posez-moi une question 🙂']);
if (mb_strlen($message) > 1000) $message = mb_substr($message, 0, 1000);

// Historique borné et assaini (uniquement user/assistant, 10 derniers messages)
$messages = [];
foreach (array_slice($history, -10) as $h) {
    if (!is_array($h)) continue;
    $role = ($h['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
    $content = trim((string)($h['content'] ?? ''));
    if ($content === '') continue;
    $messages[] = ['role' => $role, 'content' => mb_substr($content, 0, 1000)];
}

$moi = current_user();
$role = (string)($moi['role'] ?? 'utilisateur');
$fac = function_exists('my_faculte') ? my_faculte() : '';
$system = "Tu es « Assistant UWB.Learn », le chatbot officiel de la plateforme d'e-learning de "
    . "l'Université William Booth (UWB, Kinshasa, RDC). Tu réponds en français, de façon claire, "
    . "concise et bienveillante (2 à 6 phrases en général).\n"
    . "La plateforme propose : des cours en direct via Google Meet et leur replay (enregistrement) ; "
    . "une bibliothèque numérique intelligente (recherche, suggestions, lecture) ; des évaluations "
    . "en ligne corrigées par IA puis validées par le professeur ; des notifications et un espace par rôle "
    . "(étudiant, professeur, administrateur).\n"
    . "Utilisateur connecté : rôle = {$role}" . ($fac !== '' ? ", faculté = {$fac}" : '') . ".\n"
    . "Aide l'utilisateur à utiliser la plateforme et réponds aussi à ses questions d'étude. "
    . "Si tu ne sais pas, propose de contacter l'administration (infos@uwbcongo.org). "
    . "N'invente jamais de notes, de dates d'examen ni de données personnelles. "
    . "IMPORTANT — confidentiel : ne détaille jamais les mécanismes de surveillance ou d'anti-triche "
    . "des évaluations (plein écran, détection de changement d'onglet, blocage copier/coller/clic droit, "
    . "seuils d'incidents, minuteurs internes, nombre de tentatives techniques). Si on te pose la question, "
    . "réponds simplement que l'épreuve se déroule en conditions d'examen et qu'il faut rester sur la page "
    . "et respecter les consignes affichées. "
    . "Réponds en texte simple (pas de Markdown complexe).";

$messages[] = ['role' => 'user', 'content' => $message];
array_unshift($messages, ['role' => 'system', 'content' => $system]);

if (!ia_api_configuee()) {
    // Pas de clé Groq : moteur de réponses local (FAQ plateforme).
    require_once __DIR__ . '/../includes/chatbot.php';
    _chat_out(['ok' => 1, 'reply' => uwb_chat_local($message, is_array($moi) ? $moi : [])]);
}

$reply = ia_groq_reply($messages);
if ($reply === null || trim($reply) === '') {
    // IA indisponible (réseau/quota) : repli local.
    require_once __DIR__ . '/../includes/chatbot.php';
    _chat_out(['ok' => 1, 'reply' => uwb_chat_local($message, is_array($moi) ? $moi : [])]);
}
_chat_out(['ok' => 1, 'reply' => trim($reply)]);
