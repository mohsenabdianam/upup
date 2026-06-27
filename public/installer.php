<?php
// Simple installer UI backend and minimal frontend
require_once __DIR__ . '/../bot/core/Config.php';
require_once __DIR__ . '/../bot/services/Database.php';
require_once __DIR__ . '/../bot/services/Crypto.php';
require_once __DIR__ . '/../bot/services/TelegramClient.php';

use Bot\Core\Config;
use Bot\Services\Database;
use Bot\Services\Crypto;
use Bot\Services\TelegramClient;

// if form submitted, attempt install
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $botToken = trim($_POST['bot_token'] ?? '');
    $dbHost = trim($_POST['db_host'] ?? '127.0.0.1');
    $dbPort = trim($_POST['db_port'] ?? '3306');
    $dbName = trim($_POST['db_name'] ?? 'upup');
    $dbUser = trim($_POST['db_user'] ?? 'root');
    $dbPass = trim($_POST['db_pass'] ?? '');
    $adminId = trim($_POST['admin_id'] ?? '');

    $errors = [];
    if (!$botToken) $errors[] = 'BOT token required';
    if (!$adminId || !is_numeric($adminId)) $errors[] = 'Admin numeric Telegram id required';

    if (empty($errors)) {
        // write .env
        $env = "BOT_TOKEN={$botToken}\n";
        $env .= "DB_HOST={$dbHost}\nDB_PORT={$dbPort}\nDB_NAME={$dbName}\nDB_USER={$dbUser}\nDB_PASS={$dbPass}\n";
        $env .= "APP_ENV=production\nAPP_DEBUG=0\n";
        file_put_contents(__DIR__.'/../.env', $env);

        // generate master key
        $keyFile = __DIR__.'/../storage/keys/master.key';
        Crypto::generateKeyFile($keyFile);

        // run migrations
        try {
            $dbcfg = ['host'=>$dbHost,'port'=>$dbPort,'dbname'=>$dbName,'user'=>$dbUser,'pass'=>$dbPass];
            $db = new Database($dbcfg);
            $db->runFile(__DIR__.'/../bot/migrations/001_init.sql');
        } catch (Exception $e) {
            $errors[] = 'Migration or DB connection failed: '.$e->getMessage();
        }

        if (empty($errors)) {
            // set webhook (best-effort) - user must set WEBHOOK_URL via form optionally
            $webhook = trim($_POST['webhook_url'] ?? '');
            if ($webhook) {
                try {
                    $tg = new TelegramClient($botToken);
                    $secret = bin2hex(random_bytes(16));
                    $tg->setWebhook($webhook, $secret);
                    // append secret to env
                    file_put_contents(__DIR__.'/../.env', "BOT_WEBHOOK_SECRET={$secret}\n", FILE_APPEND);
                } catch (Exception $e) {
                    $errors[] = 'Webhook registration failed: '.$e->getMessage();
                }
            }
        }

        if (empty($errors)) {
            // create initial superadmin row
            try {
                $pdo = $db->pdo();
                $stmt = $pdo->prepare('INSERT IGNORE INTO users (id, role) VALUES (:id, :role)');
                $stmt->execute(['id'=>$adminId, 'role'=>'superadmin']);
            } catch (Exception $e) {
                $errors[] = 'Failed to create admin: '.$e->getMessage();
            }
        }

        if (empty($errors)) {
            $success = true;
        }
    }
}

?><!doctype html>
<html lang="fa">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>نصب‌کننده UpUp</title>
<style>
body{font-family:system-ui,Segoe UI,Roboto,"Helvetica Neue",Arial;background:linear-gradient(135deg,#0f172a,#0b1220);color:#e6eef8;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
.card{backdrop-filter:blur(8px);background:rgba(255,255,255,0.04);padding:24px;border-radius:12px;width:720px;box-shadow:0 6px 18px rgba(2,6,23,0.6)}
.input{display:block;width:100%;padding:10px;margin:8px 0;border-radius:8px;border:1px solid rgba(255,255,255,0.06);background:rgba(255,255,255,0.02);color:#fff}
.btn{background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.08);padding:10px 14px;border-radius:10px;color:#fff;cursor:pointer}
</style>
</head>
<body>
<div class="card">
  <h2>نصب‌کننده UpUp</h2>
  <?php if (!empty($errors)): ?>
    <div style="background:#3b0a0a;padding:10px;border-radius:8px;margin-bottom:8px;">خطا:<br><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <div style="background:#08351f;padding:10px;border-radius:8px;margin-bottom:8px;">نصب با موفقیت انجام شد. لطفا فایل .env را بررسی کنید و webhook را فعال کنید.</div>
  <?php endif; ?>
  <form method="post">
    <label>توکن ربات (BOT TOKEN)</label>
    <input class="input" name="bot_token" required>
    <label>آی‌دی ادمین اولیه (numeric)</label>
    <input class="input" name="admin_id" required>
    <hr>
    <label>DB Host</label>
    <input class="input" name="db_host" value="127.0.0.1">
    <label>DB Port</label>
    <input class="input" name="db_port" value="3306">
    <label>DB Name</label>
    <input class="input" name="db_name" value="upup">
    <label>DB User</label>
    <input class="input" name="db_user" value="root">
    <label>DB Pass</label>
    <input class="input" name="db_pass" value="">
    <hr>
    <label>Webhook URL (اختیاری)</label>
    <input class="input" name="webhook_url" placeholder="https://yourdomain.com/public/webhook.php">
    <div style="text-align:right;margin-top:12px">
      <button class="btn" type="submit">نصب و اجرا</button>
    </div>
  </form>
</div>
</body>
</html>
