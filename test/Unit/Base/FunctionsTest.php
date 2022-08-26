<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Base;

use ArrayIterator;
use IteratorAggregate;
use Morpho\Base\IDisposable;
use Morpho\Base\IFn;
use Morpho\Test\Unit\Base\FunctionsTest\Foo;
use Morpho\Testing\TestCase;
use RuntimeException;
use SplStack;
use stdClass;
use Stringable;
use UnexpectedValueException;

use function call_user_func;
use function count;
use function fclose;
use function file_put_contents;
use function get_class_methods;
use function Morpho\Base\{all, append, appendFn, camelize, camelizeKeys, capture, cartesianProduct, classify, compose, dasherize, deleteDups, enumVals, formatBytes, formatFloat, fromJson, humanize, indent, index, isEnumCase, isSubset, isUtf8Text, last, lastPos, lines, memoize, merge, normalizeEols, not, op, partial, permutations, pipe, prepend, prependFn, q, qq, sanitize, setProps, setsEqual, shorten, showLn, showOk, subsets, symDiff, titleize, toIt, toJson, tpl, etrim, ucfirst, underscore, underscoreKeys, unindent, union, uniqueName, unsetMany, unsetOne, unsetRecursive, using, caseVal, waitUntilNumOfAttempts, waitUntilTimeout, words, wrap, wrapFn};
use function ob_get_clean;
use function ob_start;
use function property_exists;

class FunctionsTest extends TestCase {
    private $tmpHandle;

    protected function tearDown(): void {
        parent::tearDown();
        if (isset($this->tmpHandle)) {
            fclose($this->tmpHandle);
        }
    }

    public function testIsEnumCase() {
        $this->assertFalse(isEnumCase($this));
        $this->assertFalse(isEnumCase(__CLASS__));
        $this->assertTrue(isEnumCase(Foo::First));
    }

    public function testCaseVal() {
        $this->assertSame(Foo::First->value, caseVal(Foo::First));
        $this->assertSame(123, caseVal(123));
    }

    public function testEnumVals() {
        $this->assertSame(['First' => 'abc', 'Second' => 'def'], enumVals(Foo::class));
        $this->assertSame(['abc', 'def'], enumVals(Foo::class, false));
    }

    // -------------------------------------------------------------------------

    public function dataAll_CommonCases() {
        $falsePredicate = fn () => false;
        $truePredicate = fn () => true;
        $emptyString = '';
        $emptyArr = [];
        $emptyArrIt = new ArrayIterator($emptyArr);
        $gen = function () {
            yield 'foo';
            yield 'bar';
        };
        yield [
            true,
            $falsePredicate,
            $emptyString,
        ];
        yield [
            true,
            $falsePredicate,
            $emptyArr,
        ];
        yield [
            true,
            $falsePredicate,
            $emptyArrIt,
        ];
        yield [
            false, // must return the same value as predicate
            $falsePredicate,
            $gen(),
        ];
        yield [
            true, // must return the same value as predicate
            $truePredicate,
            $gen(),
        ];
    }

    /**
     * @dataProvider dataAll_CommonCases
     */
    public function testAll_CommonCases(bool $expected, callable $predicate, mixed $list) {
        $this->assertSame($expected, all($predicate, $list));
    }

    public function dataAll_StringAndStringable_Utf8String() {
        yield [
            'ℚ ⊂ ℝ ⊂ ℂ',
        ];
        yield [
            new class implements Stringable {
                public function __toString(): string {
                    return 'ℚ ⊂ ℝ ⊂ ℂ';
                }
            },
        ];
    }

    /**
     * @dataProvider dataAll_StringAndStringable_Utf8String
     */
    public function testAll_StringAndStringable_Utf8String($s) {
        $called = [];
        $this->assertTrue(
            all(
                function ($val, $key) use (&$called) {
                    $called[] = func_get_args();
                    return true;
                },
                $s
            )
        );
        $this->assertCount(9, $called);
        $this->assertSame(['ℚ', 0], $called[0]);
        $this->assertSame([' ', 1], $called[1]);
        $this->assertSame(['⊂', 2], $called[2]);
        $this->assertSame(['ℂ', 8], $called[8]);
    }

    // -------------------------------------------------------------------------

    public function dataToIt() {
        $it = new ArrayIterator(['foo', 'bar']);
        $itAggr = new class ($it) implements IteratorAggregate {
            private ArrayIterator $it;

            public function __construct(ArrayIterator $it) {
                $this->it = $it;
            }

            public function getIterator(): \Traversable {
                return $this->it;
            }
        };
        yield [
            $itAggr,
            $itAggr,
        ];
        yield [
            [],
            '',
        ];
        yield [
            $it,
            $it,
        ];
        $gen = (function () {
            yield 'foo';
            yield 'bar';
        })();
        yield [
            $gen,
            $gen,
        ];
        yield [
            ['foo', 'bar'],
            ['foo', 'bar'],
        ];
    }

    /**
     * @dataProvider dataToIt
     */
    public function testToIt($expected, $it) {
        $this->assertSame($expected, toIt($it));
    }

    // -------------------------------------------------------------------------

    public function dataLines() {
        yield ["\n"];   // *nix
        yield ["\r\n"]; // Win
        yield ["\r"];   // old Mac
    }

    /**
     * @dataProvider dataLines
     */
    public function testLines($sep) {
        $test = function (string $val): array {
            return iterator_to_array(lines($val, true, true));
        };
        $this->assertSame([], $test(''));
        $this->assertSame([], $test("$sep"));
        $this->assertSame(['one'], $test("one"));
        $this->assertSame(['one'], $test("one$sep"));
        $this->assertSame(['one'], $test("one$sep$sep"));
        $this->assertSame(['one', 'two'], $test("one{$sep}two"));
        $this->assertSame(['one', 'two'], $test("one{$sep}two$sep"));
    }

    public function testLines_AcceptsStringable() {
        $this->assertSame(['foo', 'bar'], iterator_to_array(lines(new class implements Stringable {
            public function __toString(): string {
                return "foo\nbar";
            }
        })));
    }

    public function testWaitUntilNoOfAttempts_PredicateReturnsTrueOnSomeIteration() {
        $called = 0;
        $predicate = function () use (&$called) {
            if ($called === 4) {
                return 'abc';
            }
            $called++;
            return false;
        };

        $this->assertSame('abc', waitUntilNumOfAttempts($predicate, 0));
        $this->assertEquals(4, $called);
    }

    public function testWaitUntilNoOfAttempts_PredicateReturnsFalseAlways() {
        $called = 0;
        $predicate = function () use (&$called) {
            $called++;
            return false;
        };
        $this->expectException(RuntimeException::class, 'The number of attempts has been reached');
        waitUntilNumOfAttempts($predicate, 0);
    }

    public function testWaitUntilTimeout_Timeout() {
        $this->expectException(RuntimeException::class, 'The timeout has been reached');
        $this->assertNull(waitUntilTimeout(fn () => usleep(2000), 1000));
    }

    public function testWaitUntilTimeout_NoTimeout() {
        $this->assertSame('abc', waitUntilTimeout(fn () => 'abc', 1000));
    }

    public function testNot() {
        $fn = not(
            function (...$args) use (&$calledWithArgs) {
                $calledWithArgs = $args;
                return true;
            }
        );
        $this->assertFalse($fn('foo', 'bar'));
        $this->assertEquals(['foo', 'bar'], $calledWithArgs);
    }

    public function testToAndFromJson() {
        $v = [
            'foo' => 'bar',
            1     => new class {
                public $t = 123;
            },
        ];
        $json = toJson($v);
        $this->assertIsString($json);
        $this->assertNotEmpty($json);
        $v1 = fromJson($json);
        $this->assertCount(count($v), $v1);
        $this->assertEquals($v['foo'], $v1['foo']);
        $this->assertEquals((array) $v[1], $v1[1]);
    }

    public function testFromJson_InvalidJsonThrowsException() {
        $this->expectException(RuntimeException::class, "Invalid JSON or too deep data");
        fromJson('S => {');
    }

    public function testToJson() {
        $this->assertSame('"UTF-8 не должен эскейпиться"', toJson("UTF-8 не должен эскейпиться"));
    }

    public function testNormalizeEols() {
        $this->assertEquals("foo\nbar\nbaz\n", normalizeEols("foo\r\nbar\rbaz\r\n"));
        $this->assertEquals("", normalizeEols(""));
    }

    public function testShowLn_NoArgsWritesSingleLine() {
        ob_start();
        showLn();
        $this->assertEquals("\n", ob_get_clean());
    }

    public function testShowLn_SingleArg() {
        ob_start();
        showLn("Printed");
        $this->assertEquals("Printed\n", ob_get_clean());
    }

    public function testShowLn_MultipleArgs() {
        ob_start();
        showLn("bee", "ant");
        $this->assertEquals("bee\nant\n", ob_get_clean());
    }

    public function testShowLn_IterableArg() {
        $val = new ArrayIterator(['foo', 'bar', 'baz']);
        ob_start();
        showLn($val);
        $this->assertEquals("foo\nbar\nbaz\n", ob_get_clean());
    }

    public function testShowOk_WithoutArgs() {
        ob_start();
        showOk();
        $this->assertEquals("OK\n", ob_get_clean());
    }

    public function testShowOk_WithArgs() {
        ob_start();
        showOk("Test");
        $this->assertSame("OK: Test\n", ob_get_clean());
    }

    public function testShorten() {
        $this->assertEquals('foo...', shorten('foobarb', 6));
        $this->assertEquals('foobar', shorten('foobar', 6));
        $this->assertEquals('fooba', shorten('fooba', 6));
        $this->assertEquals('foob', shorten('foob', 6));
        $this->assertEquals('foo', shorten('foo', 6));
        $this->assertEquals('fo', shorten('fo', 6));
        $this->assertEquals('f', shorten('f', 6));
        $this->assertEquals('', shorten('', 6));

        $this->assertEquals('foob!!', shorten('foobarb', 6, '!!'));
    }

    public function testUniqueName() {
        $this->assertEquals('unique0', uniqueName());
        $this->assertEquals('unique1', uniqueName());
    }

    public function testDeleteDups() {
        $this->assertEquals('12332', deleteDups(122332, 2));
        $this->assertEquals('aaa', deleteDups('aaa', 'b'));
        $this->assertEquals('a', deleteDups('aaa', 'a'));
    }

    public function testEtrim() {
        $t = [
            '  ff  ',
            ' foo ' => [
                ' bar-',
            ],
        ];
        $expected = [
            'ff',
            ' foo ' => [
                'bar',
            ],
        ];
        $this->assertEquals($expected, etrim($t, '-'));

        $this->assertEquals('ff', etrim('__ ff  ', '_'));
    }

    public function testLast() {
        $this->assertSame('foo', last('foo', '/'));
        $this->assertSame('bar', last('foo/bar', '/'));
    }

    public function testSanitize() {
        $input = "foo[\"1][b'ar]\x00`ls`;&quot;<>";
        $this->assertEquals('foo1barlsquot', sanitize($input, '_'));
    }

    public function testClassify() {
        $this->assertEquals('Foobar', classify('foobar'));
        $this->assertEquals('Foobar', classify("&\tf\no<>o\x00`bar"));
        $this->assertEquals('FooBar', classify('foo-bar'));
        $this->assertEquals('FooBar', classify('FooBar'));
        $this->assertEquals('FooBar', classify('foo_bar'));
        $this->assertEquals('FooBar', classify('-foo-bar-'));
        $this->assertEquals('FooBar', classify('_foo-bar_'));
        $this->assertEquals('FooBar', classify('_foo-bar_'));
        $this->assertEquals('FooBar', classify('_Foo_Bar_'));
        $this->assertEquals('FooBarBaz', classify('FooBar_Baz'));
        $this->assertEquals('FooBar', classify("  foo  bar  "));
        $this->assertEquals('Foo\\Bar\\Baz', classify('foo/bar/baz'));
        $this->assertEquals('Foo\\Bar\\Baz', classify('foo\\bar\\baz'));
        //$this->assertEquals('\\Foo\\Bar\\BazTest', classify('foo\\bar/baz-test', true));
    }

    public function testUnderscore() {
        $this->assertCommon('underscore');
        $this->assertEquals('foo_bar', underscore('Foo Bar'));
        $this->assertEquals('foo_bar', underscore('FooBar'));
        $this->assertEquals('foo_bar', underscore('foo-bar'));
        $this->assertEquals('foo_bar', underscore('_foo_bar_'));
        $this->assertEquals('foo_bar', underscore('-foo_bar-'));
        $this->assertEquals('_foo_bar_', underscore('-foo_bar-', false));
        $this->assertEquals('foo_bar', underscore('-Foo_Bar-'));
        $this->assertEquals('foo_bar_baz', underscore('FooBar-Baz'));
        $this->assertEquals('foo_bar', underscore("  foo  bar  "));
        $this->assertEquals('foo__bar', underscore('foo__bar'));
    }

    private function assertCommon($fn) {
        $fn = 'Morpho\Base\\' . $fn;
        $this->assertEquals('foobar', call_user_func($fn, 'foobar'));
        $this->assertEquals('foobar', call_user_func($fn, "&\tf\no<>o\x00`bar"));
    }

    public function testDasherize() {
        $this->assertCommon('dasherize');
        $this->assertEquals('foo-bar', dasherize('foo-bar'));
        $this->assertEquals('foo-bar', dasherize('FooBar'));
        $this->assertEquals('foo-bar', dasherize('foo_bar'));
        $this->assertEquals('foo-bar', dasherize('-foo-bar-'));
        $this->assertEquals('foo-bar', dasherize('_foo-bar_'));
        $this->assertEquals('-foo-bar-', dasherize('_foo-bar_', '', false));
        $this->assertEquals('foo-bar', dasherize('_Foo_Bar_'));
        $this->assertEquals('foo-bar-baz', dasherize('FooBar_Baz'));
        $this->assertEquals('foo-bar', dasherize("  foo  bar  "));
        $this->assertEquals('foo-bar', dasherize('fooBar'));
        $this->assertEquals('foo--bar', dasherize('foo--bar'));
    }

    public function testCamelize() {
        $this->assertCommon('camelize');
        $this->assertEquals('fooBar', camelize('foo-bar'));
        $this->assertEquals('fooBar', camelize('FooBar'));
        $this->assertEquals('FooBar', camelize('FooBar', true));
        $this->assertEquals('FooBar', camelize('fooBar', true));
        $this->assertEquals('fooBar', camelize('foo_bar'));
        $this->assertEquals('fooBar', camelize('-foo-bar-'));
        $this->assertEquals('fooBar', camelize('_foo-bar_'));
        $this->assertEquals('fooBar', camelize('_foo-bar_'));
        $this->assertEquals('fooBar', camelize('_Foo_Bar_'));
        $this->assertEquals('fooBarBaz', camelize('FooBar_Baz'));
        $this->assertEquals('fooBar', camelize("  foo  bar  "));
    }

    public function testHumanize() {
        $this->assertEquals('v&quot;&quot;v pe te Adam bob camel ized.', humanize('v""v pe_te Adam bob camelIzed.'));
        $this->assertEquals('v""v pe te Adam bob camel ized.', humanize('v""v pe_te Adam bob camelIzed.', false));
    }

    public function testTitleize() {
        $this->assertEquals('V&quot;&quot;v Pe Te Adam Bob Camel Ized.', titleize('v""v pe_te Adam bob camelIzed.'));
        $this->assertEquals(
            'V""v pe te Adam bob camel ized.',
            titleize('v""v pe_te Adam bob camelIzed.', false, false)
        );
    }

    public function testPartial() {
        $add = function ($a, $b) {
            return $a + $b;
        };
        $add2 = partial($add, '2');
        $this->assertEquals(5, $add2(3));

        $concatenate = function ($a, $b, $c, $d, $e, $f) {
            return $a . $b . $c . $d . $e . $f;
        };
        $appendPrefix = partial($concatenate, 'foo', 'bar', 'baz');
        $this->assertEquals('foobarbazHelloWorld!', $appendPrefix('Hello', 'World', '!'));
    }

    public function testCompose_Closure() {
        $g = function ($a) {
            return 'g' . $a;
        };
        $f = function ($b) {
            return 'f' . $b;
        };
        $this->assertEquals('fghello', compose($f, $g)('hello'));
    }

    public function dataCompose_IFnWithClosure() {
        $ifn = new class implements IFn {
            public function __invoke(mixed $value): mixed {
                return 'IFn called ' . $value;
            }
        };
        $closure = function ($value) {
            return 'Closure called ' . $value;
        };
        return [
            [
                'IFn called Closure called test',
                $ifn,
                $closure,
            ],
            [
                'Closure called IFn called test',
                $closure,
                $ifn,
            ],
            [
                'IFn called IFn called test',
                $ifn,
                $ifn,
            ],
        ];
    }

    /**
     * @dataProvider dataCompose_IFnWithClosure
     */
    public function testCompose_IFnWithClosure($expected, $f, $g) {
        $this->assertSame($expected, compose($f, $g)('test'));
    }

    public function dataQ() {
        return $this->dataQ_("'");
    }

    private function dataQ_(string $quote) {
        return [
            [
                "$quote$quote",
                '',
            ],
            [
                "{$quote}123{$quote}",
                123,
            ],
            [
                "{$quote}Hello{$quote}",
                'Hello',
            ],
            [
                [
                    "{$quote}foo{$quote}",
                    "{$quote}bar{$quote}",
                ],
                [
                    'foo',
                    'bar',
                ],
            ],
            [
                [
                    'a' => "{$quote}foo{$quote}",
                    'b' => "{$quote}bar{$quote}",
                ],
                [
                    'a' => 'foo',
                    'b' => 'bar',
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataQ
     */
    public function testQ($expected, $actual) {
        $this->assertSame($expected, q($actual));
    }

    public function dataQQ() {
        return $this->dataQ_('"');
    }

    /**
     * @dataProvider dataQQ
     */
    public function testQQ($expected, $actual) {
        $this->assertSame($expected, qq($actual));
    }

    public function testFormatBytes() {
        $bytes = "\x2f\xe0\xab\x00\x01\xe0";
        $this->assertEquals('\x2f\xe0\xab\x00\x01\xe0', formatBytes($bytes));
        $this->assertEquals('\x2F\xE0\xAB\x00\x01\xE0', formatBytes($bytes, '\x%02X'));
    }

    public function testFormatFloat() {
        $this->assertSame('-0.12', formatFloat(-0.1212323));
        $this->assertSame('-0.13', formatFloat(-0.1252323));
        $this->assertSame('0.00', formatFloat(0));
        $this->assertSame('0.12', formatFloat(0.1212323));
        $this->assertSame('0.13', formatFloat(0.1252323));
    }

    public function testMemoize() {
        $sum = function ($a, $b) use (&$sumCalled) {
            $sumCalled++;
            return $a + $b;
        };

        $memoizedSum = memoize($sum);

        $res = $memoizedSum(2, 3);
        $this->assertSame(1, $sumCalled);
        $this->assertSame(5, $res);

        $res = $memoizedSum(2, 3);
        $this->assertSame(1, $sumCalled);
        $this->assertSame(5, $res);

        $sub = function ($a, $b) use (&$subCalled) {
            $subCalled++;
            return $a - $b;
        };

        $memoizedSub = memoize($sub);

        $res = $memoizedSub(5, 3);
        $this->assertSame(1, $subCalled);
        $this->assertSame(2, $res);

        $res = $memoizedSub(5, 3);
        $this->assertSame(1, $subCalled);
        $this->assertSame(2, $res);
    }

    public function testMemoize_FunctionReturningNull() {
        $null = function () use (&$called) {
            $called++;
            return null;
        };
        $memoized = memoize($null);
        $this->assertNull($memoized());
        $this->assertSame(1, $called);

        $this->assertNull($memoized());
        $this->assertSame(1, $called);
    }

    public function testMemoize_DifferentArgsAfterFirstCall() {
        $sum = function ($a, $b) use (&$sumCalled) {
            $sumCalled++;
            return $a + $b;
        };

        $memoizedSum = memoize($sum);

        $res = $memoizedSum(2, 3);
        $this->assertSame(1, $sumCalled);
        $this->assertSame(5, $res);

        $this->assertSame(20, $memoizedSum(7, 13));
        $this->assertSame(2, $sumCalled);

        $this->assertSame(20, $memoizedSum(7, 13));
        $this->assertSame(2, $sumCalled);
    }

    public function testCapture() {
        $this->assertSame('capture works', capture(function () {
            echo 'capture works';
        }));
    }

    public function testTpl() {
        $code = '<?php echo "Hello $world";';
        $filePath = $this->createTmpFile();
        file_put_contents($filePath, $code);
        $this->assertSame(
            'Hello World!',
            tpl($filePath, ['world' => 'World!'])
        );
    }

    /**
     * Modified version of the providesOperator() from the https://github.com/nikic/iter
     * @Copyright (c) 2013 by Nikita Popov.
     */
    public function dataOp() {
        return [
            ['instanceof', new stdClass, 'stdClass', true],
            ['*', 3, 2, 6],
            ['/', 3, 2, 1.5],
            ['%', 3, 2, 1],
            ['+', 3, 2, 5],
            ['-', 3, 2, 1],
            ['.', 'foo', 'bar', 'foobar'],
            ['<<', 1, 8, 256],
            ['>>', 256, 8, 1],
            ['<', 3, 5, true],
            ['<=', 5, 5, true],
            ['>', 3, 5, false],
            ['>=', 3, 5, false],
            ['==', 0, 'foo', false],
            ['!=', 0, 'foo', true],
            ['!=', 1, 'foo', true],
            ['===', 0, 'foo', false],
            ['!==', 0, 'foo', true],
            ['&', 3, 1, 1],
            ['|', 3, 1, 3],
            ['^', 3, 1, 2],
            ['&&', true, false, false],
            ['||', true, false, true],
            ['**', 2, 4, 16],
            ['<=>', [0 => 1, 1 => 0], [1 => 0, 0 => 1], 0],
            ['<=>', '2e1', '1e10', -1],
            ['<=>', new stdClass(), new SplStack(), 1],
            ['<=>', new SplStack(), new stdClass(), 1],
        ];
    }

    /**
     * Modified version of the testOperator() from the https://github.com/nikic/iter
     * @Copyright (c) 2013 by Nikita Popov.
     * @dataProvider dataOp
     */
    public function testOp($op, $a, $b, $result) {
        $fn1 = op($op);
        $fn2 = op($op, $b);

        $this->assertSame($result, $fn1($a, $b));
        $this->assertSame($result, $fn2($a));
    }

    public function testUsing_CallsInvoke() {
        $disposable = new class implements IDisposable {
            public $disposeArgs;
            public $invokeArgs;

            public function dispose(): void {
                $this->disposeArgs = func_get_args();
            }

            public function __invoke(mixed $value): mixed {
                $this->invokeArgs = func_get_args();
                return 'returnedFromInvoke';
            }
        };
        $val = 'foo';
        $this->assertSame('returnedFromInvoke', using($disposable, $val));
        $this->assertSame([$val], $disposable->invokeArgs);
        $this->assertSame([], $disposable->disposeArgs);
    }

    public function testUsing_CallsDispose() {
        $disposable = new class implements IDisposable {
            public $disposeArgs;
            public $invokeArgs;

            public function dispose(): void {
                $this->disposeArgs = func_get_args();
            }

            public function __invoke(mixed $value): mixed {
                throw new RuntimeException('Some error');
            }
        };
        $val = 'bar';
        try {
            using($disposable, $val);
            $this->fail();
        } catch (RuntimeException $e) {
            $this->assertSame('Some error', $e->getMessage());
        }
        $this->assertNull($disposable->invokeArgs);
        $this->assertSame([], $disposable->disposeArgs);
    }

    public function testLastPos() {
        $this->assertSame(0, lastPos('', ''));
        $this->assertSame(0, lastPos('', '', -1));
        $this->assertSame(0, lastPos('/', ''));
        $this->assertFalse(lastPos('', '/'));
        $this->assertFalse(lastPos('', '/', -1));
        $this->assertSame(0, lastPos('f', 'f'));
        $this->assertFalse(lastPos('f', 'fo'));
        $this->assertFalse(lastPos('f', 'fo', -1));
        $this->assertSame(0, lastPos('fo', 'fo', -1));
        $this->assertSame(2, lastPos('fofo', 'fo', -1));
    }

    public function testSetProps() {
        $conf = [
            'privateFoo'   => 'abc',
            'protectedBar' => 'defg',
            'publicBaz'    => 'hig',
        ];
        $instance = new class($conf) {
            private $privateFoo;
            protected $protectedBar;
            public $publicBaz;

            public $setPropsResult;

            public function __construct(array $conf) {
                $this->setPropsResult = setProps($this, $conf);
            }

            public function protectedBar() {
                return $this->protectedBar;
            }

            public function privateFoo() {
                return $this->privateFoo;
            }
        };


        $this->assertSame($instance, $instance->setPropsResult);
        $this->assertSame($conf['publicBaz'], $instance->publicBaz);
        $this->assertSame($conf['protectedBar'], $instance->protectedBar());
        $this->assertSame($conf['privateFoo'], $instance->privateFoo());
        $this->assertSame(['__construct', 'protectedBar', 'privateFoo'], get_class_methods($instance));
    }

    public function testSetProps_NotDeclaredProperty() {
        $instance = new class {
            private $privateFoo;
            protected $protectedBar;
            public $publicBaz;

            public function setProps($conf) {
                setProps($this, $conf);
            }
        };
        $conf = [
            'privateFoo'   => 'abc',
            'protectedBar' => 'defg',
            'publicBaz'    => 'hig',
            'notDeclared'  => 'some',
        ];
        try {
            $instance->setProps($conf);
            $this->fail();
        } catch (UnexpectedValueException $e) {
            $this->assertStringContainsString("Unknown property 'notDeclared'", $e->getMessage());
        }
        $this->assertFalse(property_exists($instance, 'notDeclared'));
    }

    public function testWords() {
        $this->assertSame([], words(''));
        $this->assertSame([], words('    '));
        $this->assertSame(['foo'], words(' foo   '));
        $this->assertSame(['foo', 'bar'], words(' foo   bar    '));
        $this->assertSame(['foo', 'bar'], words('foo   bar'));
        $this->assertSame(['bar'], words('bar'));
        $this->assertSame(['bar', '123'], words('bar    123   '));
        $this->assertSame(['123'], words(123));

        $this->assertSame(['foo'], words('foo', 2));
        $this->assertSame(['foo', 'bar baz'], words('foo bar baz', 2));
    }

    public function testUcfirst() {
        $this->assertSame('', ucfirst(''));
        $this->assertSame('Foo', ucfirst('foo'));
        $this->assertSame('Тест', ucfirst('тест'));
    }

    public function testIndent_Uindent() {
        $orig = <<<OUT
begin
        Bar
            baz
    Some text
end
OUT;
        $expected = <<<OUT
    begin
            Bar
                baz
        Some text
    end
OUT;
        $actual = indent($orig);
        $this->assertSame($expected, $actual);
        $this->assertSame($orig, unindent($actual));
    }

    public function testPrepend() {
        $this->assertSame([], prepend([], 'pre'));
        $this->assertSame(['pre123', 'pre123'], prepend(['123', 123], 'pre'));
        $this->assertSame('pre123', prepend('123', 'pre'));
        $this->assertSame('pre123', prepend(123, 'pre'));
    }

    public function testPrependFn() {
        $this->assertSame('prefoo', prependFn('pre')('foo'));
    }

    public function testAppend() {
        $this->assertSame([], append([], 'post'));
        $this->assertSame(['123post', '123post'], append(['123', 123], 'post'));
        $this->assertSame('123post', append('123', 'post'));
        $this->assertSame('123post', append(123, 'post'));
    }

    public function testAppendFn() {
        $this->assertSame('foopost', appendFn('post')('foo'));
    }

    public function testWrap() {
        $this->assertSame([], wrap([], 'pre', 'post'));
        $this->assertSame(['pre123post', 'pre123post'], wrap(['123', 123], 'pre', 'post'));
        $this->assertSame('pre123post', wrap('123', 'pre', 'post'));
        $this->assertSame('pre123post', wrap(123, 'pre', 'post'));

        $this->assertSame([], wrap([], '!'));
        $this->assertSame(['!123!', '!123!'], wrap(['123', 123], '!'));
        $this->assertSame('!123!', wrap('123', '!'));
        $this->assertSame('!123!', wrap(123, '!'));
    }

    public function testWrapFn() {
        $this->assertSame('prefoopost', wrapFn('pre', 'post')('foo'));
    }

    public function testSubsets_AllSubsets() {
        $this->assertEquals([[]], subsets([]));
        $this->assertEquals([[], [1]], subsets([1]));
        $subsets = subsets(['a', 'b', 'c']);
        sort($subsets);
        $this->assertSame(
            [
                [],
                ['a'],
                ['b'],
                ['c'],
                ['a', 'b'],
                ['a', 'c'],
                ['b', 'c'],
                ['a', 'b', 'c'],
            ],
            $subsets
        );
    }

    public function testSubsets_KSubsets() {
        $this->markTestIncomplete();
        $this->assertSame([[]], subsets([], 0));
        $this->assertSame([[]], subsets([1, 2], 0));
    }

    public function dataIsSubset() {
        return [
            [
                true,
                [],
                [],
            ],
            [
                false,
                [],
                ['a', 'b'],
            ],
            [
                true,
                ['a', 'b'],
                [],
            ],
            [
                true,
                ['a', 'b'],
                ['a'],
            ],
            [
                true,
                ['a', 'b'],
                ['a', 'b'],
            ],
            [
                false,
                ['a', 'b'],
                ['a', 'b', 'c'],
            ],
            [
                false,
                [2 => 'a'],
                [3 => 'a'],
            ],
            [
                true,
                [3 => 'a'],
                [3 => 'a'],
            ],
            [
                true,
                ['foo' => 'a'],
                [],
            ],
            [
                true,
                ['foo' => 'a', 'bar' => 'b'],
                ['foo' => 'a'],
            ],
            [
                true,
                ['foo' => 'a', 'bar' => 'b'],
                ['foo' => 'a', 'bar' => 'b'],
            ],
            [
                true,
                ['foo' => 'a', 'bar' => 'b', 'pizza'],
                ['foo' => 'a', 'bar' => 'b'],
            ],
            [
                true,
                ['foo' => 'a', 'bar' => 'b', 'pizza'],
                ['pizza'],
            ],
            [
                false,
                ['foo' => 'a', 'bar' => 'b', 'pizza'],
                ['pizza', 'bar' => 'foo'],
            ],
        ];
    }

    /**
     * @dataProvider dataIsSubset
     */
    public function testIsSubset($expected, $a, $b) {
        $this->assertSame($expected, isSubset($a, $b));
    }

    public function dataSetsEqual() {
        return [
            [
                [],
                [],
                true,
            ],
            [
                [],
                [0],
                false,
            ],
            [
                ['a', 'b', 'c'],
                [97, 98, 99],
                false,
            ],
            [
                ['1'],
                [1],
                true,
            ],
        ];
    }

    /**
     * @dataProvider dataSetsEqual
     */
    public function testSetsEqual($a, $b, $expected) {
        $this->assertEquals($expected, setsEqual($a, $b));
    }

    public function testUnsetOne_EmptyArr() {
        $this->assertEquals([], unsetOne([], 'some'));
        $this->assertEquals([], unsetOne([], null));
    }

    public function testUnsetOne_MultiOccurrences() {
        $this->assertSame(['bar'], unsetOne(['foo', 'bar', 'foo'], 'foo', allOccur: true));
        $this->assertSame(['bar', 'foo'], unsetOne(['foo', 'bar', 'foo'], 'foo', allOccur: false));
    }

    public function testUnsetOne_StringKeys() {
        $this->assertEquals(
            ['one' => 'first-val'],
            unsetOne(['one' => 'first-val', 'two' => 'second-val'], 'second-val')
        );
    }

    public function testUnsetOne_IntKeys() {
        $obj1 = new stdClass();
        $obj2 = new stdClass();
        $this->assertEquals([$obj2], unsetOne([$obj1, $obj2], $obj1));

        $this->assertEquals(['one', 'two'], unsetOne(['one', 'two'], 'some'));

        $this->assertEquals(['one'], unsetOne(['one', 'two'], 'two'));
    }

    public function testUnsetMany_OneOccurrence() {
        $foo = $orig = ['abc', 'def', 'ghi'];
        $this->assertSame(['abc'], unsetMany($foo, ['def', 'ghi']));
        $this->assertSame($orig, $foo);
    }

    public function testUnsetMany_MultiOccurrences() {
        $this->assertSame(
            ['bar', 3, 5, 2, 7],
            unsetMany(['foo', 'bar', 'foo', 1, 3, 5, 1, 2, 7], ['foo', 1], allOccur: true)
        );
        $this->assertSame(
            ['bar', 'foo', 3, 5, 1, 2, 7],
            unsetMany(['foo', 'bar', 'foo', 1, 3, 5, 1, 2, 7], ['foo', 1], allOccur: false)
        );
    }

    public function testIndex() {
        $this->assertEquals(
            [
                ':-)' => [
                    'one'   => ':)',
                    'two'   => ':-)',
                    'three' => ':+)',
                ],
                ':-]' => [
                    'one'   => ':]',
                    'two'   => ':-]',
                    'three' => ':+]',
                ],
            ],
            index(
                [
                    [
                        'one'   => ':)',
                        'two'   => ':-)',
                        'three' => ':+)',
                    ],
                    [
                        'one'   => ':]',
                        'two'   => ':-]',
                        'three' => ':+]',
                    ],
                ],
                'two'
            )
        );
    }

    public function testIndex_DropArg() {
        $this->assertEquals(
            [
                ':-)' => [
                    'one'   => ':)',
                    'three' => ':+)',
                ],
                ':-]' => [
                    'one'   => ':]',
                    'three' => ':+]',
                ],
            ],
            index(
                [
                    [
                        'one'   => ':)',
                        'two'   => ':-)',
                        'three' => ':+)',
                    ],
                    [
                        'one'   => ':]',
                        'two'   => ':-]',
                        'three' => ':+]',
                    ],
                ],
                'two',
                true
            )
        );
    }

    public function testUnsetRecursive() {
        $array = $this->_testArray();
        $expected = [
            'foo' => 'test',
            'bar' => [
                'something',
            ],
            'baz' => [
                'test' => [],
            ],
        ];
        $this->assertEquals($expected, unsetRecursive($array, 'unsetMe'));
        $this->assertEquals($expected, $array);
    }

    /*
    public function testHash() {
        $array = $this->_testArray();
        $hash1 = hash($array);
        $hash2 = hash($array);
        $this->assertTrue(!empty($hash1) && !empty($hash2));
        $this->assertEquals($hash1, $hash2);

        $array['other'] = 'item';
        $hash3 = hash($array);
        $this->assertTrue(!empty($hash3));
        $this->assertNotEquals($hash1, $hash3);
    }
    */

    private function _testArray() {
        return [
            'foo'     => 'test',
            'bar'     => [
                'something',
            ],
            'unsetMe' => 1,
            'baz'     => [
                'test' => [
                    'unsetMe' => [
                        'unsetMe' => 'test',
                    ],
                ],
            ],
        ];
    }

    public function testCamelizeKeys() {
        $array = [
            'foo-bar' => 'one',
            'bar_baz' => 'two',
        ];
        $expected = [
            'fooBar' => 'one',
            'barBaz' => 'two',
        ];
        $this->assertEquals($expected, camelizeKeys($array));
    }

    public function testUnderscoreKeys() {
        $array = [
            'fooBar' => 'one',
            'barBaz' => 'two',
        ];
        $expected = [
            'foo_bar' => 'one',
            'bar_baz' => 'two',
        ];
        $this->assertEquals($expected, underscoreKeys($array));
    }

    public function testUnion() {
        // todo: mixed keys: int, string
        $this->assertSame(['foo' => 'kiwi'], union(['foo' => 'apple'], ['foo' => 'kiwi']));
        $this->assertSame(['foo', 'bar', 'baz'], union(['foo', 'bar'], ['baz']));
        $this->assertSame(['foo', 'bar', 'baz'], union(['foo', 'bar'], ['baz', 'foo']));
    }

    public function dataSymmetricDiff() {
        // for each {int keys, string keys, mixed keys}
        // check {value !=, key !=}
        return [
            [
                ['foo'],
                ['foo'],
                [],
            ],
            [
                ['foo'],
                [],
                ['foo'],
            ],
            [
                [],
                [],
                [],
            ],
            // Int keys
            [
                // Int keys: keys ==, values !=
                ['foo', 'bar', 'baz'],
                ['foo', 'bar'],
                ['baz'],
            ],
            [
                // Int sequential keys: keys ==, values ==
                ['banana', 'kiwi', 'cherry'],
                ['pear', 'banana', 'mango'],
                ['pear', 'mango', 'kiwi', 'cherry'],
            ],
            [
                // Int keys: keys !=, values ==
                ['foo'],
                [1 => 'foo', 0 => 'bar'],
                [3 => 'bar'],
            ],
            [
                // Int keys: keys !=, values !=
                ['pear', 'banana', 'mango', 'kiwi', 'cherry'],
                [7 => 'pear', 11 => 'banana', 24 => 'mango'],
                [6 => 'kiwi', 0 => 'cherry'],
            ],

            // String keys
            [
                // String keys: keys !=, values !=
                ['foo' => 'banana', 'bar' => 'kiwi', 'baz' => 'cherry'],
                ['foo' => 'banana'],
                ['bar' => 'kiwi', 'baz' => 'cherry'],
            ],
            [
                // String keys: keys !=, values ==
                [],
                ['k1' => 'v1'],
                ['k2' => 'v1'],
            ],
            [
                // String keys: keys ==, values !=
                ['k1' => 'v2'],
                ['k1' => 'v1'],
                ['k1' => 'v2'],
            ],
            [
                // String keys: keys ==, values ==
                [],
                ['k1' => 'v2'],
                ['k1' => 'v2'],
            ],
        ];
    }

    /**
     * @dataProvider dataSymmetricDiff
     */
    public function testSymmetricDiff(array $expected, array $a, array $b) {
        $this->assertSame($expected, symDiff($a, $b));
    }

    public function testCartesianProduct() {
        $a = ['foo', 'bar', 'baz'];
        $b = ['blue', 'red'];
        $this->assertSame(
            [
                ['foo', 'blue'],
                ['foo', 'red'],
                ['bar', 'blue'],
                ['bar', 'red'],
                ['baz', 'blue'],
                ['baz', 'red'],
            ],
            cartesianProduct($a, $b)
        );
    }

    public function dataPermutations() {
        yield [
            [],
            [
                [],
            ],
        ];
        yield [
            [2],
            [
                [2],
            ],
        ];
        yield [
            [1, 2],
            [
                [1, 2],
                [2, 1],
            ],
        ];
        yield [
            [1, 2, 3],
            [
                [1, 2, 3],
                [1, 3, 2],
                [2, 1, 3],
                [2, 3, 1],
                [3, 1, 2],
                [3, 2, 1],
            ],
        ];
        yield [
            [1, 2, 3, 4],
            [
                [1, 2, 3, 4],
                [1, 2, 4, 3],
                [1, 3, 2, 4],
                [1, 3, 4, 2],
                [1, 4, 2, 3],
                [1, 4, 3, 2],
                [2, 1, 3, 4],
                [2, 1, 4, 3],
                [2, 3, 1, 4],
                [2, 3, 4, 1],
                [2, 4, 1, 3],
                [2, 4, 3, 1],
                [3, 1, 2, 4],
                [3, 1, 4, 2],
                [3, 2, 1, 4],
                [3, 2, 4, 1],
                [3, 4, 1, 2],
                [3, 4, 2, 1],
                [4, 1, 2, 3],
                [4, 1, 3, 2],
                [4, 2, 1, 3],
                [4, 2, 3, 1],
                [4, 3, 1, 2],
                [4, 3, 2, 1],
            ],
        ];
    }

    /**
     * @dataProvider dataPermutations
     */
    public function testPermutations(array $input, array $expected) {
        $this->assertSame($expected, permutations($input));
    }

    /**
     * This function based on https://github.com/zendframework/zend-stdlib/blob/master/test/ArrayUtilsTest.php
     * Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
     */
    public function dataMerge() {
        return [
            'merge-integer-and-string-keys'                  => [
                [
                    'foo',
                    3     => 'bar',
                    'baz' => 'baz',
                    4     => [
                        'a',
                        1 => 'b',
                        'c',
                    ],
                ],
                [
                    'baz',
                    4 => [
                        'd' => 'd',
                    ],
                ],
                true,
                [
                    0     => 'foo',
                    3     => 'bar',
                    'baz' => 'baz',
                    4     => [
                        'a',
                        1 => 'b',
                        'c',
                    ],
                    5     => 'baz',
                    6     => [
                        'd' => 'd',
                    ],
                ],
            ],
            'merge-integer-and-string-keys-preserve-numeric' => [
                [
                    'foo',
                    3     => 'bar',
                    'baz' => 'baz',
                    4     => [
                        'a',
                        1 => 'b',
                        'c',
                    ],
                ],
                [
                    'baz',
                    4 => [
                        'd' => 'd',
                    ],
                ],
                false,
                [
                    0     => 'baz',
                    3     => 'bar',
                    'baz' => 'baz',
                    4     => [
                        'a',
                        1   => 'b',
                        'c',
                        'd' => 'd',
                    ],
                ],
            ],
            'merge-arrays-recursively'                       => [
                [
                    'foo' => [
                        'baz',
                    ],
                ],
                [
                    'foo' => [
                        'baz',
                    ],
                ],
                true,
                [
                    'foo' => [
                        0 => 'baz',
                        1 => 'baz',
                    ],
                ],
            ],
            'replace-string-keys'                            => [
                [
                    'foo' => 'bar',
                    'bar' => [],
                ],
                [
                    'foo' => 'baz',
                    'bar' => 'bat',
                ],
                true,
                [
                    'foo' => 'baz',
                    'bar' => 'bat',
                ],
            ],
            'merge-with-null'                                => [
                [
                    'foo' => null,
                    null  => 'rod',
                    'cat' => 'bar',
                    'god' => 'rad',
                ],
                [
                    'foo' => 'baz',
                    null  => 'zad',
                    'god' => null,
                ],
                true,
                [
                    'foo' => 'baz',
                    null  => 'zad',
                    'cat' => 'bar',
                    'god' => null,
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataMerge
     */
    public function testMerge(array $a, array $b, bool $resetIntKeys, array $expected) {
        $this->assertEquals($expected, merge($a, $b, $resetIntKeys));
    }

    public function testIsUtf8Text() {
        $this->assertTrue(isUtf8Text('123'));
        $this->assertTrue(isUtf8Text("123\n"));
        $this->assertTrue(isUtf8Text("\n"));
        $this->assertTrue(isUtf8Text("  \n"));
        $this->assertFalse(isUtf8Text("\xc3\x28"));
        $this->assertTrue(isUtf8Text("\x00"));
        $this->assertTrue(isUtf8Text("\x40"));
    }

    public function testAny() {
        $this->markTestIncomplete();
    }

    public function testOne() {
        $this->marktestIncomplete();
    }

    public function testApply() {
        $this->markTestIncomplete();
    }

    public function testPipe() {
        $iter = [
            fn ($v) => $v + 5,
            fn ($v) => $v * 3,
        ];
        $this->assertSame(36, pipe($iter, 7));
    }
}

namespace Morpho\Test\Unit\Base\FunctionsTest;

enum Foo: string {
    case First = 'abc';
    case Second = 'def';
}