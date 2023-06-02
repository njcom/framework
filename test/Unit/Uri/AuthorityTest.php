<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Uri;

use Morpho\Uri\Authority;
use Morpho\Uri\IUriComponent;
use Morpho\Testing\TestCase;

use function rawurlencode;

class AuthorityTest extends TestCase {
    public function testAuthority() {
        $authorityStr = 'foo:bar@example.com:80';
        $authority = new Authority($authorityStr);
        $this->assertSame('foo:bar', $authority->userInfo());
        $this->assertSame('example.com', $authority->host());
        $this->assertSame(80, $authority->port());
        $this->assertSame($authorityStr, $authority->toStr(true));
        $this->assertFalse($authority->isNull());
    }

    public function testInterface() {
        $this->assertInstanceOf(IUriComponent::class, new Authority('test'));
    }

    public function testToStr_Encode() {
        $login = 'логин';
        $password = 'пароль';
        $userInfo = "$login:$password";
        $host = 'емаил.com';
        $authority = new Authority("$userInfo@$host:80");
        $this->assertSame(
            rawurlencode($login) . ':' . rawurlencode($password) . '@' . rawurlencode($host) . ':80',
            $authority->toStr(true)
        );
    }

    public function testIsNull() {
        $authority = new Authority(null);
        $this->assertTrue($authority->isNull());
        $authority->setHost('localhost');
        $this->assertFalse($authority->isNull());

        $authority = new Authority('');
        $this->assertFalse($authority->isNull());
    }
}
