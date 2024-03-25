<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Base;

use JsonSerializable;
use Morpho\Base\{Err, IFunctor, IMonad, Ok, Result};
use Morpho\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

use function strlen;
use function substr;

class ResultTest extends TestCase {
    public static function dataInterface() {
        yield [new Ok()];
        yield [new Err()];
    }

    #[DataProvider('dataInterface')]
    public function testInterface($instance) {
        $this->assertInstanceOf(IMonad::class, $instance);
        $this->assertInstanceOf(Result::class, $instance);
    }

    public function testBind_Ok() {
        $ok1Val = 'foo';
        $ok2Val = 'bar';
        $res = (new Ok($ok1Val))
            ->bind(
                function ($val) use (&$captured, $ok2Val) {
                    $captured = $val;
                    return new Ok($ok2Val);
                }
            );
        $this->assertSame($ok1Val, $captured);
        $this->assertEquals(new Ok($ok2Val), $res);
    }

    public function testBind_Err() {
        $okVal = 'foo';
        $errVal = 'bar';
        $res = (new Ok($okVal))->bind(
            function ($val) use (&$captured, $errVal) {
                $captured = $val;
                return new Err($errVal);
            }
        );
        $this->assertSame($okVal, $captured);
        $this->assertEquals(new Err($errVal), $res);
    }

    public static function dataComposition() {
        $req = [
            'name'  => "Phillip",
            'email' => "phillip@example",
        ];
        yield [
            $req,
            new Ok($req),
        ];

        $req = [
            'name'  => 'Phillip',
            'email' => "phillip@localhost",
        ];
        yield [
            $req,
            new Err('No email from localhost is allowed.'),
        ];
    }

    #[DataProvider('dataComposition')]
    public function testComposition($req, $expected) {
        // Adopted from https://docs.microsoft.com/en-us/dotnet/fsharp/language-reference/results

        $validateName = function ($req): Result {
            if ($req['name'] === null) {
                return new Err('No name found.');
            }
            if ($req['name'] === '') {
                return new Err('Name is empty.');
            }
            if ($req['name'] === 'bananas') {
                return new Err('Bananas is not a name.');
            }
            return new Ok($req);
        };

        $validateEmail = function ($req) {
            if ($req['email'] === null) {
                return new Err('No email found.');
            }
            if ($req['email'] === '') {
                return new Err('Email is empty.');
            }
            if (substr($req['email'], -strlen('localhost')) === 'localhost') {
                return new Err("No email from localhost is allowed.");
            }
            return new Ok($req);
        };

        $validateRequest = function (Result $reqResult) use ($validateName, $validateEmail, $expected): Result {
            return $reqResult->bind($validateName)
                ->bind($validateEmail)
                ->bind(
                    function ($val) use ($expected) {
                        if ($expected instanceof Err) {
                            throw new RuntimeException("Must not be called");
                        }
                        return new Ok($val);
                    }
                );
        };

        $res1 = $validateRequest(new Ok($req));
        $this->assertEquals($expected, $res1);
    }

    public function testVal() {
        $this->assertNull((new Ok())->val());
        $this->assertNull((new Err())->val());
        $this->assertSame(3, (new Ok(3))->val());
        $this->assertSame(4, (new Err(4))->val());
    }

    public function testMonadLaws_LeftIdentity() {
        $fn = function ($v) {
            return new Ok($v);
        };
        $val = 'abc';
        $this->assertEquals(
            $fn($val),
            (new Ok($val))->bind($fn),
        );
    }

    public function testMonadLaws_RightIdentity() {
        $fn = function ($v) {
            return new Ok($v);
        };
        $this->assertEquals(
            new Ok('abc'),
            (new Ok('abc'))->bind($fn)
        );
    }

    public function testMonadLaws_Associativity() {
        $f = function ($v) {
            return new Ok($v * 4);
        };
        $g = function ($v) {
            return new Ok($v * 3);
        };

        $this->assertEquals(
            (new Ok(5))->bind(fn ($x) => $f($x)->bind($g)),
            (new Ok(5))->bind($f)->bind($g)
        );
    }

    // Functor
    public function testMap() {
        $res = (new Ok(2))->map(fn ($val) => $val - 3);
        $this->assertInstanceOf(IFunctor::class, $res);
        $this->assertSame(-1, $res->val());
    }

    // Applicative
    public function testApply() {
        $fn = fn ($v) => $v - 2;
        $res = (new Ok(5))->apply(new Ok($fn));
        $this->assertInstanceOf(Ok::class, $res);
        $this->assertSame(3, $res->val());
    }

    public function testIsOk() {
        $this->assertTrue((new Ok())->isOk());
        $this->assertFalse((new Err())->isOk());
    }

    public function testJsonSerialization() {
        $val = ['foo' => 'bar'];

        $result = new Ok($val);
        $this->assertInstanceOf(JsonSerializable::class, $result);
        $this->assertJsonStringEqualsJsonString(json_encode(['ok' => $val]), json_encode($result));

        $result = new Err($val);
        $this->assertInstanceOf(JsonSerializable::class, $result);
        $this->assertJsonStringEqualsJsonString(json_encode(['err' => $val]), json_encode($result));
    }
}
