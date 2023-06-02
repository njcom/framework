<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Php;

use Throwable;

use function htmlspecialchars;
use function print_r;

class DumpListener {
    const FAILURE_EXIT_CODE = 1;

    public function __invoke(Throwable $exception): void {
        $exAsString = $exception->__toString();
        if (class_exists(Debugger::class)) {
            Debugger::instance()
                ->dumpWithExitCode($exAsString, self::FAILURE_EXIT_CODE);
        } else {
            $message = PHP_SAPI == 'cli'
                ? $exAsString
                : '<pre>' . print_r(htmlspecialchars($exAsString, ENT_QUOTES), true) . '</pre>';
            echo $message;
        }
        exit(self::FAILURE_EXIT_CODE);
    }
}
