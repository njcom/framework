<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Fs;

use Morpho\Fs\Dir;
use Morpho\Fs\Exception as FsException;
use Morpho\Fs\Link;
use Morpho\Testing\TestCase;

use function basename;
use function copy;
use function is_file;
use function md5;
use function symlink;
use function touch;
use function uniqid;
use function unlink;

class LinkTest extends TestCase {
    private $tmpDirPath;

    protected function setUp(): void {
        parent::setUp();
        $this->tmpDirPath = $this->createTmpDir(__FUNCTION__);
    }

    public function testCreate_CreatesLinkWhenLinkIsDirAndTargetIsFile() {
        $targetFilePath = $this->tmpDirPath . '/' . md5(uniqid(__FUNCTION__));
        copy(__FILE__, $targetFilePath);
        $this->assertTrue(is_file($targetFilePath));
        $linkDirPath = Dir::create($this->tmpDirPath . '/my-link');
        $expectedLinkPath = $linkDirPath . '/' . basename($targetFilePath);
        $this->assertFalse(is_file($expectedLinkPath));
        Link::create($targetFilePath, $linkDirPath);
        $this->assertTrue(is_file($expectedLinkPath));
    }

    public function testIsBroken() {
        $tmpDirPath = $this->createTmpDir();
        $targetFilePath = $tmpDirPath . '/foo';
        $linkPath = $tmpDirPath . '/bar';
        touch($targetFilePath);
        symlink($targetFilePath, $linkPath);
        $this->assertFalse(Link::isBroken($linkPath));
        unlink($targetFilePath);
        $this->assertTrue(Link::isBroken($linkPath));
    }

    public function testIsBroken_ThrowsExceptionIfNotPathPassed() {
        $this->expectException(FsException::class, "The passed path is not a symlink");
        Link::isBroken(__FILE__);
    }
}
