<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Base;

class ArrPipe implements IPipe {
    protected array $steps;

    public function __construct(array $steps = null) {
        $this->steps = (array) $steps;
    }

    public function __invoke(mixed $val): mixed {
        foreach ($this as $fn) {
            $val = $fn($val);
        }
        return $val;
    }

    public function setSteps(array $steps): void {
        $this->steps = $steps;
    }

    public function prependStep($step): static {
        array_unshift($this->steps, $step);
        return $this;
    }

    public function appendStep(callable $step): static {
        $this->steps[] = $step;
        return $this;
    }

    public function deleteStep($index): void {
        unset($this->steps[$index]);
        $this->steps = array_values($this->steps);
    }

    public function steps(): array {
        return $this->steps;
    }

    public function step(string|int $key): mixed {
        return $this->steps[$key];
    }

    public function current(): callable {
        return current($this->steps);
    }

    public function next(): void {
        next($this->steps);
    }

    public function valid(): bool {
        return isset($this->steps[$this->key()]);
    }

    public function key(): int|string|null {
        return key($this->steps);
    }

    public function rewind(): void {
        reset($this->steps);
    }

    public function count(): int {
        return count($this->steps);
    }
}