<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use UnexpectedValueException;

use function Morpho\Base\mkStream;

/**
 * PEG/Parsing Expression Grammar: parser generator, generating recursive descent parser by a grammar.
 * Based on https://peps.python.org/pep-0617/ and other related Python's PEG resources.
 */
class Peg {
    public static function parse(string $grammarSource, string $text, array $context = null) {
        $grammar = static::runParser($grammarSource)[0];
        $parserFactory = static::generateAndEvalParser($grammar, $context)['factory'];
        return static::runParser($text, parserFactory: $parserFactory)[0];
    }

    /**
     * Runs either the GrammarParser or the provided generated parser.
     * [build_parser()](https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/build.py#L208)
     * [run_parser()](https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/testutil.py#L38)
     * @param string|resource $source
     * @param callable|null   $tokenizerFactory
     * @param callable|null   $parserFactory
     * @return array
     */
    public static function runParser($source, callable $tokenizerFactory = null, callable $parserFactory = null): array {
        $tokenizer = $tokenizerFactory ? $tokenizerFactory($source) : new Tokenizer(GeneralTokenizer::tokenize($source));
        $parser = $parserFactory ? $parserFactory($tokenizer) : new GrammarParser($tokenizer);
        $ast = $parser->start();
        if (!$ast) {
            throw $parser->mkSyntaxError('Invalid syntax');
        }
        return [$ast, $parser, $tokenizer];
    }

    /**
     * [generate_parser(grammar: Grammar) -> Type[Parser]](https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/testutil.py#L26)
     */
    public static function generateParser(Grammar $grammar, array $context = null): array {
        $stream = mkStream('');
        $gen = new PhpParserGenerator($grammar, $stream);
        if ($context) {
            $context = [];
        }
        $parserClass = $gen->generate($context);
        $code = stream_get_contents($stream, offset: 0);
        if ($code === '') {
            throw new UnexpectedValueException();
        }
        return [
            'program' => $code,
            'class' => $parserClass,
        ];
    }

    /**
     * @todo: specify $context array shape
     */
    public static function generateAndEvalParser(Grammar $grammar, array $context = null): array {
        $newContext = static::generateParser($grammar, $context);
        eval('?>' . $newContext['program']);
        $class = $newContext['class'];
        $newContext['factory'] = function (...$args) use ($class) {
            return new $class(...$args);
        };
        return $newContext;
    }

    /**
     * ast.literal_eval() in Python
     */
    public static function _literalEval(string $literal): string {
        return eval('return ' . $literal . ';');
    }
}
