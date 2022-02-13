<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\App\Web\View;

use Morpho\Base\IHasServiceManager;
use Morpho\Base\IServiceManager;
use Morpho\Base\NotImplementedException;

class WidgetPlugin extends Plugin implements IHasServiceManager {
    private $serviceManager;

    public function __invoke($value) {
        throw new NotImplementedException();
        /*
        $name = $args[0];
        if ($name !== 'Menu') {
        }
        $request = $this->serviceManager['request'];
        return new MenuWidget(
            $this->serviceManager['db'],
            $request->baseRelUri(),
            $request->requestUri()
        );
        */
    }

    public function setServiceManager(IServiceManager $serviceManager): static {
        $this->serviceManager = $serviceManager;
        return $this;
    }
}
/*
class MenuWidget {
    public function __construct(Db $db, $baseUri, $requestUri) {
        $this->db = $db;
        $this->baseUri = $baseUri;
        $this->requestUri = \trim($requestUri, '/');
    }

    public function renderSystemMenu(array $conf = null, array $attributes = null) {
        $conf = \array_merge(['button' => true], (array)$conf);
        $html = '';
        if ($conf['button']) {
            $html = '<button class="btn btn-default btn-sm dropdown-toggle navbar-btn" type="button" data-toggle="dropdown" style="margin-left: 1em;"><span class="caret"></span></button>';
        }
        $html .= $this->render(Menu::SYSTEM_NAME, $attributes);
        return $html;
    }

    public function render($name, array $attributes = null) {
        if (null === $attributes) {
            $attributes = ['class' => 'dropdown-menu'];
        }
        $lines = $this->db->eval(
            'SELECT r.uri, mi.title
            FROM menu_item mi
            INNER JOIN menu m
                ON mi.menuId = m.id
            INNER JOIN route r
                ON r.module = mi.module AND r.controller = mi.controller AND r.action = mi.action
            WHERE m.name = ?
            ORDER BY mi.weight, mi.title',
            [$name]
        );
        $requestUri = $this->requestUri;
        $baseUri = $this->baseUri;
        $html = '<ul' . Phptemplateengine::attributes($attributes) . '>';
        foreach ($lines as $line) {
            $html .= '<li><a'
                . ($line['uri'] == $requestUri ? ' class="active"' : '')
                . ' href="' . escapeHtml($baseUri . $line['uri']) . '">' . escapeHtml($line['title']) . '</a></li>';
        }
        $html .= '</ul>';
        return $html;
    }
}
*/
