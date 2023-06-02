<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Testing;

use PHPUnit\Framework\TestSuite as BaseTestSuite;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RegexIterator;

abstract class TestSuite extends BaseTestSuite {
    protected string $testFileRegexp = '~((Test|TestSuite)\.php|\.phpt)$~s';

    public static function suite(): static {
        $suite = new static();
        $suite->setName(get_class($suite));
        $filePaths = $suite->testFilePaths();
        $suite->addTestFiles($filePaths);
        return $suite;
    }

    public function testFilePaths(): iterable {
        $class = get_class($this);
        if ($class === self::class) {
            return [];
        }
        $curDirPath = dirname((new ReflectionClass($class))->getFileName());
        return $this->testFilesInDir($curDirPath);
    }

    protected function testFilesInDir(string $dirPath): iterable {
        return new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dirPath)
            ),
            $this->testFileRegexp
        );
    }
}
