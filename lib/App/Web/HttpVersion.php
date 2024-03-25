<?php declare(strict_types=1);
namespace Morpho\App\Web;

enum HttpVersion: string {
    case V1_1 = '1.1';
    case V2 = '2';
    case V3 = '3';

    public static function isValid(string $httpVersion): bool {
        if (preg_match('~^(?P<major>\d)(?:\.(?P<minor>\d))?$~si', $httpVersion, $match)) {
            switch ($match['major']) {
                case '1':
                    return isset($match['minor']) && $match['minor'] === '1';
                case '2':
                case '3':
                    return !isset($match['minor']);
            }
        }
        return false;
    }
}