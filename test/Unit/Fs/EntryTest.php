<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Fs;

use Morpho\Fs\Entry;
use Morpho\Testing\TestCase;

use function basename;
use function is_dir;
use function is_file;

class EntryTest extends TestCase {
    public function testCopy_File() {
        $tmpDirPath = $this->createTmpDir();
        $targetFilePath = $tmpDirPath . '/test.txt';
        $this->assertFalse(is_file($targetFilePath));
        $this->assertEquals($targetFilePath, Entry::copy(__FILE__, $targetFilePath));
        $this->assertTrue(is_file($targetFilePath));
    }

    public function testCopy_Directory() {
        $tmpDirPath = $this->createTmpDir();
        $targetDirPath = $tmpDirPath . '/test';
        $this->assertFalse(is_dir($targetDirPath));
        $this->assertEquals($targetDirPath, Entry::copy(__DIR__, $targetDirPath));
        $this->assertTrue(is_dir($targetDirPath));
        $this->assertTrue(is_file($targetDirPath . '/' . basename(__FILE__)));
    }
}
