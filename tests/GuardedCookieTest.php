<?php

namespace GuardedCookie\Tests;

use Exception;
use GuardedCookie\GuardedCookie;
use GuardedCookie\Tests\SetCookie;
use PHPUnit\Framework\TestCase;

/**
 * @backupGlobals enabled
 */
class GuardedCookieTest extends TestCase
{
    public function testCanBeCreated()
    {
        $this->assertInstanceOf(
            GuardedCookie::class,
            new GuardedCookie('session')
        );
    }

    public function testGetReturnsNullIfNoCookie()
    {
        $cookie = new GuardedCookie('session');
        $this->assertNull($cookie->get());
        $this->assertNull($cookie->getLastError());
    }

    public function testGetValidatesHashPieces()
    {
        $_COOKIE = ['session' => 'fail'];
        $cookie = new GuardedCookie('session');
        $this->assertNull($cookie->get());
        $this->assertInstanceOf(Exception::class, $cookie->getLastError());
        $this->assertStringContainsString(
            'missing the correct number of parts',
            $cookie->getLastError()->getMessage()
        );
    }

    public function testGetValidatesCookieLength()
    {
        $_COOKIE = ['session' => str_repeat('.', 5000)];
        $cookie = new GuardedCookie('session');
        $this->assertNull($cookie->get());
        $this->assertInstanceOf(Exception::class, $cookie->getLastError());
        $this->assertStringContainsString(
            'value is too large',
            $cookie->getLastError()->getMessage()
        );
    }

    public function testGetValidatesCookieName()
    {
        $value = base64_encode(sprintf(
            '%s.%d.%s.%s',
            'wrong_cookie_name',
            time(),
            'some_data',
            'some_hash',
        ));
        $_COOKIE = ['session' => $value];
        $cookie = new GuardedCookie('session');
        $this->assertNull($cookie->get());
        $this->assertInstanceOf(Exception::class, $cookie->getLastError());
        $this->assertStringContainsString(
            'name does not match',
            $cookie->getLastError()->getMessage()
        );
    }

    public function testGettingInvalidHash()
    {
        $value = base64_encode(sprintf(
            '%s.%d.%s.%s',
            'session',
            time(),
            'some_data',
            'invalid_hash',
        ));
        $_COOKIE = ['session' => $value];
        $cookie = new GuardedCookie('session');
        $this->assertNull($cookie->get());
        $this->assertInstanceOf(Exception::class, $cookie->getLastError());
        $this->assertStringContainsString(
            'hash, does not match',
            $cookie->getLastError()->getMessage()
        );
    }

    public function testGettingExpiredCookie()
    {
        $value = base64_encode(sprintf(
            '%s.%d.%s.%s',
            'session',
            1589231808,
            'some_data',
            '801acc74c5684babad571cc8f2dc76cf05fa749540f845d2f7c594fcabe6564e',
        ));
        $_COOKIE = ['session' => $value];
        $cookie = new GuardedCookie('session', [
            'expire_in_seconds' => 10,
        ]);
        $this->assertNull($cookie->get());
        $this->assertInstanceOf(Exception::class, $cookie->getLastError());
        $this->assertStringContainsString(
            'expired',
            $cookie->getLastError()->getMessage()
        );
    }

    public function testGetFailsDecryption()
    {
        $value = base64_encode(sprintf(
            '%s.%d.%s.%s',
            'session',
            1589231808,
            base64_encode(str_repeat('.', 16) . 'some_data'),
            '2b7a9bf05e90f865f05049982f249294ba16420311ac615a3fa5993b083295ae',
        ));
        $_COOKIE = ['session' => $value];
        $cookie = new GuardedCookie('session', [
            'expire_in_seconds' => 31556952 * 5,
        ]);
        $this->assertNull($cookie->get());
        $this->assertInstanceOf(Exception::class, $cookie->getLastError());
        $this->assertStringContainsString(
            'Could not decrypt',
            $cookie->getLastError()->getMessage()
        );
    }

    public function testGetDecodesString()
    {
        $_COOKIE = [
            'session' => 'c2Vzc2lvbi4xNTg5Mjk4ODAyLlRpTVJpUHRnanFBSmk2enVxaWticUhBeU1qVm1VbmRLTlRKa1JIQnhPVzVLYzNCQ1lUZFlXRTR4VlVzNU5YQlFlbXRVVlV4b1VGWlhPVms5LjBkZjFlNTYwZTU1N2FkYTUyOGUxYThmNjc3NTcxOGU4ZGQ2MzgzM2FhZjYyMWFiYjRjZjJkNmJmZTU3ZmQwNTg=',
        ];

        $cookie = new GuardedCookie('session', [
            'expires_in_seconds' => 31556952 * 5,
        ]);
        $data = $cookie->get();
        $this->assertEquals(['hello' => 'tests'], $data);
    }

    public function testSavingCookie()
    {
        $cookie = new GuardedCookie('session');
        $cookie->save(['hello' => 'tests']);
        $encoded_cookie = SetCookie::getInstance()->get('session');
        $this->assertNotNull($encoded_cookie);

        $_COOKIE = ['session' => $encoded_cookie];
        $decoded_value = $cookie->get('session');

        $this->assertEquals(['hello' => 'tests'], $decoded_value);
    }

    public function testSavingLargeCookie()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/(value is too large)/');

        $cookie = new GuardedCookie('session');
        $cookie->save(['too_large' => str_repeat('.', 5000)]);
    }
}
