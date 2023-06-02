<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Cli;

use Morpho\Base\Env as BaseEnvironment;

class Env extends BaseEnvironment {
    public static function init(): void {
        parent::init();
        $_SERVER += [
            'HTTP_HOST'       => 'localhost',
            'SCRIPT_NAME'     => null,
            'REMOTE_ADDR'     => '127.0.0.1',
            'REQUEST_METHOD'  => 'GET',
            'SERVER_NAME'     => null,
            'SERVER_SOFTWARE' => null,
            'HTTP_USER_AGENT' => null,
            'SERVER_PROTOCOL' => 'HTTP/1.0',
            'REQUEST_URI'     => '',
        ];
    }
}
