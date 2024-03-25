<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use Morpho\Base\Converter;
use Morpho\Base\Env as BaseEnvironment;

use function ini_get;
use function preg_match;
use function strtolower;

class Env extends BaseEnvironment {
    //protected bool $startSession = false;

    public static function clientIp(): array {
        return [
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
            // http://nginx.org/en/docs/http/ngx_http_realip_module.html#real_ip_header
            'realIp' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
        ];
    }

    /**
     * @TODO: Rewrite this method.
     *
     * @return int|string Returns max upload file size in bytes or as string with suffix.
     */
    public static function maxUploadFileSize(bool $asBytes = true): int|string {
        $maxSizeIni = ini_get('post_max_size');
        $maxSize = Converter::toBytes($maxSizeIni);
        $uploadMaxSizeIni = ini_get('upload_max_filesize');
        $uploadMaxSize = Converter::toBytes($uploadMaxSizeIni);
        if ($uploadMaxSize > 0 && $uploadMaxSize < $maxSize) {
            $maxSize = $uploadMaxSize;
            $maxSizeIni = $uploadMaxSizeIni;
        }
        return $asBytes ? $maxSize : $maxSizeIni;
    }

    public static function init(): void {
        parent::init();
        $_SERVER['HTTP_REFERER'] = self::httpReferrer();
        $_SERVER['SERVER_PROTOCOL'] = self::httpVersion()->value;
        $_SERVER['HTTP_HOST'] = self::httpHost();
        $_SERVER += [
            'SCRIPT_NAME'     => null,
            'REMOTE_ADDR'     => null,
            'REQUEST_METHOD'  => 'GET',
            'SERVER_NAME'     => null,
            'SERVER_SOFTWARE' => null,
            'HTTP_USER_AGENT' => null,
        ];
    }

    /**
     * Note that referrer is correct spelling and the referer is incorrect.
     */
    public static function httpReferrer(): string {
        return $_SERVER['HTTP_REFERER'] ?? '';
    }

    // https://www.rfc-editor.org/rfc/rfc9112.html#name-http-version
    public static function httpVersion(): HttpVersion {
        if (isset($_SERVER['SERVER_PROTOCOL'])) {
            $httpVersion = (string) $_SERVER['SERVER_PROTOCOL'];
            if (str_starts_with($httpVersion, 'HTTP/')) {
                $httpVersion = substr($httpVersion, 5);
                if (HttpVersion::isValid($httpVersion)) {
                    return HttpVersion::from($httpVersion);
                }
            }
        }
        return HttpVersion::V1_1;
    }

    public static function httpHost(): string {
        return isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : '';
    }
}
