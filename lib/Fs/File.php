<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Fs;

use Closure;
use Generator;
use InvalidArgumentException;
use Morpho\Base\Conf;
use Morpho\Base\Env;
use Morpho\Base\NotImplementedException;
use Throwable;

use function basename;
use function clearstatcache;
use function copy;
use function fclose;
use function fgetcsv;
use function fgets;
use function file_get_contents;
use function file_put_contents;
use function filesize;
use function fopen;
use function fwrite;
use function implode;
use function is_array;
use function is_file;
use function is_iterable;
use function is_readable;
use function Morpho\Base\{fromJson, toJson};
use function rename;
use function rtrim;
use function strlen;
use function substr;
use function tempnam;
use function unlink;

class File extends Entry {
    public static function writeLn(string $filePath, string $line): void {
        self::write($filePath, $line . "\n");
    }

    public static function appendLn(string $filePath, string $line): void {
        self::append($filePath, $line . "\n");
    }

    /**
     * NB: To read a file and then write to it use the construct: File::writeLines($filePath, toArray(File::readLines($filePath))
     */
    public static function writeLines(string $filePath, iterable $lines): string {
        if (is_array($lines)) {
            return self::write($filePath, implode("\n", $lines));
        }
        $handle = fopen($filePath, 'w');
        if (!$handle) {
            throw new Exception("Unable to open the '$filePath' file for writing");
        }
        try {
            foreach ($lines as $line) {
                fwrite($handle, $line . "\n");
            }
        } finally {
            fclose($handle);
        }
        return $filePath;
    }

    /**
     * Writes string to a file.
     * @return string File path of the written file.
     */
    public static function write(string $filePath, string $content, array $conf = null): string {
        if ($filePath === '') {
            throw new Exception("The file path is empty");
        }
        Dir::create(Path::dirPath($filePath));

        $conf = Conf::check(
            [
                'useIncludePath' => false,
                'lock'           => true,
                'append'         => false,
                'context'        => null,
                'mode'           => Stat::FILE_PERMS,
            ],
            (array) $conf
        );
        $flags = 0;
        if ($conf['append']) {
            $flags |= FILE_APPEND;
        }
        if ($conf['lock']) {
            $flags |= LOCK_EX;
        }
        if ($conf['useIncludePath']) {
            $flags |= FILE_USE_INCLUDE_PATH;
        }
        $perms = $conf['mode'] & 0b111_111_111;
        $oldUmask = null;
        if ((0b100_100_100 & $perms) === 0) { // use umask() only any `1` in `100100100` is not set, otherwise use chmod()
            $oldUmask = umask(0666 ^ $perms); // 0666 is default value for files to subtract permissions to get umask
        }
        try {
            $result = file_put_contents($filePath, $content, $flags, $conf['context'] ?? null);
        } catch (Throwable $e) {
            if (null !== $oldUmask) {
                umask($oldUmask);
            }
            throw $e;
        }
        if (null !== $oldUmask) {
            umask($oldUmask);
        } else {
            chmod($filePath, $perms);
        }
        if (false === $result) {
            throw new Exception("Unable to write to the file '$filePath'");
        }

        return $filePath;
    }

    public static function readLines(
        string $filePath,
        null|Closure|array $filterOrConf = null,
        null|array $conf = null
    ): Generator {
        if (is_array($filterOrConf)) {
            if (is_array($conf)) {
                throw new InvalidArgumentException();
            }
            $conf = $filterOrConf;
            $filterOrConf = null;
        }
        $defaultConf = [
            'skipEmptyLines' => true,
            'rtrim'          => true,
            'ltrim'          => true,
        ];
        if ($filterOrConf) { // If a filter was specified, don't ignore empty lines.
            $defaultConf['skipEmptyLines'] = false;
        }
        $conf = Conf::check($defaultConf, (array) $conf);
        $handle = false;
        if (is_readable($filePath)) {
            $handle = fopen($filePath, 'r');
        }
        if (!$handle) {
            throw new Exception("Unable to open the '$filePath' file for reading");
        }
        try {
            $ltrim = $conf['ltrim'];
            $rtrim = $conf['rtrim'];
            $trim = $ltrim && $rtrim;
            while (false !== ($line = fgets($handle))) {
                if ($trim) {
                    $line = trim($line);
                } elseif ($ltrim) {
                    $line = ltrim($line);
                } elseif ($rtrim) {
                    $line = rtrim($line);
                }
                if ($conf['skipEmptyLines']) {
                    if (strlen($line) === 0) {
                        continue;
                    }
                }
                if (null !== $filterOrConf) {
                    if ($filterOrConf($line)) {
                        yield $line;
                    }
                } else {
                    yield $line;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return mixed Returns decoded json file's content.
     */
    public static function readJson(string $filePath) {
        return fromJson(self::read($filePath));
    }

    /**
     * Reads file as string.
     */
    public static function read(string $filePath, array $conf = null): string {
        if (!is_file($filePath)) {
            throw new FileNotFoundException($filePath);
        }

        $conf = Conf::check(
            [
                'lock'           => false,
                'offset'         => -1,
                'length'         => null,
                'useIncludePath' => false,
                'context'        => null,
                'removeBom'      => true,
            ],
            (array) $conf
        );

        $content = @file_get_contents($filePath, $conf['useIncludePath']);

        if (false === $content) {
            throw new Exception("Unable to read the '$filePath' file");
        }

        // @TODO: Handle other BOM representations, see https://en.wikipedia.org/wiki/Byte_order_mark
        if ($conf['removeBom'] && substr($content, 0, 3) === "\xEF\xBB\xBF") {
            return substr($content, 3);
        }

        return $content;
    }

    /**
     * Writes json to the file and returns the file path.
     */
    public static function writeJson(
        string $filePath,
        mixed $json,
        int $jsonFlags = null,
        array $writeConf = null
    ): string {
        return self::write($filePath, toJson($json, $jsonFlags), $writeConf);
    }

    public static function readCsv(
        string $filePath,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\'
    ): Generator {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception("Unable to read the '$filePath' file");
        }
        try {
            while (false !== ($line = fgetcsv($handle, 0, $delimiter, $enclosure, $escape))) {
                yield $line;
            }
        } finally {
            fclose($handle);
        }
    }

    public static function writeCsv(string $filePath): string {
        throw new NotImplementedException(__METHOD__);
    }

    public static function prepend(
        string $filePath,
        string $content,
        array $readConf = null,
        array $writeConf = null
    ): string {
        $writeConf['append'] = false;
        return self::write(
            $filePath,
            $content . self::read($filePath, $readConf),
            $writeConf
        );
    }

    /**
     * Appends content to the file and returns the file path.
     */
    public static function append(string $filePath, string $content, array $conf = null): string {
        return self::write($filePath, $content, Conf::check(['append' => true], (array) $conf));
    }

    /**
     * @throws \Throwable
     */
    public static function writePhpVar(string $filePath, $var, bool $stripNumericKeys = true): string {
        if ($stripNumericKeys) {
            throw new NotImplementedException();
        }
        return static::write($filePath, '<?php return ' . var_export($var, true) . ';');
    }

    /**
     * Has the same effect as truncate but should be used in different situation/context.
     */
    public static function createEmpty(string $filePath): string {
        Dir::create(Path::dirPath($filePath));
        // NB: touch() does not truncate the file, so we don't use it.
        self::truncate($filePath);
        return $filePath;
    }

    /**
     * Truncates the file to zero length.
     */
    public static function truncate(string $filePath): void {
        $handle = fopen($filePath, 'w');
        if (false === $handle) {
            throw new Exception("Unable to open the file '$filePath' for writing");
        }
        fclose($handle);
    }

    public static function isEmpty(string $filePath): bool {
        clearstatcache(true, $filePath);
        return filesize($filePath) === 0;
    }

    public static function deleteIfExists(string $filePath): void {
        if (is_file($filePath)) {
            self::delete($filePath);
        }
    }

    /**
     * Deletes the file.
     */
    public static function delete($filePath): void {
        if (is_iterable($filePath)) {
            foreach ($filePath as $path) {
                static::delete($path);
            }
            return;
        }
        if (!unlink($filePath)) {
            throw new FileNotFoundException($filePath);
        }
        clearstatcache(true, $filePath);
    }

    /**
     * Copies the source file to target directory of file and returns target.
     */
    public static function copy(
        string $sourceFilePath,
        string $targetFilePath,
        bool $overwrite = false,
        bool $skipIfExists = false
    ): string {
        if (!is_file($sourceFilePath)) {
            throw new Exception("Unable to copy: the source '$sourceFilePath' is not a file");
        }
        $targetDirPath = Path::dirPath($targetFilePath);
        if (!Stat::isDir($targetDirPath)) {
            Dir::create($targetDirPath);
        }
        if (Stat::isDir($targetFilePath)) {
            $targetFilePath = $targetFilePath . '/' . basename($sourceFilePath);
        }
        if (is_file($targetFilePath) && !$overwrite) {
            if ($skipIfExists) {
                return $targetFilePath;
            } else {
                throw new Exception("The target file '$targetFilePath' already exists");
            }
        }
        if (!@copy($sourceFilePath, $targetFilePath)) {
            throw new Exception("Unable to copy the file '$sourceFilePath' to the '$targetFilePath'");
        }

        return $targetFilePath;
    }

    /**
     * @TODO: Add support directory for the $targetFilePath
     * Moves the source file to the target file and returns the target.
     */
    public static function move(string $sourceFilePath, string $targetFilePath): string {
        Dir::create(Path::dirPath($targetFilePath));
        if (!@rename($sourceFilePath, $targetFilePath)) {
            throw new Exception("Unable to move the '$sourceFilePath' to the '$targetFilePath'");
        }
        clearstatcache();

        return $targetFilePath;
    }

    public static function mustBeReadable(string $filePath): void {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new Exception("The file '$filePath' is not readable");
        }
    }

    public static function mustExist(string $filePath): string {
        if (empty($filePath)) {
            throw new Exception("The file path is empty");
        }
        if (!is_file($filePath)) {
            throw new Exception("The file does not exist");
        }
        return $filePath;
    }

    public static function usingTmp(callable $fn, string $tmpDirPath = null): mixed {
        $tmpFilePath = tempnam($tmpDirPath ?: Env::tmpDirPath(), 'using-tmp-');
        try {
            $res = $fn($tmpFilePath);
        } finally {
            if (is_file($tmpFilePath)) {
                self::delete($tmpFilePath);
            }
        }
        return $res;
    }

    /**
     * @param string $filePath
     * @param callable $fn (string $contents): string
     */
    public static function change(string $filePath, callable $fn): void {
        $contents = file_get_contents($filePath);
        $newContents = $fn($contents);
        file_put_contents($filePath, $newContents);
    }
}
