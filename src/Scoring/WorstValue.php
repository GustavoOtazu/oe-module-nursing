<?php

/**
 * Worst-value selection for severity scores.
 *
 * APACHE II uses the worst value of each variable in the first 24 hours of the
 * ICU stay (Knaus et al., 1985) and SOFA the worst value of each system in the
 * last 24 hours (Vincent et al., 1996). "Worst" means the value that scores
 * more points, so a temperature of 34 C can be worse than one of 38.6 C. When
 * two values score the same, the most recent one is kept.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing\Scoring;

final class WorstValue
{
    /**
     * @template T of array{time: string}
     * @param list<T> $candidates each with a sortable 'time' (Y-m-d H:i:s)
     * @param callable(T): ?int $points points of a candidate, null when it cannot be scored
     * @return (T&array{points: int})|null
     */
    public static function pick(array $candidates, callable $points): ?array
    {
        $best = null;
        $bestPoints = -1;
        foreach ($candidates as $candidate) {
            $p = $points($candidate);
            if ($p === null) {
                continue;
            }
            if ($p > $bestPoints || ($p === $bestPoints && $best !== null && strcmp($candidate['time'], $best['time']) > 0)) {
                $best = $candidate + ['points' => $p];
                $best['points'] = $p;
                $bestPoints = $p;
            }
        }
        return $best;
    }

    /** OpenEMR stores vital-sign temperatures in degrees Fahrenheit. */
    public static function fahrenheitToCelsius(?float $fahrenheit): ?float
    {
        if ($fahrenheit === null || $fahrenheit <= 0) {
            return null;
        }
        return round(($fahrenheit - 32) * 5 / 9, 1);
    }

    /** Mean arterial pressure = (systolic + 2 x diastolic) / 3. */
    public static function meanArterialPressure(?float $systolic, ?float $diastolic): ?float
    {
        if ($systolic === null || $diastolic === null || $systolic <= 0 || $diastolic <= 0 || $diastolic > $systolic) {
            return null;
        }
        return round(($systolic + 2 * $diastolic) / 3, 1);
    }
}
