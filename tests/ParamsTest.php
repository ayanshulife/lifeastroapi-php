<?php

declare(strict_types=1);

namespace LifeAstroApi\Tests;

use LifeAstroApi\Ayanamsa;
use LifeAstroApi\HouseSystem;
use LifeAstroApi\Http\Query;
use LifeAstroApi\Resources\WesternNatal;
use LifeAstroApi\Resources\WesternSynastry;
use LifeAstroApi\Tests\Support\MockTransport;

/** Parameter shapes and wire names (Node: "param shapes", "query encoding", "date defaults", ...). */
final class ParamsTest extends TestCase
{
    public function testFlatBirthParams(): void
    {
        $t = new MockTransport();
        self::client($t)->chart->planets(...self::BIRTH);
        self::assertSame(
            '/v1/chart/planets?date=1990-01-15&lat=19.076&lon=72.8777&time=10%3A30&tz=Asia%2FKolkata',
            $t->lastPathAndQuery(),
        );
    }

    public function testBirthPrefixedParams(): void
    {
        $t = new MockTransport();
        self::client($t)->milan->mangalDosha(...self::BIRTH);
        $q = $t->lastQuery();
        self::assertSame('1990-01-15', $q['birth.date']);
        self::assertSame('19.076', $q['birth.lat']);
        self::assertSame('Asia/Kolkata', $q['birth.tz']);
        self::assertArrayNotHasKey('date', $q);
        self::assertArrayNotHasKey('lat', $q);
    }

    public function testBoyAndGirlArrays(): void
    {
        $t = new MockTransport();
        self::client($t)->milan->ashtakootaTotal(boy: self::BIRTH, girl: self::GIRL);
        self::assertSame(
            '/v1/milan/ashtakoota/total?boy.date=1990-01-15&boy.lat=19.076&boy.lon=72.8777&boy.time=10%3A30'
            . '&boy.tz=Asia%2FKolkata&girl.date=1992-03-20&girl.lat=28.6139&girl.lon=77.209&girl.time=08%3A15'
            . '&girl.tz=Asia%2FKolkata',
            $t->lastPathAndQuery(),
        );
    }

    public function testBirthArrayIgnoresUnknownKeysAndSkipsMissingOnes(): void
    {
        $t = new MockTransport();
        $boy = ['date' => '1990-01-15', 'tz' => 'Asia/Kolkata', 'lat' => 19.076, 'lon' => 72.8777, 'name' => 'Arjun'];
        // @phpstan-ignore argument.type (a caller without static analysis may leave `time` out)
        self::client($t)->milan->ashtakootaTotal(boy: $boy, girl: self::GIRL);
        $q = $t->lastQuery();
        self::assertArrayNotHasKey('boy.time', $q);
        self::assertArrayNotHasKey('boy.name', $q);
        self::assertSame('08:15', $q['girl.time']);
    }

    public function testPersonAAndPersonBForWesternPairs(): void
    {
        $t = new MockTransport();
        self::client($t)->western->synastry->score(personA: self::BIRTH, personB: self::GIRL, orbTrine: 6);
        $q = $t->lastQuery();
        self::assertSame('1990-01-15', $q['personA.date']);
        self::assertSame('77.209', $q['personB.lon']);
        self::assertSame('6', $q['orb_trine']);
        self::assertArrayNotHasKey('lang', $q);
    }

    public function testLowercasesAndTrimsRashi(): void
    {
        $t = new MockTransport();
        self::client($t)->horoscope->daily(rashi: '  Aries ');
        self::assertSame('aries', $t->lastQuery()['rashi']);
    }

    public function testSubstitutesAndEncodesPathParams(): void
    {
        $t = new MockTransport();
        self::client($t)->chart->divisional(...self::BIRTH, varga: 'D9');
        self::assertSame('/v1/chart/divisional/D9', $t->lastPath());

        self::client($t)->geo->place(id: 'a b/c');
        self::assertSame('/v1/geo/place/a%20b%2Fc', $t->lastPath());

        self::client($t)->dasha->vimshottariSookshma(
            ...self::BIRTH,
            md: 'Jupiter',
            ad: 'Saturn',
            pd: 'Mercury',
            sd: 'Ketu',
        );
        self::assertSame('/v1/dasha/vimshottari/Jupiter/Saturn/Mercury/Ketu', $t->lastPath());
    }

    public function testAnEmptyPathParameterThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required path parameter "varga"');
        self::client(new MockTransport())->chart->divisional(...self::BIRTH, varga: '');
    }

    public function testCustomShape(): void
    {
        $t = new MockTransport();
        self::client($t)->festivals->month(year: 2027, month: 10, lat: 28.6139, lon: 77.209, monthSystem: 'amanta');
        self::assertSame(
            '/v1/festivals/month?lat=28.6139&lon=77.209&month=10&month_system=amanta&year=2027',
            $t->lastPathAndQuery(),
        );
    }

    public function testNatalTransitMapsTheChartToBirthAndTheMomentUnprefixed(): void
    {
        $t = new MockTransport();
        self::client($t)->transit->tarabala(birth: self::BIRTH, date: '2027-01-15', time: '06:00', tz: 'Asia/Kolkata');
        $q = $t->lastQuery();
        self::assertSame('1990-01-15', $q['birth.date']);
        self::assertSame('10:30', $q['birth.time']);
        self::assertSame('2027-01-15', $q['date']);
        self::assertSame('06:00', $q['time']);
        self::assertArrayNotHasKey('lat', $q);
    }

    public function testNatalTransitPlaceAlsoSendsTheBirthPlaceUnprefixed(): void
    {
        $t = new MockTransport();
        self::client($t)->narrative->transitPhal(birth: self::BIRTH, date: '2027-01-15', time: '06:00', tz: 'UTC');
        $q = $t->lastQuery();
        self::assertSame('19.076', $q['birth.lat']);
        self::assertSame('19.076', $q['lat']);
        self::assertSame('72.8777', $q['lon']);
        self::assertSame('2027-01-15', $q['date']);
        self::assertSame('UTC', $q['tz']);
    }

    public function testNatalWindowSendsBirthPlusUnprefixedFromAndTz(): void
    {
        $t = new MockTransport();
        $birth = ['date' => '1990-01-15', 'time' => '10:30', 'tz' => 'Asia/Kolkata'];
        self::client($t)->transit->sadeSatiWindows(birth: $birth, from: '2026-01-01', tz: 'Asia/Kolkata');
        self::assertSame(
            '/v1/transit/sade-sati/windows?birth.date=1990-01-15&birth.time=10%3A30&birth.tz=Asia%2FKolkata'
            . '&from=2026-01-01&tz=Asia%2FKolkata',
            $t->lastPathAndQuery(),
        );
    }

    public function testTransitWindowsTakesAnOptionalBirth(): void
    {
        $t = new MockTransport();
        self::client($t)->transit->windows(planet: 'saturn');
        self::assertSame('/v1/transit/windows?planet=saturn', $t->lastPathAndQuery());
        self::client($t)->transit->windows(planet: 'saturn', birth: self::BIRTH, years: 5);
        self::assertSame('5', $t->lastQuery()['years']);
        self::assertSame('1990-01-15', $t->lastQuery()['birth.date']);
    }

    public function testExposesNestedWesternGroups(): void
    {
        $client = self::client(new MockTransport());
        self::assertInstanceOf(WesternNatal::class, $client->western->natal);
        self::assertInstanceOf(WesternSynastry::class, $client->western->synastry);
    }

    public function testResourceGroupsAreReadOnly(): void
    {
        $client = self::client(new MockTransport());
        $this->expectException(\Error::class);
        // @phpstan-ignore property.readOnlyAssignOutOfClass (the point of the test)
        $client->panchang = $client->panchang;
    }

    public function testKeepsALegitimateZero(): void
    {
        $t = new MockTransport();
        self::client($t)->panchang->tithi(lat: 0, lon: 0.0, tz: 'UTC', date: '2027-01-15');
        self::assertSame('0', $t->lastQuery()['lat']);
        self::assertSame('0', $t->lastQuery()['lon']);
    }

    public function testEncodesAFixedOffsetTimeZone(): void
    {
        $t = new MockTransport();
        self::client($t)->panchang->tithi(lat: 28.61, lon: 77.2, tz: '+05:30', date: '2027-01-15');
        self::assertStringContainsString('tz=%2B05%3A30', $t->lastUrl());
        self::assertSame('+05:30', $t->lastQuery()['tz']);
    }

    public function testBooleansAndIntegersAreSentLikeNode(): void
    {
        $t = new MockTransport();
        self::client($t)->milan->dashaSync(
            boy: self::BIRTH,
            girl: self::GIRL,
            timeline: true,
            years: 3,
            marriageWindows: false,
        );
        $q = $t->lastQuery();
        self::assertSame('true', $q['timeline']);
        self::assertSame('false', $q['marriage_windows']);
        self::assertSame('3', $q['years']);
    }

    public function testWholeFloatsHaveNoDecimalPart(): void
    {
        $t = new MockTransport();
        self::client($t)->chart->planets(date: '1990-01-15', time: '10:30', tz: 'Asia/Kolkata', lat: 19.0, lon: 72);
        self::assertSame('19', $t->lastQuery()['lat']);
        self::assertSame('72', $t->lastQuery()['lon']);
    }

    public function testAyanamsaAndHouseSystemAcceptEnumsAndStrings(): void
    {
        $t = new MockTransport();
        $client = self::client($t);
        $client->chart->planets(...self::BIRTH, ayanamsa: Ayanamsa::Krishnamurti, houseSystem: HouseSystem::Placidus);
        self::assertSame('krishnamurti', $t->lastQuery()['ayanamsa']);
        self::assertSame('placidus', $t->lastQuery()['house_system']);
        $client->chart->planets(...self::BIRTH, ayanamsa: 'raman', houseSystem: 'whole-sign');
        self::assertSame('raman', $t->lastQuery()['ayanamsa']);
        self::assertSame('whole-sign', $t->lastQuery()['house_system']);
    }

    public function testFillsTodaysDateForAMomentMethod(): void
    {
        $t = new MockTransport();
        self::client($t)->panchang->tithi(lat: 28.61, lon: 77.2, tz: 'Asia/Kolkata');
        self::assertSame(Query::todayIn('Asia/Kolkata'), $t->lastQuery()['date']);
    }

    public function testKeepsAnExplicitDate(): void
    {
        $t = new MockTransport();
        self::client($t)->panchang->tithi(lat: 28.61, lon: 77.2, tz: 'Asia/Kolkata', date: '2027-01-15');
        self::assertSame('2027-01-15', $t->lastQuery()['date']);
    }

    public function testTransitPositionsNeedsNoArguments(): void
    {
        $t = new MockTransport();
        self::client($t)->transit->positions();
        $q = $t->lastQuery();
        self::assertSame('UTC', $q['tz']);
        self::assertSame(Query::todayIn('UTC'), $q['date']);
        self::assertSame(['date', 'tz'], array_keys($q));
    }

    public function testPersonalisedHoroscopeSendsTheChartUnprefixedAndTheDayAsTransitDate(): void
    {
        $t = new MockTransport();
        self::client($t)->narrative->horoscopeDailyByLagna(...self::BIRTH, onDate: '2027-01-15');
        $q = $t->lastQuery();
        self::assertSame('1990-01-15', $q['date']);
        self::assertSame('2027-01-15', $q['transit_date']);
    }

    public function testReportsAcceptTheNameAndPlaceForTheCoverPage(): void
    {
        $t = new MockTransport();
        self::client($t)->reports->kundliLite(...self::BIRTH, name: 'Arjun Mehta', place: 'Mumbai');
        $q = $t->lastQuery();
        self::assertSame('Arjun Mehta', $q['name']);
        self::assertSame('Mumbai', $q['place']);
        self::assertStringContainsString('name=Arjun+Mehta', $t->lastUrl());
    }

    public function testMuhurtaBestTimeSendsPurposeTopNAndBirthDate(): void
    {
        $t = new MockTransport();
        self::client($t)->muhurta->bestTime(
            lat: 19.076,
            lon: 72.8777,
            tz: 'Asia/Kolkata',
            startDate: '2027-01-01',
            endDate: '2027-01-31',
            purpose: 'vivah',
            topN: 3,
            birthDate: '1990-01-15',
        );
        $q = $t->lastQuery();
        self::assertSame('2027-01-01', $q['start_date']);
        self::assertSame('2027-01-31', $q['end_date']);
        self::assertSame('vivah', $q['purpose']);
        self::assertSame('3', $q['top_n']);
        self::assertSame('1990-01-15', $q['birth_date']);
    }

    public function testRemediesForGrahaMapsItsOptions(): void
    {
        $t = new MockTransport();
        self::client($t)->remedies->forGraha(graha: 'saturn', reason: 'debilitated', lang: 'hi');
        $q = $t->lastQuery();
        self::assertSame('saturn', $q['graha']);
        self::assertSame('debilitated', $q['reason']);
        self::assertSame('hi', $q['lang']);
    }
}
