<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend;

use Morpho\Compiler\Frontend\AsciiStringReader;
use Morpho\Compiler\Frontend\IStringReader;
use Morpho\Compiler\Frontend\StringReaderException;
use Morpho\Testing\TestCase;

/**
 * Based on [stringscanner from ruby](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html), see [license](https://github.com/ruby/ruby/blob/master/COPYING).
 * In particular on:
 *     * https://github.com/ruby/ruby/blob/ffd0820ab317542f8780aac475da590a4bdbc7a8/test/strscan/test_stringscanner.rb
 *     * https://github.com/ruby/ruby/tree/ffd0820ab317542f8780aac475da590a4bdbc7a8/spec/ruby/library/stringscanner/
 */
class AsciiStringReaderTest extends TestCase {
    public function testInterface() {
        $reader = $this->mkReader('');
        $this->assertInstanceOf(AsciiStringReader::class, $reader);
        $this->assertInstanceOf(IStringReader::class, $reader);
    }

    public function testInputAccessors() {
        $input = 'test string';
        $reader = $this->mkReader($input);
        $this->assertInstanceOf(IStringReader::class, $reader);
        $this->assertSame($input, $reader->input());

        $reader->read('/test/');

        $this->checkState($reader, 4, 'test', ['test']);

        $input = 'a';
        $reader->setInput($input);
        $this->assertSame($input, $reader->input());
        $this->checkState($reader, 0, null, null);
        $this->assertTrue($reader->isLineStart());

        $reader->read('/a/');
        $reader->setInput('b');
        $this->checkState($reader, 0, null, null);
    }

    public function testConcat() {
        $reader = $this->mkReader('a');

        $reader->read('/a/');

        $reader->concat('b');

        $this->checkState($reader, 1, 'a', ['a']);
        $this->assertFalse($reader->isEnd());
        $this->assertSame('b', $reader->read('/b/'));
        $this->assertTrue($reader->isEnd());

        $reader->concat('c');

        $this->checkState($reader, 2, 'b', ['b']);
        $this->assertFalse($reader->isEnd());
        $this->assertSame('c', $reader->read('/c/'));
        $this->assertTrue($reader->isEnd());
    }

    public function testOffsetAccessors() {
        $reader = $this->mkReader('test string');
        $this->assertSame(0, $reader->offset());

        $reader->char();

        $this->assertSame(1, $reader->offset());

        $reader->char();

        $this->assertSame(2, $reader->offset());

        $reader->terminate();

        $this->assertSame(11, $reader->offset());
    }

    public function testLookLen() {
        $reader = $this->mkReader("test string");

        $this->assertSame(4, $reader->lookLen('/\w+/'));
        $this->checkState($reader, 0, 'test', ['test']);

        $this->assertSame(4, $reader->lookLen('/\w+/'));
        $this->checkState($reader, 0, 'test', ['test']);

        $this->assertNull($reader->lookLen('/\s+/'));
        $this->checkState($reader, 0, null, null);
    }

    public function testLook() {
        $reader = $this->mkReader("Foo Bar Baz");

        $this->assertSame("Foo", $reader->look('/Foo/'));
        $this->checkState($reader, 0, 'Foo', ['Foo']);

        $this->assertNull($reader->look('/Bar/'));
        $this->checkState($reader, 0, null, null);
    }

    public function testReadLen() {
        $reader = $this->mkReader('stra strb strc');

        $this->assertSame(4, $reader->readLen('/\w+/'));
        $this->checkState($reader, 4, 'stra', ['stra']);

        $this->assertSame(1, $reader->readLen('/\s+/'));
        $this->checkState($reader, 5, ' ', [' ']);

        $this->assertSame(4, $reader->readLen('/\w+/'));
        $this->checkState($reader, 9, 'strb', ['strb']);

        $this->assertSame(1, $reader->readLen('/\s+/'));

        $this->assertSame(4, $reader->readLen('/\w+/'));
        $this->checkState($reader, 14, 'strc', ['strc']);

        $this->assertNull($reader->readLen('/\w+/'));
        $this->checkState($reader, 14, null, null);

        $this->assertNull($reader->readLen('/\s+/'));
        $this->checkState($reader, 14, null, null);

        $this->assertTrue($reader->isEnd());
        $this->checkState($reader, 14, null, null);
    }

    public function testReadLen_EmptyStr() {
        $reader = $this->mkReader("");

        $this->assertSame(0, $reader->readLen('//'));
        $this->checkState($reader, 0, '', ['']);

        $this->assertSame(0, $reader->readLen('//'));
        $this->checkState($reader, 0, '', ['']);
    }

    public function testReadLen_MultiLineModifier() {
        $reader = $this->mkReader("a\nbc");
        $this->assertSame(2, $reader->readLen('/a\n/m'));
        $this->assertSame(1, $reader->readLen('/^b/m'));
        $this->assertSame(1, $reader->readLen('/c/'));
    }

    public function testRead() {
        $reader = $this->mkReader('stra strb strc');

        $this->assertSame('stra', $reader->read('/\w+/'));
        $this->checkState($reader, 4, 'stra', ['stra']);

        $this->assertSame(' ', $reader->read('/\s+/'));
        $this->checkState($reader, 5, ' ', [' ']);

        $this->assertSame('strb', $reader->read('/\w+/'));
        $this->checkState($reader, 9, 'strb', ['strb']);

        $this->assertSame(' ', $reader->read('/\s+/'));
        $this->checkState($reader, 10, ' ', [' ']);

        $this->assertSame('strc', $reader->read('/\w+/'));
        $this->checkState($reader, 14, 'strc', ['strc']);

        $this->assertNull($reader->read('/\w+/'));
        $this->checkState($reader, 14, null, null);

        $reader = $this->mkReader('');

        $this->assertSame("", $reader->read('//'));
        $this->checkState($reader, 0, '', ['']);

        $this->assertNull($reader->read('/\w+/'));
        $this->checkState($reader, 0, null, null);
    }

    public function testAllReadMethods() {
        $reader = $this->mkReader("Foo Bar Baz");
        $this->assertSame(4, $reader->lookLen('/Foo /'));
        $this->assertSame(0, $reader->offset());
        $this->assertNull($reader->lookLen('/Baz/'));

        $this->assertSame("Foo ", $reader->look('/Foo /'));
        $this->assertSame(0, $reader->offset());
        $this->assertNull($reader->look('/Baz/'));

        $this->assertSame(4, $reader->readLen('/Foo /'));
        $this->assertSame(4, $reader->offset());

        $this->assertNull($reader->lookLen('/Baz /'));
        $this->assertSame("Bar ", $reader->read('/Bar /'));

        $this->assertSame(8, $reader->offset());
        $this->assertNull($reader->lookLen('/az/'));
    }

    public function testLookLenUntil() {
        $reader = $this->mkReader("test string");

        $this->assertSame(3, $reader->lookLenUntil('/s/'));
        $this->checkState($reader, 0, 's', ['s']);

        $reader->read('/test/');

        $this->assertSame(2, $reader->lookLenUntil('/s/'));
        $this->checkState($reader, 4, 's', ['s']);

        $this->assertNull($reader->lookLenUntil('/e/'));
        $this->checkState($reader, 4, null, null);
    }

    public function testLookUntil() {
        $reader = $this->mkReader("Foo Bar Baz");
        $this->assertSame("Foo", $reader->lookUntil('/Foo/'));
        $this->checkState($reader, 0, 'Foo', ['Foo']);

        $this->assertSame("Foo Bar", $reader->lookUntil('/Bar/'));
        $this->checkState($reader, 0, 'Bar', ['Bar']);

        $this->assertNull($reader->lookUntil('/Qux/'));
        $this->checkState($reader, 0, null, null);
    }

    public function testReadLenUntil() {
        $reader = $this->mkReader("Foo Bar Baz");

        $this->assertSame(3, $reader->readLenUntil('/Foo/'));
        $this->checkState($reader, 3, 'Foo', ['Foo']);

        $this->assertSame(4, $reader->readLenUntil('/Bar/'));
        $this->checkState($reader, 7, 'Bar', ['Bar']);

        $this->assertNull($reader->readLenUntil('/Qux/'));
        $this->checkState($reader, 7, null, null);
    }

    public function testReadUntil() {
        $reader = $this->mkReader("Foo Bar Baz");

        $this->assertSame("Foo", $reader->readUntil('/Foo/'));
        $this->checkState($reader, 3, 'Foo', ['Foo']);

        $reader = $this->mkReader("Fri Dec 12 1975 14:39");

        $this->assertSame('Fri Dec 1', $reader->readUntil('/1/'));
        $this->checkState($reader, 9, '1', ['1']);

        $this->assertSame("Fri Dec ", $reader->preMatch());

        $this->assertNull($reader->readUntil('/XYZ/'));
        $this->checkState($reader, 9, null, null);
    }

    public function testAllReadUntilMethods() {
        $reader = $this->mkReader("Foo Bar Baz");

        $this->assertSame(8, $reader->lookLenUntil('/Bar /'));
        $this->checkState($reader, 0, 'Bar ', ['Bar ']);

        $this->assertSame("Foo Bar ", $reader->lookUntil('/Bar /'));
        $this->checkState($reader, 0, 'Bar ', ['Bar ']);

        $this->assertSame(8, $reader->readLenUntil('/Bar /'));
        $this->checkState($reader, 8, 'Bar ', ['Bar ']);

        $this->assertSame("Baz", $reader->readUntil('/az/'));
        $this->checkState($reader, 11, 'az', ['az']);
    }

    public function testPeek() {
        $reader = $this->mkReader("test string");
        $this->assertSame('', $reader->peek(0));
        $this->checkState($reader, 0, null, null);

        $this->assertSame("test st", $reader->peek(7));
        $this->checkState($reader, 0, null, null);

        $this->assertSame("test st", $reader->peek(7));
        $this->checkState($reader, 0, null, null);

        $reader->read('/test/');

        $this->assertSame(" stri", $reader->peek(5));
        $this->checkState($reader, 4, 'test', ['test']);

        $this->assertSame(" string", $reader->peek(10));
        $this->checkState($reader, 4, 'test', ['test']);

        $reader->read('/ string/');

        $this->assertSame("", $reader->peek(10));
        $this->checkState($reader, 11, ' string', [' string']);
    }

    public function testChar_EmptyString() {
        $reader = $this->mkReader('');
        $this->assertNull($reader->char());
    }

    public function testChar() {
        $reader = $this->mkReader('abcde');

        $this->assertSame('a', $reader->char());
        $this->checkState($reader, 1, 'a', ['a']);

        $this->assertSame('b', $reader->char());
        $this->checkState($reader, 2, 'b', ['b']);

        $this->assertSame('c', $reader->char());
        $this->checkState($reader, 3, 'c', ['c']);

        $this->assertSame('d', $reader->char());
        $this->checkState($reader, 4, 'd', ['d']);

        $this->assertSame('e', $reader->char());
        $this->checkState($reader, 5, 'e', ['e']);

        $this->assertNull($reader->char());
        $this->checkState($reader, 5, null, null);

        $reader = $this->mkReader("\x00\x01");

        $this->assertSame("\x00", $reader->char());
        $this->checkState($reader, 1, "\x00", ["\x00"]);

        $this->assertSame("\x01", $reader->char());
        $this->checkState($reader, 2, "\x01", ["\x01"]);

        $reader = $this->mkReader('');
        $this->assertNull($reader->char());
        $this->checkState($reader, 0, null, null);
    }

    public function testUnread() {
        $reader = $this->mkReader('test string');

        $this->assertSame('test', $reader->read('/\w+/'));

        $reader->unread();

        $this->checkState($reader, 0, null, null);

        $this->assertSame("te", $reader->read('/../'));

        $this->assertNull($reader->read('/\d/'));

        try {
            $reader->unread();
            $this->fail();
        } catch (StringReaderException $e) {
            $this->assertSame("Previous match record doesn't exist", $e->getMessage());
        }
    }

    public function testTerminate() {
        $reader = $this->mkReader('');

        $reader->terminate();

        $this->checkState($reader, 0, null, null);
        $this->assertTrue($reader->isEnd());

        $reader = $this->mkReader('abcd');

        $reader->char();

        $reader->terminate();

        $this->checkState($reader, 4, null, null);
        $this->assertTrue($reader->isEnd());

        $reader->terminate();

        $this->checkState($reader, 4, null, null);
        $this->assertTrue($reader->isEnd());
    }

    public function testReset() {
        $reader = $this->mkReader('abcd');

        $reader->char();

        $reader->reset();

        $this->checkState($reader, 0, null, null);

        $this->assertSame(null, $reader->match());
        $this->assertSame(null, $reader->preMatch());
        $this->assertSame(null, $reader->postMatch());

        $reader->read('/\w+/');

        $reader->reset();

        $this->checkState($reader, 0, null, null);

        $reader->reset();

        $this->checkState($reader, 0, null, null);
    }

    public function testIsLineStart_EmptyString() {
        $reader = $this->mkReader('');
        $this->assertTrue($reader->isLineStart());
    }

    public function testIsLineStart() {
        $reader = $this->mkReader("a\nbbb\n\ncccc\nddd\r\neee");

        $this->assertTrue($reader->isLineStart());

        $reader->read('/a/');

        $this->assertFalse($reader->isLineStart());

        $reader->read('/\n/');

        $this->assertTrue($reader->isLineStart());

        $reader->read('/b/');

        $this->assertFalse($reader->isLineStart());

        $reader->read('/b/');

        $this->assertFalse($reader->isLineStart());

        $reader->read('/b/');

        $this->assertFalse($reader->isLineStart());

        $reader->read('/\n/');

        $this->assertTrue($reader->isLineStart());

        $reader->unread();

        $this->assertFalse($reader->isLineStart());

        $reader->read('/\n/');
        $reader->read('/\n/');

        $this->assertTrue($reader->isLineStart());

        $reader->read('/c+\n/');

        $this->assertTrue($reader->isLineStart());

        $reader->read('/d+\r\n/');

        $this->assertTrue($reader->isLineStart());

        $reader->read('/e+/');

        $this->assertFalse($reader->isLineStart());
    }

    public function testIsEnd_EmptyString() {
        $reader = $this->mkReader('');
        $this->assertTrue($reader->isEnd());
    }

    public function testIsEnd() {
        $reader = $this->mkReader('test string');

        $this->assertFalse($reader->isEnd());

        $reader->read('~\w+~');

        $this->assertFalse($reader->isEnd());

        $reader->read('~\s+~');
        $reader->read('~\w+~');

        $this->assertTrue($reader->isEnd());

        $reader->read('~\w+~');

        $this->assertTrue($reader->isEnd());
    }

    public function testMatch() {
        $reader = $this->mkReader('stra strb strc');

        $reader->read('/\w+/');
        $this->assertSame('stra', $reader->match());

        $reader->read('/\s+/');
        $this->assertSame(' ', $reader->match());

        $reader->read('/st/');
        $this->assertSame('st', $reader->match());

        $reader->read('/\w+/');
        $this->assertSame('rb', $reader->match());

        $reader->read('/\s+/');
        $this->assertSame(' ', $reader->match());

        $reader->read('/\w+/');
        $this->assertSame('strc', $reader->match());

        $reader->read('/\w+/');
        $this->assertNull($reader->match());

        $reader = $this->mkReader('ab');
        $reader->char();
        $this->assertSame('a', $reader->match());
        $reader->char();
        $this->assertSame('b', $reader->match());
        $reader->char();
        $this->assertNull($reader->match());

        $reader = $this->mkReader('abc');
        $reader->readLen('/./');
        $this->assertSame('a', $reader->match());
    }

    public function testMatchLen() {
        $reader = $this->mkReader('test string');

        $this->assertNull($reader->matchLen());

        $reader->read('/test/');
        $this->assertSame(4, $reader->matchLen());
        $this->assertSame(4, $reader->matchLen());

        $reader->read('//');
        $this->assertSame(0, $reader->matchLen());

        $reader->read('/x/');
        $this->assertNull($reader->matchLen());
        $this->assertNull($reader->matchLen());

        $reader->terminate();
        $this->assertNull($reader->matchLen());

        $reader = $this->mkReader('test string');
        $this->assertNull($reader->matchLen());

        $reader->read('/test/');
        $this->assertSame(4, $reader->matchLen());

        $reader->terminate();
        $this->assertNull($reader->matchLen());
    }

    public function testPreMatch() {
        $reader = $this->mkReader('a b c d e');

        $reader->read('/\w/');
        $this->assertSame('', $reader->preMatch());

        $reader->readLen('/\s/');
        $this->assertSame('a', $reader->preMatch());

        $reader->read('/b/');
        $this->assertSame('a ', $reader->preMatch());

        $this->assertSame(' c', $reader->readUntil('/c/'));
        $this->assertSame('a b ', $reader->preMatch());

        $this->assertSame(' ', $reader->char());
        $this->assertSame('a b c', $reader->preMatch());

        $reader->char();
        $this->assertSame('a b c ', $reader->preMatch());

        $reader->char();
        $this->assertSame('a b c d', $reader->preMatch());

        $reader->read('/never match/');
        $this->assertNull($reader->preMatch());
    }

    public function testPostMatch() {
        $reader = $this->mkReader('a b c d e');

        $reader->read('/\w/');
        $this->assertSame(' b c d e', $reader->postMatch());

        $reader->readLen('/\s/');
        $this->assertSame('b c d e', $reader->postMatch());

        $reader->read('/b/');
        $this->assertSame(' c d e', $reader->postMatch());

        $reader->readUntil('/c/');
        $this->assertSame(' d e', $reader->postMatch());

        $reader->char();
        $this->assertSame('d e', $reader->postMatch());

        $reader->char();
        $this->assertSame(' e', $reader->postMatch());

        $reader->char();
        $this->assertSame('e', $reader->postMatch());

        $reader->read('/never match/');
        $this->assertNull($reader->postMatch());

        $reader->read('/./');
        $this->assertSame('', $reader->postMatch());

        $reader->read('/./');
        $this->assertNull($reader->postMatch());
    }

    public function testSubgroups_Read() {
        $reader = $this->mkReader("Timestamp: Fri Dec 12 1975 14:39");

        $reader->read("/Timestamp: /");

        $reader->read('/(\w+) (\w+) (\d+) /');

        $this->assertSame(['Fri Dec 12 ', "Fri", "Dec", "12"], $reader->groups());

        $reader->read('/(\w+) (\w+) (\d+) /');

        $this->assertNull($reader->groups());
    }

    public function testSubgroups_ReadUntil() {
        $reader = $this->mkReader("Fri Dec 12 1975 14:39");

        $this->assertNull($reader->groups());

        $reader->readUntil('/ (\d+)\s+(\d+) /');

        $groups = $reader->groups();

        $this->assertSame(
            [
                ' 12 1975 ',
                '12',
                '1975',
            ],
            $groups
        );
    }

    public function testRest() {
        $reader = $this->mkReader('test string');
        $this->assertSame("test string", $reader->rest());
        $reader->read('/test/');
        $this->assertSame(" string", $reader->rest());
        $reader->read('/ string/');
        $this->assertSame("", $reader->rest());
        $reader->read('/ string/');
    }

    public function testRestLen() {
        $reader = $this->mkReader('test string');

        $this->assertSame(11, $reader->restLen());

        $reader->read('/test/');

        $this->assertSame(7, $reader->restLen());

        $reader->read('/ string/');

        $this->assertSame(0, $reader->restLen());
    }

    public function testIsAnchored() {
        $reader = $this->mkReader('', true);
        $this->assertTrue($reader->isAnchored());

        $reader = $this->mkReader('', false);
        $this->assertFalse($reader->isAnchored());
    }

    protected function mkReader(string $input, bool $anchored = true, string $encoding = null): IStringReader {
        return new AsciiStringReader($input, $anchored);
    }

    protected function checkState(IStringReader $reader, int $expectedOffset, ?string $expectedMatch, ?array $expectedSubgroups): void {
        $this->assertSame($expectedOffset, $reader->offset());
        $this->assertSame($expectedMatch, $reader->match());
        $this->assertSame($expectedSubgroups, $reader->groups());
    }
}