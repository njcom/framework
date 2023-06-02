<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Php;

use Morpho\Base\IFn;

class LogListener implements IFn {
    protected $logger;

    public function __construct(mixed $logger) {
        $this->logger = $logger;
    }

    public function __invoke(mixed $exception): mixed {
        $this->logger->emergency($exception, ['exception' => $exception]);
        return null;
    }
}