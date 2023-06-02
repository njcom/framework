<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace {

    use Morpho\Tech\Php\Debugger;

    require_once __DIR__ . '/Trace.php';
    require_once __DIR__ . '/Frame.php';
    require_once __DIR__ . '/Debugger.php';

    if (!function_exists('d')) {
        function e29311(...$args) {
            $debugger = Debugger::instance();
            return count($args)
                ? $debugger->ignoreCaller(__FILE__, __LINE__)->dump(...$args)
                : $debugger;
        }
    }
}
namespace Morpho\Test\Unit\Tech\Php\PhpFileHeaderFixerTest {

    /**
     * Foo bar
     */
    function foo() {
    }
}