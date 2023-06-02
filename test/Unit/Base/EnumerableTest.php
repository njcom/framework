<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Base;

use Morpho\Base\Enumerable;
use Morpho\Testing\TestCase;

class EnumerableTest extends TestCase {
    public static function dataEnumerable() {
        return [
            [new EnumerableTest_Enumerable()],
            [new class extends Enumerable {
                public $publicProp = 'publicVal';
                protected $protectedProp = 'protectedVal';
                private $privateProp = 'privateVal';
            }]
        ];
    }

    /**
     * @dataProvider dataEnumerable
     */
    public function testEnumerableInForeach(Enumerable $enumerable) {
        $exposed = [];
        foreach ($enumerable as $k => $v) {
            $exposed[$k] = $v;
        }
        $this->assertSame(
            [
                'publicProp'    => 'publicVal',
                'protectedProp' => 'protectedVal',
            ],
            $exposed,
        );
    }
}

class EnumerableTest_Enumerable extends Enumerable {
    public $publicProp = 'publicVal';
    protected $protectedProp = 'protectedVal';
    private $privateProp = 'privateVal';
}