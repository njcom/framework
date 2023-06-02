<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Http;

use function basename;
use function escapeshellarg;
use function getcwd;
use function is_dir;
use function Morpho\App\Cli\sh;

class HttpClient {
    /**
     * @return string Path to the downloaded file.
     */
    public static function download(string $uri, string $destPath = null): string {
        if (null === $destPath) {
            $destPath = getcwd() . '/' . basename($uri);
        } elseif (is_dir($destPath)) {
            $destPath .= '/' . basename($uri);
        }
        // @TODO: Implement without call of the external tool.
        // @TODO: use curl, wget or fetch, see the `man parallel`
        sh('curl --progress-bar -L -o ' . escapeshellarg($destPath) . ' ' . escapeshellarg($uri), ['show' => true]);
        return $destPath;
    }

    /**
     * @todo: add HTTP protocol parameter
     */
    public static function serverAcceptsConnections(string $host, int $port): bool {
        $sock = @fsockopen($host, $port, $errNo, $errMsg, 5);
        if (false === $sock) {
            return false;
        }
        fwrite($sock, "GET / HTTP/1.1\r\n\r\n");
        return (bool) preg_match('~^HTTP/\\d+.\\d+ \\d{3}~si', fread($sock, 25));
    }
}