<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use Morpho\Compiler\ICompiler;

/**
 * Based on https://peps.python.org/pep-0617/ and other related Python's resources.
 */
class Peg implements ICompiler {
    /**
     * [build_parser()](https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/build.py)
     * @param string|resource $source Stream for the grammar or source of the grammar
     * @return array
     * @noinspection PhpMissingParamTypeInspection
     */
    public static function parse($source): array {
        $tokenizer = new GrammarTokenizer(Tokenizer::tokenize($source));
        $parser = new GrammarParser($tokenizer);
        $grammar = $parser->start();
        if (!$grammar) {
            throw $parser->mkSyntaxError('Unable to parse grammar');
        }
        return [$grammar, $parser, $tokenizer];
    }

    public function frontend(): callable {
        return function (mixed $context): array {
            return static::parse($context['file'] ?? $context);
        };
    }

    public function midend(): callable {
        return function (mixed $context): mixed {
            return $context;
        };
    }

    public function backend(): callable {
        return function (mixed $context): mixed {
            d($context);
            return new Peg($context);
        };
    }

    public function __invoke(mixed $context): mixed {
        $context = $this->frontend()($context);
        $context = $this->midend()($context);
        return $this->backend()($context);
    }
}