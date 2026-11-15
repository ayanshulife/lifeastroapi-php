<?php

declare(strict_types=1);

namespace LifeAstroApi\Pdf\Engine;

/** Page margins as CSS lengths: '10mm', '1cm', '0.5in', '20px'. Unset sides are 0. */
final class PdfMargin
{
    public function __construct(
        public readonly ?string $top = null,
        public readonly ?string $bottom = null,
        public readonly ?string $left = null,
        public readonly ?string $right = null,
    ) {
    }
}
