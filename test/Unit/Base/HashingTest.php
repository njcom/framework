<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Base;

use Morpho\Base\Hashing;
use Morpho\Testing\TestCase;

class HashingTest extends TestCase {
    public static function dataIsMd5Like() {
        (yield [false, '']);
        (yield [false, 'foo']);
        (yield [true, md5('foo')]);
        (yield [false, 'testtesttesttesttesttesttesttest']);
        (yield [false, 'testtesttesttesttesttesttesttes1']);
        (yield [false, 'testtesttesttesttesttestTESTtes1']);
        (yield [true, 'abcdabcdabcdabcdabcdabcdabcdabcd']);
        (yield [true, 'abcdabcdabcdabcdabcdabcdabcda123']);
        (yield [true, 'abcdAbcdabcdabcdAbcdabcdabcda123']);
    }

    /**
     * @dataProvider dataIsMd5Like
     */
    public function testIsMd5Like(bool $expected, string $testString) {
        $this->assertSame($expected, Hashing::isMd5Like($testString));
    }
}