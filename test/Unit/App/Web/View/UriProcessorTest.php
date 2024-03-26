<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web\View;

use Morpho\App\Web\Request;
use Morpho\Uri\Uri;
use Morpho\App\Web\View\UriProcessor;
use Morpho\Testing\TestCase;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\DataProvider;

#[BackupGlobals(enabled: true)]
class UriProcessorTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        $_GET = $_POST = $_REQUEST = $_COOKIE = [];
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/';
    }

    public static function dataProcessUrisInTags() {
        foreach (['/base/path', '/'] as $basePath) {
            // img tag
            yield [
                $basePath,
                '<img src="' . rtrim($basePath, '/') . '/foo/bar">',
                '<img src="/foo/bar">',
            ];
            // `form` tag
            yield [
                $basePath,
                '<form action="http://host/news/test1"></form>',
                '<form action="http://host/news/test1"></form>',
            ];
            yield [
                $basePath,
                '<form action="news/test1"></form>',
                '<form action="news/test1"></form>',
            ];
            yield [
                $basePath,
                '<form action="//host/news/test1"></form>',
                '<form action="//host/news/test1"></form>',
            ];
            yield [
                $basePath,
                "<form action=\"" . rtrim($basePath, '/') . "/news/test2\"></form>",
                '<form action="/news/test2"></form>',
            ];
            yield [
                $basePath,
                '<form action="<?= \'test\' ?>/news/test1"></form>',
                '<form action="<?= \'test\' ?>/news/test1"></form>',
            ];
            yield [
                $basePath,
                '<form action="' . rtrim($basePath, '/') . '/news/<?= \'test\' ?>/test1<?php echo \'ok\'; ?>"></form>',
                '<form action="/news/<?= \'test\' ?>/test1<?php echo \'ok\'; ?>"></form>',
            ];
            yield [
                $basePath,
                '<form action="' . rtrim($basePath, '/') . '/news/<?= \'test\' ?>/test1"></form>',
                '<form action="/news/<?= \'test\' ?>/test1"></form>',
            ];
            // `link` tag
            yield [
                $basePath,
                '<link href="http://host/css/test1.css">',
                '<link href="http://host/css/test1.css">',
            ];
            yield [
                $basePath,
                '<link href="css/test1.css">',
                '<link href="css/test1.css">',
            ];
            yield [
                $basePath,
                '<link href="//host/css/test1.css">',
                '<link href="//host/css/test1.css">',
            ];
            yield [
                $basePath,
                '<link href="' . rtrim($basePath, '/') . '/css/test1.css">',
                '<link href="/css/test1.css">',
            ];
            yield [
                $basePath,
                '<link href="<?= \'test\' ?>/css/test1.css">',
                '<link href="<?= \'test\' ?>/css/test1.css">',
            ];
            yield [
                $basePath,
                '<link href="' . rtrim($basePath, '/') . '/css/<?= \'test\' ?>/test1.css">',
                '<link href="/css/<?= \'test\' ?>/test1.css">',
            ];
            // `a` tag
            yield [
                $basePath,
                '<a href="http://host/css/test1"></a>',
                '<a href="http://host/css/test1"></a>',
            ];
            yield [
                $basePath,
                '<a href="css/test1"></a>',
                '<a href="css/test1"></a>',
            ];
            yield [
                $basePath,
                '<a href="//host/css/test1"></a>',
                '<a href="//host/css/test1"></a>',
            ];
            yield [
                $basePath,
                '<a href="' . rtrim($basePath, '/') . '/css/test1"></a>',
                '<a href="/css/test1"></a>',
            ];
            yield [
                $basePath,
                '<a href="<?= \'test\' ?>/css/test1"></a>',
                '<a href="<?= \'test\' ?>/css/test1"></a>',
            ];
            yield [
                $basePath,
                '<a href="' . rtrim($basePath, '/') . '/css/<?= \'test\' ?>/test1"></a>',
                '<a href="/css/<?= \'test\' ?>/test1"></a>',
            ];
            // `script` tag
            yield [
                $basePath,
                '<script src="http://host/js/test1.js"></script>',
                '<script src="http://host/js/test1.js"></script>',
            ];
            yield [
                $basePath,
                '<script src="js/test1.js"></script>',
                '<script src="js/test1.js"></script>',
            ];
            yield [
                $basePath,
                '<script src="//host/js/test1.js"></script>',
                '<script src="//host/js/test1.js"></script>',
            ];
            yield [
                $basePath,
                '<script src="' . rtrim($basePath, '/') . '/js/test1.js"></script>',
                '<script src="/js/test1.js"></script>',
            ];
            yield [
                $basePath,
                '<script src="<?= \'test\' ?>/js/test1.js"></script>',
                '<script src="<?= \'test\' ?>/js/test1.js"></script>',
            ];
            yield [
                $basePath,
                '<script src="' . rtrim($basePath, '/') . '/js/<?= \'test\' ?>/test1.js"></script>',
                '<script src="/js/<?= \'test\' ?>/test1.js"></script>',
            ];
        }
    }

    #[DataProvider('dataProcessUrisInTags')]
    public function testProcessUrisInTags(string $basePath, $expected, $tag) {
        $request = new Request();
        $uri = new Uri($basePath);
        $uri->path()->setBasePath($basePath);
        $request->uri = $uri;

        $processor = new UriProcessor($request);

        $processedHtml = $processor->__invoke($tag);

        $this->assertHtmlEquals($expected, $processedHtml);
    }
}
