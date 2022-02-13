<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\App\Web\View;

use function array_pop;
use function array_push;
use function count;

class TreeRendererPlugin extends Plugin {
    protected $internalNodeRenderer, $leafNodeRenderer;

    private $parents = [];

    public function __invoke(mixed $val): string {
        return $this->render($val);
    }

    public function render(array $nodes): string {
        if (!count($nodes)) {
            return '';
        }
        $output = '';
        foreach ($nodes as $label => $node) {
            if (isset($node['nodes'])) {
                $output .= $this->renderInternalNode($node);
            } else {
                $output .= $this->renderLeafNode($node);
            }
        }
        return '<ul' . (empty($this->parents) ? ' class="tree"' : '') . '>'
            . $output
            . '</ul>';
    }

    protected function renderInternalNode($node): string {
        $render = $this->internalNodeRenderer();
        $renderedChildren = '';
        if (!empty($node['nodes'])) {
            array_push($this->parents, $node);
            $renderedChildren = $this->render($node['nodes']);
            array_pop($this->parents);
        }
        return $render($node, $renderedChildren);
    }

    public function internalNodeRenderer(): callable {
        if (null === $this->internalNodeRenderer) {
            $this->internalNodeRenderer = function (array $node, string $renderedChildren): string {
                return '<li class="tree__node tree__node-internal">' . PhpTemplateEngine::e($node['label'])
                    //. $this->renderCheckbox($name, true)
                    . $renderedChildren
                    . '</li>';
            };
        }
        return $this->internalNodeRenderer;
    }

    protected function renderLeafNode($node): string {
        $render = $this->leafNodeRenderer();
        return $render($node);
    }

    public function leafNodeRenderer(): callable {
        if (null === $this->leafNodeRenderer) {
            $this->leafNodeRenderer = function (array $node): string {
                return '<li class="tree__node tree__node-leaf">'
                    . PhpTemplateEngine::e($node['label'])//$this->renderCheckbox($node['label'], false)
                    . '</li>';
            };
        }
        return $this->leafNodeRenderer;
    }

    public function setInternalNodeRenderer(callable $renderer): static {
        $this->internalNodeRenderer = $renderer;
        return $this;
    }

    public function setLeafNodeRenderer(callable $renderer): static {
        $this->leafNodeRenderer = $renderer;
        return $this;
    }
    /*
        protected function renderCheckbox(string $name, bool $isInternalNode): string {
            $renderInputName = function (string $name): string {
                $parents = $this->parents;
                $parents[] = $name;
                return \implode('___', \array_map([$this, 'escapeHtml'], $parents));
            };
            return '<input type="checkbox" name="' . ($isInternalNode ? 'internalNode' : 'leafNode') . '[' . $renderInputName($name) . ']"> ' . PhpTemplateEngine::e($name);
        }
    */
}
