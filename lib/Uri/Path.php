<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Uri;

use Morpho\Base\Path as BasePath;
use RuntimeException;

use function Morpho\Base\contains;
use function rawurlencode;
use function str_replace;

class Path extends BasePath implements IUriComponent {
    protected ?string $basePath = null;

    protected ?string $relPath = null;

    protected string $path;

    public function __construct(string $path) {
        $this->path = $path;
    }

    public function toStr(bool $encode = false): string {
        if ($encode) {
            return str_replace('%2F', '/', rawurlencode($this->path));
        }
        return $this->path;
    }

    public function endsWith(string $path): bool {
        return str_ends_with($this->path, $path);
    }

    public function contains(string $path): bool {
        return contains($this->path, $path);
    }

    public function setBasePath(string $basePath): void {
        if (!$this->startsWith($basePath)) {
            throw new RuntimeException('The base path is not at beginning of the path');
        }
        $this->basePath = $basePath;
    }

    public function startsWith(string $path): bool {
        return str_starts_with($this->path, $path);
    }

    public function basePath(): ?string {
        return $this->basePath;
    }

    public function relPath(): ?string {
        if (null === $this->relPath) {
            if (null !== $this->basePath) {
                $this->relPath = static::rel($this->basePath, $this->path);
            }
        }
        return $this->relPath;
    }

    public function isRel(): bool {
        return $this->path === '' || $this->path[0] !== '/';
    }
}
