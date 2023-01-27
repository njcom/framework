<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\ITokenizer;
use Morpho\Compiler\Frontend\Location;
use Morpho\Compiler\Frontend\Peg\GrammarTokenizer;
use Morpho\Compiler\Frontend\Peg\TokenException;
use Morpho\Compiler\Frontend\Peg\TokenInfo;
use Morpho\Testing\TestCase;

use const Morpho\App\LIB_DIR_NAME;
use const Morpho\Test\BASE_DIR_PATH;

class GrammarTokenizerTest extends TestCase {
    private GrammarTokenizer $tokenizer;
    private array $streams = [];

    protected function setUp(): void {
        parent::setUp();
        $this->tokenizer = new GrammarTokenizer(
            (function () {
                yield throw new \RuntimeException();
            })()
        );
    }

    protected function tearDown(): void {
        foreach ($this->streams as $stream) {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
        parent::tearDown();
    }

    public function testInterface() {
        $this->assertInstanceOf(ITokenizer::class, $this->tokenizer);
    }

    public function testMark() {
        $this->assertSame(0, $this->tokenizer->mark());
    }

    public function testTokenize_EmptyFile() {
        $stream = $this->mkStream('');
        $tokens = GrammarTokenizer::tokenize($stream);
        $this->assertInstanceOf(\Generator::class, $tokens);
        $tokens->rewind();
        $this->assertFalse($tokens->valid());
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertVoid($tokens->next());
        $this->assertNull($tokens->current());
    }

    public function testTokenize_Sample1() {
        $stream = $this->mkStream(' """\\');
        $tokens = GrammarTokenizer::tokenize($stream);
        $this->expectExceptionObject(new TokenException('EOF in multi-line string', new Location(1, 1)));
        foreach ($tokens as $token);
        //{
        //$this->assertInstanceOf(TokenInfo::class, $token);
        //}
        #d($actual);
    }

    public function testTokenize() {
        $stream = $this->mkStream(file_get_contents(BASE_DIR_PATH . '/' . LIB_DIR_NAME . '/Compiler/Frontend/Peg/peg.peg'));
        $tokens = GrammarTokenizer::tokenize($stream);
        $i = 0;
        $actual = '';
        foreach ($tokens as $token) {
            $this->assertInstanceOf(TokenInfo::class, $token);
            $actual .= (string) $token;
        }
        $expected = file_get_contents($this->getTestDirPath() . '/peg.peg.tokens');
        $this->assertSame($expected, $actual);
        $this->assertGreaterThan(0, $i);
    }

    /**
     * @param string $bytes
     * @return false|resource
     */
    private function mkStream(string $bytes) {
        try {
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, $bytes);
            rewind($stream);
            $this->streams[] = $stream;
            return $stream;
        } catch (\Throwable $e) {
            if (isset($stream)) {
                fclose($stream);
            }
        }
    }
}