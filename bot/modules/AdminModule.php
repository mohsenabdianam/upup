<?php
namespace Bot\Modules;

use Bot\Services\TelegramClient;
use Bot\Services\Database;
use Bot\Services\Crypto;

class AdminModule
{
    private $services;

    public function __construct(array $services)
    {
        $this->services = $services;
    }

    public function sendLoginLink(int $telegramId)
    {
        $tg = $this->services['telegram'];
        $site = rtrim($this->services['config']['site_url'] ?? '', '/');
        if (!$site) {
            $tg->request('sendMessage', [
                'chat_id' => $telegramId,
                'text' => "پنل وب سرور هنوز در تنظیمات پیکربندی نشده است. لطفا آدرس سایت را در .env به SITE_URL اضافه کنید.",
            ]);
            return;
        }
        $token = bin2hex(random_bytes(16));
        // store token in file storage (simple)
        $store = __DIR__ . '/../../storage/admin_tokens.json';
        if (!is_dir(dirname($store))) mkdir(dirname($store), 0700, true);
        $tokens = [];
        if (file_exists($store)) $tokens = json_decode(file_get_contents($store), true) ?: [];
        $tokens[$token] = ['id' => $telegramId, 'exp' => time() + 300];
        file_put_contents($store, json_encode($tokens));

        $link = $site.'/public/admin.php?token='.$token;
        $tg->request('sendMessage', [
            'chat_id' => $telegramId,
            'text' => "برای ورود به پنل مدیریتی روی لینک زیر بزنید (کد معتبر برای 5 دقیقه):\n$link",
            'disable_web_page_preview' => true
        ]);
    }
}
