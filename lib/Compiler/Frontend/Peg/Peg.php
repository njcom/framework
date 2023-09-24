<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use function Morpho\Base\mkStream;

/**
 * PEG/Parsing Expression Grammar: parser generator generating recursive descent parser by a grammar.
 * Based on https://peps.python.org/pep-0617/ and other related Python's PEG resources.
 */
class Peg {
    /**
     * [build_parser()](https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/build.py#L208)
     * [run_parser()](https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/testutil.py#L38)
     * @param string|resource $grammarSource
     * @return array
     */
    public static function parse($grammarSource, $grammarParser = null): array {
        $grammarTokenizer = new GrammarTokenizer(Tokenizer::tokenize($grammarSource));
        if (!$grammarParser) {
            $grammarParser = new GrammarParser($grammarTokenizer);
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
     * @return callable
     * @noinspection PhpMissingParamTypeInspection
     */
    public static function mkParser($grammarSource): callable {
        $grammar = static::parse($grammarSource)[0];
        return static::generateParser($grammar);
    }

    /**
     * [generate_parser(grammar: Grammar) -> Type[Parser]](https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/testutil.py#L26)
     * @return callable Parser factory function calling Parser's constructor.
     */
    public static function generateParser(Grammar $grammar): callable {
        $stream = mkStream('');
        // out = io.StringIO()
        $gen = new PhpParserGenerator($grammar, $stream);
        $className = $gen->generate('<string>');

        $code = stream_get_contents($stream, offset: 0);
        if ($code === '') {
            throw new \UnexpectedValueException();
        }
        d($code);
        eval('?>' . $code);
        
        return $className;
    }

    public function __invoke(mixed $context): Parser {
        [$grammar, $grammarParser, $grammarTokenizer] = static::parse($context);
        $parser = static::generateParser($grammar);
    }
}