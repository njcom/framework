<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

$baseDirPath = realpath(__DIR__ . '/..');
return [
    'siteFactory' => function (App $app) {
        $hostNameValidator = PHP_SAPI === 'cli' ? new Cli\HostNameValidator(['localhost'], 'localhost') : new Web\HostNameValidator(
            ['framework', 'localhost', '127.0.0.1']
        );
        return new SiteFactory($hostNameValidator, $app->conf());
    },
    'sites'       => [
        'localhost' => [
            'module' => [
                'name'  => VENDOR . '/localhost',
                'paths' => [
                    'dirPath'      => $baseDirPath . '/' . BACKEND_DIR_NAME . '/localhost',
                    'confFilePath' => $baseDirPath . '/' . BACKEND_DIR_NAME . '/localhost/' . CONF_DIR_NAME . '/' . SITE_CONF_FILE_NAME,
                ],
            ],
        ],
    ],
];