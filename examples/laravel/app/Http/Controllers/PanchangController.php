<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LifeAstroApi\Exception\BadRequestException;
use LifeAstroApi\Exception\LifeAstroException;
use LifeAstroApi\Lang;
use LifeAstroApi\LifeAstro;

class PanchangController extends Controller
{
    public function __construct(private readonly LifeAstro $astro)
    {
    }

    /** GET /panchang?lat=23.0225&lon=72.5714&date=2027-01-15&lang=gu */
    public function show(Request $request): JsonResponse
    {
        $input = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'lang' => ['nullable', 'in:' . implode(',', array_column(Lang::cases(), 'value'))],
        ]);

        try {
            $day = $this->astro->panchang->basic(
                lat: (float) $input['lat'],
                lon: (float) $input['lon'],
                tz: 'Asia/Kolkata',
                date: $input['date'] ?? null,
                lang: $input['lang'] ?? Lang::En,
            );
        } catch (BadRequestException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (LifeAstroException $e) {
            report($e); // status, error code and request id are on the exception
            return response()->json(['error' => 'Panchang is not available right now.'], 503);
        }

        return response()->json([
            'tithi' => $day['tithi'] ?? null,
            'nakshatra' => $day['nakshatra'] ?? null,
            'vara' => $day['vara'] ?? null,
            'sunrise' => $day['sunrise_local'] ?? null,
            'sunset' => $day['sunset_local'] ?? null,
        ]);
    }
}
