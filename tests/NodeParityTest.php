<?php

declare(strict_types=1);

namespace LifeAstroApi\Tests;

use LifeAstroApi\Tests\Support\MockTransport;
use LifeAstroApi\Tool\Samples;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The PHP SDK sends, for every method, the same path and query string as the Node.js SDK with
 * the same inputs. The Node side is recorded by tool/node-goldens.mjs into
 * tests/fixtures/node-requests.json (rerun it after `composer generate`).
 *
 * @phpstan-import-type Entry from Samples
 */
final class NodeParityTest extends TestCase
{
    /** @var array<string, array{url: string, lang_url?: string}>|null */
    private static ?array $golden = null;

    /** @return iterable<string, array{Entry}> */
    public static function entries(): iterable
    {
        foreach (Samples::manifest() as $entry) {
            yield $entry['key'] => [$entry];
        }
    }

    public function testGoldensCoverEveryMethod(): void
    {
        self::assertSame(
            array_column(Samples::manifest(), 'key'),
            array_keys(self::golden()),
            'rerun `composer goldens` after `composer generate`',
        );
    }

    /** @param Entry $entry */
    #[DataProvider('entries')]
    public function testSendsTheSameRequestAsNode(array $entry): void
    {
        $golden = self::golden()[$entry['key']] ?? null;
        self::assertNotNull($golden, 'no Node golden; run `composer goldens`');
        [$args] = Samples::arguments($entry);

        $t = new MockTransport();
        Samples::call(self::client($t), $entry, $args);
        self::assertSame(self::normalize($entry, $golden['url']), self::normalize($entry, $t->lastPathAndQuery()));

        if (Samples::hasParam($entry, 'lang')) {
            $langUrl = $golden['lang_url'] ?? null;
            self::assertNotNull($langUrl, 'Node sent no lang for this method');
            Samples::call(self::client($t), $entry, ['lang' => 'bn'] + $args);
            self::assertSame(self::normalize($entry, $langUrl), self::normalize($entry, $t->lastPathAndQuery()));
        } else {
            self::assertArrayNotHasKey('lang_url', $golden);
        }
    }

    /**
     * Location-less moments default to today's date, which differs between the day the goldens
     * were recorded and today.
     *
     * @param Entry $entry
     */
    private static function normalize(array $entry, string $url): string
    {
        if ($entry['shape'] !== 'instant') {
            return $url;
        }

        return (string) preg_replace('/([?&])date=\d{4}-\d{2}-\d{2}/', '$1date=TODAY', $url);
    }

    /** @return array<string, array{url: string, lang_url?: string}> */
    private static function golden(): array
    {
        if (self::$golden === null) {
            $data = json_decode((string) file_get_contents(__DIR__ . '/fixtures/node-requests.json'), true);
            $methods = \is_array($data) && \is_array($data['methods'] ?? null) ? $data['methods'] : [];
            self::$golden = [];
            foreach ($methods as $key => $record) {
                if (!\is_array($record) || !\is_string($record['url'] ?? null)) {
                    continue;
                }
                $row = ['url' => $record['url']];
                if (\is_string($record['lang_url'] ?? null)) {
                    $row['lang_url'] = $record['lang_url'];
                }
                self::$golden[(string) $key] = $row;
            }
        }

        return self::$golden;
    }
}
