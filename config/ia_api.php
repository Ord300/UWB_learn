<?php
// ============================================================
// UWB.Learn — API d'IA réelle (Groq, gratuite)
// 1) Créez une clé sur https://console.groq.com/keys
// 2) Collez-la ci-dessous (ou définissez la variable d'env GROQ_API_KEY)
// Sans clé : le système se rabat sur le moteur IA local (inclu).
// ============================================================

define('IA_API_PROVIDER', 'Groq');
define('IA_API_URL', 'https://api.groq.com/openai/v1/chat/completions');
define('IA_API_KEY', getenv('GROQ_API_KEY') ?: '');
define('IA_MODEL', 'llama-3.3-70b-versatile');
define('IA_TIMEOUT', 45); // secondes

function ia_api_configuee(): bool {
    return defined('IA_API_KEY') && trim((string)IA_API_KEY) !== '';
}
