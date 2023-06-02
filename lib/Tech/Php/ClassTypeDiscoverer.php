<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
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

class ClassTypeDiscoverer {
    private ?IDiscoverStrategy $discoverStrategy = null;

    /**
     * @throws \ReflectionException
     */
    public static function classTypeFilePath(string $classType): string {
        return (new ReflectionClass($classType))->getFileName();
    }

    public function fileDependsFromClassTypes(string $filePath, bool $excludeStdClasses = true): array {
        $phpCode = File::read($filePath);

        $parser = new Parser(new Lexer());

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $statements = $traverser->traverse($parser->parse($phpCode));

        $depsCollector = new ClassTypeDepsCollector();
        $traverser->addVisitor($depsCollector);
        $traverser->traverse($statements);
        return $this->filterClassTypes($depsCollector, $filePath, $excludeStdClasses);
    }

    public function classTypesDefinedInFile(string $filePath): array {
        return $this->discoverStrategy()->classTypesDefinedInFile($filePath);
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
                        "Cannot redeclare the class|interface|trait|enum '$classType' in '$filePath'"
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

    public function setDiscoverStrategy(IDiscoverStrategy $strategy): static {
        $this->discoverStrategy = $strategy;
        return $this;
    }

    protected function filterClassTypes(ClassTypeDepsCollector $depsCollector, string $filePath, bool $excludeStdClasses): array {
        $classTypes = $depsCollector->classTypes();
        return array_values($this->excludeClassTypesDefinedInTheSameFile(
            $excludeStdClasses ? $this->excludeStdClassTypes($classTypes) : $classTypes,
            $filePath,
        ));
    }

    private function excludeClassTypesDefinedInTheSameFile(array $classTypes, string $filePath): array {
        return array_diff(
            $classTypes,
            $this->classTypesDefinedInFile($filePath),
        );
    }

    private function excludeStdClassTypes(array $classTypes): array {
        $stdClassTypes = [
            'AddressInfo',
            'AppendIterator',
            'ArgumentCountError',
            'ArithmeticError',
            'ArrayIterator',
            'ArrayObject',
            'AssertionError',
            'Attribute',
            'BadFunctionCallException',
            'BadMethodCallException',
            'CURLFile',
            'CURLStringFile',
            'CachingIterator',
            'CallbackFilterIterator',
            'ClosedGeneratorException',
            'Closure',
            'Collator',
            'CompileError',
            'CurlHandle',
            'CurlMultiHandle',
            'CurlShareHandle',
            'DOMAttr',
            'DOMCdataSection',
            'DOMCharacterData',
            'DOMComment',
            'DOMDocument',
            'DOMDocumentFragment',
            'DOMDocumentType',
            'DOMElement',
            'DOMEntity',
            'DOMEntityReference',
            'DOMException',
            'DOMImplementation',
            'DOMNameSpaceNode',
            'DOMNamedNodeMap',
            'DOMNode',
            'DOMNodeList',
            'DOMNotation',
            'DOMProcessingInstruction',
            'DOMText',
            'DOMXPath',
            'DateInterval',
            'DatePeriod',
            'DateTime',
            'DateTimeImmutable',
            'DateTimeZone',
            'DeflateContext',
            'Directory',
            'DirectoryIterator',
            'DivisionByZeroError',
            'DomainException',
            'EmptyIterator',
            'Error',
            'ErrorException',
            'Exception',
            'FFI',
            'FFI\\CData',
            'FFI\\CType',
            'FFI\\Exception',
            'FFI\\ParserException',
            'Fiber',
            'FiberError',
            'FilesystemIterator',
            'FilterIterator',
            'GMP',
            'GdFont',
            'GdImage',
            'Generator',
            'GlobIterator',
            'HashContext',
            'InfiniteIterator',
            'InflateContext',
            'InternalIterator',
            'IntlBreakIterator',
            'IntlCalendar',
            'IntlChar',
            'IntlCodePointBreakIterator',
            'IntlDateFormatter',
            'IntlDatePatternGenerator',
            'IntlException',
            'IntlGregorianCalendar',
            'IntlIterator',
            'IntlPartsIterator',
            'IntlRuleBasedBreakIterator',
            'IntlTimeZone',
            'InvalidArgumentException',
            'IteratorIterator',
            'JsonException',
            'LengthException',
            'LibXMLError',
            'LimitIterator',
            'Locale',
            'LogicException',
            'Memcached',
            'MemcachedException',
            'MessageFormatter',
            'MultipleIterator',
            'NoRewindIterator',
            'Normalizer',
            'NumberFormatter',
            'OpenSSLAsymmetricKey',
            'OpenSSLCertificate',
            'OpenSSLCertificateSigningRequest',
            'OutOfBoundsException',
            'OutOfRangeException',
            'OverflowException',
            'PDO',
            'PDOException',
            'PDORow',
            'PDOStatement',
            'ParentIterator',
            'ParseError',
            'Phar',
            'PharData',
            'PharException',
            'PharFileInfo',
            'PhpToken',
            'RangeException',
            'RecursiveArrayIterator',
            'RecursiveCachingIterator',
            'RecursiveCallbackFilterIterator',
            'RecursiveDirectoryIterator',
            'RecursiveFilterIterator',
            'RecursiveIteratorIterator',
            'RecursiveRegexIterator',
            'RecursiveTreeIterator',
            'Reflection',
            'ReflectionAttribute',
            'ReflectionClass',
            'ReflectionClassConstant',
            'ReflectionEnum',
            'ReflectionEnumBackedCase',
            'ReflectionEnumUnitCase',
            'ReflectionException',
            'ReflectionExtension',
            'ReflectionFiber',
            'ReflectionFunction',
            'ReflectionFunctionAbstract',
            'ReflectionGenerator',
            'ReflectionIntersectionType',
            'ReflectionMethod',
            'ReflectionNamedType',
            'ReflectionObject',
            'ReflectionParameter',
            'ReflectionProperty',
            'ReflectionReference',
            'ReflectionType',
            'ReflectionUnionType',
            'ReflectionZendExtension',
            'RegexIterator',
            'ResourceBundle',
            'ReturnTypeWillChange',
            'RuntimeException',
            'SessionHandler',
            'SimpleXMLElement',
            'SimpleXMLIterator',
            'Socket',
            'SodiumException',
            'SplDoublyLinkedList',
            'SplFileInfo',
            'SplFileObject',
            'SplFixedArray',
            'SplHeap',
            'SplMaxHeap',
            'SplMinHeap',
            'SplObjectStorage',
            'SplPriorityQueue',
            'SplQueue',
            'SplStack',
            'SplTempFileObject',
            'Spoofchecker',
            'Transliterator',
            'TypeError',
            'UConverter',
            'UnderflowException',
            'UnexpectedValueException',
            'UnhandledMatchError',
            'ValueError',
            'WeakMap',
            'WeakReference',
            'XMLParser',
            'XMLReader',
            'XMLWriter',
            'ZipArchive',
            '__PHP_Incomplete_Class',
            'finfo',
            'mysqli',
            'mysqli_driver',
            'mysqli_result',
            'mysqli_sql_exception',
            'mysqli_stmt',
            'mysqli_warning',
            'php_user_filter',
            'stdClass',
        ];
        return array_diff($classTypes, $stdClassTypes);
    }
}

