<?php

declare(strict_types=1);

namespace LifeAstroApi\Pdf\Engine;

/** Paper sizes, as Puppeteer defines them. The report templates use A4. */
enum PaperFormat: string
{
    case A3 = 'A3';
    case A4 = 'A4';
    case A5 = 'A5';
    case Letter = 'Letter';
    case Legal = 'Legal';
    case Tabloid = 'Tabloid';

    /** Width in inches (portrait). */
    public function widthInches(): float
    {
        return match ($this) {
            self::A3 => 11.6929,
            self::A4 => 8.2677,
            self::A5 => 5.8268,
            self::Letter, self::Legal => 8.5,
            self::Tabloid => 11.0,
        };
    }

    /** Height in inches (portrait). */
    public function heightInches(): float
    {
        return match ($this) {
            self::A3 => 16.5354,
            self::A4 => 11.6929,
            self::A5 => 8.2677,
            self::Letter => 11.0,
            self::Legal => 14.0,
            self::Tabloid => 17.0,
        };
    }
}
