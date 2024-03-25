<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web;

use Morpho\App\Web\HttpVersion;
use Morpho\App\Web\StatusCode;
use Morpho\App\Web\StatusLine;
use Morpho\Testing\TestCase;

class StatusLineTest extends TestCase {
    public function testToString() {
        $statusLine = new StatusLine();
        $this->assertSame('HTTP/1.1 200', (string)$statusLine);

        $statusLine = new StatusLine(HttpVersion::V3, StatusCode::NotFound);
        $this->assertSame('HTTP/3 404', (string)$statusLine);
    }
}
