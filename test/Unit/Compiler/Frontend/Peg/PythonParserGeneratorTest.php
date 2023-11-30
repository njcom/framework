<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\Peg\Alt;
use Morpho\Compiler\Frontend\Peg\Gather;
use Morpho\Compiler\Frontend\Peg\Grammar;
use Morpho\Compiler\Frontend\Peg\NamedItem;
use Morpho\Compiler\Frontend\Peg\NamedItemList;
use Morpho\Compiler\Frontend\Peg\NameLeaf;
use Morpho\Compiler\Frontend\Peg\PythonParserGenerator;
use Morpho\Compiler\Frontend\Peg\Rhs;
use Morpho\Compiler\Frontend\Peg\Rule;
use Morpho\Compiler\Frontend\Peg\StringLeaf;
use Morpho\Testing\TestCase;

class PythonParserGeneratorTest extends TestCase {
    public function testGenerate() {
        $stream = fopen('php://memory', 'r+');
        $grammar = new Grammar([
            'start' => new Rule(
                'start',
                null,
                new Rhs([
                    new Alt(new NamedItemList([
                        new NamedItem(null, new Gather(new StringLeaf(','), new NameLeaf('thing'))),
                        new NamedItem(null, new NameLeaf('NEWLINE')),
                    ])),
                ])),
            'thing' => new Rule(
                'thing',
                null,
                new Rhs([
                    new Alt(new NamedItemList([
                        new NamedItem(null, new NameLeaf('NUMBER')),
                    ]))
                ]),
            ),
        ], []);
        $parserGen = new PythonParserGenerator($grammar, $stream);

        $parserGen->generate();

        $php = stream_get_contents($stream, offset: 0);
        $this->markTestIncomplete();
        $this->assertStringStartsWith("<?php\nnamespace", $php);
    }
}