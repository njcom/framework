<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\App\Web\View;

use Morpho\App\ISite;
use Morpho\Base\Event;

use function file_exists;
use function implode;
use function json_encode;
use function Morpho\Base\last;
use function usort;

use const Morpho\App\LIB_DIR_NAME;

class RcProcessor extends HtmlProcessor {
    public const INDEX_ATTR = '_index';

    protected array $scripts = [];

    private ISite $site;

    public function __construct($request, ISite $site) {
        parent::__construct($request);
        $this->site = $site;
    }

    protected function containerBody(array $tag): null|array|bool|string {
        if (isset($tag[self::SKIP_ATTR])) {
            unset($tag[self::SKIP_ATTR]);
            return $tag;
        }
        $childScripts = $this->scripts;
        $this->scripts = [];
        $html = $this->__invoke(
            $tag['_text']
        ); // render the parent page, extract and collect all scripts from it into $this->scripts.

        if ($childScripts) {
            // Don't add action scripts if there are any child scripts
            $scripts = array_merge($this->scripts, $childScripts);
        } else {
            $actionScripts = $this->actionScripts($this->request['view']);
            $scripts = array_merge($this->scripts, $actionScripts);
        }

        $scripts = $this->changeBodyScripts($scripts);

        $html .= $this->renderScripts($scripts);
        $tag['_text'] = $html;
        return $tag;
    }

    /**
     * Includes a file for controller's action.
     */
    public function actionScripts(string $jsModuleId): array {
        $siteConf = $this->site->conf();
        $shortModuleName = last($this->site->moduleName(), '/');
        $fullJsModuleId = $shortModuleName . '/' . LIB_DIR_NAME . '/app/' . $jsModuleId;
        $relFilePath = $fullJsModuleId . '.js';
        $jsFilePath = $siteConf['paths']['frontendModuleDirPath'] . '/' . $relFilePath;
        $scripts = [];
        if (file_exists($jsFilePath)) {
            $jsConf = $this->jsConf();
            $scripts[] = [
                'src'      => '/' . $relFilePath, // Prepend with '/' to prepend base URI path later
                '_tagName' => 'script',
                '_text'    => '',
            ];
            $scripts[] = [
                '_tagName' => 'script',
                '_text'    => 'define(["require", "exports", "' . $fullJsModuleId . '"], function (require, exports, module) { module.main(window.app || {}, ' . json_encode(
                        $jsConf,
                        JSON_UNESCAPED_SLASHES
                    ) . '); });',
            ];
        }
        return $scripts;
    }

    protected function jsConf(): array {
        $request = $this->request;
        if (isset($request['jsConf'])) {
            return (array) $request['jsConf'];
        }
        return [];
    }

    protected function sortScripts(array $scripts): array {
        $index = 0;
        foreach ($scripts as $key => $script) {
            if (!isset($script[self::INDEX_ATTR])) {
                $script[self::INDEX_ATTR] = $index;
                $index++;
            }
            $script[self::INDEX_ATTR] = floatval($script[self::INDEX_ATTR]);
            $scripts[$key] = $script;
        }
        usort(
            $scripts,
            function ($prev, $next) {
                $a = $prev[self::INDEX_ATTR];
                $b = $next[self::INDEX_ATTR];
                $diff = $a - $b;
                if (abs($diff) <= PHP_FLOAT_EPSILON && isset($prev['src']) && isset($next['src'])) {
                    // Without this sort an exact order can be unknown when indexes are equal.
                    return $prev['src'] <=> $next['src'];
                }
                if ($diff > PHP_FLOAT_EPSILON) {
                    return 1;
                }
                if ($diff >= -PHP_FLOAT_EPSILON) { // -PHP_FLOAT_EPSILON <= $diff <= PHP_FLOAT_EPSILON
                    return 0;
                }
                return -1; // $diff < -PHP_FLOAT_EPSILON
            }
        );
        return $scripts;
    }

    protected function renderScripts(array $scripts): string {
        $html = [];
        foreach ($scripts as $tag) {
            if (isset($tag['src'])) {
                $tag['src'] = $this->request->prependWithBasePath($tag['src'])->toStr(null, false);
            }
            unset($tag[self::INDEX_ATTR]);
            $html[] = $this->renderTag($tag);
        }
        return implode("\n", $html);
    }

    protected function containerScript(array $tag): null|array|bool {
        if (isset($tag[self::SKIP_ATTR])) {
            unset($tag[self::SKIP_ATTR]);
            return $tag;
        }
        if (!isset($tag['type']) || (isset($tag['type']) && $tag['type'] == 'text/javascript')) {
            $this->scripts[] = $tag;
            return false;  // remove the original tag, we will add it later.
        }
        return null;
    }

    private function changeBodyScripts(array $scripts): array {
        $scripts = $this->sortScripts($scripts);

        $event = new Event('beforeRenderScripts', $scripts);
        $event->sender = $this;
        $this->trigger($event);

        return $event->args;
    }
}