<?php

declare(strict_types=1);

namespace LifeAstroApi\Pdf;

use LifeAstroApi\Http\Query;

/**
 * JavaScript semantics used by the report templates.
 *
 * The templates are a port of the Node.js SDK's TypeScript templates and must produce
 * byte-identical HTML (tests/Pdf/PdfGoldenTest.php). These helpers give the JS behaviours that
 * differ from PHP: truthiness, `||`, `String(x)`, `Math.round`, `toFixed`, `Object.keys` order and
 * loose JSON access.
 *
 * Regex note for ports: PHP's `/u` modifier also turns on Unicode `\w`, `\d` and `\b`, while
 * JavaScript keeps them ASCII. Write `[0-9]`, `[A-Za-z0-9_]` and {@see self::WB} instead.
 *
 * @internal
 */
final class Js
{
    /** JavaScript's ASCII `\b` (word boundary), for patterns that also use the `u` modifier. */
    public const WB = '(?:(?<=[A-Za-z0-9_])(?![A-Za-z0-9_])|(?<![A-Za-z0-9_])(?=[A-Za-z0-9_]))';

    private function __construct()
    {
    }

    /** JS truthiness: null, false, 0, NaN and '' are falsy; every array (object) is truthy. */
    public static function truthy(mixed $v): bool
    {
        if ($v === null || $v === false || $v === '') {
            return false;
        }
        if (\is_int($v)) {
            return $v !== 0;
        }
        if (\is_float($v)) {
            return $v != 0.0 && !is_nan($v);
        }

        return true;
    }

    /** JS `a || b`. */
    public static function or(mixed $a, mixed $b): mixed
    {
        return self::truthy($a) ? $a : $b;
    }

    /**
     * JS `String(v)` (and a template literal's `${v}`). A missing JSON field (null here) prints as
     * `undefined` in JS; pass `$nullAs` when the original printed something else.
     */
    public static function str(mixed $v, string $nullAs = 'undefined'): string
    {
        return match (true) {
            $v === null => $nullAs,
            \is_string($v) => $v,
            \is_bool($v) => $v ? 'true' : 'false',
            \is_int($v) => (string) $v,
            \is_float($v) => Query::formatFloat($v),
            \is_array($v) => array_is_list($v)
                ? implode(',', array_map(static fn (mixed $e): string => $e === null ? '' : self::str($e), $v))
                : '[object Object]',
            default => '',
        };
    }

    /** JS `Math.round`: halves round up (towards +infinity). */
    public static function round(float|int $x): int
    {
        return (int) floor($x + 0.5);
    }

    /**
     * JS `n.toFixed(digits)`: the exact binary value rounded half up (PHP's sprintf rounds half to
     * even, so `0.25` would print `0.2` instead of JS's `0.3`).
     */
    public static function toFixed(float|int $n, int $digits): string
    {
        $n = (float) $n;
        if (is_nan($n)) {
            return 'NaN';
        }
        if (is_infinite($n)) {
            return $n > 0 ? 'Infinity' : '-Infinity';
        }
        if (abs($n) >= 1e21) {
            return self::str($n);
        }
        $negative = $n < 0;
        // The exact decimal expansion (a double has at most 52 fraction bits, so 53 places are exact
        // for |n| >= 1 and plenty to decide the rounding below that).
        $exact = sprintf('%.53F', abs($n));
        [$int, $frac] = explode('.', $exact);
        $keep = $int . substr($frac, 0, $digits);
        $next = $frac[$digits] ?? '0';
        if ($next >= '5') {
            // Round half up on the digit string.
            $chars = str_split($keep);
            for ($i = \count($chars) - 1; $i >= 0; $i--) {
                if ($chars[$i] === '9') {
                    $chars[$i] = '0';
                    continue;
                }
                $chars[$i] = (string) ((int) $chars[$i] + 1);
                break;
            }
            $keep = ($i < 0 ? '1' : '') . implode('', $chars);
        }
        $intLen = \strlen($keep) - $digits;
        $out = $digits > 0 ? substr($keep, 0, $intLen) . '.' . substr($keep, $intLen) : $keep;

        // JS keeps the sign of any negative number, even when it rounds to zero ("-0.00").
        return ($negative ? '-' : '') . $out;
    }

    /** JS `s.trim()`: also strips Unicode spaces, NBSP, U+FEFF and the line separators. */
    public static function trim(string $s): string
    {
        $out = preg_replace('/^[\s\x{FEFF}]+|[\s\x{FEFF}]+$/uD', '', $s);

        // A text PCRE cannot handle (very large, or not UTF-8) is returned untrimmed, never lost.
        return $out ?? $s;
    }

    /** JS `String(n).padStart(width, pad)`. */
    public static function padStart(mixed $v, int $width, string $pad = ' '): string
    {
        $s = self::str($v);
        $len = mb_strlen($s);

        return $len >= $width ? $s : str_repeat($pad, $width - $len) . $s;
    }

    /**
     * JS `Object.keys` order: integer-like keys first (ascending), then the rest in insertion order.
     *
     * @param array<mixed> $m
     *
     * @return list<string>
     */
    public static function keys(array $m): array
    {
        $ints = [];
        $rest = [];
        foreach (array_keys($m) as $k) {
            $s = (string) $k;
            if (preg_match('/^(0|[1-9][0-9]{0,8})$/', $s) === 1) {
                $ints[] = $s;
            } else {
                $rest[] = $s;
            }
        }
        usort($ints, static fn (string $a, string $b): int => (int) $a <=> (int) $b);

        return [...$ints, ...$rest];
    }

    /**
     * The value as a JSON object (string keys), or an empty array.
     *
     * @return array<string, mixed>
     */
    public static function map(mixed $v): array
    {
        if (!\is_array($v) || ($v !== [] && array_is_list($v))) {
            return [];
        }
        $out = [];
        foreach ($v as $k => $value) {
            $out[(string) $k] = $value;
        }

        return $out;
    }

    /**
     * The value as a JSON object, or null when it is not one.
     *
     * @return array<string, mixed>|null
     */
    public static function mapOrNull(mixed $v): ?array
    {
        return \is_array($v) && ($v === [] || !array_is_list($v)) ? self::map($v) : null;
    }

    /**
     * The value as a JSON array, or an empty list.
     *
     * @return list<mixed>
     */
    public static function list(mixed $v): array
    {
        return \is_array($v) && array_is_list($v) ? $v : [];
    }

    /** JS `Array.isArray(v)`. An empty JSON object also decodes to [] in PHP and counts as an array. */
    public static function isList(mixed $v): bool
    {
        return \is_array($v) && array_is_list($v);
    }

    /** JS `Array.isArray(v) && v.length > 0`. */
    public static function nonEmptyList(mixed $v): bool
    {
        return \is_array($v) && $v !== [] && array_is_list($v);
    }

    /** The value when it is a string, else null. */
    public static function strOrNull(mixed $v): ?string
    {
        return \is_string($v) ? $v : null;
    }

    /** The value when it is a number, else null. */
    public static function numOrNull(mixed $v): int|float|null
    {
        return \is_int($v) || \is_float($v) ? $v : null;
    }

    /** JS `typeof v === "number"`. */
    public static function isNum(mixed $v): bool
    {
        return \is_int($v) || \is_float($v);
    }

    /** A value read as a number the way the templates do (`Number(v)`, NaN as null). */
    public static function toNumber(mixed $v): int|float|null
    {
        if (\is_int($v) || \is_float($v)) {
            return $v;
        }
        if (\is_string($v) && is_numeric(trim($v))) {
            return trim($v) + 0;
        }

        return null;
    }

    /**
     * `new Date(iso)` for the ISO-8601 strings the API sends: a date-only string is UTC midnight
     * (as in JS). Null when the string is not a date JS would read.
     */
    public static function parseDate(string $iso): ?\DateTimeImmutable
    {
        $s = trim($iso);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) === 1) {
            $s .= 'T00:00:00Z';
        }
        $iso8601 = '/^\d{4}-\d{2}-\d{2}(?:[T ]\d{2}:\d{2}(?::\d{2}(?:\.\d+)?)?)?(?:Z|[+-]\d{2}:?\d{2})?$/i';
        if (preg_match($iso8601, $s) !== 1) {
            return null;
        }
        try {
            return new \DateTimeImmutable($s, new \DateTimeZone('UTC'));
        } catch (\Exception) {
            return null;
        }
    }

    /** JS `Number.isNaN(new Date(iso).getTime())` is false, i.e. the string is a readable date. */
    public static function isDate(mixed $iso): bool
    {
        return \is_string($iso) && self::parseDate($iso) !== null;
    }

    /**
     * `YYYY-MM-DD HH:MM UTC` from an ISO-8601 timestamp, as the templates' `formatGenerated` does
     * with `new Date(iso)` (the kundliLite variant: '' for an empty value, the input when it is not
     * a date). Check a template's own variant before reusing this.
     */
    public static function formatGenerated(mixed $iso): string
    {
        if (!self::truthy($iso)) {
            return '';
        }
        $s = self::str($iso);
        $d = self::parseDate($s);

        return $d === null ? $s : $d->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i') . ' UTC';
    }
}
