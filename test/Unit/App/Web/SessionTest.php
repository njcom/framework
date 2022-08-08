<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web;

use Morpho\App\Web\Session;
use Morpho\Testing\TestCase;

use function uniqid;

class SessionTest extends TestCase {
    private Session $session;

    protected function setUp(): void {
        parent::setUp();
        $_SESSION = [];
        $this->session = new Session(__CLASS__, false);
    }

    public function testStorageOfClosure() {
        $uniqId = uniqid();
        $closure = function () use ($uniqId) {
            return $uniqId;
        };
        $this->session->fn = $closure;
        $this->assertTrue(isset($this->session->fn));
        unset($this->session);
        $session = new Session(__CLASS__, false);
        $fn = $session->fn;
        $this->assertEquals($uniqId, $fn());
    }

    public function testImplementsInterfaces() {
        $this->assertInstanceOf('\\Countable', $this->session);
        $this->assertInstanceOf('\\Iterator', $this->session);
        $this->assertInstanceOf('\\ArrayAccess', $this->session);
    }

    public function testStorageKey() {
        $this->assertEquals(__CLASS__, $this->session->storageKey());
    }

    public function testMagicMethods() {
        $this->assertTrue(empty($_SESSION[Session::KEY][__CLASS__]));
        $this->assertCount(0, $this->session);
        $this->assertFalse(isset($this->session->foo));
        $this->assertTrue(empty($this->session->foo));
        $this->session->foo = 'bar';
        $this->assertEquals('bar', $this->session->foo);
        $this->assertCount(1, $this->session);
        $this->assertTrue(isset($this->session->foo));
        $this->assertFalse(empty($this->session->foo));
        $this->assertFalse(empty($_SESSION[Session::KEY][__CLASS__]));
        unset($this->session->foo);
        $this->assertCount(0, $this->session);
        $this->assertFalse(isset($this->session->foo));
        $this->assertTrue(empty($this->session->foo));
        $this->assertTrue(empty($_SESSION[Session::KEY][__CLASS__]));
    }

    public function testIterator() {
        $this->assertFalse($this->session->valid());
        $data = [1, 2, ['foo' => 'bar']];
        $this->session->fromArray($data);
        $this->assertNull($this->session->rewind());
        $this->assertTrue($this->session->valid());
        $this->assertEquals(0, $this->session->key());
        $this->assertEquals(1, $this->session->current());
        $this->assertNull($this->session->next());
        $this->assertTrue($this->session->valid());
        $this->assertEquals(1, $this->session->key());
        $this->assertEquals(2, $this->session->current());
        $this->assertNull($this->session->next());
        $this->assertTrue($this->session->valid());
        $this->assertEquals(2, $this->session->key());
        $this->assertEquals(['foo' => 'bar'], $this->session->current());
        $this->assertNull($this->session->next());
        $this->assertFalse($this->session->valid());
    }

    public function testArrayAccess() {
        $this->session['foo'] = [];
        $this->session['foo'][] = 'something';
        $this->assertEquals('something', $this->session['foo'][0]);
    }
}