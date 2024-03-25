<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Iterator;
use Morpho\Compiler\Frontend\Peg\Token;
use Morpho\Compiler\Frontend\Peg\TokenException;
use Morpho\Compiler\Frontend\Peg\PythonTokenizer;
use Morpho\Testing\TestCase;

use const Morpho\App\LIB_DIR_NAME;
use const Morpho\Test\BASE_DIR_PATH;

class PythonTokenizerTest extends TestCase {
    private PythonTokenizer $tokenizer;

    protected function setUp(): void {
        parent::setUp();
        $this->tokenizer = new PythonTokenizer();
    }

    public function testTokenize_EmptyString() {
        $this->assertTokensStr("TokenInfo(type=0 (ENDMARKER), string='', start=(1, 0), end=(1, 0), line='')", $this->tokenizer->tokenize(''));
    }

    public function testTokenize_Sample1() {
        $doubleQuote3 = '"""';
        $stream = $this->mkStream("@subheader $doubleQuote3\n");
        $tokens = $this->tokenizer->tokenize($stream);
        $this->assertTokensStr(
            <<<'EOF'
        TokenInfo(type=54 (OP), string='@', start=(1, 0), end=(1, 1), line='@subheader """\n')
        TokenInfo(type=1 (NAME), string='subheader', start=(1, 1), end=(1, 10), line='@subheader """\n')
        EOF,
            $tokens,
            new TokenException('EOF in multi-line string', [1, 11])
        );
    }

    public function testTokenize_Sample2() {
        $s = <<<'EOF'
'(' "memo" ')' { "memo" }

EOF;
        $stream = $this->mkStream($s);
        $tokens = $this->tokenizer->tokenize($stream);
        $this->assertTokensStr(
            <<<'EOF'
TokenInfo(type=3 (STRING), string="'('", start=(1, 0), end=(1, 3), line='\'(\' "memo" \')\' { "memo" }\n')
TokenInfo(type=3 (STRING), string='"memo"', start=(1, 4), end=(1, 10), line='\'(\' "memo" \')\' { "memo" }\n')
TokenInfo(type=3 (STRING), string="')'", start=(1, 11), end=(1, 14), line='\'(\' "memo" \')\' { "memo" }\n')
TokenInfo(type=54 (OP), string='{', start=(1, 15), end=(1, 16), line='\'(\' "memo" \')\' { "memo" }\n')
TokenInfo(type=3 (STRING), string='"memo"', start=(1, 17), end=(1, 23), line='\'(\' "memo" \')\' { "memo" }\n')
TokenInfo(type=54 (OP), string='}', start=(1, 24), end=(1, 25), line='\'(\' "memo" \')\' { "memo" }\n')
TokenInfo(type=4 (NEWLINE), string='\n', start=(1, 25), end=(1, 26), line='\'(\' "memo" \')\' { "memo" }\n')
TokenInfo(type=0 (ENDMARKER), string='', start=(2, 0), end=(2, 0), line='')
EOF,
            $tokens
        );
    }

    public function testTokenizeSample3() {
        $stream = $this->mkStream(file_get_contents($this->getTestDirPath() . '/new-line-at-eof.gram'));
        $tokens = $this->tokenizer->tokenize($stream);
        $this->assertTokensStr(<<<'EOF'
        TokenInfo(type=54 (OP), string='+', start=(1, 0), end=(1, 1), line='+')
        TokenInfo(type=4 (NEWLINE), string='', start=(1, 1), end=(1, 2), line='')
        TokenInfo(type=0 (ENDMARKER), string='', start=(2, 0), end=(2, 0), line='')
        EOF, $tokens);
    }

    public function testTokenize_NewLine() {
        $stream = $this->mkStream("\n\n");
        $tokens = $this->tokenizer->tokenize($stream);
        $this->assertTokensStr(
            <<<'EOF'
        TokenInfo(type=62 (NL), string='\n', start=(1, 0), end=(1, 1), line='\n')
        TokenInfo(type=62 (NL), string='\n', start=(2, 0), end=(2, 1), line='\n')
        TokenInfo(type=0 (ENDMARKER), string='', start=(3, 0), end=(3, 0), line='')
        EOF,
            $tokens
        );
    }

    public function testTokenize_MetaGrammar() {
        $stream = $this->mkStream(file_get_contents(BASE_DIR_PATH . '/' . LIB_DIR_NAME . '/Tech/Python/meta.gram'));
        $tokens = $this->tokenizer->tokenize($stream);
        $this->checkTokens(file_get_contents($this->getTestDirPath() . '/meta-token'), $tokens);
    }

    private function checkTokens(string $expected, iterable $tokens): void {
        $actual = '';
        foreach ($tokens as $token) {
            $this->assertInstanceOf(Token::class, $token);
            $actual .= $token . "\n";
        }
        $this->assertSame($expected, $actual);
        $this->assertNotEmpty($actual);
    }

    private function assertTokensStr(string $expected, Iterator $tokens, TokenException $expectedEx = null): void {
        $expected = explode("\n", trim($expected));
        $j = 0;
        try {
            foreach ($tokens as $i => $token) {
                $this->assertSame($expected[$i], (string)$token);
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