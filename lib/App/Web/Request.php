<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use ArrayObject;
use Morpho\Uri\Authority;
use Morpho\Uri\Path;
use Morpho\Uri\Uri;

use function dirname;
use function in_array;
use function intval;
use function ltrim;
use function preg_match;
use function preg_replace;
use function strlen;
use function strpos;
use function strrpos;
use function strtolower;
use function strtoupper;
use function strtr;
use function substr;
use function ucfirst;
use function ucwords;

/**
 * Some methods in this class based on \Zend\Http\PhpEnvironment\Request class.
 * @see https://github.com/zendframework/zend-http for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license https://github.com/zendframework/zend-http/blob/master/LICENSE.md New BSD License
 */
class Request extends ArrayObject implements IRequest {
    public bool $isHandled = false;
    public array $handler = [];
    public Response $response;
    public Uri $uri;
    public HttpMethod $httpMethod;
    public array $trustedProxyIps = [];
    public ArrayObject $headers;
    private ?bool $isAjax = null;

    public function __construct(array $vals = null, array $trustedProxyIps = null) {
        parent::__construct((array)$vals);
        if (null !== $trustedProxyIps) {
            $this->trustedProxyIps = $trustedProxyIps;
        }
        $this->httpMethod = $this->detectHttpMethod();
        $this->response = new Response();
        $this->headers = $this->mkHeaders();
        $this->uri = $this->mkUri();
    }

    public function redirect(string $uri = null, int $statusCode = null): IResponse {
        if (null === $uri) {
            $uri = $this->uri;
            $query = $uri->query();
            if (isset($query['redirect'])) {
                $redirectUri = rawurldecode($query['redirect']);
                $uri = new Uri($redirectUri);
                $query = $uri->query();
                if (isset($query['redirect'])) {
                    unset($query['redirect']);
                }
            }
        }
        return $this->response->mkRedirect($uri, $statusCode);
    }

    public function isAjax(bool $flag = null): bool {
        if (null !== $flag) {
            $this->isAjax = $flag;
        }
        if (null !== $this->isAjax) {
            return $this->isAjax;
        }
        $headers = $this->headers;
        return $headers->offsetExists('X-Requested-With') && $headers->offsetGet('X-Requested-With') === 'XMLHttpRequest';
    }

    public function prependWithBasePath(string $path): Uri {
        $uri = new Uri($path);
        if ($uri->authority()->isNull() && $uri->scheme() === '') {
            $uriPath = $uri->path();
            if (!$uriPath->isRel()) {
                $basePath = $this->uri->path()->basePath();
                $uriStr = Path::combine($basePath, $uri->toStr(null, false));
                $uri = new Uri($uriStr);
                $uri->path()->setBasePath($basePath);
                return $uri;
            }
        }
        return $uri;
    }

    protected function mkUri(): Uri {
        $uri = new Uri();

        $uri->setScheme($this->isSecure() ? 'https' : 'http');

        $authority = new Authority();
        [$host, $port] = $this->detectHostAndPort();
        if ($host) {
            $authority->setHost($host);
            if ($port) {
                $authority->setPort($port);
            }
        }
        $uri->setAuthority($authority);

        $detectedPath = Path::normalize($this->detectPath());
        $basePath = $this->detectBasePath($detectedPath);
        $path = new Path($detectedPath);
        $path->setBasePath($basePath);
        $uri->setPath($path);

        $queryString = isset($_SERVER['QUERY_STRING']) ? (string)$_SERVER['QUERY_STRING'] : null;
        if ($queryString) {
            $uri->setQuery($queryString);
        }

        return $uri;
    }

    protected function mkHeaders(): ArrayObject {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                if (str_starts_with($key, 'HTTP_COOKIE')) {
                    // Cookies are handled using the $_COOKIE superglobal
                    continue;
                }
                $name = strtr(substr($key, 5), '_', ' ');
                $name = strtr(ucwords(strtolower($name)), ' ', '-');
            } elseif (str_starts_with($key, 'CONTENT_')) {
                $name = substr($key, 8); // Content-
                $name = 'Content-' . (($name == 'MD5') ? $name : ucfirst(strtolower($name)));
            } else {
                continue;
            }
            $headers[$name] = $value;
        }
        return new ArrayObject($headers);
    }

    /**
     * Based on Request::isSecure() from the https://github.com/symfony/symfony/blob/master/src/Symfony/Component/HttpFoundation/Request.php
     * (c) Fabien Potencier <fabien@symfony.com>
     */
    protected function isSecure(): bool {
        $https = isset($_SERVER['HTTPS']) ? (string)$_SERVER['HTTPS'] : null;
        if (null !== $https) {
            return 'off' !== strtolower($https);
        }
        if ($this->isFromTrustedProxy()) {
            $forwardedProto = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? (string)$_SERVER['HTTP_X_FORWARDED_PROTO'] : null;
            return null !== $forwardedProto && in_array(strtolower($forwardedProto), ['https', 'on', 'ssl', '1'], true);
        }
        return false;
    }

    protected function isFromTrustedProxy(): bool {
        $remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : null;
        return null !== $remoteAddr && $this->trustedProxyIps && in_array($remoteAddr, $this->trustedProxyIps, true);
    }

    /*
    public function acceptsJson(): bool
     * {
     * $header = $this->getHeaders()->get('ACCEPT');
     * return false !== $header && false !== stripos($header->getFieldValue(), 'application/json');
     * }
     */

    protected function detectHostAndPort(): array {
        // URI host & port
        $host = null;
        $port = null;

        // Set the host
        if ($this->headers->offsetExists('Host')) {
            $host = $this->headers->offsetGet('Host');

            // works for regname, IPv4 & IPv6
            if (preg_match('~:(\d+)$~', $host, $matches)) {
                $host = substr($host, 0, -1 * (strlen($matches[1]) + 1));
                $port = (int)$matches[1];
            }
            /*            // set up a validator that check if the hostname is legal (not spoofed)
                        $hostnameValidator = new HostnameValidator([
                            'allow'       => HostnameValidator::ALLOW_ALL,
                            'useIdnCheck' => false,
                            'useTldCheck' => false,
                        ]);
                        // If invalid. Reset the host & port
                        if (!$hostnameValidator->isValid($host)) {
                            $host = null;
                            $port = null;
                        }*/
        }

        $serverName = isset($_SERVER['SERVER_NAME']) ? (string)$_SERVER['SERVER_NAME'] : null;
        if ($serverName && !$host) {
            $host = $serverName;
            $port = isset($_SERVER['SERVER_PORT']) ? (string)$_SERVER['SERVER_PORT'] : null;
            if (null !== $port) {
                $port = intval($port);
            }
            if ($port < 1) {
                $port = null;
            } else {
                // Check for misinterpreted IPv6-Address
                // Reported at least for Safari on Windows
                $serverAddr = isset($_SERVER['SERVER_ADDR']) ? (string)$_SERVER['SERVER_ADDR'] : null;
                if ($serverAddr && preg_match('/^\[[0-9a-fA-F:]+\]$/', $host)) {
                    $host = '[' . $serverAddr . ']';
                    if ($port . ']' == substr($host, strrpos($host, ':') + 1)) {
                        // The last digit of the IPv6-Address has been taken as port
                        // Unset the port so the default port can be used
                        $port = null;
                    }
                }
            }
        }
        return [$host, $port];
    }

    protected function detectPath(): string {
        $requestUri = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : null;
        if ($requestUri !== null) {
            $requestUri = preg_replace('#^[^/:]+://[^/]+#si', '', $requestUri);
            if (($qpos = strpos($requestUri, '?')) !== false) {
                return substr($requestUri, 0, $qpos);
            }
            return $requestUri;
        }
        return '/';
    }

    protected function detectBasePath(string $requestUri): string {
        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : null;
        if (null === $scriptName) {
            return '/';
        }
        $basePath = ltrim(Path::normalize(dirname($scriptName)), '/');
        return '/' . $basePath;
    }

    protected function detectHttpMethod(): HttpMethod {
        $httpMethod = isset($_SERVER['REQUEST_METHOD']) ? (string)$_SERVER['REQUEST_METHOD'] : null;
        if (null !== $httpMethod) {
            $httpMethod = strtoupper($httpMethod);
            if (HttpMethod::isValid($httpMethod)) {
                return HttpMethod::from($httpMethod);
            }
        }
        return HttpMethod::Get;
    }
}
