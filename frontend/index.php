<?php declare(strict_types=1);
namespace Morpho\App;
require __DIR__ . '/../vendor/autoload.php';
(new App(require __DIR__ . '/../conf/' . APP_CONF_FILE_NAME))->__invoke([]);
