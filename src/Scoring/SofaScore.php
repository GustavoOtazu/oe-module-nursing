<?php

/**
 * SOFA score (Sequential Organ Failure Assessment).
 *
 * Thresholds follow Vincent et al., Intensive Care Med 1996;22:707-710.
 * Each organ system scores 0-4; the total ranges 0-24. A system whose data
 * was not entered is reported as null and adds nothing to the total, so the
 * form can show how many systems were actually evaluated.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing\Scoring;

final class SofaScore
{
    /**
     * @param array<string, mixed> $v raw form values:
     *   pao2 (mmHg), fio2 (fraction 0.21-1.0 or percent 21-100),
     *   soporte_respiratorio (bool), plaquetas (x10^3/uL), bilirrubina (mg/dL),
     *   pam (mmHg), dopamina, epinefrina, norepinefrina (ug/kg/min),
     *   dobutamina (bool), glasgow (3-15), creatinina (mg/dL), diuresis_24h (mL/day)
     * @return array{resp: ?int, coag: ?int, hepatico: ?int, cardio: ?int, snc: ?int, renal: ?int, total: int, evaluados: int, pafi: ?float}
     */
    public static function calculate(array $v): array
    {
        $pafi = self::pafi(Num::parse($v['pao2'] ?? null), Num::parse($v['fio2'] ?? null));

        $result = [
            'resp'     => self::respiratory($pafi, !empty($v['soporte_respiratorio'])),
            'coag'     => self::coagulation(Num::parse($v['plaquetas'] ?? null)),
            'hepatico' => self::liver(Num::parse($v['bilirrubina'] ?? null)),
            'cardio'   => self::cardiovascular(
                Num::parse($v['pam'] ?? null),
                Num::parse($v['dopamina'] ?? null),
                !empty($v['dobutamina']),
                Num::parse($v['epinefrina'] ?? null),
                Num::parse($v['norepinefrina'] ?? null)
            ),
            'snc'      => self::cns(Num::parse($v['glasgow'] ?? null)),
            'renal'    => self::renal(Num::parse($v['creatinina'] ?? null), Num::parse($v['diuresis_24h'] ?? null)),
        ];

        $total = 0;
        $evaluated = 0;
        foreach ($result as $score) {
            if ($score !== null) {
                $total += $score;
                $evaluated++;
            }
        }

        return $result + ['total' => $total, 'evaluados' => $evaluated, 'pafi' => $pafi];
    }

    /** FiO2 may be entered as a fraction (0.4) or as a percentage (40). */
    public static function normalizeFio2(?float $fio2): ?float
    {
        if ($fio2 === null || $fio2 <= 0) {
            return null;
        }
        return $fio2 > 1 ? $fio2 / 100 : $fio2;
    }

    public static function pafi(?float $pao2, ?float $fio2): ?float
    {
        $fio2 = self::normalizeFio2($fio2);
        if ($pao2 === null || $pao2 <= 0 || $fio2 === null) {
            return null;
        }
        return round($pao2 / $fio2, 1);
    }

    /** Scores 3 and 4 require respiratory support (mechanical ventilation). */
    public static function respiratory(?float $pafi, bool $respiratorySupport): ?int
    {
        if ($pafi === null) {
            return null;
        }
        if ($pafi < 100 && $respiratorySupport) {
            return 4;
        }
        if ($pafi < 200 && $respiratorySupport) {
            return 3;
        }
        if ($pafi < 300) {
            return 2;
        }
        if ($pafi < 400) {
            return 1;
        }
        return 0;
    }

    public static function coagulation(?float $platelets): ?int
    {
        if ($platelets === null || $platelets < 0) {
            return null;
        }
        return match (true) {
            $platelets < 20  => 4,
            $platelets < 50  => 3,
            $platelets < 100 => 2,
            $platelets < 150 => 1,
            default          => 0,
        };
    }

    public static function liver(?float $bilirubin): ?int
    {
        if ($bilirubin === null || $bilirubin < 0) {
            return null;
        }
        return match (true) {
            $bilirubin >= 12.0 => 4,
            $bilirubin >= 6.0  => 3,
            $bilirubin >= 2.0  => 2,
            $bilirubin >= 1.2  => 1,
            default            => 0,
        };
    }

    /** Vasopressor doses in ug/kg/min, given for at least one hour. */
    public static function cardiovascular(?float $map, ?float $dopamine, bool $dobutamine, ?float $epinephrine, ?float $norepinephrine): ?int
    {
        $dopamine = $dopamine ?? 0.0;
        $epinephrine = $epinephrine ?? 0.0;
        $norepinephrine = $norepinephrine ?? 0.0;
        $anyDrug = $dopamine > 0 || $dobutamine || $epinephrine > 0 || $norepinephrine > 0;

        if ($map === null && !$anyDrug) {
            return null;
        }
        if ($dopamine > 15 || $epinephrine > 0.1 || $norepinephrine > 0.1) {
            return 4;
        }
        if ($dopamine > 5 || $epinephrine > 0 || $norepinephrine > 0) {
            return 3;
        }
        if ($dopamine > 0 || $dobutamine) {
            return 2;
        }
        if ($map !== null && $map < 70) {
            return 1;
        }
        return 0;
    }

    public static function cns(?float $glasgow): ?int
    {
        if ($glasgow === null || $glasgow < 3 || $glasgow > 15) {
            return null;
        }
        return match (true) {
            $glasgow < 6   => 4,
            $glasgow < 10  => 3,
            $glasgow < 13  => 2,
            $glasgow < 15  => 1,
            default        => 0,
        };
    }

    /** The worse of the creatinine and the 24 h urine output criteria is used. */
    public static function renal(?float $creatinine, ?float $urine24h): ?int
    {
        if ($creatinine === null && $urine24h === null) {
            return null;
        }
        $byCreatinine = 0;
        if ($creatinine !== null) {
            $byCreatinine = match (true) {
                $creatinine >= 5.0 => 4,
                $creatinine >= 3.5 => 3,
                $creatinine >= 2.0 => 2,
                $creatinine >= 1.2 => 1,
                default            => 0,
            };
        }
        $byUrine = 0;
        if ($urine24h !== null && $urine24h >= 0) {
            $byUrine = match (true) {
                $urine24h < 200 => 4,
                $urine24h < 500 => 3,
                default         => 0,
            };
        }
        return max($byCreatinine, $byUrine);
    }
}
