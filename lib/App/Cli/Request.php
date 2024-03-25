<?php declare(strict_types=1);
/**
 * Some methods in this class based on \Zend\Http\PhpEnvironment\Request class.
 * @see https://github.com/zendframework/zend-http for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license https://github.com/zendframework/zend-http/blob/master/LICENSE.md New BSD License
 */
namespace Morpho\App\Cli;

use ArrayObject;
use Morpho\App\IMessage;

class Request extends ArrayObject implements IMessage {
    public bool $isHandled = false;
    public array $handler = [];
    public Response $response;

    public function __construct(array $vals = null) {
        parent::__construct((array)$vals);
        $this->response = new Response();
    }
}
