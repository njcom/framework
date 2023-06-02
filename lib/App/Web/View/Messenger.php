<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web\View;

use Countable;
use IteratorAggregate;
use Traversable;
use UnexpectedValueException;

use function count;
use function in_array;

class Messenger implements Countable, IteratorAggregate {
    public const ERROR = 'error';
    public const INFO = 'info';
    public const SUCCESS = 'success';
    public const WARNING = 'warning';

    protected ?IMessageStorage $messageStorage = null;

    protected array $allowedTypes = [
        self::SUCCESS,
        self::INFO,
        self::WARNING,
        self::ERROR,
    ];

    public function clearMessages(): void {
        $this->initMessageStorage();
        $this->messageStorage->clear();
    }

    protected function initMessageStorage(): void {
        if (null === $this->messageStorage) {
            $this->messageStorage = $this->mkMessageStorage();
        }
    }

    protected function mkMessageStorage(): IMessageStorage {
        return new SessionMessageStorage(__CLASS__);
    }

    public function addSuccessMessage(string $text, array $args = null): void {
        $this->addMessage($text, $args, self::SUCCESS);
    }

    public function addMessage(string $text, array $args = null, $type = null): void {
        if (null === $type) {
            $type = self::SUCCESS;
        }
        $this->checkMessageType($type);
        $this->initMessageStorage();
        if (!isset($this->messageStorage[$type])) {
            $this->messageStorage[$type] = [];
        }
        $this->messageStorage[$type][] = [
            'text' => $text,
            'args' => (array) $args,
        ];
    }

    protected function checkMessageType($type): void {
        if (!in_array($type, $this->allowedTypes)) {
            throw new UnexpectedValueException();
        }
    }

    public function addInfoMessage(string $text, array $args = null): void {
        $this->addMessage($text, $args, self::INFO);
    }

    public function addWarningMessage(string $text, array $args = null): void {
        $this->addMessage($text, $args, self::WARNING);
    }

    public function addErrorMessage(string $text, array $args = null): void {
        $this->addMessage($text, $args, self::ERROR);
    }

    public function hasWarningMessages(): bool {
        return isset($this->messageStorage[self::WARNING]) && count($this->messageStorage[self::WARNING]) > 0;
    }

    public function hasErrorMessages(): bool {
        return isset($this->messageStorage[self::ERROR]) && count($this->messageStorage[self::ERROR]) > 0;
    }

    public function getIterator(): Traversable {
        $this->initMessageStorage();
        return $this->messageStorage;
    }

    public function count(): int {
        $this->initMessageStorage();
        return count($this->messageStorage);
    }

    public function setMessageStorage(IMessageStorage $storage): void {
        $this->messageStorage = $storage;
    }
}
