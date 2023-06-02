<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Uri;

use Morpho\Base\IFn;
use Morpho\Base\NotImplementedException;

use function preg_match;

class UriValidator implements IFn {
    private const HEX_DIGIT_RE = '[0-9A-F]';
    private const ALPHA_RE = '[A-Z]';
    private const DIGIT_RE = '\d';
    private const PCT_ENCODED_RE = '( %  ' . self::HEX_DIGIT_RE . self::HEX_DIGIT_RE . ' )';
    private const UNRESERVED_RE = '( ' . self::ALPHA_RE . ' | ' . self::DIGIT_RE . '| - | \. | _ | ~ )';

    private const SUBDELIMS_RE = "[!$&'()*+,;=]";

    public static function validateScheme(string $scheme): bool {
        return (bool) preg_match('~^([a-z][-a-z0-9+.]*)$~si', $scheme);
    }

    public static function validateAuthority(string $authority): bool {
        $authority = UriParser::parseOnlyAuthority($authority);

        $subDelimsRe = self::SUBDELIMS_RE;
        $pctEncodedRe = self::PCT_ENCODED_RE;
        $unreservedRe = self::UNRESERVED_RE;
        $hexDigRe = self::HEX_DIGIT_RE;

        $userInfoRe = "( $unreservedRe | $pctEncodedRe | $subDelimsRe | : )*";
        $userInfo = $authority->userInfo();
        if (null !== $userInfo) {
            if (!preg_match('{^' . $userInfoRe . '$}six', $userInfo)) {
                return false;
            }
        }

        // @TODO: Extract IpValidator, IpV4Validator, IpV6Validator
        // IP-literal = "[" ( IPv6address / IPvFuture  ) "]"
        // IPvFuture  = "v" 1*HEXDIG "." 1*( unreserved / sub-delims / ":" )
        // NB: we don't support the IPvFuture yet

        $decOctetRe = "( \d | ( [1-9]\d ) | ( 1\d{2} ) | ( 2[0-4]\d ) | ( 25[0-5] ) )"; // [0..255]
        $ipV4AddressRe = "$decOctetRe\.$decOctetRe\.$decOctetRe\.$decOctetRe";

        $h16Re = $hexDigRe . '{1,4}'; // 0..ffff
        $ls32Re = "( ( $h16Re : $h16Re) | $ipV4AddressRe )";  // least-significant 32 bits of address
        $ipV6AddressRe = "(
            ( ( $h16Re : ){6} $ls32Re )
            | ( :: ( $h16Re : ){5} $ls32Re )
            | ( ( $h16Re )? :: ( $h16Re : ){4} $ls32Re )
            | ( ( ( $h16Re : )? $h16Re )? :: ( $h16Re : ){3} $ls32Re )
            | ( ( ( $h16Re : ){0,2} $h16Re )? :: ( $h16Re : ){2} $ls32Re )
            | ( ( ( $h16Re : ){0,3} $h16Re )? :: $h16Re : $ls32Re )
            | ( ( ( $h16Re : ){0,4} $h16Re )? :: $ls32Re )
            | ( ( ( $h16Re : ){0,5} $h16Re )? :: $h16Re )
            | ( ( ( $h16Re : ){0,6} $h16Re )? :: )
            )";

        $ipLiteralRe = "\[$ipV6AddressRe\]";
        $regNameRe = "( $unreservedRe | $pctEncodedRe | $subDelimsRe )*";
        $hostRe = "( $ipLiteralRe | $ipV4AddressRe | $regNameRe)";

        if (!preg_match('{^' . $hostRe . '$}six', $authority->host())) {
            return false;
        }

        $port = $authority->port();
        if (null !== $port) {
            return (bool) preg_match('~^\d*$~s', (string) $port);
        }

        return true;
    }

    /**
     * See https://tools.ietf.org/html/rfc3986#section-3.3
     */
    public static function validatePath(string $path, bool $hasAuthority): bool {
        $pctEncodedRe = self::PCT_ENCODED_RE;
        $subDelimsRe = self::SUBDELIMS_RE;
        $unreservedRe = self::UNRESERVED_RE;
        // unreserved / pct-encoded / sub-delims / ":" / "@"
        $pCharRe = " ( $unreservedRe | $pctEncodedRe | $subDelimsRe | : | @ )";
        $segmentRe = "$pCharRe*";

        if ($hasAuthority) {
            // If a URI contains an authority component, then the path component must either be empty or begin with a slash ("/") character.
            // path-abempty  = *( "/" segment )
            return (bool) preg_match("{^( / $segmentRe )*$}six", $path);
        } else {
            // If a URI does not contain an authority component, then the path cannot begin with two slash characters ("//").
            // path-absolute = "/" [ segment-nz *( "/" segment ) ]   ; begins with "/" but not "//"
            // segment-nz    = 1*pchar
            $segmentNzRe = "$pCharRe+";
            return (bool) preg_match("{^ / ( $segmentNzRe ( / $segmentRe )* )? $}six", $path);
        }
        /*
           The path segments "." and "..", also known as dot-segments, are
           defined for relative reference within the path name hierarchy.  They
           are intended for use at the beginning of a relative-path reference
           (Section 4.2) to indicate relative position within the hierarchical
           tree of names.  This is similar to their role within some operating
           systems' file directory structures to indicate the current directory
           and parent directory, respectively.  However, unlike in a file
           system, these dot-segments are only interpreted within the URI path
           hierarchy and are removed as part of the resolution process (Section
           5.2).

           Aside from dot-segments in hierarchical paths, a path segment is
           considered opaque by the generic syntax.  URI producing applications
           often use the reserved characters allowed in a segment to delimit
           scheme-specific or dereference-handler-specific subcomponents.  For
           example, the semicolon (";") and equals ("=") reserved characters are
           often used to delimit parameters and parameter values applicable to
           that segment.  The comma (",") reserved character is often used for
           similar purposes.  For example, one URI producer might use a segment
           such as "name;v=1.1" to indicate a reference to version 1.1 of
           "name", whereas another might use a segment such as "name,1.1" to
           indicate the same.  Parameter types may be defined by scheme-specific
           semantics, but in most cases the syntax of a parameter is specific to
           the implementation of the URI's dereferencing algorithm.

        */
    }

    public static function validateQuery(string $query): bool {
        throw new NotImplementedException();
    }

    public static function validateFragment(string $fragment): bool {
        throw new NotImplementedException();
    }

    public function __invoke(mixed $uri): bool {
        throw new NotImplementedException();
    }
}
