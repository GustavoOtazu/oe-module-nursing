<?php

/**
 * FluidBalance Unit Tests
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing\Tests;

use OpenEMR\Modules\Nursing\Scoring\FluidBalance;
use OpenEMR\Modules\Nursing\Scoring\Num;
use PHPUnit\Framework\TestCase;

class FluidBalanceTest extends TestCase
{
    public function testPositiveBalance(): void
    {
        $result = FluidBalance::calculate([
            'ing_sueros'   => '1000',
            'ing_enteral'  => '500',
            'eg_diuresis'  => '800',
            'eg_drenajes'  => '100',
        ]);
        $this->assertSame(1500.0, $result['total_ingresos']);
        $this->assertSame(900.0, $result['total_egresos']);
        $this->assertSame(600.0, $result['balance']);
    }

    public function testNegativeBalance(): void
    {
        $result = FluidBalance::calculate(['ing_via_oral' => '300', 'eg_diuresis' => '1200']);
        $this->assertSame(-900.0, $result['balance']);
    }

    public function testEmptyFormGivesZero(): void
    {
        $result = FluidBalance::calculate([]);
        $this->assertSame(0.0, $result['total_ingresos']);
        $this->assertSame(0.0, $result['total_egresos']);
        $this->assertSame(0.0, $result['balance']);
    }

    public function testCommaDecimalAndInvalidValues(): void
    {
        $result = FluidBalance::calculate([
            'ing_medicacion' => '12,5',
            'ing_otros'      => 'abc',
            'eg_vomitos'     => '-50',
        ]);
        $this->assertSame(12.5, $result['total_ingresos']);
        $this->assertSame(0.0, $result['total_egresos']);
        $this->assertSame(12.5, $result['balance']);
    }

    public function testUnknownFieldsAreIgnored(): void
    {
        $result = FluidBalance::calculate(['balance' => '9999', 'ing_sueros' => '100']);
        $this->assertSame(100.0, $result['balance']);
    }

    public function testNumParse(): void
    {
        $this->assertNull(Num::parse(''));
        $this->assertNull(Num::parse('  '));
        $this->assertNull(Num::parse(null));
        $this->assertNull(Num::parse('1.2.3'));
        $this->assertSame(7.35, Num::parse('7,35'));
        $this->assertSame(40.0, Num::parse(40));
    }
}
