<?php

declare(strict_types=1);

namespace LifeAstroApi;

/**
 * Sidereal ayanamsa systems. Vedic methods take `ayanamsa` as one of these cases or its plain
 * value (`Ayanamsa::Krishnamurti` or `'krishnamurti'`). The API uses Lahiri when it is left out.
 */
enum Ayanamsa: string
{
    case Lahiri = 'lahiri';
    case Krishnamurti = 'krishnamurti';
    case Raman = 'raman';
    case Fagan = 'fagan';
    case Yukteshwar = 'yukteshwar';
    case TrueChitrapaksha = 'true_chitrapaksha';
}
