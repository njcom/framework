<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use ArrayObject;
use Morpho\App\Request as BaseRequest;
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
class Request extends BaseRequest implements IRequest {
    protected array $knownMethods;
    protected ?ArrayObject $headers = null;
    protected ?HttpMethod $originalMethod;
    protected ?HttpMethod $overwrittenMethod;
    protected ?bool $isAjax = null;
    private ?array $serverVars;
    private ?Uri $uri = null;
    private ?array $trustedProxyIps = null;
    private ?IResponse $response = null;

    public function __construct(array $vals = null, ?array $serverVars = null) {
        parent::__construct((array)$vals);
        $this->serverVars = $serverVars;
        $this->knownMethods = array_column(HttpMethod::cases(), 'value');
        $method = $this->detectOriginalMethod();
        $this->originalMethod = null !== $method ? $method : HttpMethod::Get;
        $this->overwrittenMethod = null;//$this->detectOverwrittenMethod();
    }

    public function setServerVars(array $vars): void {
        if (null !== $this->serverVars) {
            $this->serverVars = $vars;
        } else {
            $_SERVER = $vars;
        }
    }

    public function setServerVar(string $name, mixed $val): void {
        if (null !== $this->serverVars) {
            $this->serverVars[$name] = $val;
        } else {
            $_SERVER[$name] = $val;
        }
    }

    public function serverVar(string $name, $default = null): mixed {
        if (null !== $this->serverVars) {
            return $this->serverVars[$name] ?? $default;
        }
        return $_SERVER[$name] ?? $default;
    }

    public function setResponse(IResponse $response): void {
        $this->response = $response;
    }

    public function response(): IResponse {
        if (null === $this->response) {
            $this->response = $this->mkResponse();
        }
        return $this->response;
    }

/*    public function data(array $source, $name = null, callable|bool $filter = true): mixed {
        // NB: On change sync code with query() and post()
        if (null === $name) {
            return $filter ? etrim($source) : $source;
        }
        if (is_array($name)) {
            $data = array_intersect_key($source, array_flip(array_values($name)));
            $data += array_fill_keys($name, null);
            return $filter ? etrim($data) : $data;
        }
        if ($filter) {
            // todo: support custom $filter
            return isset($source[$name])
                ? etrim($source[$name])
                : null;
        }
        return $source[$name] ?? null;
    }*/

    public function isAjax(bool $flag = null): bool {
        if (null !== $flag) {
            $this->isAjax = $flag;
        }
        if (null !== $this->isAjax) {
            return $this->isAjax;
        }
        $headers = $this->headers();
        return $headers->offsetExists('X-Requested-With') && $headers->offsetGet(
                'X-Requested-With'
            ) === 'XMLHttpRequest';
    }

    /**
     * Note: Returned headers can contain user input and therefore can be not safe in some scenarios.
     */
    public function headers(): ArrayObject {
        if (null === $this->headers) {
            $this->headers = $this->mkHeaders();
        }
        return $this->headers;
    }

    public function setUri(Uri $uri): void {
        $this->uri = $uri;
    }

    public function uri(): Uri {
        if (null === $this->uri) {
            $this->uri = $this->mkUri();
        }
        return $this->uri;
    }

    public function prependWithBasePath(string $path): Uri {
        $uri = new Uri($path);
        if ($uri->authority()->isNull() && $uri->scheme() === '') {
            $uriPath = $uri->path();
            if (!$uriPath->isRel()) {
                $basePath = $this->uri()->path()->basePath();
                $uriStr = Path::combine($basePath, $uri->toStr(null, false));
                $uri = new Uri($uriStr);
                $uri->path()->setBasePath($basePath);
                return $uri;
            }
        }
        return $uri;
    }

    public function setTrustedProxyIps(array $ips): void {
        $this->trustedProxyIps = $ips;
    }

    public function trustedProxyIps(): ?array {
        return $this->trustedProxyIps;
    }

    public function isKnownMethod(string $method): bool {
        return in_array($method, $this->knownMethods, true);
    }

    /**
     * NB: $method must not be taken from user input.
     */
    public function setMethod(HttpMethod $method): void {
        $this->originalMethod = $method;
        $this->overwrittenMethod = null;
    }

    public function method(): HttpMethod {
        return null !== $this->overwrittenMethod
            ? $this->overwrittenMethod
            : $this->originalMethod;
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

        $queryStr = $this->serverVar('QUERY_STRING');
        if ($queryStr !== '') {
            $uri->setQuery($queryStr);
        }

        return $uri;
    }

    protected function mkHeaders(): ArrayObject {
        $headers = [];
        foreach (null !== $this->serverVars ? $this->serverVars : $_SERVER as $key => $value) {
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
        $https = $this->serverVar('HTTPS');
        if ($https) {
            return 'off' !== strtolower($https);
        }
        if ($this->isFromTrustedProxy()) {
            return in_array(strtolower($this->serverVar('HTTP_X_FORWARDED_PROTO', '')), ['https', 'on', 'ssl', '1'], true);
        }
        return false;
    }

    protected function isFromTrustedProxy(): bool {
        return null !== $this->trustedProxyIps && in_array(
                $this->serverVar('REMOTE_ADDR'),
                $this->trustedProxyIps,
                true
            );
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
        if ($this->headers()->offsetExists('Host')) {
            $host = $this->headers()->offsetGet('Host');

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

        $serverName = $this->serverVar('SERVER_NAME');
        if (!$host && $serverName) {
            $host = $serverName;
            $port = intval($this->serverVar('SERVER_PORT', -1));
            if ($port < 1) {
                $port = null;
            } else {
                // Check for misinterpreted IPv6-Address
                // Reported at least for Safari on Windows
                $serverAddr = $this->serverVar('SERVER_ADDR');
                if (isset($serverAddr) && preg_match('/^\[[0-9a-fA-F:]+\]$/', $host)) {
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
        $requestUri = $this->serverVar('REQUEST_URI');

        $normalizeUri = function ($requestUri) {
            if (($qpos = strpos($requestUri, '?')) !== false) {
                return substr($requestUri, 0, $qpos);
            }
            return $requestUri;
        };

        // Check this first so IIS will catch.
        $httpXRewriteUrl = $this->serverVar('HTTP_X_REWRITE_URL');
        if ($httpXRewriteUrl !== null) {
            $requestUri = $httpXRewriteUrl;
        }

        // Check for IIS 7.0 or later with ISAPI_Rewrite
        $httpXOriginalUrl = $this->serverVar('HTTP_X_ORIGINAL_URL');
        if ($httpXOriginalUrl !== null) {
            $requestUri = $httpXOriginalUrl;
        }

        // IIS7 with URL Rewrite: make sure we get the unencoded url
        // (double slash problem).
        $iisUrlRewritten = $this->serverVar('IIS_WasUrlRewritten');
        $unencodedUrl = $this->serverVar('UNENCODED_URL', '');
        if ('1' == $iisUrlRewritten && '' !== $unencodedUrl) {
            return $normalizeUri($unencodedUrl);
        }

        if ($requestUri !== null) {
            return $normalizeUri(preg_replace('#^[^/:]+://[^/]+#', '', $requestUri));
        }

        // IIS 5.0, PHP as CGI.
        $origPathInfo = $this->serverVar('ORIG_PATH_INFO');
        if ($origPathInfo !== null) {
            $queryString = $this->serverVar('QUERY_STRING', '');
            if ($queryString !== '') {
                $origPathInfo .= '?' . $queryString;
            }
            return $normalizeUri($origPathInfo);
        }

        return '/';
    }

    protected function detectBasePath(string $requestUri): string {
        $scriptName = $this->serverVar('SCRIPT_NAME', '');
        if ('' === $scriptName) {
            return '/';
        }
        $basePath = ltrim(Path::normalize(dirname($scriptName)), '/');
        /*        if (!Uri::validatePath($basePath)) {
                    throw new BadRequestException();
                }*/
        return '/' . $basePath;
    }

    protected function mkResponse(): IResponse {
        return new Response();
    }

    protected function detectOverwrittenMethod(): ?HttpMethod {
        $overwrittenMethod = null;
        $httpMethod = $this->serverVar('HTTP_X_HTTP_METHOD_OVERRIDE');
        if (null !== $httpMethod) {
            $overwrittenMethod = (string)$httpMethod;
        } elseif (isset($_GET['_method'])) {
            // Allow to pass a method through the special '_method' item.
            $overwrittenMethod = (string)$_GET['_method'];
            unset($_GET['_method']);
        } elseif (isset($_POST['_method'])) {
            $overwrittenMethod = (string)$_POST['_method'];
            unset($_POST['_method']);
        }
        if (null !== $overwrittenMethod) {
            $overwrittenMethod = strtoupper($overwrittenMethod);
            if ($this->isKnownMethod($overwrittenMethod)) {
                return HttpMethod::from($overwrittenMethod);
            }
        }
        return null;
    }

    protected function detectOriginalMethod(): ?HttpMethod {
        $httpMethod = $this->serverVar('REQUEST_METHOD');
        if (null !== $httpMethod) {
            $httpMethod = strtoupper((string)$httpMethod);
            if ($this->isKnownMethod($httpMethod)) {
                return HttpMethod::from($httpMethod);
            }
        }
        return null;
    }
}
