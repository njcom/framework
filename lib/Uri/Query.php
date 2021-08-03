<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Uri;

use ArrayObject;

use function is_string;
use function ltrim;
use function rawurlencode;

class Query extends ArrayObject implements IUriComponent {
    private bool $initialized = false;

    public function __construct(string|array|object|null $queryStrOrQueryArgs = null) {
        if (null === $queryStrOrQueryArgs) {
            return;
        }
        if (is_string($queryStrOrQueryArgs)) {
            $this->initialized = true;
            $query = UriParser::parseOnlyQuery($queryStrOrQueryArgs);
            $this->exchangeArray($query);
        } else {
            parent::__construct($queryStrOrQueryArgs);
        }
    }

    public function isNull(): bool {
        if ($this->initialized) {
            return false;
        }
        foreach ($this as $_) {
            return false;
        }
        return true;
    }

    public function toStr(bool $encode): string {
        $queryStr = '';
        foreach ($this as $name => $value) {
            $queryStr .= '&' . ($encode ? rawurlencode($name) : $name);
            if (null !== $value) {
                $queryStr .= '=' . ($encode ? rawurlencode($value) : $value);
            }
        }
        return ltrim($queryStr, '&');
    }
}
