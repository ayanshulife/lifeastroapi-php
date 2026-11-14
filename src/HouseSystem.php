<?php

declare(strict_types=1);

namespace LifeAstroApi;

/**
 * House systems. Chart methods take `houseSystem` as one of these cases or its plain value
 * (`HouseSystem::Placidus` or `'placidus'`); it is sent as `house_system`.
 */
enum HouseSystem: string
{
    case WholeSign = 'whole-sign';
    case Equal = 'equal';
    case Placidus = 'placidus';
    case Koch = 'koch';
    case Porphyry = 'porphyry';
    case Campanus = 'campanus';
    case Regiomontanus = 'regiomontanus';
}
