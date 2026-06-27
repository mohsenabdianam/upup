<?php
namespace Bot\Services;

class TelegramClient {
    private $token;
    private $base;
    private $timeout = 10;

    public function __construct(string $token)
    {
        $this->token = $token;
        $this->base = 'https://api.telegram.org/bot'.$this->token.'/';
    }

    public function request(string $method, array $params = []) {
        $url = $this->base.$method;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        $res = curl_exec($ch);
        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("Telegram request failed: $err");
        }
        curl_close($ch);
        $json = json_decode($res, true);
        if (!$json) throw new \RuntimeException('Invalid JSON from Telegram: '.substr($res,0,200));
        return $json;
    }

    public function setWebhook(string $url, ?string $secretToken = null) {
        $params = ['url' => $url];
        if ($secretToken) $params['secret_token'] = $secretToken;
        return $this->request('setWebhook', $params);
    }
}
