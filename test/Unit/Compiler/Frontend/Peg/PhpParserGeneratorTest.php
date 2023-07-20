<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\Peg\Grammar;
use Morpho\Compiler\Frontend\Peg\IGrammarVisitor;
use Morpho\Compiler\Frontend\Peg\ParserGenerator;
use Morpho\Compiler\Frontend\Peg\PhpParserGenerator;
use Morpho\Testing\TestCase;

class PhpParserGeneratorTest extends TestCase {
    public function testInterface() {
        $parserGen = new PhpParserGenerator(new Grammar([], []));
        $this->assertInstanceOf(ParserGenerator::class, $parserGen);
        $this->assertInstanceOf(IGrammarVisitor::class, $parserGen);
    }
}