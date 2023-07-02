<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Php;

use Exception;
use Throwable;

use function chmod;
use function error_clear_last;
use function error_get_last;
use function file_exists;
use function file_put_contents;
use function filemtime;
use function get_class;
use function glob;
use function is_dir;
use function join;
use function md5;
use function mkdir;
use function mt_rand;
use function rtrim;
use function str_replace;
use function strtolower;
use function sys_get_temp_dir;
use function time;
use function unlink;

/**
 * Basic idea and code was found at:
 * @link https://github.com/DmitryKoterov/debug_errorhook.
 */
class NoDupsListener {
    const DEFAULT_PERIOD = 300;
    // 5 min.
    const ERROR_FILE_EXT = ".error";
    const GC_PROBABILITY = 0.01;
    /**
     * @var callable
     */
    protected $listener;
    protected string $lockFileDirPath;
    protected int $period;
    protected bool $gcExecuted = false;

    public function __construct(callable $listener, string $lockFileDirPath = null, int $periodSec = null) {
        if (null === $lockFileDirPath) {
            $lockFileDirPath = $this->defaultLockFileDirPath();
        }
        $this->lockFileDirPath = $this->initLockFileDir($lockFileDirPath);
        $this->period = null !== $periodSec ? $periodSec : self::DEFAULT_PERIOD;
        $this->listener = $listener;
    }

    protected function defaultLockFileDirPath(): string {
        return sys_get_temp_dir();
    }

    protected function initLockFileDir(string $dirPath): string {
        $dirPath = rtrim($dirPath, '\\/') . "/" . strtolower(str_replace('\\', '-', get_class($this)));
        if (!@is_dir($dirPath)) {
            if (!@mkdir($dirPath, 0777, true)) {
                $error = error_get_last();
                error_clear_last();
                throw new Exception("Unable to create directory '{$dirPath}': {$error['message']}");
            }
        }
        return $dirPath;
    }

    /**
     * @param Throwable $exception
     */
    public function __invoke(mixed $ex): mixed {
        $id = $this->lockId($ex);
        if ($this->isLockExpired($id, $ex)) {
            ($this->listener)($ex);
        }
        // Touch always, even if we did not send anything. Else same errors will
        // be mailed again and again after $period (e.g. once per 5 minutes).
        $this->touch($id, $ex->getFile(), $ex->getLine());
        return null;
    }

    protected function lockId(Throwable $e): string {
        $file = $e->getFile();
        $line = $e->getLine();
        $id = md5(join(':', [get_class($e), $file, $line]));
        return $id;
    }

    protected function isLockExpired(string $id, Throwable $ex): bool {
        $filePath = $this->lockFilePath($id);
        return !file_exists($filePath) || filemtime($filePath) < time() - $this->period;
    }

    protected function lockFilePath(string $id): string {
        return $this->lockFileDirPath . '/' . $id . self::ERROR_FILE_EXT;
    }

    protected function touch(string $id, string $errFilePath, int $errLine): void {
        $filePath = $this->lockFilePath($id);
        file_put_contents($filePath, "{$errFilePath}:{$errLine}");
        @chmod($filePath, 0666);
        $this->gc();
    }

    protected function gc(): void {
        if ($this->gcExecuted || mt_rand(0, 10000) >= $this->gcProbability() * 10000) {
            return;
        }
        foreach (glob("{$this->lockFileDirPath}/*" . self::ERROR_FILE_EXT) as $filePath) {
            if (filemtime($filePath) <= time() - $this->period * 2) {
                @unlink($filePath);
            }
        }
        $this->gcExecuted = true;
    }

    protected function gcProbability(): float {
        return self::GC_PROBABILITY;
    }
}