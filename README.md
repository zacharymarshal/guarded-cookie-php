# guarded-cookie-php

Encodes and decodes authenticated and encrypted cookies in PHP.

## How To

First, create an instance of GuardedCookie with the name of the cookie you would like to encode; in this case, our cookie name is `'session'`.

```php
use GuardedCookie\GuardedCookie;

$guarded_cookie = new GuardedCookie('session', [
    'hash_key'          => 'something-super-super-secret',
    'encryption_key'    => 'something-even-more-more-secret',
]);
```

Next, you decode the cookie value, do something with it, then encode it and output the cookie response header.

```php
// The `get()` method fetches the cookie from `$_COOKIE` for you and decodes it.
$session = $guarded_cookie->get() ?: [];
$session['user_id'] = 12345;

// Somewhere before you output any data
// The `save()` method encodes your cookie with the value you pass and adds the 
// proper response headers for you.
$guarded_cookie->save($session);
```
