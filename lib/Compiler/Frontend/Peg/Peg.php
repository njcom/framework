<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
/**
 * The implementation is based on Python's PEG:
 * 1. https://medium.com/@gvanrossum_83706/peg-parsing-series-de5d41b2ed60
 * 2. https://www.python.org/dev/peps/pep-0617/
 * 3. https://www.youtube.com/watch?v=QppWTvh7_sI
 *
 */
namespace Morpho\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\IParserGen;

class Peg implements IParserGen {
    public function frontend(): callable {
        return function (mixed $context): mixed {
            $tokenGen = function () {
                // todo
                yield [];
            };
            $context = (new GrammarParser(new GrammarTokenizer($tokenGen())))($context);
            return (new GrammarChecker())($context);
        };
    }

    public function midend(): callable {
        return function (mixed $context): mixed {
            return $context;
        };
    }

    public function backend(): callable {
        return function (mixed $context): mixed {
            return new ParserGen($context);
        };
    }

    public function __invoke(mixed $context): mixed {
        $context = $this->frontend()($context);
        $context = $this->midend()($context);
        return $this->backend()($context);
    }
}