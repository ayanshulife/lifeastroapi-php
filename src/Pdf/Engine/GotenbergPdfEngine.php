<?php

declare(strict_types=1);

namespace LifeAstroApi\Pdf\Engine;

/**
 * Prints through a Gotenberg service (https://gotenberg.dev), which runs Chromium for you. Useful
 * when your PHP server cannot run a browser (shared hosting, small containers): run Gotenberg
 * anywhere you control, for example `docker run --rm -p 3000:3000 gotenberg/gotenberg:8`.
 *
 * ```php
 * $engine = new GotenbergPdfEngine('http://localhost:3000');
 * $pdf = Reports::kundliLitePdf($report, engine: $engine);
 * ```
 *
 * The official image includes Noto fonts for the Indic scripts the reports use.
 */
final class GotenbergPdfEngine implements PdfEngine
{
    /**
     * @param string $baseUrl The Gotenberg base URL, for example `http://localhost:3000`.
     * @param array<string, string> $headers Extra request headers, for example basic auth.
     */
    public function __construct(
        private readonly string $baseUrl,
        private readonly array $headers = [],
    ) {
        if (!\extension_loaded('curl')) {
            throw new PdfException('GotenbergPdfEngine needs the cURL extension (ext-curl).');
        }
    }

    public function htmlToPdf(string $html, ?PdfOptions $options = null): string
    {
        $options ??= new PdfOptions();
        [$width, $height] = $options->paperInches();
        $margin = $options->marginInches();
        $n = PdfOptions::number(...);
        $fields = [
            'paperWidth' => $n($width),
            'paperHeight' => $n($height),
            'marginTop' => $n($margin['top']),
            'marginBottom' => $n($margin['bottom']),
            'marginLeft' => $n($margin['left']),
            'marginRight' => $n($margin['right']),
            'preferCssPageSize' => $options->effectivePreferCssPageSize() ? 'true' : 'false',
            'printBackground' => $options->printBackground ? 'true' : 'false',
            // Orientation is already in paperWidth / paperHeight.
            'landscape' => 'false',
            'scale' => $n($options->scale ?? 1.0),
            'files[0]' => new \CURLStringFile($html, 'index.html', 'text/html'),
        ];
        if ($options->displayHeaderFooter) {
            $fields['files[1]'] = new \CURLStringFile(
                self::document($options->headerTemplate ?? '<span></span>'),
                'header.html',
                'text/html',
            );
            $fields['files[2]'] = new \CURLStringFile(
                self::document($options->footerTemplate ?? '<span></span>'),
                'footer.html',
                'text/html',
            );
        }

        $url = rtrim($this->baseUrl, '/') . '/forms/chromium/convert/html';
        $handle = curl_init($url);
        if ($handle === false) {
            throw new PdfException('Could not initialise cURL');
        }
        $headers = [];
        foreach ($this->headers as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }
        $timeoutMs = (int) ceil(max($options->timeout, 1.0) * 4 * 1000);
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS => $timeoutMs,
            CURLOPT_CONNECTTIMEOUT_MS => min($timeoutMs, 10_000),
            CURLOPT_NOSIGNAL => true,
        ]);
        $body = curl_exec($handle);
        if (!\is_string($body)) {
            throw new PdfException(sprintf('Gotenberg request failed: %s', curl_error($handle)));
        }
        $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        if ($status !== 200) {
            throw new PdfException(sprintf('Gotenberg returned %d: %s', $status, substr($body, 0, 500)));
        }

        return $body;
    }

    public function close(): void
    {
    }

    /** Gotenberg wants the header and footer as complete HTML documents. */
    private static function document(string $fragment): string
    {
        return '<!doctype html><html><head><meta charset="utf-8"></head>'
            . '<body style="margin:0">' . $fragment . '</body></html>';
    }
}
