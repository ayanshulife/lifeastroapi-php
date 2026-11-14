<?php

declare(strict_types=1);

namespace LifeAstroApi\Http;

/**
 * Query helpers used by the generated resource methods. The wire names they write (`birth.lat`,
 * `boy.date`, `start_date`, ...) are the contract with the API and match `src/params.ts` and
 * `encodeQuery` of the Node.js SDK: empty values are left out, keys are sorted, booleans are
 * `true` / `false` and numbers are written the way JavaScript writes them.
 *
 * @internal
 */
final class Query
{
    /** Fields of a birth array (`boy`, `girl`, `birth`, `personA`, `personB`). */
    public const BIRTH_FIELDS = ['date', 'time', 'tz', 'lat', 'lon'];

    /** Characters `encodeURIComponent` leaves as they are and `rawurlencode` does not. */
    private const URI_COMPONENT_SAFE = ['%21' => '!', '%27' => "'", '%28' => '(', '%29' => ')', '%2A' => '*'];

    private function __construct()
    {
    }

    /**
     * Set `$key` only when `$value` is meaningful: null and '' are skipped, 0 and false are kept.
     *
     * @param array<string, mixed> $query
     * @param-out array<string, mixed> $query
     */
    public static function put(array &$query, string $key, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }
        $query[$key] = $value;
    }

    /**
     * Copy the fields of a birth array onto prefixed wire names: `$boy['date']` -> `boy.date`.
     *
     * @param array<string, mixed> $query
     * @param-out array<string, mixed> $query
     * @param array<mixed>|null $fields
     * @param list<string> $names
     */
    public static function putFields(
        array &$query,
        string $prefix,
        ?array $fields,
        array $names = self::BIRTH_FIELDS,
    ): void {
        if ($fields === null) {
            return;
        }
        foreach ($names as $name) {
            self::put($query, $prefix . $name, $fields[$name] ?? null);
        }
    }

    /**
     * Fill the `{name}` placeholders of a path template, percent-encoding each value like
     * JavaScript's `encodeURIComponent`.
     *
     * @param array<string, int|string|\BackedEnum> $values
     *
     * @throws \InvalidArgumentException When a value is empty.
     */
    public static function path(string $template, array $values): string
    {
        $path = $template;
        foreach ($values as $name => $value) {
            $text = self::toWire($value);
            if ($text === '') {
                throw new \InvalidArgumentException(
                    sprintf('Missing required path parameter "%s" for %s', $name, $template)
                );
            }
            $path = str_replace('{' . $name . '}', strtr(rawurlencode($text), self::URI_COMPONENT_SAFE), $path);
        }

        return $path;
    }

    /**
     * Today's date (`YYYY-MM-DD`) in `$tz`. The API has no "today" default: every moment endpoint
     * rejects a request without `date`, so the SDK sends one. Falls back to the UTC date when
     * `$tz` is not a zone PHP knows (an IANA name such as `Asia/Kolkata`, or `+05:30`).
     */
    public static function todayIn(?string $tz, ?\DateTimeImmutable $now = null): string
    {
        $now ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        if ($tz !== null && $tz !== '') {
            try {
                return $now->setTimezone(new \DateTimeZone($tz))->format('Y-m-d');
            } catch (\Exception) {
                // Unknown zone name, or a form such as "5.5" that PHP does not accept.
            }
        }

        return $now->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d');
    }

    /**
     * Encode query parameters into `?a=1&b=2` (or '' when nothing is left), with the keys sorted
     * and null / '' values skipped, as the Node.js SDK's `encodeQuery` does.
     *
     * @param array<array-key, mixed> $query
     */
    public static function encode(array $query): string
    {
        ksort($query, SORT_STRING);
        $pairs = [];
        foreach ($query as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $pairs[] = self::formEncode((string) $key) . '=' . self::formEncode(self::toWire($value));
        }

        return $pairs === [] ? '' : '?' . implode('&', $pairs);
    }

    /**
     * The text a value is sent as: booleans `true` / `false`, numbers as JavaScript's `String(n)`,
     * enums by their value.
     *
     * @throws \InvalidArgumentException For arrays and other values that have no query form.
     */
    public static function toWire(mixed $value): string
    {
        return match (true) {
            \is_string($value) => $value,
            \is_bool($value) => $value ? 'true' : 'false',
            \is_int($value) => (string) $value,
            \is_float($value) => self::formatFloat($value),
            $value instanceof \BackedEnum => (string) $value->value,
            $value instanceof \Stringable => (string) $value,
            default => throw new \InvalidArgumentException(
                'A query value must be a string, number, bool or enum; got ' . get_debug_type($value)
            ),
        };
    }

    /**
     * A float as JavaScript's `Number.prototype.toString` writes it: `19.076`, `5` (not `5.0`),
     * `0.000001`, `1e-7`, `1.5e+21`.
     */
    public static function formatFloat(float $value): string
    {
        if (is_nan($value)) {
            return 'NaN';
        }
        if (is_infinite($value)) {
            return $value > 0 ? 'Infinity' : '-Infinity';
        }
        if ($value == 0.0) {
            return '0';
        }

        // Shortest round-trip digits (serialize_precision -1), e.g. "19.076", "1.0E-7", "1.5E+21".
        $repr = var_export(abs($value), true);
        $exponent = 0;
        $epos = stripos($repr, 'e');
        if ($epos !== false) {
            $exponent = (int) substr($repr, $epos + 1);
            $repr = substr($repr, 0, $epos);
        }
        $dot = strpos($repr, '.');
        $intPart = $dot === false ? $repr : substr($repr, 0, $dot);
        $fracPart = $dot === false ? '' : substr($repr, $dot + 1);
        $all = $intPart . $fracPart;
        $trimmed = ltrim($all, '0');
        $leading = \strlen($all) - \strlen($trimmed);
        $digits = rtrim($trimmed, '0');
        if ($digits === '') {
            return '0';
        }
        // value = 0.<digits> x 10^n
        $n = \strlen($intPart) + $exponent - $leading;
        $k = \strlen($digits);
        $sign = $value < 0 ? '-' : '';

        if ($k <= $n && $n <= 21) {
            return $sign . $digits . str_repeat('0', $n - $k);
        }
        if (0 < $n && $n <= 21) {
            return $sign . substr($digits, 0, $n) . '.' . substr($digits, $n);
        }
        if (-6 < $n && $n <= 0) {
            return $sign . '0.' . str_repeat('0', -$n) . $digits;
        }
        $e = $n - 1;
        $mantissa = $k === 1 ? $digits : $digits[0] . '.' . substr($digits, 1);

        return $sign . $mantissa . 'e' . ($e < 0 ? '-' : '+') . abs($e);
    }

    /** application/x-www-form-urlencoded, byte for byte as JavaScript's URLSearchParams. */
    private static function formEncode(string $text): string
    {
        return str_replace('%2A', '*', urlencode($text));
    }
}
