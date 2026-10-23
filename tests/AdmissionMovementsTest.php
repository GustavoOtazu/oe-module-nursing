<?php

/**
 * AdmissionMovements Unit Tests (the parts that need no database)
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing\Tests;

use OpenEMR\Modules\Nursing\AdmissionMovements;
use PHPUnit\Framework\TestCase;

class AdmissionMovementsTest extends TestCase
{
    private const BED_A3 = ['departamento' => 'terapia_adulto', 'servicio' => 'intensivo', 'cuarto' => 'a', 'cama' => '3'];

    public function testSameLocationIsNotAChange(): void
    {
        $this->assertFalse(AdmissionMovements::locationChanged(self::BED_A3, self::BED_A3));
    }

    public function testCaseSpacesAndExtraKeysAreIgnored(): void
    {
        $other = ['departamento' => 'terapia_adulto', 'servicio' => 'intensivo', 'cuarto' => ' A ', 'cama' => 3, 'equipo' => 'Cirugía', 'id' => 9];
        $this->assertFalse(AdmissionMovements::locationChanged(self::BED_A3, $other));
    }

    public function testEmptyAndMissingAreTheSame(): void
    {
        $this->assertFalse(AdmissionMovements::locationChanged(['cama' => ''], ['cama' => null]));
        $this->assertFalse(AdmissionMovements::locationChanged([], ['departamento' => null, 'cuarto' => '']));
    }

    public function testAnotherBedOrServiceIsAChange(): void
    {
        $this->assertTrue(AdmissionMovements::locationChanged(self::BED_A3, ['cama' => '4'] + self::BED_A3));
        $this->assertTrue(AdmissionMovements::locationChanged(self::BED_A3, ['servicio' => 'intermedia'] + self::BED_A3));
    }

    public function testTheTeamAloneIsNotALocationChange(): void
    {
        $this->assertFalse(AdmissionMovements::locationChanged(self::BED_A3 + ['equipo' => 'Clínica'], self::BED_A3 + ['equipo' => 'Cirugía']));
    }

    public function testDischargeTodayEndsNow(): void
    {
        $now = (int) strtotime('2026-10-20 14:35:00');
        $this->assertSame('2026-10-20 14:35:00', AdmissionMovements::endFor('2026-10-20', '2026-10-18 08:00:00', $now));
    }

    public function testPastDateEndsAtTheEndOfThatDay(): void
    {
        $now = (int) strtotime('2026-10-20 14:35:00');
        $this->assertSame('2026-10-19 23:59:59', AdmissionMovements::endFor('2026-10-19', '2026-10-18 08:00:00', $now));
    }

    public function testEndIsNeverBeforeTheStart(): void
    {
        $now = (int) strtotime('2026-10-20 14:35:00');
        $this->assertSame('2026-10-19 08:00:00', AdmissionMovements::endFor('2026-10-17', '2026-10-19 08:00:00', $now));
    }

    public function testInvalidDateEndsNow(): void
    {
        $now = (int) strtotime('2026-10-20 14:35:00');
        $this->assertSame('2026-10-20 14:35:00', AdmissionMovements::endFor('not a date', '', $now));
    }

    public function testDischargeWithTimeKeepsThatTime(): void
    {
        $now = (int) strtotime('2026-10-20 14:35:00');
        $this->assertSame('2026-10-20 14:15:03', AdmissionMovements::endFor('2026-10-20 14:15:03', '2026-10-18 08:00:00', $now));
        $this->assertSame('2026-10-19 21:10:00', AdmissionMovements::endFor('2026-10-19 21:10:00', '2026-10-18 08:00:00', $now));
    }

    public function testDischargeTimeIsNeverInTheFutureNorBeforeTheStart(): void
    {
        $now = (int) strtotime('2026-10-20 14:35:00');
        $this->assertSame('2026-10-20 14:35:00', AdmissionMovements::endFor('2026-10-20 18:00:00', '2026-10-18 08:00:00', $now));
        $this->assertSame('2026-10-20 09:00:00', AdmissionMovements::endFor('2026-10-20 08:30:00', '2026-10-20 09:00:00', $now));
    }
}
