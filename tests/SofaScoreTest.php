<?php

/**
 * SofaScore Unit Tests
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing\Tests;

use OpenEMR\Modules\Nursing\Scoring\SofaScore;
use PHPUnit\Framework\TestCase;

class SofaScoreTest extends TestCase
{
    public function testHealthyPatientScoresZero(): void
    {
        $r = SofaScore::calculate([
            'pao2' => '95', 'fio2' => '0.21',
            'plaquetas' => '250', 'bilirrubina' => '0.8',
            'pam' => '85', 'glasgow' => '15', 'creatinina' => '0.9',
        ]);
        $this->assertSame(0, $r['total']);
        $this->assertSame(6, $r['evaluados']);
    }

    public function testCriticalPatientScoresMaximum(): void
    {
        $r = SofaScore::calculate([
            'pao2' => '55', 'fio2' => '100', 'soporte_respiratorio' => '1',
            'plaquetas' => '15', 'bilirrubina' => '14',
            'norepinefrina' => '0.3', 'glasgow' => '4',
            'creatinina' => '1.0', 'diuresis_24h' => '150',
        ]);
        $this->assertSame(55.0, $r['pafi']);
        $this->assertSame(4, $r['resp']);
        $this->assertSame(4, $r['coag']);
        $this->assertSame(4, $r['hepatico']);
        $this->assertSame(4, $r['cardio']);
        $this->assertSame(4, $r['snc']);
        $this->assertSame(4, $r['renal']);
        $this->assertSame(24, $r['total']);
    }

    public function testMissingSystemsAreNotEvaluated(): void
    {
        $r = SofaScore::calculate(['plaquetas' => '120', 'glasgow' => '14']);
        $this->assertNull($r['resp']);
        $this->assertNull($r['hepatico']);
        $this->assertNull($r['cardio']);
        $this->assertNull($r['renal']);
        $this->assertSame(2, $r['evaluados']);
        $this->assertSame(2, $r['total']);
    }

    public function testFio2AsPercentOrFraction(): void
    {
        $this->assertSame(SofaScore::pafi(80, 0.4), SofaScore::pafi(80, 40));
        $this->assertSame(200.0, SofaScore::pafi(80, 0.4));
    }

    public function testRespiratoryRequiresSupportForThreeAndFour(): void
    {
        $this->assertSame(2, SofaScore::respiratory(150, false));
        $this->assertSame(3, SofaScore::respiratory(150, true));
        $this->assertSame(2, SofaScore::respiratory(80, false));
        $this->assertSame(4, SofaScore::respiratory(80, true));
        $this->assertSame(1, SofaScore::respiratory(399.9, false));
        $this->assertSame(0, SofaScore::respiratory(400, false));
    }

    public function testCoagulationBoundaries(): void
    {
        $this->assertSame(0, SofaScore::coagulation(150));
        $this->assertSame(1, SofaScore::coagulation(149));
        $this->assertSame(2, SofaScore::coagulation(99));
        $this->assertSame(3, SofaScore::coagulation(49));
        $this->assertSame(4, SofaScore::coagulation(19));
    }

    public function testLiverBoundaries(): void
    {
        $this->assertSame(0, SofaScore::liver(1.19));
        $this->assertSame(1, SofaScore::liver(1.2));
        $this->assertSame(2, SofaScore::liver(2.0));
        $this->assertSame(3, SofaScore::liver(6.0));
        $this->assertSame(4, SofaScore::liver(12.0));
    }

    public function testCardiovascular(): void
    {
        $this->assertSame(0, SofaScore::cardiovascular(70, null, false, null, null));
        $this->assertSame(1, SofaScore::cardiovascular(69, null, false, null, null));
        $this->assertSame(2, SofaScore::cardiovascular(80, 5, false, null, null));
        $this->assertSame(2, SofaScore::cardiovascular(80, null, true, null, null));
        $this->assertSame(3, SofaScore::cardiovascular(80, 5.1, false, null, null));
        $this->assertSame(3, SofaScore::cardiovascular(80, null, false, null, 0.1));
        $this->assertSame(4, SofaScore::cardiovascular(80, 15.1, false, null, null));
        $this->assertSame(4, SofaScore::cardiovascular(80, null, false, 0.11, null));
        $this->assertNull(SofaScore::cardiovascular(null, null, false, null, null));
    }

    public function testCnsBoundaries(): void
    {
        $this->assertSame(0, SofaScore::cns(15));
        $this->assertSame(1, SofaScore::cns(13));
        $this->assertSame(2, SofaScore::cns(10));
        $this->assertSame(3, SofaScore::cns(6));
        $this->assertSame(4, SofaScore::cns(5));
        $this->assertNull(SofaScore::cns(2));
    }

    public function testRenalUsesWorstCriterion(): void
    {
        $this->assertSame(1, SofaScore::renal(1.5, null));
        $this->assertSame(3, SofaScore::renal(3.5, null));
        $this->assertSame(3, SofaScore::renal(1.0, 450));
        $this->assertSame(4, SofaScore::renal(2.5, 150));
        $this->assertSame(0, SofaScore::renal(null, 1500));
        $this->assertNull(SofaScore::renal(null, null));
    }
}
