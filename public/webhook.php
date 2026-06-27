<?php
// basic webhook endpoint
require_once __DIR__ . '/../bot/core/Config.php';
require_once __DIR__ . '/../bot/services/TelegramClient.php';
require_once __DIR__ . '/../bot/services/Database.php';
require_once __DIR__ . '/../bot/services/Crypto.php';

use Bot\Core\Config;
use Bot\Services\TelegramClient;
use Bot\Services\Database;
use Bot\Services\Crypto;

// load env
Config::load(__DIR__.'/../.env');

$secret = Config::get('BOT_WEBHOOK_SECRET', null);
// optional: verify header if set
if ($secret) {
    $header = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? null;
    if ($header !== $secret) {
        http_response_code(403);
        echo 'forbidden';
        exit;
    }
}

$body = file_get_contents('php://input');
if (!$body) { http_response_code(400); echo 'no input'; exit; }
$update = json_decode($body, true);
if (!$update) { http_response_code(400); echo 'invalid json'; exit; }

// simple logging
$logDir = __DIR__ . '/../storage/logs';
if (!is_dir($logDir)) mkdir($logDir, 0700, true);
file_put_contents($logDir.'/webhook.log', date('c').' '.json_encode($update)."\n", FILE_APPEND);

// TODO: route update to modules - minimal echo for now
http_response_code(200);
echo 'ok';
