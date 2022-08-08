<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web\View;

use Morpho\App\Web\View\HtmlSemiParser;
use Morpho\Testing\TestCase;

use function trim;

class HtmlSemiParserTest extends TestCase {
    private HtmlSemiParser $parser;

    protected function setUp(): void {
        parent::setUp();
        $this->parser = new HtmlSemiParser();
    }

    public function testCallsTagHandler() {
        $handler = $this->parser->attachHandlersFrom(new MyTagHandler());
        $html = <<<HTML
<body>
    <a href="/foo/bar" class="my">Some text</a>
</body>
HTML;
        $this->parser->__invoke($html);
        $this->assertEquals(['href' => '/foo/bar', 'class' => 'my'], $handler->attributes());
        $this->assertEquals('a', $handler->tagName());
    }

    public function testCallContainerHandler() {
        $handler = $this->parser->attachHandlersFrom(new MyContainerHandler());
        $html = <<<HTML
<div class="my-class" style="width: 98%;">
    <a href="foo">123</a>
</div>
HTML;
        $this->parser->__invoke($html);
        $this->assertEquals('<a href="foo">123</a>', $handler->text());
        $this->assertEquals(
            [
                'class' => 'my-class',
                'style' => 'width: 98%;',
            ],
            $handler->attributes()
        );
        $this->assertEquals('div', $handler->tagName());
    }

    public function testSkipsTagHandlerIfNoTag() {
        $handler = $this->parser->attachHandlersFrom(new MyTagHandler());
        $html = <<<HTML
<body>
    <form action="/some/uri" method="post">
        <input name="some_name">
    </form>
</body>
HTML;
        $this->parser->__invoke($html);
        $this->assertNull($handler->tagName());
        $this->assertEquals([], $handler->attributes());
    }

    public function testCanRemoveTag() {
        $handler = $this->parser->attachHandlersFrom(new RemoveTagHandler());
        $html = <<<HTML
<body>
<br>
<br>
</body>
HTML;
        $expected = <<<HTML
<body>
<br>
</body>
HTML;
        $this->assertEquals($expected, $this->parser->__invoke($html));
    }

    public function testCanRemoveContainer() {
        $handler = $this->parser->attachHandlersFrom(new RemoveTagHandler());
        $html = <<<HTML
<body>
<script src="template.dart"></script>
<script src="main.dart"></script>
</body>
HTML;
        $expected = <<<HTML
<body>
<script src="main.dart"></script>
</body>
HTML;
        $this->assertEquals($expected, $this->parser->__invoke($html));
    }
}

abstract class TagHandler {
    protected $tag = [];

    public function tagName() {
        return isset($this->tag['_tagName']) ? $this->tag['_tagName'] : null;
    }

    public function attributes() {
        $attribs = [];
        foreach ($this->tag as $key => $value) {
            if ($key[0] == '_') {
                continue;
            }
            $attribs[$key] = $value;
        }
        return $attribs;
    }
}

class MyContainerHandler extends TagHandler {
    public function containerDiv($tag) {
        $this->tag = $tag;
    }

    public function text() {
        return trim($this->tag['_text']);
    }
}

class MyTagHandler extends TagHandler {
    public function tagA($tag) {
        $this->tag = $tag;
    }
}

class RemoveTagHandler {
    private $remove = false;

    public function tagBr($tag) {
        if ($this->remove) {
            return false;
        }
        $this->remove = true;
    }

    public function containerScript($tag) {
        if ($tag['src'] == 'template.dart') {
            return false;
        }
    }
}
