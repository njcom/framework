<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web\View;

use ArrayObject;
use Closure;
use Morpho\App\Web\IRequest;
use Morpho\Uri\Uri;
use Morpho\Base\ArrPipe;
use Morpho\Base\Conf;
use Morpho\Base\NotImplementedException;
use Morpho\Fs\File;
use Morpho\Fs\Path;
use RuntimeException;
use Throwable;
use Traversable;
use UnexpectedValueException;

use function extract;
use function htmlspecialchars;
use function htmlspecialchars_decode;
use function implode;
use function is_array;
use function is_numeric;
use function is_scalar;
use function Morpho\Base\dasherize;
use function Morpho\Base\deleteDups;
use function Morpho\Base\toJson;
use function ob_end_clean;
use function ob_get_clean;
use function ob_start;
use function preg_replace;
use function trim;
use function ucfirst;

class PhpTemplateEngine extends ArrPipe {
    //public const HIDDEN_FIELD = 'hidden';

    public IRequest $request;

    public const string VIEW_FILE_EXT = '.phtml';

    public array $vars = [];

    private static array $htmlIds = [];

    protected bool $forceCompile;
    protected array $baseSourceDirPaths = [];
    protected string $targetDirPath;

    private array $plugins = [];

    /**
     * @var callable
     */
    private $pluginFactory;

    private ?Uri $uri = null;

    public function __construct(array $conf = null) {
        $this->init();
        $conf = (array)$conf;
        $this->forceCompile = $conf['forceCompile'] ?? false;
        $this->pluginFactory = $conf['pluginFactory'] ?? function () {
        };
        $this->request = $conf['request'];
        parent::__construct($conf['steps']);
    }

    public static function e($text): string {
        if ($text instanceof Closure) {
            // NB: only Closure is supported for performance
            return $text();
        }
        return htmlspecialchars((string)$text, ENT_QUOTES);
    }

    /**
     * Opposite to e().
     */
    public static function de($text): string {
        return htmlspecialchars_decode((string)$text, ENT_QUOTES);
    }

    public function __set(string $varName, $value): void {
        $this->vars[$varName] = $value;
    }

    public function __get(string $varName) {
        if (!isset($this->vars[$varName])) {
            throw new RuntimeException("The template variable '$varName' was not set.");
        }
        return $this->vars[$varName];
    }

    public function __isset(string $varName): bool {
        return isset($this->vars[$varName]);
    }

    public function __unset(string $name): void {
        unset($this->vars[$name]);
    }

    /*
        public function isUserLoggedIn(): bool {
            return $this->serviceManager['userManager']->isUserLoggedIn();
        }

        public function loggedInUser() {
            return $this->serviceManager['userManager']->loggedInUser();
        }
    */

    public function handler(): mixed {
        return $this->request->handler['fn'];
    }

    public function forceCompile(bool $flag = null): bool {
        if (null !== $flag) {
            return $this->forceCompile = $flag;
        }
        return $this->forceCompile;
    }

    public function setBaseTargetDirPath(string $dirPath): void {
        $this->targetDirPath = $dirPath;
    }

    public function addBaseSourceDirPath(string $dirPath): static {
        $baseDirPaths = $this->baseSourceDirPaths;
        $key = array_search($dirPath, $baseDirPaths);
        if (false !== $key) {
            unset($baseDirPaths[$key]);
        }
        $baseDirPaths[] = $dirPath;
        $this->baseSourceDirPaths = array_values($baseDirPaths);
        return $this;
    }

    public function clearBaseSourceDirPaths(): void {
        $this->baseSourceDirPaths = [];
    }

    public function baseSourceDirPaths(): array {
        return $this->baseSourceDirPaths;
    }

    /**
     * Translates PHTML code into PHP code and evaluates the PHP code by exporting variables from the $context.
     */
    public function __invoke(mixed $context): string {
        $sourceAbsFilePath = $this->sourceAbsFilePath($context['_view']);
        $targetAbsFilePath = $this->targetDirPath . '/' . $context['_view'] . '.php';
        $this->compileFile($sourceAbsFilePath, $targetAbsFilePath, []);
        return $this->evalPhpFile($targetAbsFilePath, $context);
    }

    /**
     * Evaluates PHPTemplateEngine code.
     * @param string $sourceCode
     * @param array  $__vars
     * @return string
     */
    public function eval(string $sourceCode, array $__vars): string {
        // 1. Compile to PHP
        $__code = $this->compile($sourceCode);
        unset($sourceCode);
        extract($__vars, EXTR_SKIP);
        unset($__vars);
        ob_start();
        try {
            eval('?>' . $__code);
        } catch (Throwable $e) {
            // Don't output any result in case of Error
            ob_end_clean();
            throw $e;
        }
        return trim(ob_get_clean());
    }

    /**
     * Evaluates PHP from the passed PHP file making elements of the $__vars be accessible as PHP variables for code in it.
     */
    public function evalPhpFile(string $__phpFilePath, array $__vars = []): string {
        // NB: We can't use the Base\tpl() function here as we need to preserve $this
        extract($__vars, EXTR_SKIP);
        unset($__vars);
        ob_start();
        try {
            require $__phpFilePath;
        } catch (Throwable $e) {
            // Don't output any result in case of Error
            ob_end_clean();
            throw $e;
        }
        return trim(ob_get_clean());
    }

    /**
     * Evaluates a file containing PHPTemplateEngine code by compiling it to PHP and evaluating the produced PHP. The result of evaluation is returned as string.
     * @param string     $sourceAbsFilePath
     * @param array|null $context
     * @return string
     * @throws Throwable
     */
    public function evalFile(string $sourceAbsFilePath, array $context = null): string {
        $candidateDirPaths = [];
        for ($i = count($this->baseSourceDirPaths) - 1; $i >= 0; $i--) {
            $baseSourceDirPath = $this->baseSourceDirPaths[$i];
            if (str_starts_with($sourceAbsFilePath, $baseSourceDirPath)) {
                $candidateDirPaths[] = $baseSourceDirPath;
            }
        }
        if (!$candidateDirPaths) {
            throw new UnexpectedValueException("Unable to find a base directory for the file " . $sourceAbsFilePath);
        }
        $max = [0, strlen($candidateDirPaths[0])];
        for ($i = 1, $n = count($candidateDirPaths); $i < $n; $i++) {
            $candidateDirPath = $candidateDirPaths[$i];
            $length = strlen($candidateDirPath);
            if ($length > $max[1]) {
                $max = [$i, $length];
            }
        }
        $baseSourceDirPath = $candidateDirPaths[$max[0]];
        $targetRelFilePath = Path::changeExt(Path::rel($baseSourceDirPath, $sourceAbsFilePath), 'php');
        $targetAbsFilePath = $this->targetDirPath . '/' . $targetRelFilePath;
        $this->compileFile($sourceAbsFilePath, $targetAbsFilePath, []);
        return $this->evalPhpFile($targetAbsFilePath, (array)$context);
    }

    public function pageHtmlId(): string {
        $handler = $this->request->handler;
        return $this->htmlId(str_replace('/', '-', $handler['controllerPath'])) . '-' . dasherize($handler['method']);
    }

    public function htmlId(string $htmlId): string {
        $htmlId = dasherize(deleteDups(preg_replace('/[^\w-]/s', '-', (string)$htmlId), '-'));
        if (isset(self::$htmlIds[$htmlId])) {
            $htmlId .= '-' . self::$htmlIds[$htmlId]++;
        } else {
            self::$htmlIds[$htmlId] = 1;
        }
        return $this->e($htmlId);
    }

    public function tag1(string $tagName, array $attribs = null, array $conf = []): string {
        $conf['single'] = true;
        return $this->tag($tagName, null, $attribs, $conf);
    }

    /**
     * @param string      $tagName
     * @param string|null $text
     * @param array|null  $attribs
     * @param array|null  $conf
     * @return string
     */
    public function tag(string $tagName, string $text = null, array $attribs = null, array $conf = null): string {
        $conf = Conf::check(
            [
                'escape' => true,
                'single' => false,
                'xml'    => false,
                'eol'    => false,
            ],
            (array)$conf
        );
        $output = $this->openTag($tagName, $attribs, $conf['xml']);
        if (!$conf['single']) {
            $output .= $conf['escape'] ? $this->e($text) : $text;
            $output .= $this->closeTag($tagName);
        }
        if ($conf['eol']) {
            $output .= "\n";
        }
        return $output;
    }

    public function openTag(string $tagName, array $attribs = null, bool $isXml = false): string {
        return '<'
            . $this->e($tagName)
            . $this->attribs($attribs)
            . ($isXml ? ' />' : '>');
    }

    public function closeTag(string $name): string {
        return '</' . $this->e($name) . '>';
    }

    public function attribs(?array $attribs): string {
        $newAttribs = [];
        foreach ((array)$attribs as $key => $val) {
            if (!is_numeric($key)) {
                $newAttribs[] = $this->e($key) . '="' . $this->e($val) . '"';
            } else {
                // Attribute without a value like `checked`
                $newAttribs[] = $this->e($val);
            }
        }
        return $newAttribs ? ' ' . implode(' ', $newAttribs) : '';
    }

    public function textControl(array $attribs): string {
        $attribs['type'] = 'text';
        return $this->tag1('input', $this->addCommonAttribsOfControl($attribs));
    }

    public function textareaControl(array $attribs): string {
        $val = $attribs['value'];
        unset($attribs['value']);
        return $this->tag('textarea', $val, $this->addCommonAttribsOfControl($attribs));
    }

    public function checkboxControl(array $attribs): string {
        $attribs['type'] = 'checkbox';
        if (!isset($attribs['value'])) {
            $attribs['value'] = 1;
        }
        return $this->tag1('input', $this->addCommonAttribsOfControl($attribs));
    }

    public function selectControl(?iterable $options, mixed $selectedOption = null, array $attribs = null): string {
        $html = $this->openTag('select', $this->addCommonAttribsOfControl((array)$attribs));
        if (null !== $options) {
            $html .= $this->optionControls($options, $selectedOption);
        }
        $html .= '</select>';
        return $html;
    }

    public function optionControls(iterable $options, mixed $selectedOption = null): string {
        $html = '';
        if (null === $selectedOption || is_scalar($selectedOption)) {
            $selectedVal = (string)$selectedOption;
            foreach ($options as $val => $text) {
                $val = (string)$val;
                $html .= '<option value="' . $this->e(
                        $val
                    ) . '"' . ($val === $selectedVal ? ' selected' : '') . '>' . $this->e($text) . '</option>';
            }
            return $html;
        }
        if (!is_array($selectedOption) && !$selectedOption instanceof Traversable) {
            throw new UnexpectedValueException();
        }
        $newOptions = [];
        foreach ($options as $value => $text) {
            $newOptions[(string)$value] = $text;
        }
        $selectedOptions = [];
        foreach ($selectedOption as $val) {
            $val = (string)$val;
            $selectedOptions[$val] = true;
        }
        foreach ($newOptions as $value => $text) {
            $html .= '<option value="' . $this->e(
                    $value
                ) . '"' . (isset($selectedOptions[$value]) ? ' selected' : '') . '>' . $this->e($text) . '</option>';
        }
        return $html;
    }

    public function httpMethod(string $method = null, array $attribs = null): string {
        return $this->hiddenControl(['name' => '_method', 'value' => $method] + (array)$attribs);
    }

    public function hiddenControl(array $attribs): string {
        $attribs['type'] = 'hidden';
        return $this->tag1('input', $this->addCommonAttribsOfControl($attribs));
    }

    public function buttonControl(string $text, array $attribs = null): string {
        return $this->tag('button', $text, $attribs);
    }

    public function submitControl(string $text, array $attribs = null): string {
        $attribs = (array)$attribs;
        $attribs['type'] = 'submit';
        return $this->tag('button', $text, $attribs);
    }

    /**
     * For the $uri === 'http://foo/bar' adds the query argument redirect=$currentPageUri
     * i.e. returns Uri which will redirect to the current page.
     * E.g.: if the current URI === 'http://baz/' then the call
     *     $templateEngine->uriWithRedirectToSelf('http://foo/bar')
     * will return 'http://foo/bar?redirect=http://baz
     */
    public function uriWithRedirectToSelf(string|Uri $uri): string {
        $newUri = $this->request->prependWithBasePath(is_string($uri) ? $uri : $uri->toStr(null, false));
        $newUri->query()['redirect'] = $this->uri()->toStr(null, false);
        return $newUri->toStr(null, true);
    }

    public function uri(): Uri {
        if (null === $this->uri) {
            $this->uri = $this->request->uri();
        }
        return $this->uri;
    }

    /**
     * Renders link - HTML `a` tag.
     */
    public function l(string|Uri $uri, string $text = null, array $attribs = null, array $conf = null): string {
        $uriStr = is_string($uri) ? $uri : $uri->toStr(null, false);
        $attribs['href'] = $this->request->prependWithBasePath($uriStr)->toStr(null, false);
        if (null === $text) {
            $text = $attribs['href'];
        }
        return $this->tag('a', $text, $attribs, $conf);
    }

    public function ul(iterable $items, array $attribs = null): string {
        return '<ul' . $this->attribs($attribs) . '>' . $this->list($items) . '</ul>';
    }

    public function ol(iterable $items, array $attribs = null): string {
        return '<ol' . $this->attribs($attribs) . '>' . $this->list($items) . '</ol>';
    }

    public function list(iterable $items): string {
        $html = '';
        foreach ($items as $item) {
            $html .= '<li>' . $this->e($item) . '</li>';
        }
        return $html;
    }

    public function copyright(string $brand, string|int $startYear = null): string {
        $currentYear = date('Y');
        if ($startYear === $currentYear) {
            $range = $currentYear;
        } else {
            $range = intval($startYear) . '-' . $currentYear;
        }
        return '© ' . $range . ', ' . $this->e($brand);
    }

    public function jsConf(): ArrayObject {
        if (!isset($this->request['jsConf'])) {
            $this->request['jsConf'] = new ArrayObject();
        }
        return $this->request['jsConf'];
    }

    public function toJson(mixed $val): string {
        return toJson($val);
    }

    public function __call(string $pluginName, array $args) {
        $plugin = $this->plugin($pluginName);
        return $plugin(...$args);
    }

    public function plugin(string $name): mixed {
        $name = ucfirst($name);
        if (!isset($this->plugins[$name])) {
            $this->plugins[$name] = ($this->pluginFactory)($name);
        }
        return $this->plugins[$name];
    }

    /**
     * @param array|null $scripts If null then actions scripts will be added.
     */
    public function addJs(array $scripts = null): void {
        if (null !== $scripts) {
            throw new NotImplementedException('todo: append specified scripts');
        }
        // if null append actions scripts
        $this->step('rcProcessor')
            ->on('beforeRenderScripts', function ($event) {
                $event->exchangeArray(
                    array_merge(
                        $event->getArrayCopy(),
                        $event->sender->actionScripts($this->request['view'])
                    ),
                );
            });
    }

    protected function sourceAbsFilePath(string $sourceAbsOrRelFilePath, bool $throwExIfNotFound = true): bool|string {
        $sourceAbsOrRelFilePath .= self::VIEW_FILE_EXT;
        if (Path::isAbs($sourceAbsOrRelFilePath) && is_readable($sourceAbsOrRelFilePath)) {
            return $sourceAbsOrRelFilePath;
        }
        for ($i = count($this->baseSourceDirPaths()) - 1; $i >= 0; $i--) {
            $baseSourceDirPath = $this->baseSourceDirPaths[$i];
            $sourceAbsFilePath = Path::combine($baseSourceDirPath, $sourceAbsOrRelFilePath);
            if (is_readable($sourceAbsFilePath)) {
                return $sourceAbsFilePath;
            }
        }
        if ($throwExIfNotFound) {
            throw new RuntimeException(
                "Unable to detect an absolute file path for the path '$sourceAbsOrRelFilePath', searched in paths:\n'"
                . implode(PATH_SEPARATOR, $this->baseSourceDirPaths) . "'"
            );
        }
        return false;
    }

    /**
     * @throws \Throwable
     */
    protected function compileFile(string $sourceFilePath, string $targetFilePath, array $context): void {
        $forceCompile = $this->forceCompile;
        if ($forceCompile || !file_exists($targetFilePath)) {
            $context['filePath'] = $sourceFilePath;
            $context['program'] = file_get_contents($sourceFilePath);
            $preprocessed = parent::__invoke($context);
            File::write($targetFilePath, $preprocessed['program']);
        }
    }

    protected function compile(string $sourceCode): string {
        $context = parent::__invoke(['program' => $sourceCode]);
        return $context['program'];
    }

    protected function addCommonAttribsOfControl(array $attribs): array {
        if (!isset($attribs['id']) && isset($attribs['name'])) {
            $attribs['id'] = $this->htmlId($attribs['name']);
        }
        return $attribs;
    }

    private function init(): void {
        self::$htmlIds = [];
    }
}
