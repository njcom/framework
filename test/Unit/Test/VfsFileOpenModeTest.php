<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Test;

use Morpho\Testing\TestCase;
use Morpho\Testing\VfsFileOpenMode;

class VfsFileOpenModeTest extends TestCase {
    public function testModes() {
        $mode = new VfsFileOpenMode('r');
        $this->assertFalse($mode->create());
        $this->assertFalse($mode->append());
        $this->assertTrue($mode->readOnly());
        $this->assertFalse($mode->truncate());
        $this->assertFalse($mode->writeOnly());
        $this->assertFalse($mode->readWrite());
        $this->assertFalse($mode->canWrite());
        $this->assertTrue($mode->canRead());

        $mode = new VfsFileOpenMode('r+');
        $this->assertFalse($mode->create());
        $this->assertFalse($mode->append());
        $this->assertFalse($mode->readOnly());
        $this->assertFalse($mode->truncate());
        $this->assertFalse($mode->writeOnly());
        $this->assertTrue($mode->readWrite());
        $this->assertTrue($mode->canWrite());
        $this->assertTrue($mode->canRead());

        $checkWriteBinary = function ($mode) {
            $mode = new VfsFileOpenMode($mode);
            $this->assertTrue($mode->create());
            $this->assertFalse($mode->append());
            $this->assertFalse($mode->readOnly());
            $this->assertTrue($mode->truncate());
            $this->assertTrue($mode->writeOnly());
            $this->assertFalse($mode->readWrite());
            $this->assertTrue($mode->canWrite());
            $this->assertFalse($mode->canRead());
        };
        $checkWriteBinary('w');
        $checkWriteBinary('wb');

        $mode = new VfsFileOpenMode('w+');
        $this->assertTrue($mode->create());
        $this->assertFalse($mode->append());
        $this->assertFalse($mode->readOnly());
        $this->assertTrue($mode->truncate());
        $this->assertFalse($mode->writeOnly());
        $this->assertTrue($mode->readWrite());
        $this->assertTrue($mode->canWrite());
        $this->assertTrue($mode->canRead());

        $mode = new VfsFileOpenMode('a');
        $this->assertTrue($mode->create());
        $this->assertTrue($mode->append());
        $this->assertFalse($mode->readOnly());
        $this->assertFalse($mode->truncate());
        $this->assertTrue($mode->writeOnly());
        $this->assertFalse($mode->readWrite());
        $this->assertTrue($mode->canWrite());
        $this->assertFalse($mode->canRead());

        $mode = new VfsFileOpenMode('a+');
        $this->assertTrue($mode->create());
        $this->assertTrue($mode->append());
        $this->assertFalse($mode->readOnly());
        $this->assertFalse($mode->truncate());
        $this->assertFalse($mode->writeOnly());
        $this->assertTrue($mode->readWrite());
        $this->assertTrue($mode->canWrite());
        $this->assertTrue($mode->canRead());
    }
}