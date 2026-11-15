<?php

declare(strict_types=1);

namespace LifeAstroApi\Pdf;

/**
 * Branding for a report: your logo, colours, company name, footer line and watermark. Every field
 * is optional; a field you set wins over the branding inside the report JSON (`Branding`), and
 * the rest falls back to it, then to the defaults.
 *
 * ```php
 * $branding = new Branding(companyName: 'Acme Astro', logoUrl: 'https://cdn.example/logo.png',
 *     primaryColor: '#6b21a8');
 * ```
 */
final class Branding
{
    /**
     * @param string|null $companyName Shown on the cover (and the Brihad running header).
     * @param string|null $logoUrl Logo image URL or a `data:image/...` URI, at the top of the cover.
     *     Only http(s), data:image and relative URLs are used.
     * @param string|null $primaryColor Accent colour (headings, table headers), e.g. `#6b21a8`.
     * @param string|null $secondaryColor Colour of meta text and the footer.
     * @param string|null $footerText Footer line on every page. Default "Generated using LifeAstroAPI".
     * @param string|null $watermarkText Diagonal watermark, e.g. `SAMPLE` or `DRAFT`.
     */
    public function __construct(
        public readonly ?string $companyName = null,
        public readonly ?string $logoUrl = null,
        public readonly ?string $primaryColor = null,
        public readonly ?string $secondaryColor = null,
        public readonly ?string $footerText = null,
        public readonly ?string $watermarkText = null,
    ) {
    }

    /**
     * From an array with the camelCase keys above (as the Node.js SDK takes them).
     *
     * @param array<string, mixed> $values
     */
    public static function fromArray(array $values): self
    {
        $get = static fn (string $key): ?string => isset($values[$key]) && \is_string($values[$key])
            ? $values[$key]
            : null;

        return new self(
            $get('companyName'),
            $get('logoUrl'),
            $get('primaryColor'),
            $get('secondaryColor'),
            $get('footerText'),
            $get('watermarkText'),
        );
    }

    /**
     * Accepts a Branding, a camelCase array or null.
     *
     * @param Branding|array<string, mixed>|null $branding
     *
     * @internal
     */
    public static function of(self|array|null $branding): ?self
    {
        return \is_array($branding) ? self::fromArray($branding) : $branding;
    }
}
