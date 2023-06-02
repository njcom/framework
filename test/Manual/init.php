<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Manual;

use ErrorException;
use RuntimeException;

use function error_reporting;
use function ini_get;

use function ini_set;
use function set_error_handler;

use const ASSERT_ACTIVE;
use const ASSERT_BAIL;
use const ASSERT_CALLBACK;

error_reporting(E_ALL);
ini_set('display_errors', '1');
set_error_handler(
    function ($severity, $message, $filePath, $lineNo) {
        if (!(error_reporting() & $severity)) {
            return;
        }
        throw new ErrorException($message, 0, $severity, $filePath, $lineNo);
    }
);
if (ini_get('zend.assertions') !== '1') {
    throw new RuntimeException("The 'zend.assertions' ini parameter must be set to 1 for expectations");
}
assert_options(ASSERT_ACTIVE, true);     // enable assert() evaluation?
assert_options(
    ASSERT_WARNING,
    false
);   // issue a PHP warning for each failed assertion, handled by the ASSERT_CALLBACK
assert_options(ASSERT_BAIL, false);      // terminate execution on failed assertions, handled by the ASSERT_CALLBACK
assert_options(                          // callback to call on failed assertions
    ASSERT_CALLBACK,
    function (string $filePath, int $lineNo, string $assertionExpr, string $description = null) {
        echo 'Failed assertion: ' . rtrim($assertionExpr) . "\n";
        echo 'Description: ' . rtrim($description) . "\n";
        echo "File: $filePath\n";
        echo "Line: $lineNo\n";
        exit(1);
    }
);

require __DIR__ . '/../../vendor/autoload.php';
