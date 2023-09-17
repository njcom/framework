<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use Morpho\Compiler\ICompiler;

use function Morpho\Base\mkStream;

/**
 * Based on https://peps.python.org/pep-0617/ and other related Python's resources.
 */
class Peg implements ICompiler {
    /**
     * [build_parser()](https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/build.py#L208)
     * [run_parser()](https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/testutil.py#L38)
     * @param string|resource      $grammarSource Stream for the grammar or source of the grammar
     * @param string|callable|null $parserClass
     * @return array
     * @noinspection PhpMissingParamTypeInspection
     */
    public static function parse($grammarSource, string|callable $parserClass = null): array {
        $grammarTokenizer = new GrammarTokenizer(Tokenizer::tokenize($grammarSource));
        if (null === $parserClass) {
            $grammarParser = new GrammarParser($grammarTokenizer);
        } else {
            $grammarParser = $parserClass($grammarTokenizer);
        }
        $grammar = $grammarParser->start();
        if (!$grammar) {
            throw $grammarParser->mkSyntaxError('Unable to parse grammar');
        }
        return [$grammar, $grammarParser, $grammarTokenizer];
    }

    /**
     * [make_parser(source: str) -> Type[Parser]](https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/testutil.py#L58)
     * @param string|resource $grammarSource Stream for the grammar or source of the grammar
     * @return string
     * @noinspection PhpMissingParamTypeInspection
     */
    public static function mkParser($grammarSource): string {
        $grammar = static::parse($grammarSource)[0];
        return static::generateParser($grammar);
    }

    /**
     * [generate_parser(grammar: Grammar) -> Type[Parser]](https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/testutil.py#L26)
     */
    public static function generateParser(Grammar $grammar): callable {
        $stream = mkStream('');
        // out = io.StringIO()
        $gen = new PhpParserGenerator($grammar, $stream);
        $gen->generate('<string>');

        $code = stream_get_contents($stream, offset: 0);
        if ($code === '') {
            throw new \UnexpectedValueException();
        }

        d(eval('?>' . $code));
        //return new 
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