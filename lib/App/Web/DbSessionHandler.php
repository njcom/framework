<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use Morpho\Base\NotImplementedException;
use SessionHandlerInterface;

class DbSessionHandler implements SessionHandlerInterface {
    public function close(): bool {
        throw new NotImplementedException();
    }

    public function destroy(string $sessionId): bool {
        throw new NotImplementedException();
    }

    public function gc(int $maxlifetime): false|int {
        throw new NotImplementedException();
    }

    public function open(string $savePath, string $name): bool {
        throw new NotImplementedException();
    }

    public function read(string $sessionId): false|string {
        throw new NotImplementedException();
    }

    public function write(string $sessionId, string $sessionData): bool {
        throw new NotImplementedException();
    }
}
