<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web\View;

use Morpho\Base\EventManager;
use Morpho\Base\IFn;
use RuntimeException;

use function array_key_exists;
use function array_keys;
use function array_push;
use function array_unshift;
use function array_values;
use function call_user_func;
use function count;
use function get_class_methods;
use function getmypid;
use function ini_get;
use function ini_set;
use function is_array;
use function join;
use function md5;
use function microtime;
use function preg_match;
use function preg_match_all;
use function preg_replace_callback;
use function preg_split;
use function rtrim;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function substr;

/**
 * This class is changed version of HTML_SemiParser class originally written by Dmitry Koterov: http://forum.dklab.ru/users/DmitryKoterov/, original code was found at: https://github.com/DmitryKoterov/html_formpersister
 */
class HtmlSemiParser extends EventManager implements IFn {
    protected string $tagHandlerPrefix = 'tag';
    protected string $containerHandlerPrefix = 'container';

    /**
     * Characters inside tag RE (between < and >).
     */
    protected string $tagRe = '(?>(?xs) (?> [^>"\']+ | " [^"]* " | \' [^\']* \' )* )';

    protected bool $isHtml5 = true;

    /**
     * Containers, whose bodies are not parsed by the library. E.g. ['script', 'iframe', 'textarea', 'select', 'title']
     */
    protected array $ignoredTags = [];
    protected bool $skipIgnoredTags = true;

    private array $tagHandlers = [];
    private array $containerHandlers = [];
    private array $tagsPreprocessors = [];

    private string $replaceHash; // unique hash to replace all the tags
    private array $spIgnored;
    private bool $selfAdd;

    public function __construct() {
        $this->selfAdd = true;
        $this->attachHandlersFrom($this);
        $this->selfAdd = false;

        // Generate unique hash.
        static $num = 0;
        $this->replaceHash = md5(microtime() . ' ' . ++$num . ' ' . getmypid());
    }

    public function attachHandlersFrom(object $obj, bool $atFront = false): object {
        foreach (get_class_methods($obj) as $method) {
            if (0 === strpos($method, $this->tagHandlerPrefix)) {
                $this->attachTagHandler(
                    substr($method, strlen($this->tagHandlerPrefix)),
                    [$obj, $method],
                    $atFront
                );
            } elseif (0 === strpos($method, $this->containerHandlerPrefix)) {
                $this->attachContainerHandler(
                    substr($method, strlen($this->containerHandlerPrefix)),
                    [$obj, $method],
                    $atFront
                );
                // Check the selfAdd to avoid infinite call of the preprocessTags, as it is already defined here.
            } elseif (!$this->selfAdd && $method === 'preprocessTags') {
                if (!$atFront) {
                    array_push($this->tagsPreprocessors, [$obj, $method]);
                } else {
                    array_unshift($this->tagsPreprocessors, [$obj, $method]);
                }
            }
        }
        return $obj;
    }

    /**
     * Adds new tag handler for future processing.
     *
     * Handler is a callable which is will be for each tag found in the parsed document. This callable could be used to replace tag. Here is the prototype: handler(array $attributes): null|false|string|array
     *
     * Callback get 1 parameter - parsed tag attribute array.
     * The following types instead of "mixed" is supported:
     *
     * - NULL  If handler returns null, source tag is not modified.
     * - false If handler returns false,  source tag will be removed.
     * - string        Returning value is used t replace original tag.
     * - array         Returning value is treated as associative array of
     *                 tag attributes. Array also contains two special
     *                 elements:
     *                 - "_tagName": name of tag;
     *                 - "_text":    string representation of tag body
     *                               (for containers only, see below).
     *                               String representation of tag will be
     *                               reconstructed automatically by that array.
     */
    public function attachTagHandler(string $tagName, callable $handler, bool $atFront = false): void {
        $tagName = strtolower($tagName);
        if (!isset($this->tagHandlers[$tagName])) {
            $this->tagHandlers[$tagName] = [];
        }
        if (!$atFront) {
            array_push($this->tagHandlers[$tagName], $handler);
        } else {
            array_unshift($this->tagHandlers[$tagName], $handler);
        }
    }

    /**
     * Add the container handler.
     *
     * Containers are processed just like simple tags (see addTag()), but they also have
     * bodies saved in "_text" attribute.
     */
    public function attachContainerHandler(string $tagName, callable $handler, bool $atFront = false): void {
        $tagName = strtolower($tagName);
        if (!isset($this->containerHandlers[$tagName])) {
            $this->containerHandlers[$tagName] = [];
        }
        if (!$atFront) {
            array_push($this->containerHandlers[$tagName], $handler);
        } else {
            array_unshift($this->containerHandlers[$tagName], $handler);
        }
    }

    /**
     * Processes a HTML string and calls all attached handlers.
     */
    public function __invoke(mixed $html): mixed {
        if (is_array($html)) { // html is context?
            $html['program'] = $this->__invoke($html['program']);
            return $html;
        }
        $reTagIn = $this->tagRe;

        // Remove ignored container bodies from the string.
        $this->spIgnored = [];
        if ($this->skipIgnoredTags && $this->ignoredTags) {
            $reIgnoredNames = join("|", $this->ignoredTags);
            $reIgnored = "{(<($reIgnoredNames) (?> \\s+ $reTagIn)? >) (.*?) (</\\2>)}six";
            // Note that we MUST increase backtrack_limit, else error
            // PREG_BACKTRACK_LIMIT_ERROR will be generated on large SELECTs
            // (see preg_last_error() in PHP5).
            $oldLimit = ini_get('pcre.backtrack_limit');
            ini_set('pcre.backtrack_limit', strval(1024 * 1024 * 10));
            $html = preg_replace_callback(
                $reIgnored,
                [$this, "ignoredTagsToHash"],
                $html
            );
            ini_set('pcre.backtrack_limit', $oldLimit);
        }
        $sp_ignored = [
            $this->spIgnored,
            array_keys($this->spIgnored),
            array_values($this->spIgnored),
        ];
        unset($this->spIgnored);

        // Replace tags and containers.
        $hashlen = strlen($this->replaceHash) + 10;
        $reTagNames = join("|", array_keys($this->tagHandlers));
        $reConNames = join("|", array_keys($this->containerHandlers));
        $infos = [];
        // (? >...) [without space] is much faster than (?:...) in this case.
        if ($this->tagHandlers) {
            $infos["tagHandlers"] = "/( <($reTagNames) (?> (\\s+ $reTagIn) )? > () )/isx";
        }
        if ($this->containerHandlers) {
            $infos["containerHandlers"] = "/(<($reConNames)(?>(\\s+$reTagIn))?>(.*?)(?:<\\/\\2\\s*>|\$))/is";
        }
        foreach ($infos as $src => $re) {
            // Split buffer into tags.
            $chunks = preg_split($re, $html, 0, PREG_SPLIT_DELIM_CAPTURE);
            $textParts = [$chunks[0]]; // unparsed text parts
            $foundTags = []; // found tags
            for ($i = 1, $n = count($chunks); $i < $n; $i += 5) {
                // $i points to sequential tag (or container) subchain.
                $originalTagText = $chunks[$i];
                $tagName = $chunks[$i + 1];
                $attribsString = $chunks[$i + 2];
                $containerBody = $chunks[$i + 3];
                $tFollow = $chunks[$i + 4]; // - following unparsed text block

                // Add tag to array for pre-processing.
                $tag = $this->parseAttributes($attribsString);
                $tag['_orig'] = $originalTagText;
                $tag['_tagName'] = $tagName;
                if ($src == "containerHandlers") {
                    if (strlen($containerBody) < $hashlen && isset($sp_ignored[0][$containerBody])) {
                        // Maybe it is temporarily removed content - place back!
                        // Fast solution working in most cases (key-based hash lookup
                        // is much faster than str_replace() below).
                        $containerBody = $sp_ignored[0][$containerBody];
                    } else {
                        // We must pass unmangled content to container processors!
                        $containerBody = str_replace($sp_ignored[1], $sp_ignored[2], $containerBody);
                    }
                    $tag['_text'] = $containerBody;
                } elseif (substr($attribsString, -1) == '/') {
                    $tag['_text'] = null;
                }
                $foundTags[] = $tag;
                $textParts[] = $tFollow;
            }

            // Save original tags.
            $origTags = $foundTags;

            if (count($this->tagsPreprocessors)) {
                $foundTags = $this->preprocessTags($foundTags);
            }

            // Process all found tags and join the buffer.
            $html = $textParts[0];
            for ($i = 0, $n = count($foundTags); $i < $n; $i++) {
                $tag = $this->runHandlersForTag($foundTags[$i]);
                if (false === $tag) {
                    // Remove tag.
                    $html = rtrim($html);
                } elseif (!is_array($tag)) {
                    // String representation.
                    $html .= $tag;
                } else {
                    $prefix = isset($tag['_prefix']) ? $tag['_prefix'] : "";
                    unset($tag['_prefix']);
                    $suffix = isset($tag['_suffix']) ? $tag['_suffix'] : "";
                    unset($tag['_suffix']);
                    if (!isset($tag['_orig']) || $tag !== $origTags[$i]) {
                        // Build the tag back if it is changed.
                        $text = $this->renderTag($tag);
                    } else {
                        // Else - use original tag string.
                        // We use this algorithm because of non-unicode tag parsing mode:
                        // e.g. entity &nbsp; in tag attributes is replaced by &amp;nbsp;
                        // in renderTag(), but if the tag is not modified at all, we do
                        // not care and do not call renderTag() at all saving original &nbsp;.
                        $text = $tag['_orig'];
                    }
                    $html .= $prefix . $text . $suffix;
                }
                $html .= $textParts[$i + 1];
            }
        }

        // Return temporarily removed containers back.
        $html = str_replace($sp_ignored[1], $sp_ignored[2], $html);

        return $html;
    }

    /**
     * Parse the attribute string: "a1=v1 a2=v2 ..." of the tag.
     */
    protected function parseAttributes(string $attribs): array {
        $preg = '/([-\w:]+) \s* ( = \s* (?> ("[^"]*" | \'[^\']*\' | \S*) ) )?/sx';
        $regs = null;
        preg_match_all($preg, $attribs, $regs);
        $names = $regs[1];
        $checks = $regs[2];
        $values = $regs[3];
        $tag = [];
        for ($i = 0, $c = count($names); $i < $c; $i++) {
            $name = strtolower($names[$i]);
            if (empty($checks[$i])) {
                $value = $name;
            } else {
                $value = $values[$i];
                if ($value[0] == '"' || $value[0] == "'") {
                    $value = substr($value, 1, -1);
                }
            }
            $tag[$name] = $value;
        }
        return $tag;
    }

    /**
     * This function is called after all tags and containers are
     * found in HTML text, but BEFORE any replaces.
     */
    protected function preprocessTags(array $foundTags): array {
        foreach ($this->tagsPreprocessors as $tagPreprocessor) {
            $foundTags = $tagPreprocessor($foundTags);
        }
        return $foundTags;
    }

    /**
     * @param array $tag
     * @return array|false|string|null Handled tag.
     */
    protected function runHandlersForTag(array $tag): array|false|null|string {
        $tagName = strtolower($tag['_tagName']);
        // Processing tag or container?
        $handlers = $this->handlersOfTag($tagName, isset($tag['_text']));
        // Use all handlers from right to left.
        for ($i = count($handlers) - 1; $i >= 0; $i--) {
            $handler = $handlers[$i];
            $result = call_user_func($handler, $tag, $tagName);
            if (null !== $result) {
                if (!is_array($result)) {
                    return $result;
                }
                $tag = $result;
            }
        }
        return $tag;
    }

    protected function handlersOfTag(string $tagName, bool $isContainerTag): array {
        return $isContainerTag ? $this->containerHandlers[$tagName] : $this->tagHandlers[$tagName];
    }

    /**
     * Recreate the tag or container by its parsed attributes.
     *
     * If $attr[_text] is present, make container.
     *
     * @param array $tag Attributes of tag. These attributes could
     *                      include two special attributes:
     *                      '_text':    tag is a container with body.
     *                                  If null - <tag ... />.
     *                                  If not present - <tag ...>.
     *                      '_tagName': name of this tag.
     *                      '_orig':    ignored (internal usage).
     *
     * @return string HTML representation of tag or container.
     */
    protected function renderTag(array $tag): string {
        $attrStr = '';
        foreach ($tag as $name => $value) {
            if (is_string($name) && $name[0] === '_') {
                continue;
            }
            $attrStr .= ' ' . PhpTemplateEngine::e($name);
            if ($value !== null) {
                $attrStr .= '="' . $this->encodeAttrValue($value) . '"';
            }
        }
        if (empty($tag['_tagName'])) {
            throw new RuntimeException('Tag name is empty');
        }
        if (!array_key_exists('_text', $tag)) { // do not use isset()!
            $tagHtmlStr = "<{$tag['_tagName']}{$attrStr}>";
        } elseif ($tag['_text'] === null) {
            $tagHtmlStr = "<{$tag['_tagName']}{$attrStr}" . ($this->isHtml5 ? '>' : ' />');
        } else {
            $tagHtmlStr = "<{$tag['_tagName']}{$attrStr}>{$tag['_text']}</{$tag['_tagName']}>";
        }
        return $tagHtmlStr;
    }

    protected function encodeAttrValue(string $attrValue): string {
        if (false !== strpos($attrValue, '<?')) {
            // Modified RE from https://github.com/nikic/PHP-Parser/blob/master/grammar/rebuildParsers.php#L34
            $groups = preg_split(
                '~(?P<php> (?: <\?php|<\?= ) [^?]*+(?:\?(?!>)[^?]*+)*+ \?> )~six',
                $attrValue,
                -1,
                PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
            );
            $value = '';
            foreach ($groups as $group) {
                if (preg_match('~^(?: <\?php|<\?= ) [^?]*+(?:\?(?!>)[^?]*+)*+ \?>$~six', $group)) { // ignore PHP code
                    $value .= $group;
                } else {
                    $value .= PhpTemplateEngine::e($group);
                }
            }
            return $value;
        } else {
            return PhpTemplateEngine::e($attrValue);
        }
    }

    /**
     * Replace found ignored container body by hash value.
     *
     * Container's open and close tags are NOT modified!
     * Later hash value will be replaced back to original text.
     */
    protected function ignoredTagsToHash(array $m): string {
        static $counter = 0;
        $hash = $this->replaceHash . ++$counter . "|";
        // DO NOT use chr(0) here!!!
        $this->spIgnored[$hash] = $m[3];
        return $m[1] . $hash . $m[4];
    }
}
