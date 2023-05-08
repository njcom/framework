<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Fs;

use Morpho\Base\SecurityException;
use Morpho\Fs\Exception as FsException;
use Morpho\Fs\Path;
use Morpho\Test\Unit\Base\PathTest as BasePathTest;
use Morpho\Testing\TestCase;

use UnexpectedValueException;

use function basename;
use function touch;

class PathTest extends TestCase {
    public static function dataIsAbs() {
        return [
            [
                '',
                false,
            ],
            [
                'ab',
                false,
            ],
            [
                '\\',  // UNC/Universal Naming Convention
                false,
            ],
            [
                '/',
                true,
            ],
            [
                'C:/',
                true,
            ],
            [
                'ab/cd',
                false,
            ],
            [
                'ab/cd/',
                false,
            ],
            [
                'ab/cd\\',
                false,
            ],
            [
                'ab\\cd',
                false,
            ],
            [
                'C:\\',
                true,
            ],
            [
                __FILE__,
                true,
            ],
        ];
    }

    /**
     * @dataProvider dataIsAbs
     */
    public function testIsAbs($path, $isAbs) {
        $isAbs ? $this->assertTrue(Path::isAbs($path)) : $this->assertFalse(Path::isAbs($path));
    }

    public function testIsAbsWinPath() {
        $this->assertFalse(Path::isAbsWinPath(''));
        $this->assertFalse(Path::isAbsWinPath('/'));
        $this->assertFalse(Path::isAbsWinPath('C'));
        $this->assertFalse(Path::isAbsWinPath('C:'));
        $this->assertFalse(Path::isAbsWinPath('CD:\\'));
        $this->assertTrue(Path::isAbsWinPath('C:\\'));
        $this->assertTrue(Path::isAbsWinPath('C:/'));
    }

    public static function dataAssertSafe_NotSafePath() {
        return [
            ['..'],
            ['C:/foo/../bar'],
            ['C:\foo\..\bar'],
            ["some/file.php\x00"],
            ["foo/../bar"],
            ["foo/.."],
        ];
    }

    /**
     * @dataProvider dataAssertSafe_NotSafePath
     */
    public function testAssertSafe_NotSafePath($path) {
        $this->expectException(SecurityException::class, 'Invalid file path was detected.');
        Path::assertSafe($path);
    }

    public static function dataAssertSafe_SafePath() {
        return [
            [
                '',
                '.',
                'C:/foo/bar',
                'C:\foo\bar',
                'foo/bar',
                '/foo/bar/index.php',
            ],
        ];
    }

    /**
     * @dataProvider dataAssertSafe_SafePath
     */
    public function testAssertSafe_SafePath($path) {
        $this->assertSame($path, Path::assertSafe($path));
    }

    public static function dataNormalize() {
        yield from (new BasePathTest(__METHOD__))->dataNormalize();
        $fixSlashes = fn($path) => str_replace('\\', '/', $path);
        $data = [
            ['C:/', 'C:/'],
            ['C:/', 'C:\\'],
            ['C:/foo/bar', 'C:/foo/bar'],
            ['C:/foo/bar', 'C:\\foo\\bar'],
            [$fixSlashes(__DIR__), __DIR__],
            [$fixSlashes(__FILE__), __FILE__],
            [$fixSlashes(__DIR__), __DIR__ . '/_files/..'],
            [$fixSlashes(__FILE__), __DIR__ . '/_files/../' . basename(__FILE__)],
            [$fixSlashes(__DIR__ . '/non-existing'), __DIR__ . '/non-existing'],
            ['vfs://some/path', 'vfs://some/path'],
        ];
        foreach ([true, false] as $isWin) {
            foreach ($data as $sample) {
                yield [
                    $sample[0],
                    $isWin ? str_replace('/', '\\', $sample[1]) : $sample[1],
                ];
            }
        }
    }

    /**
     * @dataProvider dataNormalize
     */
    public function testNormalize(string $expected, string $path) {
        $this->assertSame($expected, Path::normalize($path));
    }

    public static function dataCombine() {
        yield from (new BasePathTest(__METHOD__))->dataCombine();
        // https://docs.microsoft.com/en-us/dotnet/standard/io/file-path-formats
        yield from [
            [
                '\\\\',
                '\\\\',
            ],
            [
                'C:/foo\\bar/baz',
                'C:/foo\\bar',
                'baz',
            ],
            [
                'C:/foo\\bar/baz',
                'C:/foo\\bar',
                '/baz',
            ],
            [
                'C:/foo\\bar/baz',
                'C:/foo\\bar/',
                '/baz',
            ],
            [
                'C:/',
                'C:/',
                '',
                '/',
            ],
            [
                'C:\\',
                'C:\\',
                '',
                '\\',
                '',
            ],
            [
                '\\\\127.0.0.1',
                '\\\\',
                '127.0.0.1',
                '\\',
            ],
        ];
    }

    /**
     * @dataProvider dataCombine
     */
    public function testCombine(string $expected, ...$paths) {
        $this->assertSame($expected, Path::combine(...$paths));
    }

    public function testNameWithoutExt() {
        $this->assertSame('', Path::nameWithoutExt(''));
        $this->assertSame('', Path::nameWithoutExt('.jpg'));
        $this->assertSame('foo', Path::nameWithoutExt('foo.jpg'));
    }

    public function testExt() {
        $this->assertSame('', Path::ext(''));
        $this->assertSame('jpg', Path::ext('.jpg'));
        $this->assertSame('txt', Path::ext('conf.txt'));
        $this->assertSame('txt', Path::ext('.conf.txt'));

        $this->assertSame('txt', Path::ext('dir/.txt'));
        $this->assertSame('txt', Path::ext('dir/conf.txt'));
        $this->assertSame('php', Path::ext(__FILE__));
        $this->assertSame('ts', Path::ext(__DIR__ . '/test.d.ts'));

        $this->assertSame('', Path::ext('term.'));
    }

    public function testFileName() {
        $this->assertSame('PathTest.php', Path::fileName(__FILE__));
    }

    public function testNormalizeExt() {
        $this->assertSame('.php', Path::normalizeExt('.php'));
        $this->assertSame('.php', Path::normalizeExt('php'));
    }

    public function testChangeExt_GuessOldExt() {
        $this->assertSame('test.jpg', Path::changeExt('test.txt', 'jpg'));

        $this->assertSame('term.txt', Path::changeExt('term.jpg', 'txt'));
        $this->assertSame('term.txt', Path::changeExt('term.jpg', '.txt'));

        $this->assertSame('term.txt', Path::changeExt('term.txt', 'txt'));
        $this->assertSame('term.txt', Path::changeExt('term.txt', '.txt'));

        $this->assertSame('term.txt', Path::changeExt('term', 'txt'));
        $this->assertSame('term.txt', Path::changeExt('term', '.txt'));

        $this->assertSame('/foo/bar/term.txt', Path::changeExt('/foo/bar/term.jpg', 'txt'));
        $this->assertSame('/foo/bar/term.txt', Path::changeExt('/foo/bar/term.jpg', '.txt'));
        $this->assertSame('/foo/bar/term.txt', Path::changeExt('/foo/bar/term.', 'txt'));

        $this->assertSame('dir/foo.d.ts', Path::changeExt('dir/foo.d.ts', 'd.ts'));
    }

    public static function dataChangeExt_GuessOldExt_EmptyPathOrNewExt() {
        yield [
            'term',
            null,
            '',
            //'term', 
        ];
        yield [
            '',
            null,
            '.ext',
        ];
    }

    /**
     * @dataProvider dataChangeExt_GuessOldExt_EmptyPathOrNewExt
     */
    public function testChangeExt_GuessExt_EmptyPathOrNewExt(string $path, ?string $oldExt, string $newExt) {
        $this->expectExceptionObject(new UnexpectedValueException("Path or extension can't be empty"));
        Path::changeExt($path, $newExt, $oldExt);
    }

    public function testDropExt_Quess() {
        $this->assertSame('C:\\foo\\bar\\test', Path::dropExt('C:\\foo\\bar\\test'));
        $this->assertSame('/foo/bar/test', Path::dropExt('/foo/bar/test.php'));
        $this->assertSame('test', Path::dropExt('test.php'));
    }

    public function testDropExt_ConcreteExt() {
        $this->assertSame('foo', Path::dropExt('foo.txt', '.txt'));
        $this->assertSame('foo', Path::dropExt('foo.txt', 'txt'));
        $this->assertSame('C:\\foo\\bar\\test', Path::dropExt('C:\\foo\\bar\\test.foo.bar', '.foo.bar'));
        $this->assertSame('/foo/bar/test', Path::dropExt('/foo/bar/test.php', '.php'));
        $this->assertSame('test.php', Path::dropExt('test.php', '.ts'), 'Skips invalid extension');
    }

    public function testUnique_ThrowsExceptionWhenParentDirDoesNotExist() {
        $this->expectException(FsException::class, "does not exist");
        Path::unique(__FILE__ . '/foo');
    }

    public function testUnique_ParentDirExistUniquePathPassedAsArg() {
        $uniquePath = __DIR__ . '/unique123';
        $this->assertSame($uniquePath, Path::unique($uniquePath));
    }

    public function testUnique_ExistingFileWithExt() {
        $this->assertSame(__DIR__ . '/' . basename(__FILE__, '.php') . '-0.php', Path::unique(__FILE__));
    }

    public function testUnique_ExistingFileWithoutExt() {
        $tmpDirPath = $this->createTmpDir();
        $tmpFilePath = $tmpDirPath . '/abc';
        touch($tmpFilePath);
        $this->assertSame($tmpFilePath . '-0', Path::unique($tmpFilePath));
    }

    public function testUnique_ExistingDirectory() {
        $this->assertSame(__DIR__ . '-0', Path::unique(__DIR__));
    }

    public function testUnique_ThrowsExceptionWhenNumberOfAttemptsReachedForFile() {
        $filePath = __FILE__;
        $expectedMessage = "Unable to generate an unique path for the '$filePath' (tried 0 times)";
        $this->expectException(FsException::class, $expectedMessage);
        Path::unique($filePath, true, 0);
    }

    public function testUnique_ThrowsExceptionWhenNumberOfAttemptsReachedForDirectory() {
        $dirPath = __DIR__;
        $expectedMessage = "Unable to generate an unique path for the '$dirPath' (tried 0 times)";
        $this->expectException(FsException::class, $expectedMessage);
        Path::unique($dirPath, true, 0);
    }

    public function testDirPath() {
        $this->assertSame('', Path::dirPath(''));
        $this->assertSame('/', Path::dirPath('/'));
        $this->assertSame('/', Path::dirPath('/foo'));
        $this->assertSame('/foo', Path::dirPath('/foo/bar'));
        $this->assertSame('vfs://', Path::dirPath('vfs://'));
        $this->assertSame('vfs:///', Path::dirPath('vfs:///'));
        $this->assertSame('vfs:///foo', Path::dirPath('vfs:///foo/bar'));
        $this->assertSame('vfs:///foo', Path::dirPath('vfs:///foo/bar/'));
    }
}
