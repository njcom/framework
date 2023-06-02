<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App;

use Morpho\App\PasswordManager;
use Morpho\Testing\TestCase;

use function array_diff;
use function implode;
use function preg_match;
use function preg_quote;
use function str_split;
use function strlen;

class PasswordManagerTest extends TestCase {
    public function testGeneratePassword() {
        $this->checkPassword(
            function ($password) {
                return (bool) preg_match('~^[A-Z0-9]+$~', $password);
            },
            31,
            PasswordManager::USE_UPPER_CHARS | PasswordManager::USE_DIGIT_CHARS
        );
        $this->checkPassword(
            function ($password) {
                return (bool) preg_match('~^[[:punct:]]+$~', $password);
            },
            20,
            PasswordManager::USE_CONFUSED_CHARS
        );
        $this->checkPassword(
            function ($password) {
                $chars = array_diff(str_split(PasswordManager::PUNCT_CHARS), str_split(PasswordManager::CONFUSED_CHARS));
                /*
                $re = \array_reduce($chars, function ($acc, $char) {
                    if ($acc === '') {
                        return preg_quote($char, '~');
                    }
                    $acc .= '|' . preg_quote($char, '~');
                    return $acc;
                }, '');
                */
                $re = '[' . preg_quote(implode('', $chars), '~') . ']';
                return (bool) preg_match('~^(' . $re . ')+$~', $password);
            },
            25,
            PasswordManager::USE_PUNCT_CHARS ^ PasswordManager::USE_CONFUSED_CHARS
        );
    }

    private function checkPassword(callable $predicate, int $length, int $flags) {
        for ($i = 0; $i < 5; $i++) {
            // 5 attempts should be enough to ensure that the generated strings are matching the regular expression $re
            $passwordManager = new PasswordManager();
            $password = $passwordManager->generatePassword($length, $flags);
            $this->assertEquals($length, strlen($password));
            $this->assertTrue($predicate($password));
        }
    }
}