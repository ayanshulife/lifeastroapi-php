<?php

declare(strict_types=1);

namespace LifeAstroApi\Pdf\Engine;

use LifeAstroApi\Http\Query;

/** Options for {@see PdfEngine::htmlToPdf()}, the same as the Node.js SDK's `htmlToPdf` options. */
final class PdfOptions
{
    /**
     * @param PaperFormat $format Paper size. The report templates also set A4 in their own CSS.
     * @param bool $landscape Landscape orientation.
     * @param bool $printBackground Print background colours and images (the templates are branded).
     * @param bool|null $preferCssPageSize Use the template's own `@page` size. Default true, and
     *     false when a running header/footer is on.
     * @param PdfMargin|null $margin Page margins. Default: the template's own.
     * @param bool $displayHeaderFooter Print `headerTemplate` / `footerTemplate` in the page margin.
     * @param string|null $headerTemplate Running header HTML (inline styles only). Chromium fills
     *     `<span class="pageNumber|totalPages|title|date|url">`.
     * @param string|null $footerTemplate Running footer HTML (inline styles only).
     * @param float|null $scale Render scale, 0.1 to 2.
     * @param float $timeout Seconds allowed for the page to load and print.
     */
    public function __construct(
        public readonly PaperFormat $format = PaperFormat::A4,
        public readonly bool $landscape = false,
        public readonly bool $printBackground = true,
        public readonly ?bool $preferCssPageSize = null,
        public readonly ?PdfMargin $margin = null,
        public readonly bool $displayHeaderFooter = false,
        public readonly ?string $headerTemplate = null,
        public readonly ?string $footerTemplate = null,
        public readonly ?float $scale = null,
        public readonly float $timeout = 30.0,
    ) {
    }

    /** A copy with a running header and footer (and the margin they need when none is set). */
    public function withHeaderFooter(string $header, string $footer, PdfMargin $defaultMargin): self
    {
        return new self(
            $this->format,
            $this->landscape,
            $this->printBackground,
            $this->preferCssPageSize,
            $this->margin ?? $defaultMargin,
            true,
            $header,
            $footer,
            $this->scale,
            $this->timeout,
        );
    }

    /**
     * Paper width and height in inches, after orientation.
     *
     * @return array{0: float, 1: float}
     */
    public function paperInches(): array
    {
        $w = $this->format->widthInches();
        $h = $this->format->heightInches();

        return $this->landscape ? [$h, $w] : [$w, $h];
    }

    /** Whether the template's `@page` size wins (never with a running header/footer). */
    public function effectivePreferCssPageSize(): bool
    {
        return $this->displayHeaderFooter ? false : ($this->preferCssPageSize ?? true);
    }

    /**
     * The margins in inches: top, bottom, left, right.
     *
     * @return array{top: float, bottom: float, left: float, right: float}
     */
    public function marginInches(): array
    {
        $m = $this->margin;

        return [
            'top' => self::cssLengthToInches($m?->top),
            'bottom' => self::cssLengthToInches($m?->bottom),
            'left' => self::cssLengthToInches($m?->left),
            'right' => self::cssLengthToInches($m?->right),
        ];
    }

    /**
     * A CSS length ('26mm', '1cm', '0.5in', '20px', '20') in inches, as Puppeteer reads it.
     * Null or empty gives 0.
     *
     * @throws \InvalidArgumentException For anything else.
     */
    public static function cssLengthToInches(?string $value): float
    {
        if ($value === null || trim($value) === '') {
            return 0.0;
        }
        if (preg_match('/^\s*(-?[0-9]*\.?[0-9]+)\s*(px|in|cm|mm)?\s*$/i', $value, $m) !== 1) {
            throw new \InvalidArgumentException("Not a CSS length: {$value}");
        }
        $n = (float) $m[1];

        return match (strtolower($m[2] ?? 'px')) {
            'in' => $n,
            'cm' => $n / 2.54,
            'mm' => $n / 25.4,
            default => $n / 96,
        };
    }

    /** A number as text for a form field or JSON, the way JavaScript prints it. */
    public static function number(float $n): string
    {
        return Query::formatFloat($n);
    }
}
