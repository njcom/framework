<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Uri;

use Morpho\Uri\IUriComponent;
use Morpho\Uri\Query;
use Morpho\Testing\TestCase;

class QueryTest extends TestCase {
    public function testInterface() {
        $this->assertInstanceOf(IUriComponent::class, new Query());
    }

    public function testNonEmptyConstructorArg() {
        $query = new Query('first=value&arr[]=foo bar&arr[test]=baz');
        $this->assertSame('value', $query['first']);
        $this->assertSame(['foo bar', 'test' => 'baz'], $query['arr']);
        $this->assertCount(2, $query);
    }

    public function testEmptyConstructorArg() {
        $query = new Query('');
        $this->assertFalse($query->isNull());
        $this->assertSame('', $query->toStr(true));
    }

    public function testQueryArgWithoutValueOrWithEmptyValue() {
        $query = new Query('foo');
        $this->assertSame('foo', $query->toStr(true));

        $query = new Query('foo=');
        $this->assertSame('foo=', $query->toStr(true));
    }

    public function testQuery() {
        $query = new Query();

        $this->assertTrue($query->isNull());
        $this->assertSame('', $query->toStr(true));

        $query['foo'] = 'bar';
        $this->assertFalse($query->isNull());

        $query['has space'] = 'тест';
        $this->assertSame('foo=bar&has%20space=%D1%82%D0%B5%D1%81%D1%82', $query->toStr(true));

        unset($query['foo']);
        $this->assertSame('has%20space=%D1%82%D0%B5%D1%81%D1%82', $query->toStr(true));
        $this->assertFalse($query->isNull());

        unset($query['has space']);
        $this->assertTrue($query->isNull());
        $this->assertSame('', $query->toStr(true));
    }
}
