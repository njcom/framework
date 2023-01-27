<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Testing;

use Closure;
use FilesystemIterator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase as BaseTestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionObject;
use RuntimeException;

use function chmod;
use function count;
use function date_default_timezone_get;
use function date_default_timezone_set;
use function defined;
use function dirname;
use function fileperms;
use function is_dir;
use function is_file;
use function is_string;
use function md5;
use function microtime;
use function mkdir;
use function pathinfo;
use function preg_replace;
use function realpath;
use function rmdir;
use function str_replace;
use function strpos;
use function sys_get_temp_dir;
use function tempnam;
use function touch;
use function trim;
use function uniqid;
use function unlink;

use const Morpho\App\TEST_DATA_DIR_NAME;

abstract class TestCase extends BaseTestCase {
    private array $tmpDirPaths = [];
    private array $tmpFilePaths = [];
    private string $classFilePath;
    private ?string $prevTimezone = null;

/*    public function expectException(string $exceptionClass, $message = '', $code = null): void {
        parent::expectException($exceptionClass);
        if ($message !== null && $message !== '') {
            $this->expectExceptionMessage($message);
        }
        if ($code !== null) {
            $this->expectExceptionCode($code);
        }
    }*/

    protected function setUp(): void {
        parent::setUp();
        Vfs::register();
    }

    protected function tearDown(): void {
        parent::tearDown();
        if (null !== $this->prevTimezone) {
            date_default_timezone_set($this->prevTimezone);
            $this->prevTimezone = null;
        }
        $this->deleteTmpFiles();
        $this->deleteTmpDirs();
        Vfs::unregister();
    }

    /*
    protected function initSession()
    {
        try {
            (new EnvInitializer())->initSession();
        } catch (\RuntimeException $e) {
            // fallback case.
            $GLOBALS['_SESSION'] = array();
        }
    }
    */

    protected function tmpDirPath(): string {
        return sys_get_temp_dir();
    }

    protected function tmpFilePath(): string {
        $tmpFilePath = $this->createTmpFile();
        unlink($tmpFilePath);
        return $tmpFilePath;
    }

    protected function createTmpFile(
        string $prefix = null,
        string $suffix = null,
        bool $deleteOnTearDown = true
    ): string {
        $prefix = (string) $prefix;
        $suffix = (string) $suffix;
        if ($prefix === '') {
            $prefix = strtolower(__FUNCTION__);
        }
        $tmpFilePath = tempnam($this->tmpDirPath(), (string) $prefix);
        if ($suffix !== '') {
            unlink($tmpFilePath);
            $tmpFilePath .= $suffix;
            touch($tmpFilePath);
        }
        if (!is_file($tmpFilePath)) {
            throw new RuntimeException();
        }
        if ($deleteOnTearDown) {
            $this->tmpFilePaths[] = $tmpFilePath;
        }
        return $tmpFilePath;
    }

    protected function assertSetsEqual(array $expected, array $actual): void {
        $this->assertCount(count($expected), $actual);
        foreach ($expected as $expect) {
            // @TODO: Better implementation, not O(n^2)?
            $this->assertContains($expect, $actual);
        }
    }

    protected function env(): mixed {
        return Env::instance();
    }

    protected function sutConf(): mixed {
        return SutConf::instance();
    }

    /**
     * Note: we can't use name testDirPath as it will be considered as test method.
     */
    protected function getTestDirPath(): string {
        $classFilePath = $this->classFilePath();
        return dirname($classFilePath) . '/' . TEST_DATA_DIR_NAME . '/' . pathinfo($classFilePath, PATHINFO_FILENAME);
    }

    protected function createTmpDir(string $dirName = null): string {
        $tmpDirPath = $this->tmpDirPath() . '/' . md5(uniqid('', true));
        $this->tmpDirPaths[] = $tmpDirPath;
        $tmpDirPath .= null !== $dirName ? '/' . $dirName : '';
        if (is_dir($tmpDirPath)) {
            throw new RuntimeException("The directory '$tmpDirPath' is already exists.");
        }
        mkdir($tmpDirPath, 0777, true);
        return $tmpDirPath;
    }

    protected function ns(): string {
        $class = get_class($this);
        return substr($class, 0, strrpos($class, '\\'));
    }

    protected function assertIntString($val): void {
        $this->assertMatchesRegularExpression(
            '~^[-+]?\d+$~si',
            $val,
            "The value is not either an integer or an integer string"
        );
    }

    protected function assertHtmlEquals($expected, $actual, $message = ''): void {
        $expected = $this->normalizeHtml($expected);
        $actual = $this->normalizeHtml($actual);
        self::assertSame($expected, $actual, $message);
    }

    protected function normalizeHtml(string $html): string {
        return preg_replace(['~>\s+~si', '~\s+<~'], ['>', '<'], trim($html));
    }

    protected function assertVoid($value): void {
        $this->assertNull($value);
    }

    protected function checkBoolAccessor(callable $callback, bool $initialValue): void {
        $this->assertSame($initialValue, $callback());
        $this->assertTrue($callback(true), 'Returns the passed true');
        $this->assertTrue($callback(), 'Returns the previous value that was set: true');
        $this->assertFalse($callback(false), 'Returns the passed false');
        $this->assertFalse($callback(), 'Returns the previous value that was set: false');
    }

    protected function checkAccessors(callable $getter, $initialValue, $newValue, $setterReturnVal = null): void {
        if (!isset($getter[1]) || !is_string($getter[1])) {
            throw new InvalidArgumentException();
        }
        if ($initialValue instanceof Closure) {
            $initialValue($getter());
        } else {
            $this->assertSame($initialValue, $getter());
        }
        [$object, $methodName] = $getter;
        $this->assertSame($setterReturnVal, $object->{'set' . $methodName}($newValue));
        $this->assertSame($newValue, $object->$methodName());
    }

    protected function checkCanSetNull(callable $getter): void {
        if (!isset($getter[1]) || !is_string($getter[1])) {
            throw new InvalidArgumentException();
        }
        [$object, $methodName] = $getter;
        $this->assertNull($object->{'set' . $methodName}(null));
        $this->assertNull($getter());
    }

    protected function setDefaultTimezone(): void {
        $this->prevTimezone = @date_default_timezone_get();
        date_default_timezone_set($this->sutConf()['timezone']);
    }

    protected function randomString(): string {
        return md5(uniqid((string) microtime(true)));
    }

    protected function markTestAsNotRisky(): void {
        $this->addToAssertionCount(1);
        // $this->assertTrue(true) may work too.
    }

    private function tryDeleteDir(string $dirPath): bool {
        $this->fixPerms($dirPath);
        return @rmdir($dirPath);
    }

    private function classFilePath(): string {
        if (!isset($this->classFilePath)) {
            $filePath = (new ReflectionObject($this))->getFileName();
            $isWindows = defined('PHP_WINDOWS_VERSION_BUILD');
            $this->classFilePath = $isWindows ? str_replace('\\', '/', $filePath) : $filePath;
        }

        return $this->classFilePath;
    }

    private function deleteTmpDirs(): void {
        $sysTmpDirPath = $this->tmpDirPath();
        foreach ($this->tmpDirPaths as $tmpDirPath) {
            if (is_dir($tmpDirPath)) {
                foreach (
                    new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($tmpDirPath, FilesystemIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::CHILD_FIRST
                    ) as $path => $_
                ) {
                    if (str_contains($path, $sysTmpDirPath) && $path !== $sysTmpDirPath) {
                        if (is_link($path)) {
                            unlink($path);
                            continue;
                        }
                        if (is_dir($path)) {
                            if (false === $this->tryDeleteDir($path)) {
                                $parentDirPath = realpath($path . '/..');
                                if ($parentDirPath !== $sysTmpDirPath) {
                                    if ($this->fixPerms($parentDirPath)) {
                                        rmdir($path);
                                    }
                                }
                            }
                        } else {
                            if (false === $this->tryDeleteFile($path)) {
                                $parentDirPath = realpath($path . '/..');
                                if ($parentDirPath !== $sysTmpDirPath) {
                                    if ($this->fixPerms($parentDirPath)) {
                                        unlink($path);
                                    }
                                }
                            }
                        }
                    }
                }
                if (str_contains($tmpDirPath, $sysTmpDirPath)) {
                    rmdir($tmpDirPath);
                }
            }
        }
    }

    private function deleteTmpFiles(): void {
        foreach ($this->tmpFilePaths as $tmpFilePath) {
            if (is_file($tmpFilePath)) {
                $this->tryDeleteFile($tmpFilePath);
            }
        }
    }

    private function tryDeleteFile(string $filePath): bool {
        $this->fixPerms($filePath);
        return @unlink($filePath);
    }

    private function fixPerms(string $path): bool {
        $prevMode = @fileperms($path) & 07777;
        if (!$prevMode) {
            return false;
        }
        return @chmod($path, $prevMode | 0200); // set the write bit (in octal)
    }
}
