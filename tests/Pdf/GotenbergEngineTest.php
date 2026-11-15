<?php

declare(strict_types=1);

namespace LifeAstroApi\Tests\Pdf;

use LifeAstroApi\Pdf\Engine\GotenbergPdfEngine;
use LifeAstroApi\Pdf\Engine\PaperFormat;
use LifeAstroApi\Pdf\Engine\PdfException;
use LifeAstroApi\Pdf\Engine\PdfMargin;
use LifeAstroApi\Pdf\Engine\PdfOptions;
use PHPUnit\Framework\TestCase;

/** The Gotenberg engine sends the right form, against a local stand-in server (no Docker needed). */
final class GotenbergEngineTest extends TestCase
{
    /** @var resource|null */
    private static $server = null;

    private static string $baseUrl = '';

    public static function setUpBeforeClass(): void
    {
        $probe = stream_socket_server('tcp://127.0.0.1:0');
        if ($probe === false) {
            self::markTestSkipped('Cannot open a local port');
        }
        $name = (string) stream_socket_get_name($probe, false);
        fclose($probe);
        $cmd = [PHP_BINARY, '-S', $name, __DIR__ . '/../fixtures/gotenberg-server.php'];
        $process = proc_open($cmd, [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
        if (!\is_resource($process)) {
            self::markTestSkipped('Cannot start the PHP built-in server');
        }
        self::$server = $process;
        self::$baseUrl = "http://{$name}/gotenberg/";
        [$host, $port] = explode(':', $name);
        for ($i = 0; $i < 100; $i++) {
            $socket = @fsockopen($host, (int) $port, $errno, $error, 0.1);
            if ($socket !== false) {
                fclose($socket);

                return;
            }
            usleep(50_000);
        }
        self::markTestSkipped('The PHP built-in server did not start');
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$server !== null) {
            proc_terminate(self::$server);
            proc_close(self::$server);
            self::$server = null;
        }
    }

    /** @return array<string, mixed> */
    private static function decode(string $pdf): array
    {
        self::assertStringStartsWith('%PDF-test ', $pdf);
        $echo = json_decode(substr($pdf, 10), true);
        self::assertIsArray($echo);
        $out = [];
        foreach ($echo as $key => $value) {
            $out[(string) $key] = $value;
        }

        return $out;
    }

    public function testSendsTheHtmlAndThePageGeometry(): void
    {
        $engine = new GotenbergPdfEngine(self::$baseUrl, ['Authorization' => 'Basic dXNlcjpwYXNz']);
        $echo = self::decode($engine->htmlToPdf('<p>नमस्ते</p>', new PdfOptions(
            format: PaperFormat::Letter,
            landscape: true,
            margin: new PdfMargin(top: '25.4mm', left: '1in'),
        )));
        self::assertSame([
            'paperWidth' => '11',
            'paperHeight' => '8.5',
            'marginTop' => '1',
            'marginBottom' => '0',
            'marginLeft' => '1',
            'marginRight' => '0',
            'preferCssPageSize' => 'true',
            'printBackground' => 'true',
            'landscape' => 'false',
            'scale' => '1',
        ], $echo['fields']);
        self::assertSame(['index.html' => '<p>नमस्ते</p>'], $echo['files']);
        self::assertSame('Basic dXNlcjpwYXNz', $echo['auth']);
    }

    public function testSendsTheRunningHeaderAndFooterAsDocuments(): void
    {
        $engine = new GotenbergPdfEngine(rtrim(self::$baseUrl, '/'));
        $options = (new PdfOptions())->withHeaderFooter('<b>H</b>', '<i>F</i>', new PdfMargin('26mm', '16mm'));
        $echo = self::decode($engine->htmlToPdf('<p>x</p>', $options));
        $fields = $echo['fields'];
        self::assertIsArray($fields);
        self::assertSame('false', $fields['preferCssPageSize']);
        $files = $echo['files'];
        self::assertIsArray($files);
        self::assertSame(['index.html', 'header.html', 'footer.html'], array_keys($files));
        self::assertIsString($files['header.html']);
        self::assertStringContainsString('<body style="margin:0"><b>H</b></body>', $files['header.html']);
    }

    public function testAnErrorStatusThrows(): void
    {
        $engine = new GotenbergPdfEngine(self::$baseUrl . 'missing/');
        $this->expectException(PdfException::class);
        $this->expectExceptionMessage('Gotenberg returned 404');
        $engine->htmlToPdf('<p>x</p>');
    }
}
