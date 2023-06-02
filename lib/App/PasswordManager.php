<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

use Morpho\Base\Rand;
use RuntimeException;
use UnexpectedValueException;

use function password_hash;
use function password_needs_rehash;
use function password_verify;
use function str_replace;
use function str_split;
use function strlen;

use const PASSWORD_DEFAULT;

class PasswordManager {
    public const PASSWORD_LENGTH = 24;
    public const MAX_PASSWORD_LENGTH = 72;
    // Mnemonic: names of the following constants matching names of character classes in perl regular expressions like [:punct:], [:lower:] etc, see http://php.net/manual/en/regexp.reference.character-classes.php
    public const PUNCT_CHARS = '!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~';
    // [:punct:]
    public const LOWER_CHARS = 'abcdefghijklmnopqrstuvwxyz';
    // [:lower:]
    public const UPPER_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    // [:upper:]
    public const DIGIT_CHARS = '0123456789';
    // [:digit:]
    // Characters which are hard to read or which can cause problems in some programs, e.g. shell.
    public const CONFUSED_CHARS = '"\'`|loIO01';
    public const USE_LOWER_CHARS = 0b1;
    public const USE_UPPER_CHARS = 0b10;
    public const USE_DIGIT_CHARS = 0b100;
    public const USE_PUNCT_CHARS = 0b11000;
    public const USE_CONFUSED_CHARS = 0b1000;
    // subset of PUNCT_CHARS
    public const USE_ALL_CHARS = self::USE_LOWER_CHARS | self::USE_UPPER_CHARS | self::USE_DIGIT_CHARS | self::USE_PUNCT_CHARS;
    const COST = 12;

    public static function generatePassword(
        int $length = self::PASSWORD_LENGTH,
        int $flags = self::USE_ALL_CHARS ^ self::USE_CONFUSED_CHARS
    ): string {
        $chars = '';
        if ($flags & self::USE_PUNCT_CHARS) {
            $chars .= self::PUNCT_CHARS;
        }
        if ($flags & self::USE_LOWER_CHARS) {
            $chars .= self::USE_LOWER_CHARS;
        }
        if ($flags & self::USE_UPPER_CHARS) {
            $chars .= self::UPPER_CHARS;
        }
        if ($flags & self::USE_DIGIT_CHARS) {
            $chars .= self::DIGIT_CHARS;
        }
        if (!($flags & self::USE_CONFUSED_CHARS)) {
            $chars = str_replace(str_split(self::CONFUSED_CHARS), '', $chars);
        }
        return Rand::randStr($length, $chars);
    }

    public static function isOutdatedHash(string $passwordHash): bool {
        return password_needs_rehash($passwordHash, static::algo(), static::conf());
    }

    /**
     * @return string|null Returns null for PHP >= 7.4.0 && <= 7.4.2 and string for PHP >= 7.4.3
     */
    private static function algo(): ?string {
        return PASSWORD_DEFAULT;
    }

    private static function conf(): array {
        return ['cost' => self::COST];
    }

    /**
     * @return string Password hash, 60 characters.
     */
    public static function passwordHash(string $plainPassword): string {
        if (strlen($plainPassword) > self::MAX_PASSWORD_LENGTH) {
            throw new UnexpectedValueException("Password too long");
        }
        $passwordHash = password_hash($plainPassword, static::algo(), self::conf());
        if (false === $passwordHash) {
            throw new RuntimeException("Unable to generate password hash");
        }
        return $passwordHash;
    }

    public static function isValidPassword(string $plainPassword, string $passwordHash): bool {
        return password_verify($plainPassword, $passwordHash);
    }
}