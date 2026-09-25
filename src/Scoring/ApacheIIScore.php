<?php

/**
 * APACHE II score (Acute Physiology and Chronic Health Evaluation II).
 *
 * Point tables follow Knaus et al., Crit Care Med 1985;13(10):818-829.
 * The score is the sum of the Acute Physiology Score (12 variables, using the
 * worst value of the first 24 h), the age points and the chronic health points.
 * Missing variables add 0 points, which is how the score is usually handled
 * in practice, and the result reports which variables were evaluated.
 *
 * This implementation only computes the score; it does not estimate mortality,
 * because that also requires the diagnostic category weights of the original model.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing\Scoring;

final class ApacheIIScore
{
    /** Atmospheric pressure (mmHg) used for the alveolar gas equation. */
    private const P_ATM = 760.0;
    /** Water vapour pressure at 37 C (mmHg). */
    private const P_H2O = 47.0;
    /** Respiratory quotient. */
    private const RQ = 0.8;

    public const ADMISSION_NON_OPERATIVE = 'NO_QUIRURGICO';
    public const ADMISSION_EMERGENCY_POSTOP = 'URGENCIA';
    public const ADMISSION_ELECTIVE_POSTOP = 'ELECTIVO';

    /**
     * @param array<string, mixed> $v raw form values:
     *   temperatura (C, rectal), pam (mmHg), frecuencia_cardiaca, frecuencia_respiratoria,
     *   fio2 (fraction or percent), pao2 (mmHg), paco2 (mmHg), ph, bicarbonato (mmol/L,
     *   used only when there is no pH), sodio (mmol/L), potasio (mmol/L), creatinina (mg/dL),
     *   insuficiencia_renal_aguda (bool), hematocrito (%), leucocitos (x10^3/uL),
     *   glasgow (3-15), edad (years), enfermedad_cronica (bool), tipo_ingreso (see constants)
     * @return array{fisiologicos: int, edad: int, cronicos: int, total: int, a_ado2: ?float, detalle: array<string, ?int>, evaluados: int}
     */
    public static function calculate(array $v): array
    {
        $fio2 = SofaScore::normalizeFio2(Num::parse($v['fio2'] ?? null));
        $pao2 = Num::parse($v['pao2'] ?? null);
        $paco2 = Num::parse($v['paco2'] ?? null);
        $aado2 = self::aaGradient($fio2, $pao2, $paco2);

        $ph = Num::parse($v['ph'] ?? null);

        $detail = [
            'temperatura'  => self::temperature(Num::parse($v['temperatura'] ?? null)),
            'pam'          => self::meanArterialPressure(Num::parse($v['pam'] ?? null)),
            'fc'           => self::heartRate(Num::parse($v['frecuencia_cardiaca'] ?? null)),
            'fr'           => self::respiratoryRate(Num::parse($v['frecuencia_respiratoria'] ?? null)),
            'oxigenacion'  => self::oxygenation($fio2, $pao2, $aado2),
            'ph'           => $ph !== null ? self::arterialPh($ph) : self::bicarbonate(Num::parse($v['bicarbonato'] ?? null)),
            'sodio'        => self::sodium(Num::parse($v['sodio'] ?? null)),
            'potasio'      => self::potassium(Num::parse($v['potasio'] ?? null)),
            'creatinina'   => self::creatinine(Num::parse($v['creatinina'] ?? null), !empty($v['insuficiencia_renal_aguda'])),
            'hematocrito'  => self::hematocrit(Num::parse($v['hematocrito'] ?? null)),
            'leucocitos'   => self::whiteBloodCells(Num::parse($v['leucocitos'] ?? null)),
            'glasgow'      => self::glasgow(Num::parse($v['glasgow'] ?? null)),
        ];

        $physiology = 0;
        $evaluated = 0;
        foreach ($detail as $points) {
            if ($points !== null) {
                $physiology += $points;
                $evaluated++;
            }
        }

        $agePoints = self::age(Num::parse($v['edad'] ?? null));
        $chronicPoints = self::chronicHealth(!empty($v['enfermedad_cronica']), (string) ($v['tipo_ingreso'] ?? ''));

        return [
            'fisiologicos' => $physiology,
            'edad'         => $agePoints,
            'cronicos'     => $chronicPoints,
            'total'        => $physiology + $agePoints + $chronicPoints,
            'a_ado2'       => $aado2,
            'detalle'      => $detail,
            'evaluados'    => $evaluated,
        ];
    }

    /** Alveolar-arterial O2 gradient: FiO2 x (Patm - PH2O) - PaCO2 / RQ - PaO2. */
    public static function aaGradient(?float $fio2, ?float $pao2, ?float $paco2): ?float
    {
        if ($fio2 === null || $pao2 === null || $paco2 === null) {
            return null;
        }
        return round($fio2 * (self::P_ATM - self::P_H2O) - $paco2 / self::RQ - $pao2, 1);
    }

    public static function temperature(?float $t): ?int
    {
        if ($t === null) {
            return null;
        }
        return match (true) {
            $t >= 41   => 4,
            $t >= 39   => 3,
            $t >= 38.5 => 1,
            $t >= 36   => 0,
            $t >= 34   => 1,
            $t >= 32   => 2,
            $t >= 30   => 3,
            default    => 4,
        };
    }

    public static function meanArterialPressure(?float $map): ?int
    {
        if ($map === null) {
            return null;
        }
        return match (true) {
            $map >= 160 => 4,
            $map >= 130 => 3,
            $map >= 110 => 2,
            $map >= 70  => 0,
            $map >= 50  => 2,
            default     => 4,
        };
    }

    public static function heartRate(?float $hr): ?int
    {
        if ($hr === null) {
            return null;
        }
        return match (true) {
            $hr >= 180 => 4,
            $hr >= 140 => 3,
            $hr >= 110 => 2,
            $hr >= 70  => 0,
            $hr >= 55  => 2,
            $hr >= 40  => 3,
            default    => 4,
        };
    }

    /** Ventilated or not. */
    public static function respiratoryRate(?float $rr): ?int
    {
        if ($rr === null) {
            return null;
        }
        return match (true) {
            $rr >= 50 => 4,
            $rr >= 35 => 3,
            $rr >= 25 => 1,
            $rr >= 12 => 0,
            $rr >= 10 => 1,
            $rr >= 6  => 2,
            default   => 4,
        };
    }

    /** FiO2 >= 0.5 is scored by the A-aDO2 gradient; FiO2 < 0.5 by the PaO2. */
    public static function oxygenation(?float $fio2, ?float $pao2, ?float $aado2): ?int
    {
        if ($fio2 === null) {
            return null;
        }
        if ($fio2 >= 0.5) {
            if ($aado2 === null) {
                return null;
            }
            return match (true) {
                $aado2 >= 500 => 4,
                $aado2 >= 350 => 3,
                $aado2 >= 200 => 2,
                default       => 0,
            };
        }
        if ($pao2 === null) {
            return null;
        }
        return match (true) {
            $pao2 > 70  => 0,
            $pao2 >= 61 => 1,
            $pao2 >= 55 => 3,
            default     => 4,
        };
    }

    public static function arterialPh(?float $ph): ?int
    {
        if ($ph === null) {
            return null;
        }
        return match (true) {
            $ph >= 7.7  => 4,
            $ph >= 7.6  => 3,
            $ph >= 7.5  => 1,
            $ph >= 7.33 => 0,
            $ph >= 7.25 => 2,
            $ph >= 7.15 => 3,
            default     => 4,
        };
    }

    /** Serum HCO3 (venous, mmol/L): used only when no arterial blood gas is available. */
    public static function bicarbonate(?float $hco3): ?int
    {
        if ($hco3 === null) {
            return null;
        }
        return match (true) {
            $hco3 >= 52 => 4,
            $hco3 >= 41 => 3,
            $hco3 >= 32 => 1,
            $hco3 >= 22 => 0,
            $hco3 >= 18 => 2,
            $hco3 >= 15 => 3,
            default     => 4,
        };
    }

    public static function sodium(?float $na): ?int
    {
        if ($na === null) {
            return null;
        }
        return match (true) {
            $na >= 180 => 4,
            $na >= 160 => 3,
            $na >= 155 => 2,
            $na >= 150 => 1,
            $na >= 130 => 0,
            $na >= 120 => 2,
            $na >= 111 => 3,
            default    => 4,
        };
    }

    public static function potassium(?float $k): ?int
    {
        if ($k === null) {
            return null;
        }
        return match (true) {
            $k >= 7   => 4,
            $k >= 6   => 3,
            $k >= 5.5 => 1,
            $k >= 3.5 => 0,
            $k >= 3   => 1,
            $k >= 2.5 => 2,
            default   => 4,
        };
    }

    /** Points are doubled in acute renal failure. */
    public static function creatinine(?float $cr, bool $acuteRenalFailure): ?int
    {
        if ($cr === null) {
            return null;
        }
        $points = match (true) {
            $cr >= 3.5 => 4,
            $cr >= 2   => 3,
            $cr >= 1.5 => 2,
            $cr >= 0.6 => 0,
            default    => 2,
        };
        return $acuteRenalFailure ? $points * 2 : $points;
    }

    public static function hematocrit(?float $hct): ?int
    {
        if ($hct === null) {
            return null;
        }
        return match (true) {
            $hct >= 60 => 4,
            $hct >= 50 => 2,
            $hct >= 46 => 1,
            $hct >= 30 => 0,
            $hct >= 20 => 2,
            default    => 4,
        };
    }

    public static function whiteBloodCells(?float $wbc): ?int
    {
        if ($wbc === null) {
            return null;
        }
        return match (true) {
            $wbc >= 40 => 4,
            $wbc >= 20 => 2,
            $wbc >= 15 => 1,
            $wbc >= 3  => 0,
            $wbc >= 1  => 2,
            default    => 4,
        };
    }

    /** 15 minus the Glasgow Coma Scale. */
    public static function glasgow(?float $gcs): ?int
    {
        if ($gcs === null || $gcs < 3 || $gcs > 15) {
            return null;
        }
        return 15 - (int) $gcs;
    }

    public static function age(?float $years): int
    {
        if ($years === null || $years < 0) {
            return 0;
        }
        return match (true) {
            $years >= 75 => 6,
            $years >= 65 => 5,
            $years >= 55 => 3,
            $years >= 45 => 2,
            default      => 0,
        };
    }

    /**
     * History of severe organ insufficiency or immunocompromise:
     * 5 points for non-operative or emergency postoperative patients, 2 for elective postoperative.
     */
    public static function chronicHealth(bool $hasChronicDisease, string $admissionType): int
    {
        if (!$hasChronicDisease) {
            return 0;
        }
        return $admissionType === self::ADMISSION_ELECTIVE_POSTOP ? 2 : 5;
    }
}
