<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Php;

use Psr\Log\LoggerInterface as ILogger;

class LogListener {
    protected ILogger $logger;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function __invoke(mixed $exception): void {
        $this->logger->emergency($exception, ['exception' => $exception]);
    }
}