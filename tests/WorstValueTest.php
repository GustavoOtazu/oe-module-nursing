<?php

/**
 * WorstValue Unit Tests
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing\Tests;

use OpenEMR\Modules\Nursing\Scoring\ApacheIIScore;
use OpenEMR\Modules\Nursing\Scoring\SofaScore;
use OpenEMR\Modules\Nursing\Scoring\WorstValue;
use PHPUnit\Framework\TestCase;

class WorstValueTest extends TestCase
{
    public function testPicksValueWithMorePointsNotTheLatest(): void
    {
        // Hypothermia (34.5 C, 1 point) is worse than the later 37 C (0 points).
        $best = WorstValue::pick([
            ['value' => 34.5, 'time' => '2026-09-25 08:00:00'],
            ['value' => 37.0, 'time' => '2026-09-25 14:00:00'],
        ], static fn(array $c): ?int => ApacheIIScore::temperature($c['value']));
        $this->assertSame(34.5, $best['value']);
        $this->assertSame(1, $best['points']);
    }

    public function testTieKeepsMostRecent(): void
    {
        $best = WorstValue::pick([
            ['value' => 80.0, 'time' => '2026-09-25 08:00:00'],
            ['value' => 90.0, 'time' => '2026-09-25 12:00:00'],
            ['value' => 85.0, 'time' => '2026-09-25 10:00:00'],
        ], static fn(array $c): ?int => ApacheIIScore::heartRate($c['value']));
        $this->assertSame(90.0, $best['value']);
    }

    public function testLowestMapIsWorstForSofa(): void
    {
        $best = WorstValue::pick([
            ['value' => 75.0, 'time' => '2026-09-25 08:00:00'],
            ['value' => 65.0, 'time' => '2026-09-25 09:00:00'],
            ['value' => 80.0, 'time' => '2026-09-25 10:00:00'],
        ], static fn(array $c): ?int => SofaScore::cardiovascular($c['value'], null, false, null, null));
        $this->assertSame(65.0, $best['value']);
    }

    public function testUnscorableCandidatesAreSkipped(): void
    {
        $this->assertNull(WorstValue::pick([], static fn(array $c): ?int => 0));
        $best = WorstValue::pick([
            ['value' => 0.0, 'time' => '2026-09-25 08:00:00'],
            ['value' => 12.0, 'time' => '2026-09-25 09:00:00'],
        ], static fn(array $c): ?int => SofaScore::cns($c['value']));
        $this->assertSame(12.0, $best['value']);
    }

    public function testFahrenheitToCelsius(): void
    {
        $this->assertSame(37.0, WorstValue::fahrenheitToCelsius(98.6));
        $this->assertSame(39.2, WorstValue::fahrenheitToCelsius(102.6));
        $this->assertNull(WorstValue::fahrenheitToCelsius(0.0));
        $this->assertNull(WorstValue::fahrenheitToCelsius(null));
    }

    public function testMeanArterialPressure(): void
    {
        $this->assertSame(93.3, WorstValue::meanArterialPressure(120, 80));
        $this->assertSame(65.0, WorstValue::meanArterialPressure(95, 50));
        $this->assertNull(WorstValue::meanArterialPressure(80, 120));
        $this->assertNull(WorstValue::meanArterialPressure(null, 80));
    }
}
