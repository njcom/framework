<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web\View;

use Morpho\App\Web\IRequest;

abstract class HtmlProcessor extends HtmlSemiParser {
    protected const SKIP_ATTR = '_skip';

    protected IRequest $request;

    public function __construct(IRequest $request) {
        parent::__construct();
        $this->request = $request;
    }
}
