<?php
// Carrega config.php (que NAO vai para o GitHub). Sem ele, as chaves ficam vazias
// e as telas que dependem de Google/Gemini mostram erro, mas o resto do sistema funciona.
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}
if (!defined('GOOGLE_MAPS_API_KEY')) define('GOOGLE_MAPS_API_KEY', '');
if (!defined('GEMINI_API_KEY'))      define('GEMINI_API_KEY', '');
