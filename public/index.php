<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GuardedCookie\GuardedCookie;

$session_cookie = new GuardedCookie('session');

$session = $session_cookie->get() ?: [];

// This is useful for seeing if someone might be messing
// with your cookies
$last_cookie_error = $session_cookie->getLastError();

if (!empty($_GET)) {
    foreach ($_GET as $key => $value) {
        $session[$key] = $value;
    }

    $session_cookie->save($session);
    http_response_code(302);
    header('Location: /');
    return;
}

$session_cookie->save($session);
http_response_code(200);
echo "Can you find the 🍪?";

var_dump($session);
echo $last_cookie_error ? sprintf('Error: %s', $last_cookie_error->getMessage()) : '';
