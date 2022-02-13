<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace {

    use Morpho\Tech\Php\Debugger;

    require_once __DIR__ . '/Trace.php';
    require_once __DIR__ . '/Frame.php';
    require_once __DIR__ . '/Debugger.php';

    if (!function_exists('d')) {
        function d(...$args) {
            $debugger = Debugger::instance();
            return count($args) ? $debugger->ignoreCaller(__FILE__, __LINE__)->dump(...$args) : $debugger;
        }
    }
    if (!function_exists('dd')) {
        function dd(): void {
            Debugger::instance()->ignoreCaller(__FILE__)->dump();
        }
    }
    if (!function_exists('dt')) {
        function dt(): void {
            Debugger::instance()->ignoreCaller(__FILE__)->trace();
        }
    }
}

namespace Morpho\Tech\Php {

    require_once __DIR__ . '/PhpErrorException.php';

    const LICENSE_COMMENT = "/**\n * This file is part of morpho-os/framework\n * It is distributed under the 'Apache License Version 2.0' license.\n * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.\n */";
    const PHP_SHEBANG_FULL_RE = '~^#!/usr/bin/env\\s+php$~';

    use Composer\Autoload\ClassLoader;
    use PhpParser\Node;
    use PhpParser\Node\Stmt\Class_ as ClassStmt;
    use PhpParser\Node\Stmt\Interface_ as InterfaceStmt;
    use PhpParser\Node\Stmt\Trait_ as TraitStmt;
    use PhpParser\NodeTraverser;
    use PhpParser\NodeVisitorAbstract;
    use PhpParser\ParserFactory;
    use PhpParser\PrettyPrinter\Standard as PrettyPrinter;
    use RuntimeException;

    use function file_get_contents;
    use function is_array;
    use function spl_autoload_functions;

    /**
     * @param string $text Text to parse.
     * @return Node\Stmt[]|null Array of statements, representing the text.
     */
    function parse(string $text): ?array {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        return $parser->parse($text);
    }

    function parseFile(string $filePath): ?array {
        return parse(file_get_contents($filePath));
    }

    function visit(array $nodes, array $visitors): array {
        $traverser = new NodeTraverser();
        foreach ($visitors as $visitor) {
            $traverser->addVisitor($visitor);
        }
        $traverser->traverse($nodes);
        return $nodes;
    }

    function pp(array $nodes): string {
        $pp = new PrettyPrinter(['shortArraySyntax' => true]);
        return $pp->prettyPrint($nodes);
    }

    function ppFile(array $nodes): string {
        $pp = new PrettyPrinter();
        return $pp->prettyPrintFile($nodes);
    }

    function process(string $text, array $visitors): string {
        $nodes = parse($text);
        $nodes = visit($nodes, $visitors);
        return pp($nodes);
    }

    function processFile(string $filePath, array $visitors): ?string {
        $nodes = parseFile($filePath);
        /*            if (null === $nodes) {
                        // non-throwing error handler is used and parser was unable to recover from an error.
                        throw new UnexpectedValueException();
                    }*/
        $nodes = visit($nodes, $visitors);
        return pp($nodes);
    }

    function isClassType(Node $node): bool {
        return $node instanceof ClassStmt || $node instanceof InterfaceStmt || $node instanceof TraitStmt;
    }

    /**
     * Returns the first found Composer's autoloader - an instance of the \Composer\Autoloader\ClassLoader.
     */
    function composerAutoloader(): ClassLoader {
        foreach (spl_autoload_functions() as $callback) {
            if (is_array($callback) && $callback[0] instanceof ClassLoader && $callback[1] === 'loadClass') {
                return $callback[0];
            }
        }
        throw new RuntimeException("Unable to find the Composer's autoloader in the list of autoloaders");
    }

    function isShebangNode(Node $node): bool {
        return $node instanceof Node\Stmt\InlineHTML && substr($node->value, 0, 2) == '#!';
    }

    function varToStr(mixed $var, bool $removeNumericKeys = true): string {
        $visitor = new class ($removeNumericKeys) extends NodeVisitorAbstract {
            public function __construct(public $removeNumericKeys) {
            }

            public function enterNode(Node $node) {
                if ($node instanceof Node\Expr\Array_) {
                    $node->setAttribute('kind', Node\Expr\Array_::KIND_SHORT);
                } elseif ($node instanceof Node\Expr\ArrayItem) {
                    if ($this->removeNumericKeys && $node->key instanceof Node\Scalar\LNumber) {
                        $node->key = null;
                    }
                }
                return parent::enterNode($node);
            }
        };
        $nodes = parse('<?php ' . var_export($var, true) . ';');
        visit($nodes, [$visitor]);

        return rtrim(pp($nodes), ';');
    }

    function removeComments(string $source): string {
        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } elseif (!in_array($token[0], [T_COMMENT, T_DOC_COMMENT])) {
                $output .= $token[1];
            }
        }

        // replace multiple new lines with a newline
        $output = preg_replace(['/\s+$/Sm', '/\n+/S'], "\n", $output);

        return $output;
    }
}