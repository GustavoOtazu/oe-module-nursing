<?php

/**
 * AdmissionTeams Unit Tests (the parts that need no database)
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing\Tests;

use OpenEMR\Modules\Nursing\AdmissionTeams;
use PHPUnit\Framework\TestCase;

class AdmissionTeamsTest extends TestCase
{
    /** @return list<array<string, string>> */
    private static function rows(): array
    {
        return [
            ['role' => AdmissionTeams::ROLE_SHARED, 'status' => AdmissionTeams::ACTIVE, 'team' => 'Cirugía General'],
            ['role' => AdmissionTeams::ROLE_SHARED, 'status' => AdmissionTeams::ENDED, 'team' => 'Traumatología'],
            ['role' => AdmissionTeams::ROLE_CONSULT, 'status' => AdmissionTeams::REQUESTED, 'team' => 'Nefrología'],
            ['role' => AdmissionTeams::ROLE_CONSULT, 'status' => AdmissionTeams::ANSWERED, 'team' => 'Cardiología'],
            ['role' => AdmissionTeams::ROLE_CONSULT, 'status' => AdmissionTeams::CANCELLED, 'team' => 'Infectología'],
        ];
    }

    public function testOnlyActiveSharedTeamsStillFollowThePatient(): void
    {
        $this->assertSame(['Cirugía General'], AdmissionTeams::activeSharedTeams(self::rows()));
    }

    public function testOnlyUnansweredConsultationsArePending(): void
    {
        $this->assertSame(['Nefrología'], AdmissionTeams::pendingConsults(self::rows()));
    }

    public function testAConsultationIsNotSharedCare(): void
    {
        // Nefrología was asked for an opinion; it can still be added as a team that follows the patient.
        $this->assertNull(AdmissionTeams::sharedConflict(self::rows(), 'Nefrología', 'Terapia Intensiva'));
    }

    public function testTheSameTeamCannotFollowThePatientTwice(): void
    {
        $this->assertSame('duplicate', AdmissionTeams::sharedConflict(self::rows(), ' cirugía general ', 'Terapia Intensiva'));
    }

    public function testATeamThatStoppedFollowingCanBeAddedAgain(): void
    {
        $this->assertNull(AdmissionTeams::sharedConflict(self::rows(), 'Traumatología', 'Terapia Intensiva'));
    }

    public function testThePrimaryTeamIsNotSharedCare(): void
    {
        $this->assertSame('primary', AdmissionTeams::sharedConflict([], 'TERAPIA INTENSIVA', 'Terapia Intensiva'));
    }

    public function testATeamNeedsAName(): void
    {
        $this->assertSame('empty', AdmissionTeams::sharedConflict([], '   ', null));
        $this->assertNull(AdmissionTeams::sharedConflict([], 'Cirugía General', null));
    }

    public function testRecordsCannotBeInTheFuture(): void
    {
        $now = strtotime('2026-11-02 10:00:00');
        $this->assertSame('2026-11-02 10:00:00', AdmissionTeams::clampWhen('2026-11-03 08:00', '2026-11-01 00:00:00', $now));
        // A few minutes of clock skew are accepted as they are.
        $this->assertSame('2026-11-02 10:04:00', AdmissionTeams::clampWhen('2026-11-02 10:04', '2026-11-01 00:00:00', $now));
    }

    public function testRecordsCannotStartBeforeTheStay(): void
    {
        $now = strtotime('2026-11-02 10:00:00');
        $this->assertSame('2026-11-01 08:30:00', AdmissionTeams::clampWhen('2026-10-30 12:00', '2026-11-01 08:30:00', $now));
    }

    public function testAnInvalidDateMeansNow(): void
    {
        $now = strtotime('2026-11-02 10:00:00');
        $this->assertSame('2026-11-02 10:00:00', AdmissionTeams::clampWhen('', '2026-11-01 00:00:00', $now));
        $this->assertSame('2026-11-02 10:00:00', AdmissionTeams::clampWhen('no es una fecha', '', $now));
    }
}
