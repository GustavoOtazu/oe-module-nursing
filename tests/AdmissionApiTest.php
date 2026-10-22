<?php

/**
 * Admission API Unit Tests (validation; no database)
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing\Tests;

use OpenEMR\Modules\Nursing\Api\AdmissionApi;
use PHPUnit\Framework\TestCase;

class AdmissionApiTest extends TestCase
{
    private const VALID = [
        'patient_uuid'  => '96506861-511f-4f6d-bc97-b65a78cf1995',
        'nro_registro'  => '884512',
        'fecha_ingreso' => '2026-10-20T08:30:00-03:00',
        'ubicacion'     => ['departamento' => 'UTI ADULTOS', 'servicio' => 'TERAPIA INTENSIVA', 'cuarto' => '2', 'cama' => '07'],
    ];

    public function testValidAdmission(): void
    {
        $this->assertSame([], AdmissionApi::validateCreate(self::VALID));
    }

    public function testAdmissionNeedsPatientRecordNumberDateAndBed(): void
    {
        $this->assertCount(4, AdmissionApi::validateCreate([]));
        $this->assertNotSame([], AdmissionApi::validateCreate(['nro_registro' => "1' OR '1'='1"] + self::VALID));
        $this->assertNotSame([], AdmissionApi::validateCreate(['patient_uuid' => '12'] + self::VALID));
        $this->assertNotSame([], AdmissionApi::validateCreate(['ubicacion' => ['cama' => '  ']] + self::VALID));
    }

    public function testRecordNumber(): void
    {
        $this->assertTrue(AdmissionApi::validRecordNumber('884512'));
        $this->assertTrue(AdmissionApi::validRecordNumber('A-12.3'));
        $this->assertFalse(AdmissionApi::validRecordNumber(''));
        $this->assertFalse(AdmissionApi::validRecordNumber('12/3'));
        $this->assertFalse(AdmissionApi::validRecordNumber(884512));
        $this->assertFalse(AdmissionApi::validRecordNumber(str_repeat('1', 41)));
    }

    public function testDatesWithOffsetAreConvertedToTheServerZone(): void
    {
        $zone = date_default_timezone_get();
        date_default_timezone_set('America/Asuncion');
        try {
            $this->assertSame('2026-10-20 08:30:00', AdmissionApi::normalizeDateTime('2026-10-20T08:30:00-03:00'));
            $this->assertSame('2026-10-20 08:30:00', AdmissionApi::normalizeDateTime('2026-10-20T11:30:00Z'));
            $this->assertSame('2026-10-20 08:30:00', AdmissionApi::normalizeDateTime('2026-10-20 08:30:00'));
        } finally {
            date_default_timezone_set($zone);
        }
    }

    public function testInvalidDates(): void
    {
        $this->assertNull(AdmissionApi::normalizeDateTime('2026-02-30 10:00:00'));
        $this->assertNull(AdmissionApi::normalizeDateTime('20/10/2026'));
        $this->assertNull(AdmissionApi::normalizeDateTime(''));
        $this->assertNull(AdmissionApi::normalizeDateTime(null));
    }

    public function testUpdateNeedsSomethingToChange(): void
    {
        $this->assertNotSame([], AdmissionApi::validateUpdate([]));
        $this->assertSame([], AdmissionApi::validateUpdate(['alta' => '2026-10-22T11:00:00-03:00']));
        $this->assertSame([], AdmissionApi::validateUpdate(['ubicacion' => ['cama' => '08', 'desde' => '2026-10-21T14:05:00-03:00']]));
        $this->assertNotSame([], AdmissionApi::validateUpdate(['ubicacion' => ['cama' => '08', 'desde' => 'ayer']]));
        $this->assertNotSame([], AdmissionApi::validateUpdate(['ubicacion' => 'cama 8']));
        $this->assertNotSame([], AdmissionApi::validateUpdate(['alta' => 'hoy']));
    }

    public function testLocationIsTrimmedAndCut(): void
    {
        $clean = AdmissionApi::cleanLocation(['cama' => ' 07 ', 'cuarto' => '', 'servicio' => str_repeat('x', 80)]);
        $this->assertSame('07', $clean['cama']);
        $this->assertNull($clean['cuarto']);
        $this->assertNull($clean['departamento']);
        $this->assertSame(55, mb_strlen((string) $clean['servicio']));
    }
}
