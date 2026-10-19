<?php

/**
 * Vital Signs Take Unit Tests
 *
 * Points of a whole vital signs take in each scale (ClinicalPrefill) and the
 * choice of the worst take among several (WorstValue::pick), as used by the
 * "Vital signs take" select of the SOFA and APACHE II forms.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing\Tests;

use OpenEMR\Modules\Nursing\Prefill\ClinicalPrefill;
use OpenEMR\Modules\Nursing\Scoring\WorstValue;
use PHPUnit\Framework\TestCase;

class VitalsTakeTest extends TestCase
{
    /**
     * @return array{temp_c: ?float, map: ?float, pulse: ?float, respiration: ?float}
     */
    private static function take(?float $temp, ?float $map, ?float $pulse, ?float $resp): array
    {
        return ['temp_c' => $temp, 'map' => $map, 'pulse' => $pulse, 'respiration' => $resp];
    }

    public function testApacheAddsTheFourVitalVariables(): void
    {
        // 39.2 C (3) + MAP 56.7 (2) + HR 135 (2) + RR 32 (1)
        $this->assertSame(8, ClinicalPrefill::apacheTakePoints(self::take(39.2, 56.7, 135.0, 32.0)));
        // 38.6 C (1) + MAP 80 (0) + HR 105 (0) + RR 22 (0)
        $this->assertSame(1, ClinicalPrefill::apacheTakePoints(self::take(38.6, 80.0, 105.0, 22.0)));
        // All within normal ranges
        $this->assertSame(0, ClinicalPrefill::apacheTakePoints(self::take(37.8, 96.7, 95.0, 20.0)));
    }

    public function testApacheUsesWhatTheTakeHas(): void
    {
        // Only the heart rate was recorded: 145 bpm gives 3 points.
        $this->assertSame(3, ClinicalPrefill::apacheTakePoints(self::take(null, null, 145.0, null)));
    }

    public function testApacheTakeWithNothingToScoreIsNull(): void
    {
        $this->assertNull(ClinicalPrefill::apacheTakePoints(self::take(null, null, null, null)));
    }

    public function testSofaOnlyUsesTheMeanArterialPressure(): void
    {
        $this->assertSame(1, ClinicalPrefill::sofaTakePoints(self::take(40.0, 61.7, 150.0, 40.0)));
        $this->assertSame(0, ClinicalPrefill::sofaTakePoints(self::take(40.0, 90.0, 150.0, 40.0)));
        $this->assertNull(ClinicalPrefill::sofaTakePoints(self::take(40.0, null, 150.0, 40.0)));
    }

    public function testWorstTakeIsTheOneWithMorePointsNotTheLatest(): void
    {
        $takes = [
            ['id' => 39, 'time' => '2026-10-19 01:03:10'] + self::take(37.8, 96.7, 95.0, 20.0),
            ['id' => 40, 'time' => '2026-10-19 01:03:20'] + self::take(39.2, 56.7, 135.0, 32.0),
            ['id' => 41, 'time' => '2026-10-19 01:03:30'] + self::take(38.6, 80.0, 105.0, 22.0),
        ];
        $worst = WorstValue::pick($takes, ClinicalPrefill::apacheTakePoints(...));
        $this->assertSame(40, $worst['id']);
        $this->assertSame(8, $worst['points']);
    }

    public function testWorstTakeTieKeepsTheMostRecent(): void
    {
        $takes = [
            ['id' => 1, 'time' => '2026-10-19 08:00:00'] + self::take(null, 65.0, null, null),
            ['id' => 2, 'time' => '2026-10-19 12:00:00'] + self::take(null, 60.0, null, null),
        ];
        // Both MAP below 70 give 1 SOFA point: the later take is proposed.
        $worst = WorstValue::pick($takes, ClinicalPrefill::sofaTakePoints(...));
        $this->assertSame(2, $worst['id']);
    }
}
