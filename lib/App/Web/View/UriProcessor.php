<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web\View;

class UriProcessor extends HtmlProcessor {
    protected function tagIMg(array $tag): array {
        return $this->prependAttrWithBasePath($tag, 'src');
    }

    protected function tagLink(array $tag): array {
        return $this->prependAttrWithBasePath($tag, 'href');
    }

    protected function tagA(array $tag): array {
        return $this->prependAttrWithBasePath($tag, 'href');
    }

    protected function tagForm(array $tag): array {
        return $this->prependAttrWithBasePath($tag, 'action');
    }

    protected function tagScript(array $tag): array {
        return $this->prependAttrWithBasePath($tag, 'src');
    }

    private function prependAttrWithBasePath(array $tag, string $attrName): array {
        if (isset($tag[$this->skipAttr])) {
            return $tag;
        }
        if (isset($tag[$attrName])) {
            $attrVal = $tag[$attrName];
            $pos = strpos($attrVal, '<?');
            if ($pos === 0) {
                return $tag;
            }
            if (false !== $pos) {
                $pre = substr($attrVal, 0, $pos);
                $tag[$attrName] = $this->request->prependWithBasePath($pre)->toStr(null, false) . substr($attrVal, $pos);
            } else {
                $tag[$attrName] = $this->request->prependWithBasePath($attrVal)->toStr(null, false);
            }
        }
        return $tag;
    }
}
