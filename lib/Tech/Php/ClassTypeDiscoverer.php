<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Tech\Php;

use Morpho\Fs\Dir;
use Morpho\Fs\File;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser\Php7 as Parser;
use ReflectionClass;
use RuntimeException;

use function array_filter;
use function array_merge;
use function get_declared_classes;
use function get_declared_interfaces;
use function get_declared_traits;
use function substr;

class ClassTypeDiscoverer {
    private ?IDiscoverStrategy $discoverStrategy = null;

    public static function definedClassTypes(): array {
        return array_merge(
            self::definedClasses(),
            get_declared_interfaces(),
            get_declared_traits()
        );
    }

    public static function definedClasses(): array {
        return array_filter(
            get_declared_classes(),
            function ($class) {
                // Skip anonymous classes.
                return 'class@anonymous' !== substr($class, 0, 15);
            }
        );
    }

    public static function classTypeFilePath(string $classType): string {
        return (new ReflectionClass($classType))->getFileName();
    }

    public static function fileDependsFromClassTypes(string $filePath, bool $excludeStdClasses = true): array {
        $phpCode = File::read($filePath);

        $parser = new Parser(new Lexer());

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $statements = $traverser->traverse($parser->parse($phpCode));

        $depsCollector = new ClassTypeDepsCollector();
        $traverser->addVisitor($depsCollector);
        $traverser->traverse($statements);
        return $excludeStdClasses
            ? (new StdClassTypeFilter())->__invoke($depsCollector->classTypes())
            : $depsCollector->classTypes();
    }

    public function classTypesDefinedInDir(string|iterable $dirPaths, string $regExp = null, array $conf = null): array {
        $conf = (array) $conf + ['recursive' => true];
        $filePaths = Dir::filePaths($dirPaths, $regExp ?: Dir::PHP_FILE_RE, $conf);
        $map = [];
        $discoverStrategy = $this->discoverStrategy();
        foreach ($filePaths as $filePath) {
            foreach ($discoverStrategy->classTypesDefinedInFile($filePath) as $classType) {
                if (isset($map[$classType])) {
                    throw new RuntimeException(
                        "Cannot redeclare the class|interface|trait '$classType' in '$filePath'"
                    );
                }
                $map[$classType] = $filePath;
            }
        }
        return $map;
    }

    public function discoverStrategy(): IDiscoverStrategy {
        if (null === $this->discoverStrategy) {
            $this->discoverStrategy = new TokenStrategy();
        }
        return $this->discoverStrategy;
    }

    public function classTypesDefinedInFile(string $filePath): array {
        return $this->discoverStrategy()->classTypesDefinedInFile($filePath);
    }

    public function setDiscoverStrategy(IDiscoverStrategy $strategy): static {
        $this->discoverStrategy = $strategy;
        return $this;
    }
}

