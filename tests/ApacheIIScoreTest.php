<?php

/**
 * ApacheIIScore Unit Tests
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
use PHPUnit\Framework\TestCase;

class ApacheIIScoreTest extends TestCase
{
    public function testNormalValuesScoreZero(): void
    {
        $r = ApacheIIScore::calculate([
            'temperatura' => '37', 'pam' => '90', 'frecuencia_cardiaca' => '80',
            'frecuencia_respiratoria' => '16', 'fio2' => '0.21', 'pao2' => '90',
            'ph' => '7.40', 'sodio' => '140', 'potasio' => '4.0', 'creatinina' => '1.0',
            'hematocrito' => '40', 'leucocitos' => '8', 'glasgow' => '15', 'edad' => '30',
        ]);
        $this->assertSame(0, $r['fisiologicos']);
        $this->assertSame(0, $r['total']);
        $this->assertSame(12, $r['evaluados']);
    }

    public function testCompleteExample(): void
    {
        // 70-year-old, emergency postoperative, cirrhosis (chronic disease).
        $r = ApacheIIScore::calculate([
            'temperatura' => '39.2',            // 3
            'pam' => '65',                       // 2
            'frecuencia_cardiaca' => '125',      // 2
            'frecuencia_respiratoria' => '28',   // 1
            'fio2' => '0.4', 'pao2' => '58',     // 3 (FiO2 < 0.5, PaO2 55-60)
            'ph' => '7.30',                      // 2
            'sodio' => '128',                    // 2
            'potasio' => '3.2',                  // 1
            'creatinina' => '1.8',               // 2
            'hematocrito' => '28',               // 2
            'leucocitos' => '18',                // 1
            'glasgow' => '12',                   // 3
            'edad' => '70',                      // 5
            'enfermedad_cronica' => '1',
            'tipo_ingreso' => ApacheIIScore::ADMISSION_EMERGENCY_POSTOP, // 5
        ]);
        $this->assertSame(24, $r['fisiologicos']);
        $this->assertSame(5, $r['edad']);
        $this->assertSame(5, $r['cronicos']);
        $this->assertSame(34, $r['total']);
    }

    public function testOxygenationUsesGradientWhenFio2HighAndPao2WhenLow(): void
    {
        // FiO2 1.0, PaO2 80, PaCO2 40: A-aDO2 = 713 - 50 - 80 = 583 -> 4 points
        $aado2 = ApacheIIScore::aaGradient(1.0, 80, 40);
        $this->assertSame(583.0, $aado2);
        $this->assertSame(4, ApacheIIScore::oxygenation(1.0, 80, $aado2));
        $this->assertSame(0, ApacheIIScore::oxygenation(0.5, 300, 150.0));
        $this->assertNull(ApacheIIScore::oxygenation(0.6, 80, null));
        $this->assertSame(1, ApacheIIScore::oxygenation(0.3, 70, null));
        $this->assertSame(0, ApacheIIScore::oxygenation(0.3, 71, null));
        $this->assertSame(4, ApacheIIScore::oxygenation(0.3, 54, null));
    }

    public function testBicarbonateOnlyWhenNoPh(): void
    {
        $withPh = ApacheIIScore::calculate(['ph' => '7.40', 'bicarbonato' => '10']);
        $this->assertSame(0, $withPh['detalle']['ph']);
        $withoutPh = ApacheIIScore::calculate(['bicarbonato' => '10']);
        $this->assertSame(4, $withoutPh['detalle']['ph']);
    }

    public function testTemperatureBoundaries(): void
    {
        $this->assertSame(4, ApacheIIScore::temperature(41));
        $this->assertSame(3, ApacheIIScore::temperature(39));
        $this->assertSame(1, ApacheIIScore::temperature(38.5));
        $this->assertSame(0, ApacheIIScore::temperature(38.4));
        $this->assertSame(0, ApacheIIScore::temperature(36));
        $this->assertSame(1, ApacheIIScore::temperature(35.9));
        $this->assertSame(2, ApacheIIScore::temperature(32));
        $this->assertSame(3, ApacheIIScore::temperature(30));
        $this->assertSame(4, ApacheIIScore::temperature(29.9));
    }

    public function testHeartRateAndMapBoundaries(): void
    {
        $this->assertSame(0, ApacheIIScore::heartRate(70));
        $this->assertSame(2, ApacheIIScore::heartRate(69));
        $this->assertSame(3, ApacheIIScore::heartRate(40));
        $this->assertSame(4, ApacheIIScore::heartRate(39));
        $this->assertSame(0, ApacheIIScore::meanArterialPressure(70));
        $this->assertSame(2, ApacheIIScore::meanArterialPressure(69));
        $this->assertSame(4, ApacheIIScore::meanArterialPressure(49));
        $this->assertSame(4, ApacheIIScore::meanArterialPressure(160));
    }

    public function testCreatinineDoubledInAcuteRenalFailure(): void
    {
        $this->assertSame(3, ApacheIIScore::creatinine(2.5, false));
        $this->assertSame(6, ApacheIIScore::creatinine(2.5, true));
        $this->assertSame(2, ApacheIIScore::creatinine(0.5, false));
    }

    public function testElectrolytesAndHematology(): void
    {
        $this->assertSame(1, ApacheIIScore::sodium(150));
        $this->assertSame(0, ApacheIIScore::sodium(149));
        $this->assertSame(4, ApacheIIScore::sodium(110));
        $this->assertSame(1, ApacheIIScore::potassium(5.5));
        $this->assertSame(4, ApacheIIScore::potassium(2.4));
        $this->assertSame(1, ApacheIIScore::hematocrit(46));
        $this->assertSame(4, ApacheIIScore::hematocrit(19));
        $this->assertSame(2, ApacheIIScore::whiteBloodCells(2.9));
        $this->assertSame(4, ApacheIIScore::whiteBloodCells(0.9));
    }

    public function testAgeAndChronicHealth(): void
    {
        $this->assertSame(0, ApacheIIScore::age(44));
        $this->assertSame(2, ApacheIIScore::age(45));
        $this->assertSame(3, ApacheIIScore::age(55));
        $this->assertSame(5, ApacheIIScore::age(65));
        $this->assertSame(6, ApacheIIScore::age(75));
        $this->assertSame(0, ApacheIIScore::chronicHealth(false, ApacheIIScore::ADMISSION_NON_OPERATIVE));
        $this->assertSame(5, ApacheIIScore::chronicHealth(true, ApacheIIScore::ADMISSION_NON_OPERATIVE));
        $this->assertSame(2, ApacheIIScore::chronicHealth(true, ApacheIIScore::ADMISSION_ELECTIVE_POSTOP));
    }

    public function testGlasgowPoints(): void
    {
        $this->assertSame(0, ApacheIIScore::glasgow(15));
        $this->assertSame(12, ApacheIIScore::glasgow(3));
        $this->assertNull(ApacheIIScore::glasgow(0));
    }
}
