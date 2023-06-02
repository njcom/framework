<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Php;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_ as ClassStmt;
use PhpParser\Node\Stmt\Function_ as FunctionStmt;
use PhpParser\Node\Stmt\Interface_ as InterfaceStmt;
use PhpParser\Node\Stmt\Namespace_ as NamespaceStmt;
use PhpParser\Node\Stmt\Enum_ as EnumStmt;
use PhpParser\Node\Stmt\Trait_ as TraitStmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use ReflectionFunction;

use function count;
use function file_get_contents;
use function Morpho\Base\contains;

class FileReflection {
    private string $filePath;

    public function __construct(string $filePath) {
        $this->filePath = $filePath;
    }

    public function namespaces(): iterable {
        $stmts = parseFile($this->filePath);

        $traverser = new NodeTraverser();

        $traverser->addVisitor(new NameResolver());

        $stmts = $traverser->traverse($stmts);

        $globalClassTypes = $globalFunctions = [];
        foreach ($stmts as $stmt) {
            if ($stmt instanceof NamespaceStmt) {
                yield new NamespaceReflection(
                    $this->filePath(),
                    null !== $stmt->name ? $stmt->name->toString() : null,
                    $this->classTypes($stmt),
                    $this->functions($stmt),
                    false
                );
            } elseif ($this->isClassType($stmt)) {
                $globalClassTypes[] = $this->nodeName($stmt);
            } elseif ($this->isFunction($stmt)) {
                $globalFunctions[] = $this->nodeName($stmt);
            }
        }
        if (count($globalClassTypes) || count($globalFunctions)) {
            yield new NamespaceReflection($this->filePath(), null, $globalClassTypes, $globalFunctions, true);
        }
    }

    public function interfaces(): iterable {
        return $this->filterClassTypes(function (ClassTypeReflection $rClass) {
            return $rClass->isInterface();
        });
    }

    public function classes(): iterable {
        return $this->filterClassTypes(function (ClassTypeReflection $rClass) {
            return !$rClass->isTrait() && !$rClass->isInterface();
        });
    }

    public function traits(): iterable {
        return $this->filterClassTypes(function (ClassTypeReflection $rClass) {
            return $rClass->isTrait();
        });
    }

    public function enums(): iterable {
        return $this->filterClassTypes(function (ClassTypeReflection $rClass) {
            return $rClass->isEnum();
        });
    }

    public function filePath(): string {
        return $this->filePath;
    }

    private function classTypes(NamespaceStmt $nsNode): iterable {
        foreach ($nsNode->stmts as $node) {
            if ($this->isClassType($node)) {
                yield $node->namespacedName->toString();
            }
        }
    }

    private function isClassType(Node $node): bool {
        return $node instanceof ClassStmt
            || $node instanceof TraitStmt
            || $node instanceof InterfaceStmt
            || $node instanceof EnumStmt;
    }

    private function functions(NamespaceStmt $nsNode): iterable {
        foreach ($nsNode->stmts as $node) {
            if ($this->isFunction($node)) {
                yield $node->namespacedName->toString();
            }
        }
    }

    private function isFunction(Node $node): bool {
        return $node instanceof FunctionStmt;
    }

    private function nodeName(Node $node): string {
        if ($node->name instanceof Identifier) {
            return $node->name->name;
        }
        return $node->name;
    }

    private function filterClassTypes(Closure $filter): iterable {
        foreach ($this->namespaces() as $rNamespace) {
            /** @var $rNamespace NamespaceReflection */
            yield from $rNamespace->classTypes($filter);
        }
    }
}

/**
 * This class should not be instantiated directly, use methods of the ReflectionFile instead.
 */
class NamespaceReflection {
    private $name;
    private $classTypes;
    private $functions;
    private $isGlobal;
    private $filePath;

    public function __construct(
        string $filePath,
        ?string $name,
        iterable $classTypes,
        iterable $functions,
        bool $isGlobal
    ) {
        $this->filePath = $filePath;
        $this->name = $name;
        $this->classTypes = $classTypes;
        $this->functions = $functions;
        $this->isGlobal = $isGlobal;
    }

    public function filePath(): string {
        return $this->filePath;
    }

    public function isGlobal(): bool {
        return $this->isGlobal;
    }

    public function name(): ?string {
        return $this->name;
    }

    /**
     * @param callable|null $filter
     * @return iterable Iterable over \ReflectionClass
     * @throws \ReflectionException
     */
    public function classTypes(callable $filter = null): iterable {
        $this->requireFile($this->filePath);
        foreach ($this->classTypes as $class) {
            $rClass = new ClassTypeReflection($class);
            if ($filter) {
                if ($filter($rClass)) {
                    yield $rClass;
                }
            } else {
                yield $rClass;
            }
        }
    }

    protected function requireFile(string $filePath): void {
        if (contains($filePath, '://')) { // for streams use another approach.
            $php = file_get_contents($filePath);
            eval('?>' . $php);
        } else {
            require_once $filePath;
        }
    }

    /**
     * @return iterable Iterable over \ReflectionFunction
     * @throws \ReflectionException
     */
    public function functions(): iterable {
        require_once $this->filePath;
        foreach ($this->functions as $function) {
            yield new ReflectionFunction($function);
        }
    }
}
