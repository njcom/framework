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
    public static function parse(string $grammarSource, string $text, array $context = null): array {
        $grammar = static::parseGrammar($grammarSource);
        $parserFactory = static::generateAndEvalParser($grammar, $context)['parserFactory'];
        $tokenizerFactory = $context['tokenizerFactory'] ?? self::mkTokenizer(...);
        $tokenizer = $tokenizerFactory($text);
        $parser = $parserFactory($tokenizer);
        $grammar = static::runParser($parser);
        return [$grammar, $parser, $tokenizer];
    }

    /**
     * Generate parser by the grammar
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
            'parserClass' => $parserClass,
        ];
    }

    /**
     * @todo: specify $context array shape
     */
    public static function generateAndEvalParser(Grammar $grammar, array $context = null): array {
        $newContext = static::generateParser($grammar, $context);
        /*        try {
                    eval('?>' . $newContext['program']);
                } catch (\ParseError $e) {
                    d($newContext['program']);
                }*/
        eval('?>' . $newContext['program']);
        $class = $newContext['parserClass'];
        $newContext['parserFactory'] = function (...$args) use ($class) {
            return new $class(...$args);
        };
        return $newContext;
    }

    public static function mkGrammarParser(ITokenizer $tokenizer): GrammarParser {
        return new GrammarParser($tokenizer);
    }

    /**
     * @param string|resource $source
     * @return \Morpho\Compiler\Frontend\Peg\ITokenizer
     */
    public static function mkTokenizer($source): ITokenizer {
        return new Tokenizer(PythonTokenizer::tokenize($source));
    }

    public static function runParser(Parser $parser): mixed {
        $ast = $parser->start();
        if (!$ast) {
            throw $parser->mkSyntaxError('Invalid syntax');
        }
        return $ast;
    }

    /**
     * @param string|resource $grammarSource
     */
    public static function parseGrammar($grammarSource): mixed {
        $tokenizer = static::mkTokenizer($grammarSource);
        $parser = static::mkGrammarParser($tokenizer);
        return static::runParser($parser);
    }

    /**
     * ast.literal_eval() in Python
     */
    public static function _literalEval(string $literal): string {
        // Handle ''' and """ Python strings
        if (preg_match('~^(\'\'\'|""")(?P<val>.*)(\\1)$~s', $literal, $match)) {
            $literal = '"' . $match['val'] . '"';
        }
        if ($literal === "''" || $literal === '""') {
            return '';
        }
/*        try {
            return eval('return ' . $literal . ';');
        } catch (\ParseError $e) {
            d($literal);
        }*/
        return eval('return ' . $literal . ';');
    }
}
