<?php

/**
 * Fluid balance (intake minus output) for a nursing shift.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing\Scoring;

final class FluidBalance
{
    /** Intake fields of form_balance_hidrico, in milliliters. */
    public const INTAKE_FIELDS = [
        'ing_via_oral',
        'ing_enteral',
        'ing_parenteral',
        'ing_sueros',
        'ing_medicacion',
        'ing_hemoderivados',
        'ing_otros',
    ];

    /** Output fields of form_balance_hidrico, in milliliters. */
    public const OUTPUT_FIELDS = [
        'eg_diuresis',
        'eg_drenajes',
        'eg_sng',
        'eg_vomitos',
        'eg_deposiciones',
        'eg_perdidas_insensibles',
        'eg_otros',
    ];

    /**
     * @param array<string, mixed> $values raw form values keyed by field name
     * @return array{total_ingresos: float, total_egresos: float, balance: float}
     */
    public static function calculate(array $values): array
    {
        $in = self::sum($values, self::INTAKE_FIELDS);
        $out = self::sum($values, self::OUTPUT_FIELDS);

        return [
            'total_ingresos' => $in,
            'total_egresos'  => $out,
            'balance'        => round($in - $out, 2),
        ];
    }

    /**
     * Negative volumes make no clinical sense, so they are ignored.
     *
     * @param array<string, mixed> $values
     * @param list<string> $fields
     */
    private static function sum(array $values, array $fields): float
    {
        $total = 0.0;
        foreach ($fields as $field) {
            $v = Num::parse($values[$field] ?? null);
            if ($v !== null && $v > 0) {
                $total += $v;
            }
        }
        return round($total, 2);
    }
}
