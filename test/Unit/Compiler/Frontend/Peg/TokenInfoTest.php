<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\Peg\Token;
use Morpho\Compiler\Frontend\Peg\TokenType;
use Morpho\Testing\TestCase;

class TokenInfoTest extends TestCase {
    public function testApi() {
        $tokenInfo = new Token(TokenType::NL, "\n", [1, 1], [1, 2], "foo\n");
        $this->assertSame(TokenType::NL, $tokenInfo->type);
        $this->assertEquals([1, 1], $tokenInfo->start);
        $this->assertEquals([1, 2], $tokenInfo->end);
        $this->assertSame("foo\n", $tokenInfo->line);
        $this->assertSame('TokenInfo(type=62 (NL), string=\'\\n\', start=(1, 1), end=(1, 2), line=\'foo\\n\')', (string) $tokenInfo);
    }
}