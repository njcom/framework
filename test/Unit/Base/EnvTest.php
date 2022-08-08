<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Base;

use Morpho\Base\Env;
use Morpho\Testing\TestCase;

use function ini_set;
use function strpos;
use function strtoupper;

class EnvTest extends TestCase {
    private $oldZendEnableGc;

    protected function setUp(): void {
        parent::setUp();
        $this->oldZendEnableGc = ini_set('zend.enable_gc', '1'); // we change this setting below.
    }

    protected function tearDown(): void {
        parent::tearDown();
        ini_set('zend.enable_gc', $this->oldZendEnableGc);
    }

    public function testIsCli() {
        $this->assertTrue(Env::isCli());
    }

    public function testBoolIniVal() {
        $this->assertTrue(Env::boolIniVal('realpath_cache_size'));

        $setting = 'zend.enable_gc';
        $this->assertTrue(Env::boolIniVal($setting));

        ini_set($setting, '0');
        $this->assertFalse(Env::boolIniVal($setting));

        ini_set($setting, '1');
        $this->assertTrue(Env::boolIniVal($setting));

        // Names are case sensitive, so such setting should not exist.
        $this->assertFalse(Env::boolIniVal(strtoupper($setting)));

        $this->assertFalse(Env::boolIniVal(__FUNCTION__));
    }

    public function dataIniValToBool() {
        return [
            [
                true,
                '123',
            ],
            [
                true,
                'some',
            ],
            [
                true,
                'On',
            ],
            [
                true,
                '1',
            ],
            [
                true,
                1,
            ],
            [
                true,
                'True',
            ],
            [
                true,
                'true',
            ],
            [
                true,
                true,
            ],
            [
                true,
                'yes',
            ],
            [
                true,
                -1,
            ],
            [
                false,
                0,
            ],
            [
                false,
                '0',
            ],
            [
                false,
                'Off',
            ],
            [
                false,
                'False',
            ],
            [
                false,
                'false',
            ],
            [
                false,
                'No',
            ],
            [
                false,
                'None',
            ],
            [
                false,
                '',
            ],
        ];
    }

    /**
     * @dataProvider dataIniValToBool
     */
    public function testIniValToBool($expected, $actual) {
        $this->assertEquals($expected, Env::iniValToBool($actual));
    }

    public function testIsBoolLikeIniVal() {
        $this->assertFalse(Env::isBoolIniVal('abc'));
        $this->assertFalse(Env::isBoolIniVal('100M'));
        $this->assertFalse(Env::isBoolIniVal('01'));
        $this->assertFalse(Env::isBoolIniVal('10'));
        $this->assertFalse(Env::isBoolIniVal(10));
        $this->assertFalse(Env::isBoolIniVal('2'));
        $this->assertFalse(Env::isBoolIniVal('-1'));
        $this->assertFalse(Env::isBoolIniVal(-1));
        $this->assertFalse(Env::isBoolIniVal(2));
        $this->assertFalse(Env::isBoolIniVal('90.58333'));
        $this->assertFalse(Env::isBoolIniVal(90.58333));
        $this->assertFalse(Env::isBoolIniVal('&'));
        foreach (['on', 'true', 'yes', '1', 1, 'off', 'false', 'none', '', '0', 0] as $v) {
            $this->assertTrue(Env::isBoolIniVal($v));
        }
    }

    public function testTmpDirPath() {
        $tmpDirPath = Env::tmpDirPath();
        $this->assertNotEmpty($tmpDirPath && (false === strpos($tmpDirPath, '\\')));
    }
}
