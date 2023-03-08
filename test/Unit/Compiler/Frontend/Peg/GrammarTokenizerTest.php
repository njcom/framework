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

use RuntimeException;
use Throwable;

use const Morpho\App\Cli\OUT_FD;
use const Morpho\App\LIB_DIR_NAME;
use const Morpho\Test\BASE_DIR_PATH;

class GrammarTokenizerTest extends TestCase {
    private GrammarTokenizer $tokenizer;
    private array $streams = [];

    protected function setUp(): void {
        parent::setUp();
        $this->tokenizer = new GrammarTokenizer(
            (function () {
                yield throw new RuntimeException();
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
        $this->assertTokensStr("TokenInfo(type=0 (ENDMARKER), string='', start=(1, 0), end=(1, 0), line='')", $tokens);
    }

    public function testTokenize_Sample1() {
        $doubleQuote3 = '"""';
        $stream = $this->mkStream("@subheader $doubleQuote3\n");
        $tokens = GrammarTokenizer::tokenize($stream);
        $this->assertTokensStr(<<<'EOF'
        TokenInfo(type=54 (OP), string='@', start=(1, 0), end=(1, 1), line='@subheader """\n')
        TokenInfo(type=1 (NAME), string='subheader', start=(1, 1), end=(1, 10), line='@subheader """\n')
        EOF, $tokens, new TokenException('EOF in multi-line string', new Location(1, 11)));
    }

    public function testTokenize_NewLine() {
        $stream = $this->mkStream("\n\n");
        $tokens = GrammarTokenizer::tokenize($stream);
        $this->assertTokensStr(<<<'EOF'
        TokenInfo(type=62 (NL), string='\n', start=(1, 0), end=(1, 1), line='\n')
        TokenInfo(type=62 (NL), string='\n', start=(2, 0), end=(2, 1), line='\n')
        TokenInfo(type=0 (ENDMARKER), string='', start=(3, 0), end=(3, 0), line='')
        EOF, $tokens);
    }

    public function testTokenize_PegGrammar() {
        $stream = $this->mkStream(file_get_contents(BASE_DIR_PATH . '/' . LIB_DIR_NAME . '/Compiler/Frontend/Peg/peg.peg'));
        $tokens = GrammarTokenizer::tokenize($stream);
        $this->checkTokens(file_get_contents($this->getTestDirPath() . '/peg.peg.tokens'), $tokens);
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
        } catch (Throwable $e) {
            if (isset($stream)) {
                fclose($stream);
            }
        }
    }

    private function checkTokens(string $expected, iterable $tokens): void {
        $actual = '';
        foreach ($tokens as $token) {
            $this->assertInstanceOf(TokenInfo::class, $token);
            $actual .= $token . "\n";
        }
        $this->assertSame($expected, $actual);
        $this->assertNotEmpty($actual);
    }

    private function tokensToStr(iterable $tokens): string {
        $result = '';
        foreach ($tokens as $token) {
            $result .= $token . "\n";
        }
        return $result;
    }

    private function assertTokensStr(string $expected, iterable $tokens, TokenException $expectedEx = null): void {
        $expected = explode("\n", trim($expected));
        $j = 0;
        try {
            foreach ($tokens as $i => $token) {
                $this->assertSame($expected[$i], (string) $token);
                $j++;
            }
        } catch (TokenException $actualEx) {
            if ($expectedEx) {
                $this->assertSame($expectedEx->getMessage(), $actualEx->getMessage());
            } else {
                throw $actualEx;
            }
        }
        $this->assertSame($j, count($expected));
    }
}