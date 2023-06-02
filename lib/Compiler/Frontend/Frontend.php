<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend;

use Morpho\Base\NotImplementedException;
use Morpho\Compiler\ConfigurablePipe;
use UnexpectedValueException;

class Frontend extends ConfigurablePipe implements IFrontend {
    public function __invoke(mixed $val): mixed {
        //$parser = $conf['parser'];
        return $val;
    }

    public function current(): callable {
        $index = $this->index;
        if ($index === 0) {
            return $this->parser();
        }
        if ($index === 1) {
            return $this->sema();
        }
        throw new UnexpectedValueException();
    }

    /**
     * Returns parser. Parser may or may not include a lexer.
     * @return callable
     */
    public function parser(): callable {
        throw new NotImplementedException();
    }

    /**
     * Returns semantic analyzer
     */
    public function sema(): callable {
        throw new NotImplementedException();
    }

    public function count(): int {
        // count([$this->parser(), $this->sema()])
        return 2;
    }
}