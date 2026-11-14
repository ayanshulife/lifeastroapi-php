<?php

declare(strict_types=1);

namespace LifeAstroApi\Tool;

use LifeAstroApi\Http\Query;
use LifeAstroApi\LifeAstro;

/**
 * Sample arguments for every SDK method, built from tool/samples.json and the resolved manifest
 * (tool/manifest.json, written by tool/generate.mjs). Used by the live sweep and the tests;
 * tool/node-goldens.mjs builds the same inputs for the Node SDK.
 *
 * @phpstan-type Param array{name: string, type: string, required: bool, kind: string,
 *     wires?: list<string>, prefix?: string, place?: bool}
 * @phpstan-type Extra array{wire: string, field: string, required: bool}
 * @phpstan-type Entry array{key: string, path: string, shape: string, cost: int|null,
 *     extras: list<Extra>, params: list<Param>}
 */
final class Samples
{
    /** @var array<string, mixed>|null */
    private static ?array $samples = null;

    /** @var list<Entry>|null */
    private static ?array $manifest = null;

    /** @return list<Entry> */
    public static function manifest(): array
    {
        if (self::$manifest === null) {
            /** @var list<Entry> $entries */
            $entries = self::readJson(__DIR__ . '/manifest.json');
            self::$manifest = $entries;
        }

        return self::$manifest;
    }

    /**
     * Manifest entries, optionally without paths starting with one of `$skip` and only paths
     * containing one of `$only`.
     *
     * @param list<string> $skip
     * @param list<string> $only
     *
     * @return list<Entry>
     */
    public static function entries(array $skip = [], array $only = []): array
    {
        $out = [];
        foreach (self::manifest() as $entry) {
            foreach ($skip as $prefix) {
                if (str_starts_with($entry['path'], $prefix)) {
                    continue 2;
                }
            }
            if ($only !== []) {
                $hit = false;
                foreach ($only as $needle) {
                    $hit = $hit || str_contains($entry['path'], $needle);
                }
                if (!$hit) {
                    continue;
                }
            }
            $out[] = $entry;
        }

        return $out;
    }

    /** @param Entry $entry */
    public static function hasParam(array $entry, string $name): bool
    {
        foreach ($entry['params'] as $param) {
            if ($param['name'] === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Named arguments for one manifest entry, and the samples that are missing.
     *
     * @param Entry $entry
     *
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    public static function arguments(array $entry): array
    {
        $samples = self::samples();
        $input = self::table($samples, 'shapes')[$entry['shape']] ?? [];
        if (!\is_array($input)) {
            $input = [];
        }
        $pathSamples = self::table($samples, 'path');
        $wireSamples = self::table($samples, 'wire');
        $missing = [];
        foreach ($entry['params'] as $param) {
            if ($param['kind'] !== 'path') {
                continue;
            }
            if (\array_key_exists($param['name'], $pathSamples)) {
                $input[$param['name']] = $pathSamples[$param['name']];
            } else {
                $missing[] = 'path:' . $param['name'];
            }
        }
        foreach ($entry['extras'] as $extra) {
            if (!$extra['required']) {
                continue;
            }
            if (\array_key_exists($extra['wire'], $wireSamples)) {
                $input[$extra['field']] = $wireSamples[$extra['wire']];
            } else {
                $missing[] = 'extra:' . $extra['wire'];
            }
        }
        $perPath = self::table($samples, 'perPath')[$entry['path']] ?? [];
        foreach (\is_array($perPath) ? $perPath : [] as $name => $value) {
            $input[$name] = $value;
        }
        $missing = array_values(array_filter(
            $missing,
            static fn (string $m): bool => !(str_starts_with($m, 'path:') && isset($input[substr($m, 5)])),
        ));

        $args = [];
        foreach ($entry['params'] as $param) {
            if (\array_key_exists($param['name'], $input)) {
                $args[$param['name']] = self::coerce($input[$param['name']], $param['type']);
            }
        }

        return [$args, $missing];
    }

    /**
     * Call the SDK method of a manifest entry (`western.solarArc.directions` ...).
     *
     * @param Entry $entry
     * @param array<string, mixed> $args
     *
     * @return array<mixed>
     */
    public static function call(LifeAstro $client, array $entry, array $args): array
    {
        $parts = explode('.', $entry['key']);
        $method = array_pop($parts);
        $target = $client;
        foreach ($parts as $part) {
            $target = $target->{$part};
            if (!\is_object($target)) {
                throw new \LogicException("No resource group {$part} for {$entry['key']}");
            }
        }
        $callable = [$target, $method];
        if (!\is_callable($callable)) {
            throw new \LogicException("No method {$entry['key']} on the client");
        }
        $result = $callable(...$args);
        if (!\is_array($result)) {
            throw new \LogicException("{$entry['key']} did not return an array");
        }

        return $result;
    }

    /** A JSON-friendly copy of the arguments, for messages. */
    public static function describe(mixed $args): string
    {
        return (string) json_encode($args, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** Sample values are JSON; convert them to the PHP type the parameter declares. */
    private static function coerce(mixed $value, string $type): mixed
    {
        return match ($type) {
            'float' => \is_numeric($value) ? (float) $value : $value,
            'int' => \is_numeric($value) ? (int) $value : $value,
            'string', 'Lang', 'Ayanamsa', 'HouseSystem' => \is_scalar($value) ? Query::toWire($value) : $value,
            default => $value,
        };
    }

    /** @return array<string, mixed> */
    private static function samples(): array
    {
        if (self::$samples === null) {
            $data = self::readJson(__DIR__ . '/samples.json');
            self::$samples = [];
            foreach (\is_array($data) ? $data : [] as $key => $value) {
                self::$samples[(string) $key] = $value;
            }
        }

        return self::$samples;
    }

    /**
     * @param array<string, mixed> $samples
     *
     * @return array<string, mixed>
     */
    private static function table(array $samples, string $name): array
    {
        $table = $samples[$name] ?? [];
        $out = [];
        foreach (\is_array($table) ? $table : [] as $key => $value) {
            $out[(string) $key] = $value;
        }

        return $out;
    }

    private static function readJson(string $file): mixed
    {
        $text = file_get_contents($file);
        if ($text === false) {
            throw new \RuntimeException("Cannot read {$file}; run `composer generate` first.");
        }

        return json_decode($text, true, 512, JSON_THROW_ON_ERROR);
    }
}
