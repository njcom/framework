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
    /**
     * Runs either the GrammarParser or a generated parser.
     * [build_parser()](https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/build.py#L208)
     * [run_parser()](https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/testutil.py#L38)
     * @param string|resource $source
     * @param callable|null $parserFactory
     * @return array
     * @noinspection PhpMissingParamTypeInspection
     */
    public static function runParser($source, callable $parserFactory = null): array {
        $tokenizer = new Tokenizer(GeneralTokenizer::tokenize($source));
        $parser = $parserFactory ? $parserFactory($tokenizer) : new GrammarParser($tokenizer);
        $ast = $parser->start();
        if (!$ast) {
            throw $parser->mkSyntaxError('Unable to parse grammar');
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
}
