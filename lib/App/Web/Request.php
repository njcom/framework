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

use function array_fill_keys;
use function array_flip;
use function array_intersect_key;
use function array_values;
use function dirname;
use function in_array;
use function intval;
use function is_array;
use function is_string;
use function ltrim;
use function Morpho\Base\etrim;
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
        $this->knownMethods = array_column(HttpMethod::cases(), 'value');
        parent::__construct((array) $vals);
        $this->serverVars = $serverVars;
        $method = $this->detectOriginalMethod();
        $this->originalMethod = null !== $method ? $method : HttpMethod::Get;
        $this->overwrittenMethod = $this->detectOverwrittenMethod();
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

    public function isGetMethod(): bool {
        return $this->method() === HttpMethod::Get;
    }

    public function isPostMethod(): bool {
        return $this->method() === HttpMethod::Post;
    }

    public function isDeleteMethod(): bool {
        return $this->method() === HttpMethod::Delete;
    }

    public function isPatchMethod(): bool {
        return $this->method() === HttpMethod::Patch;
    }

    public function isPutMethod(): bool {
        return $this->method() === HttpMethod::Put;
    }

    public function isHeadMethod(): bool {
        return $this->method() === HttpMethod::Head;
    }

    /*
    public function isConnectMethod(): bool {
        return $this->method() === self::CONNECT_METHOD;
    }

    public function isOptionsMethod(): bool {
        return $this->method() === self::OPTIONS_METHOD;
    }

    public function isTraceMethod(): bool {
        return $this->method() === self::TRACE_METHOD;
    }
    */

    public function knownMethods(): array {
        return $this->knownMethods;
    }

    public function isKnownMethod($method): bool {
        return is_string($method) && in_array($method, $this->knownMethods, true);
    }

    /**
     * Calls one of:
     *     - get()
     *     - patch()
     *     - post()
     * @TODO:
     *     - options()
     *     - delete()
     *     - head()
     *     - put()
     *     - trace()
     *     - connect()
     *     - propfind()
     */
    public function args(string|array|null $names = null, callable|bool $filter = true): mixed {
        $method = $this->method();
        return match ($method) {
            HttpMethod::Get => $this->query($names, $filter),
            HttpMethod::Post => $this->post($names, $filter),
            HttpMethod::Patch => $this->patch($names, $filter),
            default => throw new BadRequestException(),
        };
    }

    public function query($name = null, callable|bool $filter = true): mixed {
        // NB: On change sync with data() and post()
        if (null === $name) {
            return $filter ? etrim($_GET) : $_GET;
        }
        if (is_array($name)) {
            $data = array_intersect_key($_GET, array_flip(array_values($name)));
            $data += array_fill_keys($name, null);
            return $filter ? etrim($data) : $data;
        }
        if ($filter) {
            // todo: support custom $filter
            return isset($_GET[$name])
                ? etrim($_GET[$name])
                : null;
        }
        return $_GET[$name] ?? null;
    }

    public function hasQuery(string $name): bool {
        return isset($_GET[$name]);
    }

    public function post($name = null, callable|bool $filter = true): mixed {
        // NB: On change sync with data() and query()
        if (null === $name) {
            return $filter ? etrim($_POST) : $_POST;
        }
        if (is_array($name)) {
            $data = array_intersect_key($_POST, array_flip(array_values($name)));
            $data += array_fill_keys($name, null);
            return $filter ? etrim($data) : $data;
        }
        if ($filter) {
            // todo: support custom $filter
            return isset($_POST[$name])
                ? etrim($_POST[$name])
                : null;
        }
        return $_POST[$name] ?? null;
    }

    public function hasPost(string $name): bool {
        return isset($_POST[$name]);
    }

    public function patch($name = null, callable|bool $filter = true): mixed {
        if ($this->overwrittenMethod === HttpMethod::Patch) {
            return $this->post($name, $filter);
        }
        // @TODO: read from php://input using resource up to 'post_max_size' and 'max_input_vars' php.ini values, check PHP sources for possible handling of the php://input and applying these settings already on PHP core level.
        throw new BadRequestException('Method not allowed');
    }

    public function data(array $source, $name = null, callable|bool $filter = true): mixed {
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
    }

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
     * Note: Returned headers can contain user input and therefore can be not safe in some scenarious.
     */
    public function headers(): ArrayObject {
        if (null === $this->headers) {
            $this->initHeaders();
        }
        return $this->headers;
    }

    public function setUri(Uri $uri): void {
        $this->uri = $uri;
    }

    public function uri(): Uri {
        if (null === $this->uri) {
            $this->initUri();
        }
        return $this->uri;
    }

    public function prependWithBasePath(string $uriStr): Uri {
        $uri = new Uri($uriStr);
        if ($uri->authority()->isNull() && $uri->scheme() === '') {
            $path = $uri->path();
            if (!$path->isRel()) {
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

    protected function initUri(): void {
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

        $this->uri = $uri;
    }

    protected function initHeaders(): void {
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
        $this->headers = new ArrayObject($headers);
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
            return in_array(
                strtolower($this->serverVar('HTTP_X_FORWARDED_PROTO', '')),
                ['https', 'on', 'ssl', '1'],
                true
            );
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
                $port = (int) $matches[1];
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
                // Check for missinterpreted IPv6-Address
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
            $overwrittenMethod = (string) $httpMethod;
        } elseif (isset($_GET['_method'])) {
            // Allow to pass a method through the special '_method' item.
            $overwrittenMethod = (string) $_GET['_method'];
            unset($_GET['_method']);
        } elseif (isset($_POST['_method'])) {
            $overwrittenMethod = (string) $_POST['_method'];
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
            $httpMethod = strtoupper((string) $httpMethod);
            if ($this->isKnownMethod($httpMethod)) {
                return HttpMethod::from($httpMethod);
            }
        }
        return null;
    }
}
