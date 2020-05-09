<?php

namespace GuardedCookie;

use Exception;
use Throwable;

class GuardedCookie
{
    private $hash_key;
    private $encryption_key;

    private $expire_in_seconds;

    public function __construct(string $hash_key, string $encryption_key, array $options = [])
    {
        $this->hash_key = $hash_key;
        $this->encryption_key = $encryption_key;
        $this->setOptions($options);
    }

    public function encode(string $name, $data): string
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
        $data = "{$name}.{$now}.{$data}";
        $hmac = hash_hmac('sha256', $data, $this->hash_key);
        $data .= ".{$hmac}";
        $data = base64_encode($data);

        return $data;
    }

    public function decode(string $name, string $data)
    {
        // Decode
        $data = base64_decode($data);
        try {
            [, $date, $data, $hmac] = explode('.', $data);
        } catch (Throwable $e) {
            throw new Exception(sprintf('Invalid hmac %s', $e));
        }

        // Verify hash
        $verify_hmac_data = "{$name}.{$date}.{$data}";
        $verify_hmac = hash_hmac('sha256', $verify_hmac_data, $this->hash_key);

        if (hash_equals($verify_hmac, $hmac) !== true) {
            throw new Exception('Invalid hash!');
        }

        // Verify expiration
        $date = (int) $date;
        $now = time();

        if ($date < $now - $this->expire_in_seconds) {
            throw new Exception('Cookie has expired.');
        }

        // Decrypt
        $data = base64_decode($data);
        $iv_length = openssl_cipher_iv_length('AES-256-CBC');
        $iv = substr($data, 0, $iv_length);
        $data = substr($data, $iv_length);

        $data = openssl_decrypt($data, 'AES-256-CBC', $this->encryption_key, 0, $iv);

        if ($data === false) {
            throw new Exception('Could not decrypt the data.');
        }

        // De-serialize
        $data = json_decode($data, true);

        return $data;
    }

    private function setOptions($options)
    {
        $expire_in_seconds = 3600;
        extract($options, EXTR_IF_EXISTS);

        $this->expire_in_seconds = (int) $expire_in_seconds;
    }
}
