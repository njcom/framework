<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
/**
 * Based on https://github.com/willdurand/Negotiation library, original author William Durand, MIT license.
 */
namespace Morpho\App\Web;

/**
 * See [RFC 9110 12.5. Content Negotiation Fields](https://datatracker.ietf.org/doc/html/rfc9110#name-content-negotiation-fields)
 */
class MediaTypeNegotiator {
    public function detectBest(string $headerValue, array $priorities): array|false {
        if (!$headerValue || !$priorities) {
            return false;
        }
        $mediaRanges = [];
        foreach (self::parseAcceptHeaderValue($headerValue) as $mediaRange) {
            $parsedMediaRange = self::parseMediaRange($mediaRange);
            if ($parsedMediaRange) {
                $mediaRanges[] = $parsedMediaRange;
            }
        }
        if (!$mediaRanges) {
            return false;
        }

        $preferredMediaRanges = [];
        foreach ($priorities as $priority) {
            $parsedPriority = self::parseMediaRange($priority);
            if ($parsedPriority) {
                $preferredMediaRanges[] = $parsedPriority;
            }
        }
        if (!$preferredMediaRanges) {
            return false;
        }

        $matches = self::findMatches($mediaRanges, $preferredMediaRanges);

        $specificMatches = array_reduce($matches, function (array $carry, $match) {
            $index = $match['index'];
            if (!isset($carry[$index]) || $carry[$index]['score'] < $match['score']) {
                $carry[$index] = $match;
            }
            return $carry;
        }, []);
        usort($specificMatches, function (array $a, array $b): int {
            if ($a['quality'] !== $b['quality']) {
                return $a['quality'] > $b['quality'] ? -1 : 1;
            }
            if ($a['index'] !== $b['index']) {
                return $a['index'] > $b['index'] ? 1 : -1;
            }
            return 0;
        });
        $match = array_shift($specificMatches);
        return $match ? $preferredMediaRanges[$match['index']] : false;
    }

    public static function findMatches(array $mediaRanges, array $preferredMediaRanges): array {
        $matches = [];
        foreach ($preferredMediaRanges as $preferredMediaRangeIndex => $preferredMediaRange) {
            foreach ($mediaRanges as $mediaRange) {
                if (null !== $match = self::match($mediaRange, $preferredMediaRange, $preferredMediaRangeIndex)) {
                    $matches[] = $match;
                }
            }
        }
        return $matches;
    }

    public static function parseAcceptHeaderValue(string $headerValue): array|false {
        $ok = preg_match_all('/(?:[^,"]*+(?:"[^"]*+")?)+[^,"]*+/', $headerValue, $matches);
        if (!$ok) {
            return false;
        }
        return array_values(array_filter(array_map(trim(...), $matches[0])));
    }

    public static function parseMediaRange(string $mediaRange): array|false {
        $parts = explode(';', $mediaRange);
        $type = array_shift($parts);
        $parameters = [];
        foreach ($parts as $part) {
            $part = explode('=', $part);
            if (2 !== count($part)) {
                continue; // TODO: throw exception here?
            }
            $key = strtolower(trim($part[0])); // TODO: technically not allowed space around "=". throw exception?
            $parameters[$key] = trim($part[1], ' "');
        }
        $type = trim(strtolower($type));
        if ($type === '*') {
            $type = '*/*';
        }

        $quality = 1.0;
        if (isset($parameters['q'])) {
            $quality = (float)$parameters['q'];
            unset($parameters['q']);
        }

        $paramsString = function (array $parameters) {
            $parts = [];
            ksort($parameters);
            foreach ($parameters as $key => $val) {
                $parts[] = sprintf('%s=%s', $key, $val);
            }
            return implode('; ', $parts);
        };

        $normalizedValue = $type . ($parameters ? "; " . $paramsString($parameters) : '');

        $parts = explode('/', $type);
        if (count($parts) !== 2 || !$parts[0] || !$parts[1]) {
            return false;
        }

        return [
            'type'            => $type,
            'quality'         => $quality,
            'parameters'      => $parameters,
            'value'           => $mediaRange,
            'normalizedValue' => $normalizedValue,
            'basePart'        => $parts[0],
            'subPart'         => $parts[1],
        ];
    }

    protected static function match(array $mediaRange, array $preferredMediaRange, int $preferredMediaRangeIndex): ?array {
        if (!$mediaRange || !$preferredMediaRange) {
            return null;
        }

        $acceptBase = $mediaRange['basePart'];
        $priorityBase = $preferredMediaRange['basePart'];

        $acceptSub = $mediaRange['subPart'];
        $prioritySub = $preferredMediaRange['subPart'];

        $intersection = array_intersect_assoc($mediaRange['parameters'], $preferredMediaRange['parameters']);
        $baseEqual = !strcasecmp($acceptBase, $priorityBase);
        $subEqual = !strcasecmp($acceptSub, $prioritySub);
        if (($acceptBase === '*' || $baseEqual) && ($acceptSub === '*' || $subEqual) && count($intersection) === count($mediaRange['parameters'])) {
            $score = 100 * $baseEqual + 10 * $subEqual + count($intersection);
            return [
                'quality' => $mediaRange['quality'] * $preferredMediaRange['quality'],
                'score'   => $score,
                'index'   => $preferredMediaRangeIndex,
            ];
        }

        if (!str_contains($acceptSub, '+') || !str_contains($prioritySub, '+')) {
            return null;
        }
        // Handle "+" segment wildcards
        [$acceptSub, $acceptPlus] = explode('+', $acceptSub, 2);
        [$prioritySub, $priorityPlus] = explode('+', $prioritySub, 2);

        // If no wildcards in either the subtype or + segment, do nothing.
        if (!($acceptBase === '*' || $baseEqual)
            || !($acceptSub === '*' || $prioritySub === '*' || $acceptPlus === '*' || $priorityPlus === '*')
        ) {
            return null;
        }

        $subEqual = !strcasecmp($acceptSub, $prioritySub);
        $plusEqual = !strcasecmp($acceptPlus, $priorityPlus);

        if (($acceptSub === '*' || $prioritySub === '*' || $subEqual)
            && ($acceptPlus === '*' || $priorityPlus === '*' || $plusEqual)
            && count($intersection) === count($mediaRange['parameters'])
        ) {
            $score = 100 * $baseEqual + 10 * $subEqual + $plusEqual + count($intersection);
            return [
                'quality' => $mediaRange['quality'] * $preferredMediaRange['quality'],
                'score'   => $score,
                'index'   => $preferredMediaRangeIndex,
            ];
        }
        return null;
    }
}
