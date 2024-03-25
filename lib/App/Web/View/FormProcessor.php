<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web\View;

/**
 * This class is changed version of HTML_FormPersister class originally written by Dmitry Koterov:
 * http://forum.dklab.ru/users/DmitryKoterov/, original code was found at: https://github.com/DmitryKoterov/html_formpersister
 */
class FormProcessor extends HtmlProcessor {
    const DEFAULT_METHOD = 'post';
    /*private array $fpAutoIndexes;

    public function __invoke(mixed $context): mixed {
        $this->fpAutoIndexes = [];
        return parent::__invoke($context);
    }
    */

    protected function tagForm($tag) {
        if (!isset($tag['method'])) {
            $tag['method'] = self::DEFAULT_METHOD;
        }
        if (!isset($tag['action'])) {
            /*            if (strtolower($tag['method']) === 'get') {
                            $tag['action'] = $request->path();
                        } else {
                            */
            $tag['action'] = $this->request->uri->toStr(null, false);
        }
        return $tag;
    }

    /**
     * <INPUT> tag handler.
     * See HTML_SemiParser.
     *
     * protected function tag_input($attr) {
     * static $uid = 0;
     * switch (@strtolower($attr['type'])) {
     * case 'radio':
     * if (!isset($attr['name'])) return;
     * if (isset($attr['checked']) || !isset($attr['value'])) return;
     * if ($attr['value'] == $this->getCurValue($attr)) $attr['checked'] = 'checked';
     * else unSet($attr['checked']);
     * break;
     * case 'checkbox':
     * if (!isset($attr['name'])) return;
     * if (isset($attr['checked'])) return;
     * if (!isset($attr['value'])) $attr['value'] = 'on';
     * if ($this->getCurValue($attr, true)) $attr['checked'] = 'checked';
     * break;
     * case 'image':
     * case 'submit':
     * if (isset($attr['confirm'])) {
     * $attr['onclick'] = 'return confirm("' . $attr['confirm'] . '")';
     * unSet($attr['confirm']);
     * }
     * break;
     * case 'text':
     * case 'password':
     * case 'hidden':
     * case '':
     * default:
     * if (!isset($attr['name'])) return;
     * if (!isset($attr['value']))
     * $attr['value'] = $this->getCurValue($attr);
     * break;
     * }
     * // Handle label pseudo-attribute. Button is placed RIGHTER
     * // than the text if label text ends with "^". Example:
     * // <input type=checkbox label="hello">   ==>  [x]hello
     * // <input type=checkbox label="hello^">  ==>  hello[x]
     * if (isset($attr['label'])) {
     * $text = $attr['label'];
     * if (!isset($attr['id'])) $attr['id'] = 'FPlab' . ($uid++);
     * $right = 1;
     * if ($text[strlen($text) - 1] == '^') {
     * $right = 0;
     * $text = \substr($text, 0, -1);
     * }
     * unSet($attr['label']);
     * $attr[$right ? '_right' : '_left'] = '<label for="' . $this->quoteHandler($attr['id']) . '">' . $text . '</label>';
     * }
     * // We CANNOT return $orig_attr['_orig'] if attributes are not modified,
     * // because we know nothing about following handlers. They may need
     * // the parsed attributes, not a plain text.
     * unset($attr['default']);
     * return $attr;
     * }
     *
     * /**
     * <TEXTAREA> tag handler.
     * See HTML_SemiParser.
     * protected function container_textarea($attr) {
     * if (trim($attr['_text']) == '') {
     * $attr['_text'] = $this->quoteHandler($this->getCurValue($attr));
     * }
     * unset($attr['default']);
     * return $attr;
     * }
     *
     * /**
     * <SELECT> tag handler.
     * See HTML_SemiParser.
     *
     * protected function container_select($attr) {
     * if (!isset($attr['name'])) return;
     *
     * // Multiple lists MUST contain [] in the name.
     * if (isset($attr['multiple']) && \strpos($attr['name'], '[]') === false) {
     * $attr['name'] .= '[]';
     * }
     *
     * $curVal = $this->getCurValue($attr);
     * $body = "";
     *
     * // Get some options from variable?
     * // All the text outside <option>...</option> container are treated as variable name.
     * // E.g.: <select...> <option>...</option> ... some[global][options] ... <option>...</option> ... </select>
     * $attr['_text'] = preg_replace_callback('{
     * (
     * (?:^ | </option> | </optgroup> | <optgroup[^>]*>)
     * \s*
     * )
     * \$?
     * ( [^<>\s]+ ) # variable name
     * (?=
     * \s*
     * (?:$ | <option[\s>] | <optgroup[\s>] | </optgroup>)
     * )
     * }six',
     * [&$this, '_optionsFromVar_callback'],
     * $attr['_text']
     * );
     *
     * // Parse options, fetch its values and save them to array.
     * // Also determine if we have at least one selected option.
     * $body = $attr['_text'];
     * $parts = preg_split("/<option\s*({$this->sp_reTagIn})>/si", $body, -1, PREG_SPLIT_DELIM_CAPTURE);
     * $hasSelected = 0;
     * for ($i = 1, $n = count($parts); $i < $n; $i += 2) {
     * $opt = [];
     * $this->parseAttrib($parts[$i], $opt);
     * if (isset($opt['value'])) {
     * $value = $opt['value'];
     * } else {
     * // Option without value: spaces are shrinked (experimented on IE).
     * $text = preg_replace('{</?(option|optgroup)[^>]*>.*}si', '', $parts[$i + 1]);
     * $value = \trim($text);
     * $value = preg_replace('/\s\s+/', ' ', $value);
     * if (strpos($value, '&') !== false) {
     * $value = $this->_unhtmlspecialchars($value);
     * }
     * }
     * if (isset($opt['selected'])) $hasSelected++;
     * $parts[$i] = [$opt, $value];
     * }
     *
     * // Modify options list - add selected attribute if needed, but ONLY
     * // if we do not already have selected options!
     * if (!$hasSelected) {
     * foreach ($parts as $i => $parsed) {
     * if (!\is_array($parsed)) continue;
     * list ($opt, $value) = $parsed;
     * if (isset($attr['multiple'])) {
     * // Inherit some <select> attributes.
     * if ($this->getCurValue($opt + $attr + ['value' => $value], true)) { // merge
     * $opt['selected'] = 'selected';
     * }
     * } else {
     * if ($curVal == $value) {
     * $opt['selected'] = 'selected';
     * }
     * }
     * $opt['_tagName'] = 'option';
     * $parts[$i] = $this->makeTag($opt);
     * }
     * $body = join('', $parts);
     * }
     *
     * $attr['_text'] = $body;
     * unset($attr['default']);
     * return $attr;
     * }
     *
     *
     * /**
     * Create set of <option> tags from array.
     * protected function makeOptions($options, $curId = false) {
     * $body = '';
     * foreach ($options as $k => $text) {
     * if (\is_array($text)) {
     * // option group
     * $options = '';
     * foreach ($text as $ko => $v) {
     * $opt = ['_tagName' => 'option', 'value' => $ko, '_text' => $this->quoteHandler(strval($v))];
     * if ($curId !== false && strval($curId) === strval($ko)) {
     * $opt['selected'] = "selected";
     * }
     * $options .= HTML_SemiParser::makeTag($opt);
     * }
     * $grp = ['_tagName' => 'optgroup', 'label' => $k, '_text' => $options];
     * $body .= $this->makeTag($grp);
     * } else {
     * // single option
     * $opt = ['_tagName' => 'option', 'value' => $k, '_text' => $this->quoteHandler($text)];
     * if ($curId !== false && strval($curId) === strval($k)) {
     * $opt['selected'] = "selected";
     * }
     * $body .= $this->makeTag($opt);
     * }
     * }
     * return $body;
     * }
     *
     * /**
     * Value extractor.
     *
     * Try to find corresponding entry in $_POST, $_GET etc. for tag
     * with name attribute $attr['name']. Support complex form names
     * like 'fiels[one][two]', 'field[]' etc.
     *
     * If $isBoolean is set, always return true or false. Used for
     * checkboxes and multiple selects (names usually trailed with "[]",
     * but may not be trailed too).
     *
     * @param array &$arr Array to fetch from.
     * @param mixed &$name Complex form-field name.
     * @param array &$autoindexes Container to hold auto-indexes
     * @return Current "value" of specified tag.
     * protected function getCurValue($attr, $isBoolean = false) {
     * $name = @$attr['name'];
     * if ($name === null) return null;
     * $isArrayLike = false; // boolean AND contain [] in the name
     * // Handle boolean fields.
     * if ($isBoolean && false !== ($p = \strpos($name, '[]'))) {
     * $isArrayLike = true;
     * $name = \substr($name, 0, $p) . \substr($name, $p + 2);
     * }
     * // Search for value in ALL arrays,
     * // EXCEPT $_REQUEST, because it also holds Cookies!
     * $fromForm = true;
     * if (false !== ($v = $this->_deepFetch($_POST, $name, $this->fp_autoindexes[$name]))) $value = $v;
     * elseif (false !== ($v = $this->_deepFetch($_GET, $name, $this->fp_autoindexes[$name]))) $value = $v;
     * elseif (isset($attr['default'])) {
     * $value = $attr['default'];
     * if ($isBoolean) return $value !== '' && $value !== "0";
     * // For array fields it is possible to enumerate all the
     * // values in SCALAR using ';'.
     * if ($isArrayLike && !\is_array($value)) $value = explode(';', $value);
     * $fromForm = false;
     * } else {
     * // If no data is found in GET, POST etc., always return false in boolean mode.
     * if ($isBoolean) return false;
     * $value = '';
     * }
     * if ($fromForm) {
     * // Remove slashes on stupid magic_quotes_gpc mode.
     * // TODO: handle nested arrays too!
     * if (\is_scalar($value) && get_magic_quotes_gpc() && !@constant('MAGIC_QUOTES_GPC_DISABLED')) {
     * $value = stripslashes($value);
     * }
     * }
     * // Return value depending on field type.
     * $attrValue = strval(isset($attr['value']) ? $attr['value'] : 'on');
     * if ($isArrayLike) {
     * // Array-like field? If present, return true.
     * if (!\is_array($value)) return false;
     * // Unfortunately we MUST use strict mode in \in_array()
     * // and cast array values to string before checking.
     * // This is because \in_array(0, array('one')) === true.
     * return \in_array(strval($attrValue), \array_map('strval', $value), true);
     * } else {
     * if ($isBoolean) {
     * // Non-array boolean elements must be boolean-equal to match, OR
     * // GET-POST-... values must be === true. Why? Because the followind
     * // checkboxes must be turned on:
     * // - $_POST['a'] = true; ... <input type="checkbox" name="a" value="x">
     * // - $_POST['b'] = ""; ...   <input type="checkbox" name="b" value="">
     * return (bool)@strval($value) === (bool)$attrValue;
     * } else {
     * // This is not boolean nor array field. Return it now.
     * return @strval($value);
     * }
     * }
     * }
     *
     * /**
     * Fetch an element of $arr array using "complex" key $name.
     *
     * $name can be in form of "zzz[aaa][bbb]",
     * it means $arr[zzz][aaa][bbb].
     *
     * If $name contain auto-indexed parts (e.g. a[b][]), replace
     * it by corresponding indexes.
     *
     * $name may be scalar name or array (already splitted name,
     * see _splitMultiArray() method).
     *
     * @return found value, or false if $name is not found.
     * protected static function _deepFetch(&$arr, &$name, &$autoindexes) {
     * if (\is_scalar($name) && \strpos($name, '[') === false) {
     * // Fast fetch.
     * return isset($arr[$name]) ? $arr[$name] : false;
     * }
     * // Else search into deep.
     * $parts = static::_splitMultiArray($name);
     * $leftPrefix = '';
     * foreach ($parts as $i => $k) {
     * if (!strlen($k)) {
     * // Perform auto-indexing.
     * if (!isset($autoindexes[$leftPrefix])) $autoindexes[$leftPrefix] = 0;
     * $parts[$i] = $k = $autoindexes[$leftPrefix]++;
     * }
     * if (!\is_array($arr)) {
     * // Current container is not array.
     * return false;
     * }
     * if (!array_key_exists($k, $arr)) {
     * // No such element.
     * return false;
     * }
     * $arr = &$arr[$k];
     * $leftPrefix = \strlen($leftPrefix) ? $leftPrefix . "[$k]" : $k;
     * }
     * if (!\is_scalar($name)) {
     * $name = $parts;
     * } else {
     * $name = $leftPrefix;
     * }
     * return $arr;
     * }
     *
     * /**
     * Highly internal function. Must be re-written if some new
     * version of would support syntax like "zzz['aaa']['b\'b']" etc.
     * For "zzz[aaa][bbb]" returns array(zzz, aaa, bbb).
     * protected static function _splitMultiArray($name) {
     * if (\is_array($name)) return $name;
     * if (strpos($name, '[') === false) return [$name];
     * $regs = null;
     * preg_match_all('/ ( ^[^[]+ | \[ .*? \] ) (?= \[ | $) /xs', $name, $regs);
     * $arr = [];
     * foreach ($regs[0] as $s) {
     * if ($s[0] == '[') $arr[] = \substr($s, 1, -1);
     * else $arr[] = $s;
     * }
     * return $arr;
     * }
     *
     * /**
     * Callback function to replace variables in <select> body by set of options.
     * protected function _optionsFromVar_callback($p) {
     * $dummy = [];
     * $name = trim($p[2]);
     * $options = $this->_deepFetch($GLOBALS, $name, $dummy);
     * if ($options === null || $options === false) return $p[1] . "<option>???</option>";
     * return $p[1] . $this->makeOptions($options);
     * }
     */
}
