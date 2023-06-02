<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Tech\Xml;

use Countable;
use Iterator;
use Morpho\Base\InvalidConfException;
use Morpho\Tech\Xml\Doc;
use Morpho\Testing\TestCase;

class DocTest extends TestCase {
    public function testSelect() {
        $html = <<<OUT
<div>
    <ol>
        <li>One</li>
        <li>Two</li>
        <li>Three</li>
    </ol>
</div>
OUT;
        $doc = Doc::parse($html);
        $nodes = $doc->select('//li');
        $this->assertCount(3, $nodes);
        $this->assertSame('One', $nodes->item(0)->nodeValue);
        $this->assertSame('Two', $nodes->item(1)->nodeValue);
        $this->assertSame('Three', $nodes->item(2)->nodeValue);
        $i = 0;
        foreach ($nodes as $node) {
            switch ($i) {
                case 0:
                    $this->assertSame('One', $node->nodeValue);
                    break;
                case 1:
                    $this->assertSame('Two', $node->nodeValue);
                    break;
                case 2:
                    $this->assertSame('Three', $node->nodeValue);
                    break;
            }
            $i++;
        }
        $this->assertSame(3, $i);
        $this->assertSame(3, $nodes->count());

        $this->assertInstanceOf(Countable::class, $nodes);
        $this->assertInstanceOf(Iterator::class, $nodes);

        $this->assertSame($nodes->item(0), $nodes->head());
        $this->assertSame([$nodes->item(1), $nodes->item(2)], $nodes->tail());
        $this->assertSame($nodes->item(2), $nodes->last());
        $this->assertSame([$nodes->item(0), $nodes->item(1)], $nodes->init());
    }

    public function testMk_ThrowsExceptionOnInvalidConf() {
        $this->expectException(InvalidConfException::class, "Invalid conf keys: invalidOne, invalidTwo");
        Doc::mk(['encoding' => 'utf-8', 'invalidOne' => 'first', 'invalidTwo' => 'second']);
    }

    public function testMk_DefaultConf() {
        $doc = Doc::mk();
        $this->assertInstanceOf(Doc::class, $doc);
        $doc1 = Doc::mk();
        $this->assertInstanceOf(Doc::class, $doc1);
        $this->assertNotSame($doc, $doc1);
    }

    public function testParse_ThrowsExceptionOnInvalidConf() {
        $this->expectException(InvalidConfException::class, "Invalid conf keys: invalidOne, invalidTwo");
        Doc::parse("foo", ['encoding' => 'utf-8', 'invalidOne' => 'first', 'invalidTwo' => 'second']);
    }

    public function testParse_FixEncodingConfParam() {
        $html = <<<OUT
<!DOCTYPE html><html><body>µ</body></html>
OUT;
        $doc = Doc::parse($html, ['fixEncoding' => true, 'formatOutput' => false]);
        $this->assertHtmlEquals(
            <<<OUT
<!DOCTYPE html>
<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head><body>µ</body></html>
OUT
            ,
            $doc->saveHTML()
        );

        $doc = Doc::parse($html, ['fixEncoding' => false, 'formatOutput' => false]);
        $this->assertHtmlEquals(
            <<<OUT
<!DOCTYPE html>
<html><body>&Acirc;&micro;</body></html>
OUT
            ,
            $doc->saveHTML()
        );
    }
}
