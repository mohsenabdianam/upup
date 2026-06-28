<?php
// Admin panel (simple token-based login via Telegram link)
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../bot/core/Config.php';
require_once __DIR__ . '/../bot/services/TelegramClient.php';
require_once __DIR__ . '/../bot/services/Database.php';

use Bot\Core\Config;
use Bot\Services\TelegramClient;
use Bot\Services\Database;

Config::load(__DIR__.'/../.env');
$site = rtrim(Config::get('SITE_URL', ''), '/');
session_start();

$token = $_GET['token'] ?? null;
$store = __DIR__ . '/../storage/admin_tokens.json';
$authenticated = false;
if ($token && file_exists($store)) {
    $tokens = json_decode(file_get_contents($store), true) ?: [];
    if (isset($tokens[$token]) && $tokens[$token]['exp'] >= time()) {
        // login
        $_SESSION['admin_id'] = $tokens[$token]['id'];
        // consume token
        unset($tokens[$token]); file_put_contents($store, json_encode($tokens));
        $authenticated = true;
    }
}
if (isset($_SESSION['admin_id'])) $authenticated = true;

if (!$authenticated) {
    // show request form
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $tgToken = Config::get('BOT_TOKEN');
        $tg = new TelegramClient($tgToken);
        $tgid = trim($_POST['tg_id'] ?? '');
        if (!is_numeric($tgid)) $error = 'آی دی عددی صحیح نیست.';
        else {
            // send login link via bot
            $adminModule = new Bot\Modules\AdminModule(['telegram'=>$tg,'config'=>['site_url'=>$site]]);
            $adminModule->sendLoginLink((int)$tgid);
            $msg = 'لینک ورود از طریق بات ارسال شد.';
        }
    }
    ?>
    <!doctype html><html lang="fa"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>پنل مدیر - ورود</title></head><body>
    <h2>ورود مدیر</h2>
    <?php if (!empty($error)) echo '<div style="color:red">'.htmlspecialchars($error).'</div>'; ?>
    <?php if (!empty($msg)) echo '<div style="color:green">'.htmlspecialchars($msg).'</div>'; ?>
    <form method="post">
      <label>آی‌دی عددی تلگرام</label><br>
      <input name="tg_id"><br>
      <button type="submit">ارسال لینک ورود</button>
    </form>
    </body></html>
    <?php
    exit;
}

// authenticated dashboard
$admin_id = $_SESSION['admin_id'];
// connect DB
$dbCfg = [
    'host'=>Config::get('DB_HOST','127.0.0.1'),
    'port'=>Config::get('DB_PORT','3306'),
    'dbname'=>Config::get('DB_NAME','upup'),
    'user'=>Config::get('DB_USER','root'),
    'pass'=>Config::get('DB_PASS','')
];
$db = new Database($dbCfg);
$pdo = $db->pdo();
$usersCount = $pdo->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
$filesCount = $pdo->query('SELECT COUNT(*) AS c FROM files')->fetch()['c'];
$jobsPending = $pdo->query("SELECT COUNT(*) AS c FROM jobs WHERE status='pending'")->fetch()['c'];
?>
<!doctype html><html lang="fa"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>پنل مدیر</title></head><body>
<h2>پنل مدیر</h2>
<p>شما با آی‌دی: <?php echo htmlspecialchars($admin_id); ?> وارد شده‌اید.</p>
<ul>
  <li>تعداد کاربران: <?php echo $usersCount; ?></li>
  <li>تعداد فایل‌ها: <?php echo $filesCount; ?></li>
  <li>Jobهای در انتظار: <?php echo $jobsPending; ?></li>
</ul>
<form method="post" action="admin_actions.php">
  <h3>ارسال پیام همگانی</h3>
  <textarea name="broadcast_text" rows="6" cols="60"></textarea><br>
  <button type="submit">ارسال به صف</button>
</form>
</body></html>
