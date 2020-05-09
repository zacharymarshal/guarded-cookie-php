<?php

namespace GuardedCookie;

use Exception;
use Throwable;

class GuardedCookie
{
    private $name;
    private $hash_key;
    private $encryption_key;
    private $expire_in_seconds = 86400 * 7;
    private $domain;
    private $path = '/';
    private $httponly = true;
    private $secure = true;
    private $samesite = 'Strict';
    private $last_error;

    public function __construct(string $name, array $options = [])
    {
        $this->name = $name;
        $this->hash_key = $options['hash_key'] ?? getenv('GUARDED_COOKIE_HASH_KEY');
        $this->encryption_key = $options['encryption_key'] ?? getenv('GUARDED_COOKIE_ENCRYPTION_KEY');
        $this->expire_in_seconds = (int) ($options['expire_in_seconds'] ?? $this->expire_in_seconds);
        $this->domain = $options['domain'] ?? getenv('GUARDED_COOKIE_DOMAIN');
        $this->path = $options['path'] ?? $this->path;
        $this->httponly = (bool) ($options['httponly'] ?? $this->httponly);
        $this->secure = (bool) ($options['secure'] ?? $this->secure);
        $this->samesite = $options['samesite'] ?? $this->samesite;
    }

    public function get()
    {
        if (!isset($_COOKIE[$this->name])) {
            return null;
        }

        try {
            $this->last_error = null;
            return $this->decode($_COOKIE[$this->name]);
        } catch (Throwable $e) {
            $this->last_error = $e;
        }

        return null;
    }

    public function getLastError()
    {
        return $this->last_error;
    }

    public function save($data): void
    {
        $encoded_value = $this->encode($data);
        setcookie('session', $encoded_value, [
            'expires'  => time() + $this->expire_in_seconds,
            'path'     => $this->path,
            'domain'   => $this->domain,
            'httponly' => $this->httponly,
            'secure'   => $this->secure,
            'samesite' => $this->samesite,
        ]);
    }

    private function encode($data): string
    {
        // Serialize
        $data = json_encode($data, JSON_UNESCAPED_SLASHES);

        // Encrypt
        $iv = random_bytes(openssl_cipher_iv_length('AES-256-CBC'));
        $data = openssl_encrypt($data, 'AES-256-CBC', $this->encryption_key, 0, $iv);
        $data = $iv . $data;

        // Hash
        $data = base64_encode($data);
        $now = time();
        $data = "{$this->name}.{$now}.{$data}";
        $hmac = hash_hmac('sha256', $data, $this->hash_key);
        $data .= ".{$hmac}";
        $data = base64_encode($data);

        return $data;
    }

    private function decode(string $value)
    {
        // Decode
        $value = base64_decode($value);

        $parts = explode('.', $value);
        if (count($parts) !== 4) {
            throw new Exception('Invalid hash, missing the correct number of parts.');
        }

        [$name, $time, $data, $hash] = $parts;
        $time = (int) $time;

        if ($name !== $this->name) {
            throw new Exception('Invalid hash, cookie name does not match');
        }

        // Verify hash
        $verify_hash_data = sprintf('%s.%d.%s', $this->name, $time, $data);
        $verify_hash = hash_hmac('sha256', $verify_hash_data, $this->hash_key);

        if (hash_equals($verify_hash, $hash) !== true) {
            throw new Exception('Invalid hash, does not match.');
        }

        // Verify expiration
        if ($time < time() - $this->expire_in_seconds) {
            throw new Exception('Cookie has expired.');
        }

        // Decrypt
        $data = base64_decode($data);
        $iv_length = openssl_cipher_iv_length('AES-256-CBC');
        $iv = substr($data, 0, $iv_length);
        $data = substr($data, $iv_length);

        $data = openssl_decrypt($data, 'AES-256-CBC', $this->encryption_key, 0, $iv);

        if ($data === false) {
            throw new Exception('Could not decrypt the data in our hash.');
        }

        // De-serialize
        $data = json_decode($data, true);

        return $data;
    }
}
