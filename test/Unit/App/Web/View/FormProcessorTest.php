<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web\View;

use Morpho\App\Web\Request;
use Morpho\Uri\Uri;
use Morpho\App\Web\View\FormProcessor;
use Morpho\Testing\TestCase;

class FormProcessorTest extends TestCase {
    private FormProcessor $formPersister;

    protected function setUp(): void {
        parent::setUp();
        $request = $this->createMock(Request::class);
        $uri = $this->createMock(Uri::class);
        $uri->expects($this->any())
            ->method('toStr')
            ->willReturn('/foo/bar<script?one=ok&two=done');
        $request->expects($this->any())
            ->method('uri')
            ->willReturn($uri);
        $this->formPersister = new FormProcessor($request);
    }

    public function testInvoke_FormWithoutMethodAndActionAttrs_SetsBoth() {
        $this->assertSame('post', FormProcessor::DEFAULT_METHOD);
        $html = '<form></form>';
        $this->assertEquals(
            '<form method="' . FormProcessor::DEFAULT_METHOD . '" action="/foo/bar&lt;script?one=ok&amp;two=done"></form>',
            $this->formPersister->__invoke($html)
        );
    }

    public function testInvoke_FormWithMethodWithoutActionAttrs_SetsAction() {
        $html = '<form method="get"></form>';
        $this->assertEquals(
            '<form method="get" action="/foo/bar&lt;script?one=ok&amp;two=done"></form>',
            $this->formPersister->__invoke($html)
        );
    }
}
