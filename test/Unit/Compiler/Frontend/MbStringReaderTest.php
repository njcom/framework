<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend;

use Morpho\Compiler\Frontend\IStringReader;
use Morpho\Compiler\Frontend\MbStringReader;

class MbStringReaderTest extends AsciiStringReaderTest {
    public function testInterface() {
        parent::testInterface();
        $this->assertInstanceOf(MbStringReader::class, $this->mkReader(''));
    }

    protected function mkReader(string $input, bool $anchored = true, string $encoding = null): IStringReader {
        return new MbStringReader($input, $encoding, $anchored);
    }

    public function testRead() {
        parent::testRead();
        $reader = $this->mkReader('Привет Мир!');
        $this->assertSame('П', $reader->read('/П/'));
        $this->checkState($reader, 1, 'П', ['П']);
        $this->assertSame('р', $reader->read('/р/'));
        $this->checkState($reader, 2, 'р', ['р']);
        $this->assertSame('ивет', $reader->read('/ивет/'));
        $this->checkState($reader, 6, 'ивет', ['ивет']);
    }

    public function testReadUntil() {
        parent::testReadUntil();
        $reader = $this->mkReader('Привет Мир!');
        $this->assertSame("Привет", $reader->readUntil('/Привет/'));
        $this->checkState($reader, 6, 'Привет', ['Привет']);
    }

    public function testChar() {
        parent::testChar();
        $reader = $this->mkReader('Привет Мир!');
        $this->assertSame('П', $reader->char());
        $this->checkState($reader, 1, 'П', ['П']);
        $this->assertSame('р', $reader->char());
        $this->checkState($reader, 2, 'р', ['р']);
    }

    public function testPeek() {
        parent::testPeek();
        $reader = $this->mkReader('Привет Мир!');
        $this->assertSame('Прив', $reader->peek(4));
    }

    public function testTerminate() {
        parent::testTerminate();
        $reader = $this->mkReader('Привет Мир!');
        $reader->terminate();
        $this->assertTrue($reader->isEnd());
        $this->checkState($reader, 11, null, null);
    }

    public function testIsLineStart() {
        parent::testIsLineStart();
        $reader = $this->mkReader("Привет\nМир!");
        $this->assertTrue($reader->isLineStart());
        $reader->read('/П/');
        $this->assertFalse($reader->isLineStart());
        $this->assertSame('ривет', $reader->read('/ривет/'));
        $this->assertSame("\n", $reader->read('/./s'));
        $this->assertTrue($reader->isLineStart());
    }

    public function testIsEnd() {
        parent::testIsEnd();
        $reader = $this->mkReader("Привет Мир!");
        $this->assertFalse($reader->isEnd());
        $reader->read('/Привет Мир/');
        $this->assertFalse($reader->isEnd());
        $reader->read('/!/');
        $this->assertTrue($reader->isEnd());
    }

    public function testMatchLen() {
        parent::testMatchLen();
        $reader = $this->mkReader("Привет Мир!");
        $reader->read('/Прив/');
        $this->assertSame(4, $reader->matchLen());
    }

    public function testPreMatch() {
        parent::testPreMatch();
        $reader = $this->mkReader("Привет Мир!");
        $reader->read('/Привет/');
        $reader->read('/ /');
        $this->assertSame('Привет', $reader->preMatch());
    }

    public function testPostMatch() {
        parent::testPostMatch();
        $reader = $this->mkReader("Привет Мир!");
        $reader->read('/Привет/');
        $reader->read('/ /');
        $this->assertSame('Мир!', $reader->postMatch());
    }

    public function testRest() {
        parent::testRest();
        $reader = $this->mkReader("Привет Мир!");
        $this->assertSame("Привет Мир!", $reader->rest());
        $reader->read('/Привет/');
        $this->assertSame(' Мир!', $reader->rest());
    }

    public function testRestLen() {
        parent::testRestLen();
        $reader = $this->mkReader("Привет Мир!");
        $this->assertSame(11, $reader->restLen());
        $reader->read('/Привет/');
        $this->assertSame(5, $reader->restLen());
    }
}