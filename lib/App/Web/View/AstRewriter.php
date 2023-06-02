<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web\View;

use Morpho\Base\NotImplementedException;
use PhpParser\Node;
use PhpParser\Node\Arg as ArgNode;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch as ConstFetchExpr;
use PhpParser\Node\Expr\FuncCall as FuncCallExpr;
use PhpParser\Node\Name as NameNode;
use PhpParser\Node\Scalar\MagicConst\Dir as DirMagicConst;
use PhpParser\Node\Scalar\MagicConst\File as FileMagicConst;
use PhpParser\Node\Scalar\String_ as StringScalar;
use PhpParser\Node\Stmt\Echo_ as EchoStatement;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;

use function dirname;
use function file_get_contents;
use function realpath;

class AstRewriter extends NodeVisitorAbstract {
    private array $context;
    private PhpProcessor $processor;

    public function __construct(PhpProcessor $processor, array $context) {
        $this->processor = $processor;
        $this->context = $context;
    }

    public function enterNode(Node $node) {
        $attributes = $node->getAttributes();
        if (!empty($attributes['comments'])) {
            unset($attributes['comments']);
            $node->setAttributes($attributes);
        }

        if ($node instanceof DirMagicConst) {
            return new StringScalar(realpath(dirname($this->context['filePath'])));
        } elseif ($node instanceof FileMagicConst) {
            return new StringScalar($this->context['filePath']);
        }
        return null;
    }

    public function leaveNode(Node $node) {
        if ($node instanceof EchoStatement) {
            return new EchoStatement(
                [
                    new FuncCallExpr(
                        new NameNode(
                            ['htmlspecialchars']
                        ),
                        [
                            new ArgNode(
                                $node->exprs[0]
                            ),
                            new ArgNode(
                                new ConstFetchExpr(
                                    new NameNode(['ENT_QUOTES'])
                                )
                            ),
                        ]
                    ),
                ]
            );
        } elseif ($node instanceof Node\Stmt\Expression) {
            $expr = $node->expr;
            if ($expr instanceof Node\Expr\Include_) {
                if ($expr->type !== Node\Expr\Include_::TYPE_REQUIRE) {
                    throw new NotImplementedException(
                        "Only 'require' expression is supported, the support of include|include_once|require_once was not implemented yet"
                    );
                }
                return $this->evalRequire($expr->expr);
            }
        }
        return null;
    }

    protected function evalRequire(Expr $expr): array {
        $filePath = $this->evalExpr($expr);
        $code = file_get_contents($filePath);
        $processor = $this->processor;
        $ast = $processor->parse($code);
        $context['filePath'] = $filePath;
        return $processor->rewrite($ast, $context);
    }

    protected function evalExpr(Expr $expr): mixed {
        $printer = new PrettyPrinter();
        return eval('return ' . $printer->prettyPrintExpr($expr) . ';');
    }
}
