<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend;

use Morpho\Compiler\Frontend\EarleyParser;
use Morpho\Compiler\Frontend\IProgramNode;
use Morpho\Compiler\Frontend\ITopDownParser;
use Morpho\Testing\TestCase;

class EarleyParserTest extends TestCase {
    private EarleyParser $parser;

    protected function setUp(): void {
        parent::setUp();
        $this->parser = new EarleyParser();
    }

    public function testInterface() {
        $this->assertInstanceOf(ITopDownParser::class, $this->parser);
    }

    public function testInvoke() {
        $program = $this->parser->__invoke(null);
        $this->assertInstanceOf(IProgramNode::class, $program);
    }
}