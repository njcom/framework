<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web\View;

use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\Parser\Php7 as Parser;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;

class PhpProcessor {
    public function __invoke(mixed $context): mixed {
        $ast = $this->parse($context['program']);
        $ast = $this->rewrite($ast, $context);
        $context['program'] = $this->prettyPrint($ast);
        return $context;
    }

    public function parse(string $code): array {
        $parser = new Parser(new Lexer());
        return $parser->parse($code);
    }

    public function rewrite(array $ast, $context): array {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new AstRewriter($this, $context));
        return $traverser->traverse($ast);
    }

    public function prettyPrint(array $ast): string {
        return (new PrettyPrinter())->prettyPrintFile($ast);
    }
}
