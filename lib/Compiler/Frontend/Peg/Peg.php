<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
/**
 * The implementation is based on Python's PEG:
 * 1. https://medium.com/@gvanrossum_83706/peg-parsing-series-de5d41b2ed60
 * 2. https://www.python.org/dev/peps/pep-0617/
 * 3. https://www.youtube.com/watch?v=QppWTvh7_sI
 */
namespace Morpho\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\IParserGen;

class Peg implements IParserGen {
    /**
     * [build_parser()](https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/build.py)
     * @param string|resource $source Stream for the grammar or source of the grammar
     * @return array
     * @noinspection PhpMissingParamTypeInspection
     */
    public static function build($source): array {
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
            return self::build($context['file'] ?? $context);
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
            return new ParserGen($context);
        };
    }

    public function __invoke(mixed $val): mixed {
        $context = $this->frontend()($val);
        $context = $this->midend()($context);
        return $this->backend()($context);
    }
}