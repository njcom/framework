<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Fs;

use Morpho\Fs\Stat;
use Morpho\Testing\TestCase;

use function chmod;
use function link;
use function posix_mknod;
use function touch;

class StatTest extends TestCase {
    private int $oldUmask;

    protected function setUp(): void {
        parent::setUp();
        $this->oldUmask = umask();
    }

    protected function tearDown(): void {
        umask($this->oldUmask);
        parent::tearDown();
    }

    public function testModeAndModeStr() {
        $tmpFilePath = $this->createTmpFile();
        $mode = 0644;
        $this->assertTrue(chmod($tmpFilePath, $mode));
        $this->assertSame($mode, Stat::mode($tmpFilePath));
        $this->assertSame('0644', Stat::modeStr($tmpFilePath));
    }

    public function testIsBlockDev() {
        //$this->assertTrue(posix_mknod($tmpDirPath . '/block-dev', POSIX_S_IFBLK | $mode, $dev[0], $dev[1]));
        // /dev/loop0 requires `loop` kernel module: `lsmod | grep loop`, if it is missing and the kernel module is loaded run the `sudo losetup -f`.
        $path = $this->env()->isCi() ? '/tmp/block-dev-test' : '/dev/loop0';
        $this->assertTrue(Stat::isEntry($path));
        $this->assertTrue(Stat::isBlockDev($path));
        $this->assertFalse(Stat::isCharDev($path));
        $this->assertFalse(Stat::isNamedPipe($path));
        $this->assertFalse(Stat::isSocket($path));
    }

    public function testIsCharDev() {
        //$this->assertTrue(posix_mknod($tmpDirPath . '/char-dev', POSIX_S_IFCHR | $mode, $dev[0], $dev[1]));
        $path = '/dev/urandom';
        $this->assertTrue(Stat::isEntry($path));
        $this->assertFalse(Stat::isBlockDev($path));
        $this->assertTrue(Stat::isCharDev($path));
        $this->assertFalse(Stat::isNamedPipe($path));
        $this->assertFalse(Stat::isSocket($path));
    }

    public function testIsNamedPipe() {
        $tmpDirPath = $this->createTmpDir();
        $mode = 0777;
        //$this->assertTrue(posix_mknod($tmpDirPath . '/reg-file', POSIX_S_IFREG | $mode));
        $path = $tmpDirPath . '/fifo';
        $this->assertTrue(posix_mknod($path, POSIX_S_IFIFO | $mode));

        $this->assertTrue(Stat::isEntry($path));
        $this->assertFalse(Stat::isBlockDev($path));
        $this->assertFalse(Stat::isCharDev($path));
        $this->assertTrue(Stat::isNamedPipe($path));
        $this->assertFalse(Stat::isSocket($path));
    }

    public function testIsSocket() {
        $tmpDirPath = $this->createTmpDir();
        $unixSocketFilePath = $tmpDirPath . '/sock';
        /*
        //$dev = [125, 1];
        $mode = 0777;
        $this->assertTrue(\posix_mknod($path, POSIX_S_IFSOCK | $mode, $dev[0], $dev[1]));
        */
        $sockAddress = 'unix://' . $unixSocketFilePath;
        umask(0); // To allow to read the file
        try {
            $serverSock = stream_socket_server($sockAddress, $errNo, $errStr);

            $this->assertTrue(Stat::isEntry($unixSocketFilePath));
            $this->assertFalse(Stat::isBlockDev($unixSocketFilePath));
            $this->assertFalse(Stat::isCharDev($unixSocketFilePath));
            $this->assertFalse(Stat::isNamedPipe($unixSocketFilePath));
            $this->assertTrue(Stat::isSocket($unixSocketFilePath));
        } finally {
            fclose($serverSock);
        }
    }

    public function testIsEntry_Link() {
        $tmpDirPath = $this->createTmpDir();
        $linkPath = $tmpDirPath . '/link';

        $targetPath = $tmpDirPath . '/foo';
        $this->assertTrue(touch($targetPath));

        $this->assertTrue(link($targetPath, $linkPath));
        $this->assertTrue(Stat::isEntry($linkPath));
    }

    public function testIsEntry_RegularFile() {
        $this->assertTrue(Stat::isEntry(__FILE__));
    }

    public function testIsEntry_Directory() {
        $this->assertTrue(Stat::isEntry(__DIR__));
    }

    public function testIsEntry_NonExistingFile() {
        $this->assertFalse(Stat::isEntry(__FILE__ . '/non'));
    }
}
