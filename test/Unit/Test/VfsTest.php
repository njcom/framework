<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Test;

use Exception;
use Morpho\Fs\IFs;
use Morpho\Fs\Stat;
use Morpho\Testing\Vfs;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use UnexpectedValueException;

use function closedir;
use function dirname;
use function fclose;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filesize;
use function fopen;
use function fread;
use function fseek;
use function fstat;
use function ftell;
use function ftruncate;
use function fwrite;
use function get_class;
use function in_array;
use function is_dir;
use function is_file;
use function mkdir;
use function opendir;
use function readdir;
use function rename;
use function rmdir;
use function rtrim;
use function stream_get_wrappers;
use function stream_wrapper_unregister;
use function strlen;
use function strpos;
use function substr;
use function touch;
use function umask;
use function unlink;

// NB: We extend the PHPUnit's TestCase intentionally, to allow to test the Vfs::register() and similar methods.
class VfsTest extends TestCase {
    private $umask;

    protected function setUp(): void {
        parent::setUp();
        $this->umask = umask();
    }

    protected function tearDown(): void {
        parent::tearDown();
        umask($this->umask);
        Vfs::resetState();
        if (in_array(Vfs::SCHEME, stream_get_wrappers())) {
            stream_wrapper_unregister(Vfs::SCHEME);
        }
    }

    public function testInterface() {
        $this->assertInstanceOf(IFs::class, new Vfs());
    }

    public function testRegisterApi() {
        $this->assertFalse(Vfs::isRegistered());
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertNull(Vfs::register());
        $this->assertTrue(Vfs::isRegistered());
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertNull(Vfs::unregister());
        $this->assertFalse(Vfs::isRegistered());
    }

    public function testFileApi() {
        Vfs::register();
        $prefix = Vfs::URI_PREFIX;

        $uri1 = $prefix . '/foo';
        $uri2 = $prefix . '/bar';

        $handle1 = fopen($uri1, 'w');
        $handle2 = fopen($uri2, 'w');
        $this->assertSame(
            [
                $uri1,
                $uri2,
            ],
            $this->paths($prefix . '/')
        );

        $this->assertSame(0, ftell($handle1));
        $contents = 'Foo bar';
        $this->assertSame(strlen($contents), fwrite($handle1, $contents));
        $this->assertSame(strlen($contents), ftell($handle1));

        $this->assertSame(0, ftell($handle2));

        $this->assertSame(0, fseek($handle1, 2));

        $this->assertSame(2, ftell($handle1));

        $this->assertTrue(unlink($uri2));

        $this->assertSame([$uri1], $this->paths($prefix . '/'));

        $this->assertTrue(fclose($handle2));
        $this->assertTrue(fclose($handle1));

        $handle1 = fopen($uri1, 'r');
        $this->assertSame('Foo ', fread($handle1, 4));

        $this->assertTrue(fclose($handle1));
    }

    private function paths(string $dirPath): array {
        $handle = opendir($dirPath);
        $paths = [];
        $prefix = Vfs::URI_PREFIX;
        if (0 === strpos($dirPath, $prefix)) {
            $dirPath = substr($dirPath, strlen($prefix));
        }
        while (false !== ($entry = readdir($handle))) {
            $paths[] = $prefix . rtrim($dirPath, '\\/') . '/' . $entry;
        }
        closedir($handle);
        return $paths;
    }

    public function testRename() {
        $this->markTestIncomplete();
    }

    public function testTruncation_WithFtruncate() {
        Vfs::register();
        $uri = Vfs::prefixUri('/foo/bar');
        mkdir(dirname($uri));
        $handle = fopen($uri, 'a');
        $contents = 'Hello World';
        $this->assertSame(strlen($contents), fwrite($handle, $contents));

        $this->assertTrue(file_exists($uri));
        $this->assertTrue(is_file($uri));
        $offset = ftell($handle);
        $this->assertSame(strlen($contents), $offset);
        $this->assertSame(strlen($contents), filesize($uri));

        $truncateAndCheck = function ($newSize) use ($offset, $handle, $uri) {
            $this->assertTrue(ftruncate($handle, $newSize));

            $this->assertSame($newSize, filesize($uri));
            // The file pointer should not change.
            $this->assertSame($offset, ftell($handle));
        };

        $truncateAndCheck(3);
        $truncateAndCheck(0);
    }

    public function testDirApi() {
        Vfs::register();
        $uri = Vfs::prefixUri('/upload/blog/123');
        $this->assertFalse(is_dir($uri));
        $this->assertTrue(mkdir($uri, 0755, true));
        $this->assertTrue(is_dir($uri));
        $this->assertTrue(rmdir($uri));
        $this->assertFalse(is_dir($uri));
    }

    public function dataEntryName() {
        $prefix = Vfs::URI_PREFIX;
        yield [
            '',
            new UnexpectedValueException('Empty URI'),
        ];
        yield [
            $prefix . '/',
            new UnexpectedValueException("Unable to get name for the root"),
        ];
        yield [
            $prefix,
            new UnexpectedValueException("Path must be not empty and must start with the '/'"),
        ];
        yield [
            $prefix . 'foo',
            new UnexpectedValueException("Path must be not empty and must start with the '/'"),
        ];
        yield [
            $prefix . '/foo',
            'foo',
        ];
        yield [
            $prefix . '/foo/bar',
            'bar',
        ];
    }

    /**
     * @dataProvider dataEntryName
     */
    public function testEntryName(string $uri, $expectedNameOrException) {
        if ($expectedNameOrException instanceof Exception) {
            $this->expectException(get_class($expectedNameOrException));
            $this->expectExceptionMessage($expectedNameOrException->getMessage());
            Vfs::entryName($uri);
        } else {
            $this->assertSame($expectedNameOrException, Vfs::entryName($uri));
        }
    }

    public function testOpenFileWithTheSameUriAsDir() {
        Vfs::register();
        $entryUri = Vfs::URI_PREFIX . '/foo/bar';
        mkdir($entryUri, 0755, true);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to open file, entry is a directory');
        file_put_contents($entryUri, 'test');
    }

    public function testMakeExistingDir() {
        Vfs::register();
        $entryUri = Vfs::URI_PREFIX . '/foo/bar';
        mkdir($entryUri, 0755, true);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to create directory, such directory already exists');
        mkdir($entryUri, 0755, true);
    }

    public function testStat() {
        Vfs::register();

        $fileUri = Vfs::URI_PREFIX . '/foo';

        $contents = 'foo';

        $handle = fopen($fileUri, 'w');
        fwrite($handle, 'foo');
        // http://php.net/manual/en/function.stat.php
        $stat = fstat($handle);
        fclose($handle);

        $this->assertSame((Stat::FILE_BASE_MODE & ~umask()) | Stat::FILE, $stat['mode']);
        $this->assertSame(strlen($contents), $stat['size']);
        $this->assertCount(13 * 2, $stat);
    }

    public function testUmask_File() {
        Vfs::register();
        umask(0266);
        $fileUri = Vfs::URI_PREFIX . '/test';
        file_put_contents($fileUri, 'hello');
        $stat = \stat($fileUri);
        $this->assertSame(0400 | Stat::FILE, $stat['mode']);
    }

    public function testUmask_Dir() {
        Vfs::register();
        umask(0377);
        $dirUri = Vfs::URI_PREFIX . '/test';
        mkdir($dirUri);
        $stat = \stat($dirUri);
        $this->assertSame(0400 | Stat::DIR, $stat['mode']);
    }

    public function dataReadingAfterWriting() {
        yield [
            '',
        ];
        yield [
            'test',
        ];
    }

    /**
     * @dataProvider dataReadingAfterWriting
     */
    public function testReadingAfterWriting(string $contents) {
        Vfs::register();
        $fileUri = Vfs::URI_PREFIX . '/foo';
        file_put_contents($fileUri, $contents);
        $this->assertSame($contents, file_get_contents($fileUri));
    }

    public function testRename_File() {
        Vfs::register();

        $oldDirUri = Vfs::URI_PREFIX . '/old';
        $oldFileUri = $oldDirUri . '/foo';
        mkdir($oldDirUri);

        $newDirUri = Vfs::URI_PREFIX . '/new';
        $newFileUri = $newDirUri . '/bar';
        mkdir($newDirUri);

        $this->assertTrue(touch($oldFileUri));
        $this->assertFileExists($oldFileUri);
        $this->assertFileDoesNotExist($newFileUri);

        $this->assertTrue(rename($oldFileUri, $newFileUri));
        $this->assertFileDoesNotExist($oldFileUri);
        $this->assertFileExists($newFileUri);
    }
}
