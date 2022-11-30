<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Fs;

use DirectoryIterator;
use FilesystemIterator;
use LogicException;
use Morpho\Base\InvalidConfException;
use Morpho\Fs\Dir;
use Morpho\Fs\Exception as FsException;
use Morpho\Fs\Stat;
use Morpho\Testing\TestCase;

use RecursiveDirectoryIterator;

use RecursiveIteratorIterator;

use function basename;
use function chdir;
use function chmod;
use function count;
use function func_num_args;
use function getcwd;
use function is_dir;
use function is_file;
use function iterator_to_array;
use function mkdir;
use function preg_grep;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function sort;
use function str_replace;
use function symlink;
use function touch;

class DirTest extends TestCase {
    public function testPhpFilesRe() {
        $this->assertEquals(1, preg_match(Dir::PHP_FILE_FULL_RE, __FILE__));
        $this->assertEquals(1, preg_match(Dir::PHP_FILE_FULL_RE, basename(__FILE__)));
        $this->assertEquals(1, preg_match(Dir::PHP_FILE_FULL_RE, 'foo/.php'));
        $this->assertEquals(1, preg_match(Dir::PHP_FILE_FULL_RE, '.php'));

        $this->assertEquals(0, preg_match(Dir::PHP_FILE_FULL_RE, __FILE__ . '.ts'));
        $this->assertEquals(0, preg_match(Dir::PHP_FILE_FULL_RE, basename(__FILE__) . '.ts'));
        $this->assertEquals(0, preg_match(Dir::PHP_FILE_FULL_RE, 'foo/.ts'));
        $this->assertEquals(0, preg_match(Dir::PHP_FILE_FULL_RE, '.ts'));
    }

    public function testIn() {
        $curDirPath = getcwd();
        $otherDirPath = $this->createTmpDir();
        $fn = function ($otherDirPathArg) use ($otherDirPath, &$called) {
            $this->assertEquals($otherDirPath, getcwd());
            $this->assertEquals($otherDirPath, $otherDirPathArg);
            $called = true;
            return 'res from fn';
        };
        $res = Dir::in($otherDirPath, $fn);
        $this->assertEquals('res from fn', $res);
        $this->assertTrue($called);
        $this->assertEquals($curDirPath, getcwd());
    }

    public function testDelete_WhenParentNotWritable_BoolPredicate() {
        $tmpDirPath = $this->createTmpDir();
        $parentDirPath = $tmpDirPath . '/foo';
        $childDirPath = $parentDirPath . '/bar';
        $testFilePath = $childDirPath . '/test';

        mkdir($childDirPath, 0700, true);
        touch($testFilePath);
        chmod($parentDirPath, 0500);

        $this->assertTrue(is_file($testFilePath));

        Dir::delete($childDirPath, false);

        $this->assertFalse(is_file($testFilePath));
        $this->assertTrue(is_dir($parentDirPath));
    }

    public function testDelete_WhenParentNotWritable_ClosurePredicate() {
        $this->markTestIncomplete();
    }

    public function testDelete_KeepSomeDirectories() {
        $tmpDirPath = $this->createTmpDir();
        mkdir($tmpDirPath . '/foo');
        mkdir($tmpDirPath . '/fox');
        touch($tmpDirPath . '/foo/test.txt');
        mkdir($tmpDirPath . '/foo/bar');
        mkdir($tmpDirPath . '/foo/bar/baz');
        touch($tmpDirPath . '/foo/bar/bird.txt');
        $delete = function ($path, $isDir) use (&$called, $tmpDirPath) {
            $called++;
            switch ($path) {
                case $tmpDirPath:
                    return false; // keep
                case $tmpDirPath . '/foo':
                    $this->assertTrue($isDir);
                    return false; // keep
                case $tmpDirPath . '/fox':
                    $this->assertTrue($isDir);
                    return false; // keep
                case $tmpDirPath . '/foo/test.txt':
                    $this->assertFalse($isDir);
                    return false; // keep
                case $tmpDirPath . '/foo/bar':
                    $this->assertTrue($isDir);
                    return true; // delete this directory and all its content.
                case $tmpDirPath . '/foo/bar/baz':
                    $this->fail('must not be called as the parent directory will be deleted');
                    break;
                case $tmpDirPath . '/foo/bar/bird.txt':
                    $this->fail('must not be called as the parent directory will be deleted');
                    break;
                default:
                    $this->fail('Unknown path: ' . $path);
            }
            $this->assertEquals(2, func_num_args());
        };
        Dir::delete($tmpDirPath, $delete);
        $this->assertTrue($called > 0);
    }

    public function testDelete_Predicate_Depth1() {
        $tmpDirPath = $this->createTmpDir();
        touch($tmpDirPath . '/foo');
        touch($tmpDirPath . '/bar');
        mkdir($tmpDirPath . '/baz');
        Dir::delete(
            $tmpDirPath,
            function ($filePath) {
                return basename($filePath) === 'bar';
            }
        );
        $this->assertSame(['baz', 'foo'], $this->pathsInDir($tmpDirPath));
    }

    public function testDelete_Predicate_Depth2() {
        $tmpDirPath = $this->createTmpDir();
        touch($tmpDirPath . '/foo');
        mkdir($tmpDirPath . '/bar');
        touch($tmpDirPath . '/bar/cow');
        touch($tmpDirPath . '/bar/wolf');
        touch($tmpDirPath . '/baz');
        Dir::delete(
            $tmpDirPath,
            function ($filePath) {
                return basename($filePath) === 'wolf';
            }
        );
        $this->assertSame(['bar', 'bar/cow', 'baz', 'foo'], $this->pathsInDir($tmpDirPath));
    }

    public function testDelete_DeleteSelf() {
        $tmpDirPath = $this->createTmpDir();
        touch($tmpDirPath . '/orange.dat');
        mkdir($tmpDirPath . '/foo');
        touch($tmpDirPath . '/foo/test.txt');
        mkdir($tmpDirPath . '/foo/bar');
        touch($tmpDirPath . '/foo/bar/bird.txt');

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertVoid(Dir::delete($tmpDirPath, false));
        $this->assertTrue(is_dir($tmpDirPath));
        $this->assertSame([], $this->pathsInDir($tmpDirPath));

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertVoid(Dir::delete($tmpDirPath, true));
        $this->assertFalse(is_dir($tmpDirPath));
    }

    public function testDeleteIfExist_DeleteSelf() {
        $tmpDirPath = $this->createTmpDir();
        touch($tmpDirPath . '/orange.dat');
        mkdir($tmpDirPath . '/foo');
        touch($tmpDirPath . '/foo/test.txt');
        mkdir($tmpDirPath . '/foo/bar');
        touch($tmpDirPath . '/foo/bar/bird.txt');

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertVoid(Dir::delete($tmpDirPath, false));
        $this->assertTrue(is_dir($tmpDirPath));
        $this->assertSame([], $this->pathsInDir($tmpDirPath));

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertVoid(Dir::delete($tmpDirPath));
        $this->assertFalse(is_dir($tmpDirPath));
    }

    public function testDelete_VfsDir() {
        $dirPath = 'vfs:///some/dir/path';
        mkdir($dirPath, 0755, true);
        $filePath = $dirPath . '/foo';
        touch($filePath);
        $this->assertFileExists($dirPath);

        $dirPathToDelete = 'vfs:///some/dir';

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertVoid(Dir::delete($dirPathToDelete));

        $this->assertDirectoryExists('vfs:///some');
        $this->assertDirectoryDoesNotExist($dirPathToDelete);
    }

    public function testDeleteEmptyDirs_Recursive() {
        $tmpDirPath = $this->createTmpDir();
        mkdir($tmpDirPath . '/foo/bar/baz', 0777, true);
        mkdir($tmpDirPath . '/foo/test');
        touch($tmpDirPath . '/foo/pig.txt');

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertVoid(Dir::deleteEmptyDirs($tmpDirPath, true));

        $this->assertEquals(['foo', 'foo/pig.txt'], $this->pathsInDir($tmpDirPath));
    }

    public function testBrokenLinkPaths() {
        $tmpDirPath = $this->createTmpDir();
        symlink($tmpDirPath . '/foo', $tmpDirPath . '/bar');
        touch($tmpDirPath . '/dest');
        symlink($tmpDirPath . '/dest', $tmpDirPath . '/src');
        $paths = Dir::brokenLinkPaths($tmpDirPath);
        $this->assertEquals([$tmpDirPath . '/bar'], iterator_to_array($paths, false));
    }

    public function testEmptyDirPaths() {
        $tmpDirPath = $this->createTmpDir();
        mkdir($tmpDirPath . '/foo/bar/baz', 0777, true);
        mkdir($tmpDirPath . '/foo/test');
        touch($tmpDirPath . '/foo/pig.txt');

        $emptyDirPaths = iterator_to_array(Dir::emptyDirPaths($tmpDirPath, true), false);

        sort($emptyDirPaths);
        $this->assertEquals([$tmpDirPath . '/foo/bar/baz', $tmpDirPath . '/foo/test'], $emptyDirPaths);
    }

    public function testMustExist_RelPath_0AsArg() {
        $tmpDirPath = $this->createTmpDir();
        $dirPath = $tmpDirPath . '/0';
        mkdir($dirPath);
        chdir($tmpDirPath);
        $this->assertEquals('0', Dir::mustExist('0'));
    }

    public function testMustExist_AbsPath() {
        $this->assertEquals(__DIR__, Dir::mustExist(__DIR__));
    }

    public function testIsEmptyDir() {
        $this->assertFalse(Dir::isEmpty($this->getTestDirPath()));
        $this->assertTrue(Dir::isEmpty($this->createTmpDir()));
    }

    public function testMove_WhenTargetNotExist() {
        $sourceDirPath = $this->createTmpDir('source');
        mkdir($sourceDirPath . '/bar');
        touch($sourceDirPath . '/bar/1.txt');
        $this->assertTrue(is_dir($sourceDirPath . '/bar'));
        $this->assertTrue(is_file($sourceDirPath . '/bar/1.txt'));

        $targetDirPath = $this->createTmpDir('some') . '/target';
        $this->assertFalse(is_dir($targetDirPath . '/bar'));
        $this->assertFalse(is_dir($targetDirPath . '/bar'));
        $this->assertFalse(is_file($targetDirPath . '/bar/1.txt'));

        $this->assertEquals($targetDirPath, Dir::move($sourceDirPath, $targetDirPath));

        $this->assertFalse(is_dir($sourceDirPath . '/bar'));
        $this->assertFalse(is_file($sourceDirPath . '/bar/1.txt'));
        $this->assertTrue(is_dir($targetDirPath));
        $this->assertTrue(is_dir($targetDirPath . '/bar'));
        $this->assertTrue(is_file($targetDirPath . '/bar/1.txt'));
    }

    public function testCreate_CantCreateEmptyDir() {
        $this->expectException(FsException::class, "The directory path is empty");
        Dir::create('');
    }

    public function testCreate_DoesNotCreateIfDirExists() {
        $this->assertSame(__DIR__, Dir::create(__DIR__));
    }

    public function testCreateAndRecreate_AcceptsArray() {
        $tmpDirPath = $this->createTmpDir();

        $paths = [$tmpDirPath . '/foo', $tmpDirPath . '/bar'];
        $checkExist = function () use ($paths) {
            $this->assertDirectoryExists($paths[0]);
            $this->assertDirectoryExists($paths[1]);
        };

        $this->assertSame($paths, Dir::create($paths));
        $checkExist();

        $this->assertTrue(touch($paths[0] . '/test'));
        $this->assertTrue(touch($paths[1] . '/test'));

        $this->assertSame($paths, Dir::recreate($paths));
        $checkExist();
        $this->assertFileDoesNotExist($paths[0] . '/test');
        $this->assertFileDoesNotExist($paths[1] . '/test');
    }

    public function testPaths_Recursive() {
        $testDirPath = $this->getTestDirPath();
        $expected = [
            $testDirPath . '/1.txt',
            $testDirPath . '/2',
            $testDirPath . '/2/3.php',
            $testDirPath . '/4',
            $testDirPath . '/4/5',
            $testDirPath . '/4/5/6.php',
        ];
        $actual = iterator_to_array(Dir::paths($testDirPath, true), false);
        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual);

        $actual = iterator_to_array(Dir::paths($testDirPath, true), false);
        sort($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testPaths_NotRecursive() {
        $testDirPath = $this->getTestDirPath();
        $expected = [
            $testDirPath . '/1.txt',
            $testDirPath . '/2',
            $testDirPath . '/4',
        ];
        sort($expected);

        $actual = iterator_to_array(Dir::paths($testDirPath), false);
        sort($actual);
        $this->assertEquals($expected, $actual);

        $actual = iterator_to_array(Dir::paths($testDirPath), false);
        sort($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testCopy_IntoItself_TargetPathEqualsSourcePath_ThrowsException() {
        $sourceDirPath = $this->createTmpDir() . '/foo';
        mkdir($sourceDirPath);
        $targetDirPath = $sourceDirPath;
        $this->expectException(FsException::class, "Cannot copy the directory '$sourceDirPath' into itself");
        Dir::copy($sourceDirPath, $targetDirPath);
    }

    public function testCopy_IntoItself_TargetDirContainsTheSameDirName_ThrowsException() {
        $tmpDirPath = $this->createTmpDir();
        $sourceDirPath = $tmpDirPath . '/foo';
        mkdir($sourceDirPath);
        $targetDirPath = $tmpDirPath;
        $this->expectException(FsException::class, "The '$tmpDirPath' directory already contains the 'foo'");
        Dir::copy($sourceDirPath, $targetDirPath);
    }

    public function testCopy_TargetDirContainsTheSameSubdir() {
        $sourceDirPath = $this->createTmpDir();
        mkdir($sourceDirPath . '/test1/foo', Stat::DIR_PERMS, true);

        $targetDirPath = $this->createTmpDir();
        mkdir($targetDirPath . '/test1/foo', Stat::DIR_PERMS, true);

        $sourceDirPath = $sourceDirPath . '/test1';

        $this->assertEquals(
            $targetDirPath . '/' . basename($sourceDirPath),
            Dir::copy($sourceDirPath, $targetDirPath)
        );

        $this->assertEquals(['test1', 'test1/foo'], $this->pathsInDir($targetDirPath));
    }

    public function testCopy_TargetDirNotExist_TargetDirNameNotEqualSourceDirName() {
        $sourceDirPath = $this->createTmpDir() . '/foo';
        mkdir($sourceDirPath);
        $targetDirPath = $this->createTmpDir() . '/bar';

        $this->assertEquals(
            $targetDirPath,
            Dir::copy($sourceDirPath, $targetDirPath)
        );

        $this->assertEquals([], $this->pathsInDir($targetDirPath));
    }

    public function testCopy_TargetDirNotExist_TargetDirNameEqualsSourceDirName() {
        $sourceDirPath = $this->createTmpDir() . '/foo';
        mkdir($sourceDirPath);
        $targetDirPath = $this->createTmpDir() . '/bar/foo';

        $this->assertEquals(
            $targetDirPath,
            Dir::copy($sourceDirPath, $targetDirPath)
        );

        $this->assertEquals([], $this->pathsInDir($targetDirPath));
    }

    public function testCopy_TargetDirExists_TargetDirNameNotEqualsSourceDirName() {
        $sourceDirPath = $this->createTmpDir() . '/foo';
        mkdir($sourceDirPath);
        $targetDirPath = $this->createTmpDir() . '/bar';
        mkdir($targetDirPath);

        $this->assertEquals(
            $targetDirPath . '/' . basename($sourceDirPath),
            Dir::copy($sourceDirPath, $targetDirPath)
        );

        $this->assertEquals(['foo'], $this->pathsInDir($targetDirPath));
    }

    public function testCopy_TargetDirExists_TargetDirNameEqualsSourceDirName() {
        $sourceDirPath = $this->createTmpDir() . '/foo';
        mkdir($sourceDirPath);
        $targetDirPath = $this->createTmpDir() . '/bar';
        mkdir($targetDirPath . '/foo', Stat::DIR_PERMS, true);

        $this->assertEquals(
            $targetDirPath . '/' . basename($sourceDirPath),
            Dir::copy($sourceDirPath, $targetDirPath)
        );

        $this->assertEquals(['foo'], $this->pathsInDir($targetDirPath));
    }

    public function testCopy_TargetDirExists_NestedDirExists() {
        $sourceDirPath = $this->createTmpDir();
        mkdir($sourceDirPath . '/public/module/system', Stat::DIR_PERMS, true);
        touch($sourceDirPath . '/public/module/system/composer.json');

        $targetDirPath = $this->createTmpDir();
        mkdir($targetDirPath . '/public/module/bootstrap', Stat::DIR_PERMS, true);

        $sourceDirPath = $sourceDirPath . '/public';

        $this->assertEquals(
            $targetDirPath . '/' . basename($sourceDirPath),
            Dir::copy($sourceDirPath, $targetDirPath)
        );

        $paths = $this->pathsInDir($targetDirPath, false);
        sort($paths);
        $this->assertEquals(
            [
                $targetDirPath . '/public',
                $targetDirPath . '/public/module',
                $targetDirPath . '/public/module/bootstrap',
                $targetDirPath . '/public/module/system',
                $targetDirPath . '/public/module/system/composer.json',
            ],
            $paths
        );
    }

    public function testCopy_WithFiles_TargetDirExists() {
        $sourceDirPath = $this->createTmpDir();
        touch($sourceDirPath . '/file1.txt');
        mkdir($sourceDirPath . '/dir1');
        touch($sourceDirPath . '/dir1/file2.txt');

        $targetDirPath = $this->createTmpDir();
        $this->assertNotEquals($sourceDirPath, $targetDirPath);
        touch($targetDirPath . '/file1.txt');
        mkdir($targetDirPath . '/dir1');
        touch($targetDirPath . '/dir1/file2.txt');

        $this->assertEquals(
            $targetDirPath . '/' . basename($sourceDirPath),
            Dir::copy($sourceDirPath, $targetDirPath)
        );

        $this->assertDirContentsEqual($sourceDirPath, $targetDirPath . '/' . basename($sourceDirPath));
    }

    public function testCopy_WithFiles_TargetDirNotExists() {
        $sourceDirPath = $this->createTmpDir();
        touch($sourceDirPath . '/file1.txt');
        mkdir($sourceDirPath . '/dir1');
        touch($sourceDirPath . '/dir1/file2.txt');

        $targetDirPath = $this->createTmpDir() . '/target';
        $this->assertFalse(is_dir($targetDirPath));

        $this->assertEquals(
            $targetDirPath,
            Dir::copy($sourceDirPath, $targetDirPath)
        );

        $this->assertDirContentsEqual($sourceDirPath, $targetDirPath);
    }

    public function testCopyContents() {
        $sourceDirPath = $this->createTmpDir();
        touch($sourceDirPath . '/file1.txt');
        mkdir($sourceDirPath . '/dir1');
        touch($sourceDirPath . '/dir1/file2.txt');
        mkdir($sourceDirPath . '/.git');
        touch($sourceDirPath . '/.git/conf');

        $targetDirPath = $this->createTmpDir() . '/target';

        $this->assertSame($targetDirPath, Dir::copyContents($sourceDirPath, $targetDirPath));
        $this->assertTrue(is_file($targetDirPath . '/file1.txt'));
        $this->assertTrue(is_file($targetDirPath . '/dir1/file2.txt'));
        $this->assertTrue(is_file($targetDirPath . '/.git/conf'));

        $count = 0;
        foreach (new DirectoryIterator($targetDirPath) as $item) {
            if ($item->isDot()) {
                continue;
            }
            $count++;
        }
        $this->assertSame(3, $count);
    }

    public function testDirPaths_Recursive() {
        $testDirPath = $this->getTestDirPath();
        $expected = [
            $testDirPath . '/2',
            $testDirPath . '/4',
            $testDirPath . '/4/5',
        ];
        $actual = iterator_to_array(Dir::dirPaths($testDirPath, true), false);
        sort($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testDirPaths_NotRecursive() {
        $testDirPath = $this->getTestDirPath();
        $it = Dir::dirPaths($testDirPath, false);
        $actual = iterator_to_array($it, false);
        $expected = [
            $testDirPath . '/2',
            $testDirPath . '/4',
        ];
        sort($actual);
        sort($expected);
        $this->assertEquals($expected, $actual);
    }

    public function testDirPaths_ShouldNotIncludeSymlinks() {
        $tmpDirPath = $this->createTmpDir();
        $this->assertSame([], glob($tmpDirPath . '/*'));

        mkdir($tmpDirPath . '/foo');
        mkdir($tmpDirPath . '/bar');
        symlink($tmpDirPath, $tmpDirPath . '/link');

        $paths = glob($tmpDirPath . '/*');
        sort($paths);
        $this->assertSame([$tmpDirPath . '/bar', $tmpDirPath . '/foo', $tmpDirPath . '/link'], $paths);

        $paths = iterator_to_array(Dir::dirPaths($tmpDirPath), false);
        sort($paths);
        $this->assertSame([$tmpDirPath . '/bar', $tmpDirPath . '/foo'], $paths);
    }

    public function testDirNames_NotRecursive() {
        $testDirPath = $this->getTestDirPath();
        $dirNames = iterator_to_array(Dir::dirNames($testDirPath), false);
        $this->assertEquals(['2', '4'], $dirNames);
    }

    public function testFileNames_NotRecursive() {
        $this->assertEquals(
            ['1.txt'],
            iterator_to_array(
                Dir::fileNames($this->getTestDirPath()),
                false
            )
        );
    }

    public function testFileNames_Recursive() {
        $fileNames = Dir::fileNames($this->getTestDirPath(), true);
        $fileNames = iterator_to_array($fileNames, false);
        sort($fileNames);
        $this->assertEquals(
            [
                '1.txt',
                '3.php',
                '6.php',
            ],
            $fileNames
        );
    }

    private function pathsInDir(string $dirPath, bool $stripDirPath = true): array {
        $paths = [];
/*        if (!is_dir($dirPath)) {
            return $paths;
        }*/
        $dirPath = str_replace('\\', '/', $dirPath);
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS), RecursiveIteratorIterator::SELF_FIRST) as $entry) {
            if ($stripDirPath) {
                $paths[] = preg_replace('{^' . preg_quote($dirPath) . '/}si', '', $entry->getPathname());
            } else {
                $paths[] = $entry->getPathname();
            }
        }
        sort($paths);
        return $paths;
    }

    private function assertDirContentsEqual(string $dirPathExpectedContent, string $dirPathActualContent) {
        $expected = $this->pathsInDir($dirPathExpectedContent);
        $actual = $this->pathsInDir($dirPathActualContent);
        $this->assertTrue(count($actual) > 0);
        $this->assertEquals($expected, $actual);
    }
}
