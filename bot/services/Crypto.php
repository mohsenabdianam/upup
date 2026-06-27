<?php
namespace Bot\Services;

class Crypto {
    private $key;

    public function __construct(string $masterKeyFile)
    {
        if (!file_exists($masterKeyFile)) {
            throw new \RuntimeException("Master key not found: $masterKeyFile");
        }
        $k = file_get_contents($masterKeyFile);
        $this->key = base64_decode(trim($k));
        if ($this->key === false || strlen($this->key) < 16) {
            throw new \RuntimeException('Invalid master key');
        }
    }

    public static function generateKeyFile(string $path) {
        $key = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_KEYBYTES);
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0700, true);
        file_put_contents($path, base64_encode($key));
        chmod($path, 0600);
        return $path;
    }

    public function encrypt(string $plaintext): string {
        if (function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt')) {
            $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_NPUBBYTES);
            $ct = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plaintext, '', $nonce, $this->key);
            return base64_encode($nonce . $ct);
        }
        // fallback: openssl (less preferred)
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-gcm'));
        $tag = '';
        $ct = openssl_encrypt($plaintext, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag);
        return base64_encode($iv . $tag . $ct);
    }

    public function decrypt(string $blob): string {
        $raw = base64_decode($blob);
        if (function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_decrypt')) {
            $nonce_len = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_NPUBBYTES;
            $nonce = substr($raw, 0, $nonce_len);
            $ct = substr($raw, $nonce_len);
            $pt = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($ct, '', $nonce, $this->key);
            if ($pt === false) throw new \RuntimeException('Decrypt failed');
            return $pt;
        }
        $iv_len = openssl_cipher_iv_length('aes-256-gcm');
        $iv = substr($raw, 0, $iv_len);
        $tag = substr($raw, $iv_len, 16);
        $ct = substr($raw, $iv_len + 16);
        $pt = openssl_decrypt($ct, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($pt === false) throw new \RuntimeException('Decrypt failed');
        return $pt;
    }
}
