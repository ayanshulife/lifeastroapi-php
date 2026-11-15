<?php

declare(strict_types=1);

namespace LifeAstroApi\Pdf\Engine;

/**
 * Prints a complete HTML document (from a `Reports::render*Html()` call) to PDF bytes. The SDK
 * ships {@see ChromePdfEngine} (a headless Chrome, Chromium or Edge on your server) and
 * {@see GotenbergPdfEngine} (a Gotenberg service you run). Implement this interface to use
 * anything else.
 */
interface PdfEngine
{
    /**
     * @return string The PDF bytes.
     *
     * @throws PdfException When printing fails.
     */
    public function htmlToPdf(string $html, ?PdfOptions $options = null): string;

    /** Releases the engine (closes a browser the engine launched). Safe to call twice. */
    public function close(): void;
}
