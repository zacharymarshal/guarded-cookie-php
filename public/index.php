<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GuardedCookie\GuardedCookie;

$guard = new GuardedCookie(getenv('HASH_KEY'), getenv('ENCRYPTION_KEY'), [
    'expire_in_seconds' => 86400 * 30,
]);

$session = [];
if (!empty($_COOKIE['session'])) {
    try {
        $session = $guard->decode('session', $_COOKIE['session']);
    } catch (Throwable $e) {
        $session = [];
    }
}

$session_commit = function () use ($guard, &$session) {
    setcookie('session', $guard->encode('session', $session), [
        'expires'  => 0,
        'path'     => '/',
        'domain'   => getenv('DOMAIN'),
        'httponly' => true,
        'secure'   => true,
        'samesite' => 'Strict'
    ]);
};

if (!empty($_GET)) {
    foreach ($_GET as $key => $value) {
        $session[$key] = $value;
    }
    $session_commit();

    http_response_code(302);
    header('Location: /');
    return;
}

http_response_code(200);
$session_commit();

echo "Can you find the 🍪?";

var_dump($session);
