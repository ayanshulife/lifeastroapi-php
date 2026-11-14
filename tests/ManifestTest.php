<?php

declare(strict_types=1);

namespace LifeAstroApi\Tests;

use LifeAstroApi\Resources\Resources;
use LifeAstroApi\Tests\Support\MockTransport;
use LifeAstroApi\Tool\Samples;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Every generated method, checked against the manifest and against the website's verified
 * examples (tests/fixtures/examples.json): each method is called once through a mock transport
 * and must send every parameter the API marks as required for that endpoint.
 *
 * @phpstan-import-type Entry from Samples
 */
final class ManifestTest extends TestCase
{
    /** Methods in the Node.js SDK 0.9.1 (and the Python and Dart SDKs). */
    private const NODE_METHOD_COUNT = 322;

    /** Params the examples list as required that the SDK leaves out on purpose. */
    private const NOT_REALLY_REQUIRED = [
        // The handler reads `tz` only together with `date` (the transit moment); like the Node
        // SDK, transit.sadeSati sends the natal chart only.
        '/v1/transit/sade-sati' => ['tz'],
    ];

    /** @return iterable<string, array{Entry}> */
    public static function entries(): iterable
    {
        foreach (Samples::manifest() as $entry) {
            yield $entry['key'] => [$entry];
        }
    }

    public function testOneMethodPerManifestEntry(): void
    {
        $manifest = Samples::manifest();
        self::assertCount(self::NODE_METHOD_COUNT, $manifest);
        self::assertCount(self::NODE_METHOD_COUNT, array_unique(array_column($manifest, 'key')));
    }

    public function testTheResourceGroupsHoldExactlyTheManifestMethods(): void
    {
        $client = self::client(new MockTransport());
        $found = [];
        $groups = (new \ReflectionClass(Resources::class))->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($groups as $group) {
            $object = $group->getValue($client);
            self::assertIsObject($object);
            $this->collect($object, $group->getName(), $found);
        }
        sort($found);
        $expected = array_column(Samples::manifest(), 'key');
        sort($expected);
        self::assertSame($expected, $found);
    }

    /** @param list<string> $found */
    private function collect(object $object, string $prefix, array &$found): void
    {
        $class = new \ReflectionClass($object);
        foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getName() !== '__construct' && $method->getDeclaringClass()->getName() === $class->getName()) {
                $found[] = $prefix . '.' . $method->getName();
            }
        }
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $child = $property->getValue($object);
            self::assertIsObject($child);
            $this->collect($child, $prefix . '.' . $property->getName(), $found);
        }
    }

    /** @param Entry $entry */
    #[DataProvider('entries')]
    public function testEveryMethodHasDocsWithItsCostAndPath(array $entry): void
    {
        $method = self::reflect($entry['key']);
        $doc = (string) $method->getDocComment();
        self::assertNotNull($entry['cost'], $entry['path']);
        self::assertStringContainsString("Cost: {$entry['cost']} credit", $doc);
        self::assertStringContainsString("`GET {$entry['path']}`", $doc);
        self::assertStringContainsString('@return array<mixed>', $doc);
        $returnType = $method->getReturnType();
        self::assertInstanceOf(\ReflectionNamedType::class, $returnType);
        self::assertSame('array', $returnType->getName());
        foreach ($method->getParameters() as $param) {
            self::assertTrue($param->hasType(), "{$entry['key']} \${$param->getName()} has a native type");
        }
    }

    /** @param Entry $entry */
    #[DataProvider('entries')]
    public function testSendsTheRequiredParams(array $entry): void
    {
        [$args, $missing] = Samples::arguments($entry);
        self::assertSame([], $missing, 'no sample value');

        $t = new MockTransport();
        Samples::call(self::client($t), $entry, $args);
        $pattern = '#^' . implode('[^/]+', array_map(
            static fn (string $part): string => preg_quote($part, '#'),
            preg_split('/\{[^}]+\}/', $entry['path']) ?: [],
        )) . '$#';
        self::assertMatchesRegularExpression($pattern, $t->lastPath());

        $example = self::exampleFor($entry['path']);
        if ($example === null) {
            return;
        }
        $sent = array_keys($t->lastQuery());
        $skip = self::NOT_REALLY_REQUIRED[$entry['path']] ?? [];
        foreach ($example['required'] as $name) {
            if (str_contains($entry['path'], '{' . $name . '}') || \in_array($name, $skip, true)) {
                continue;
            }
            self::assertContains($name, $sent, "{$entry['key']} ({$entry['path']}) must send {$name}");
        }
    }

    /** @return iterable<string, array{Entry}> */
    public static function entriesWithLang(): iterable
    {
        foreach (Samples::manifest() as $entry) {
            if (Samples::hasParam($entry, 'lang')) {
                yield $entry['key'] => [$entry];
            }
        }
    }

    /** @param Entry $entry */
    #[DataProvider('entriesWithLang')]
    public function testLangReachesTheWire(array $entry): void
    {
        [$args] = Samples::arguments($entry);
        $t = new MockTransport();
        Samples::call(self::client($t), $entry, ['lang' => 'bn'] + $args);
        self::assertSame('bn', $t->lastQuery()['lang'] ?? null);
    }

    private static function reflect(string $key): \ReflectionMethod
    {
        $parts = explode('.', $key);
        $method = (string) array_pop($parts);
        $target = self::client(new MockTransport());
        foreach ($parts as $part) {
            $target = $target->{$part};
            self::assertIsObject($target);
        }

        return new \ReflectionMethod($target, $method);
    }

    /** @return array{required: list<string>}|null */
    private static function exampleFor(string $path): ?array
    {
        $examples = self::examples();
        $norm = static fn (string $p): string => (string) preg_replace('/\{[^}]+\}/', '{}', $p);
        $found = $examples[$path] ?? null;
        if ($found === null) {
            foreach ($examples as $template => $example) {
                if ($norm($template) === $norm($path)) {
                    $found = $example;
                    break;
                }
                if (str_contains($template, '{')) {
                    $regex = '#^' . implode('[^/]+', array_map(
                        static fn (string $part): string => preg_quote($part, '#'),
                        preg_split('/\{[^}]+\}/', $template) ?: [],
                    )) . '$#';
                    if (preg_match($regex, $path) === 1) {
                        $found = $example;
                        break;
                    }
                }
            }
        }

        return $found === null ? null : ['required' => $found];
    }

    /**
     * The `required` list of every example, by path.
     *
     * @return array<string, list<string>>
     */
    private static function examples(): array
    {
        static $examples = null;
        if (\is_array($examples)) {
            /** @var array<string, list<string>> $examples */
            return $examples;
        }
        $decoded = json_decode((string) file_get_contents(__DIR__ . '/fixtures/examples.json'), true);
        $examples = [];
        foreach (\is_array($decoded) ? $decoded : [] as $path => $example) {
            $required = \is_array($example) && \is_array($example['required'] ?? null) ? $example['required'] : [];
            $examples[(string) $path] = array_values(array_filter($required, '\is_string'));
        }

        return $examples;
    }
}
