<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web\View;

use Morpho\App\ISite;
use Morpho\App\Web\IRequest;
use Morpho\App\Web\View\FormProcessor;
use Morpho\App\Web\View\PhpProcessor;
use Morpho\App\Web\View\PhpTemplateEngine;
use Morpho\App\Web\View\RcProcessor;
use Morpho\App\Web\View\UriProcessor;
use Morpho\Base\IPipe;
use Morpho\Testing\TestCase;

use function date;
use function file_put_contents;
use function str_replace;

class PhpTemplateEngineTest extends TestCase {
    private PhpTemplateEngine $templateEngine;

    public function setUp(): void {
        parent::setUp();
        $this->templateEngine = new PhpTemplateEngine($this->templateEngineConf());
    }

    private function templateEngineConf(): array {
        $request = $this->createMock(IRequest::class);
        $site = $this->createMock(ISite::class);
        return [
            'request' => $request,
            'site'    => $site,
            'steps'   => [
                'phpProcessor'    => new PhpProcessor(),
                'uriProcessor'    => new UriProcessor($request),
                'formPersister'   => new FormProcessor($request),
                'scriptProcessor' => new RcProcessor($request, $site),
            ],
        ];
    }

    public function testInterface() {
        $this->assertInstanceOf(IPipe::class, $this->templateEngine);
    }

    public function dataEval() {
        yield [
            '',
            '',
            [],
        ];
        yield [
            "It&#039;s",
            '<?= "It$foo";',
            ['foo' => "'s"],
        ];
    }

    /**
     * @dataProvider dataEval
     */
    public function testEval_DefaultSteps($expected, $source, $vars) {
        $compiled = $this->templateEngine->eval($source, $vars);
        $this->assertSame($expected, $compiled);
    }

    public function testEval_WithoutSteps() {
        $code = '<?php echo "Hello $world";';
        $this->templateEngine->setSteps([]);
        $this->assertSame([], $this->templateEngine->steps());

        $res = $this->templateEngine->eval($code, ['world' => 'World!']);

        $this->assertSame('Hello World!', $res);
    }

    public function testEval_PrependCustomStep() {
        $code = '<?php echo ??;';
        $this->templateEngine->prependStep(
            function ($context) {
                $context['program'] = str_replace('??', '"<span>$smile</span>"', $context['program']);
                return $context;
            }
        );
        $res = $this->templateEngine->eval($code, ['smile' => ':)']);
        $this->assertSame(
            htmlspecialchars('<span>:)</span>', ENT_QUOTES),
            $res
        );
    }

    public function testEvalPhpFile_PreservingThis() {
        $code = '<?php echo "$this->a $b";';
        $filePath = $this->createTmpFile();
        file_put_contents($filePath, $code);

        $templateEngine = new class ($this->templateEngineConf()) extends PhpTemplateEngine {
            protected $a = 'Hello';
        };
        $this->assertSame(
            'Hello World!',
            $templateEngine->evalPhpFile($filePath, ['b' => 'World!'])
        );
    }

    public function testForceCompileAccessor() {
        $this->checkBoolAccessor([$this->templateEngine, 'forceCompile'], false);
    }

    public function testSelectControl_Empty() {
        $this->assertHtmlEquals("<select></select>", $this->templateEngine->selectControl([]));
    }

    public function testSelectControl_IndexedArrOptions_WithoutSelectedOption() {
        $options = ['foo', 'bar'];
        $html = $this->templateEngine->selectControl($options);
        $this->assertHtmlEquals('<select><option value="0">foo</option><option value="1">bar</option></select>', $html);
    }

    public function testSelectControl_IndexedArrOptions_WithSingleSelectedOption() {
        $options = ['foo', 'bar'];
        $html = $this->templateEngine->selectControl($options, 1);
        $this->assertHtmlEquals(
            '<select><option value="0">foo</option><option value="1" selected>bar</option></select>',
            $html
        );
    }

    public function testSelectControl_IndexedArrOptions_WithMultipleSelectedOptions() {
        $options = ['foo', 'bar'];
        $html = $this->templateEngine->selectControl($options, [0, 1]);
        $this->assertHtmlEquals(
            '<select><option value="0" selected>foo</option><option value="1" selected>bar</option></select>',
            $html
        );
    }

    public function testSelectControl_AddsIdAttribIfNotSpecifiedFromNameAttrib() {
        $html = $this->templateEngine->selectControl(null, null, ['name' => 'task[id]']);
        $this->assertHtmlEquals('<select name="task[id]" id="task-id"></select>', $html);
    }

    public function testTag() {
        $this->assertSame('<foo bar="baz">hello</foo>', $this->templateEngine->tag('foo', 'hello', ['bar' => 'baz']));
    }

    public function testTag_EolConfParam() {
        $this->assertEquals("<foo></foo>", $this->templateEngine->tag('foo'));
        $this->assertEquals("<foo></foo>\n", $this->templateEngine->tag('foo', null, null, ['eol' => true]));
        $this->assertEquals("<foo></foo>", $this->templateEngine->tag('foo', null, null, ['eol' => false]));
    }

    public function testTag_EscapeConfParam() {
        $this->assertEquals(
            '<foo>&quot;</foo>',
            $this->templateEngine->tag('foo', '"', null, ['eol' => false, 'escape' => true])
        );
        $this->assertEquals('<foo>&quot;</foo>', $this->templateEngine->tag('foo', '"', null, ['eol' => false]));
        $this->assertEquals(
            '<foo>"</foo>',
            $this->templateEngine->tag('foo', '"', null, ['eol' => false, 'escape' => false])
        );
    }

    public function testTag1_MultipleAttribs() {
        $attribs = ['href' => 'foo/bar.css', 'rel' => 'stylesheet'];
        $expected = '<link href="foo/bar.css" rel="stylesheet">';
        $this->assertEquals(
            $expected,
            $this->templateEngine->tag('link', null, $attribs, ['eol' => false, 'single' => true])
        );
        $this->assertEquals(
            $expected,
            $this->templateEngine->tag1('link', $attribs, ['eol' => false])
        );
    }

    public function testTag1_Html5() {
        $this->assertSame('<foo bar="baz">', $this->templateEngine->tag1('foo', ['bar' => 'baz']));
        $this->assertSame('<foo bar="baz">', $this->templateEngine->tag1('foo', ['bar' => 'baz'], ['xml' => false]));
    }

    public function testTag1_Xml() {
        $this->assertSame('<foo bar="baz" />', $this->templateEngine->tag1('foo', ['bar' => 'baz'], ['xml' => true]));
    }

    public function testHtmlId() {
        $this->assertEquals('foo-1-bar-2-test', $this->templateEngine->htmlId('foo[1][bar][2][test]'));
        $this->assertEquals('foo-1-bar-2-test-1', $this->templateEngine->htmlId('foo_1-bar_2[test]'));
        $this->assertEquals('fo-o', $this->templateEngine->htmlId('<fo>&o\\'));
        $this->assertEquals('fo-o-1', $this->templateEngine->htmlId('<fo>&o\\'));
        $this->assertEquals('foo-bar', $this->templateEngine->htmlId('FooBar'));
        $this->assertEquals('foo-bar-1', $this->templateEngine->htmlId('FooBar'));
    }

    public function testPageHtmlId() {
        $request = $this->createMock(IRequest::class);
        $request->expects($this->any())
            ->method('handler')
            ->willReturn(
                [
                    'controllerPath' => 'foo/bar',
                    'method'         => 'baz',
                ]
            );
        $this->templateEngine->setRequest($request);

        $this->assertSame('foo-bar-baz', $this->templateEngine->pageHtmlId());
    }

    public function testEmptyAttribs() {
        $this->assertEquals('', $this->templateEngine->attribs([]));
    }

    public function testMultipleAttribs() {
        $this->assertEquals(
            ' data-api name="foo" id="some-id"',
            $this->templateEngine->attribs(['data-api', 'name' => 'foo', 'id' => 'some-id'])
        );
    }

    public function testCopyright() {
        $curYear = date('Y');
        $brand = 'Mices\'s';

        $startYear = $curYear - 2;
        $this->assertEquals(
            '© ' . $startYear . '-' . $curYear . ', Mices&#039;s',
            $this->templateEngine->copyright($brand, $startYear)
        );

        $startYear = $curYear;
        $this->assertEquals(
            '© ' . $startYear . ', Mices&#039;s',
            $this->templateEngine->copyright($brand, $startYear)
        );
    }

    public function testEncodeDecode_SpecialCharsWithText() {
        $original = '<h1>Hello</h1>';
        $encoded = $this->templateEngine->e($original);
        $this->assertEquals('&lt;h1&gt;Hello&lt;/h1&gt;', $encoded);
        $this->assertEquals($original, $this->templateEngine->de($encoded));
    }

    public function testEncodeDecode_OnlySpecialChars() {
        // $specialChars taken from Zend\Escaper\EscaperTest:
        $specialChars = [
            '\'' => '&#039;',
            '"'  => '&quot;',
            '<'  => '&lt;',
            '>'  => '&gt;',
            '&'  => '&amp;',
        ];
        foreach ($specialChars as $char => $expected) {
            $encoded = $this->templateEngine->e($char);
            $this->assertSame($expected, $encoded);
            $this->assertSame($char, $this->templateEngine->de($encoded));
        }
    }
}
