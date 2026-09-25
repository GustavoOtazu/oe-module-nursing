<?php

/**
 * SOFA Score Form - report.php
 * Renders a summary of the SOFA score for the OpenEMR encounter report view.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once(dirname(__DIR__, 6) . "/globals.php");

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Database\QueryUtils;

function escala_sofa_report(int $pid, int $encounter, int $cols, int $id): void
{
    // Adds "Print" next to "Delete" in the encounter form list (see EncounterFormButtons).
    \OpenEMR\Modules\Nursing\EncounterFormButtons::render('escala_sofa', 'SOFA Score', (int) $pid, (int) $encounter, (int) $id);

    if (!AclMain::aclCheckCore('encounters', 'notes')) {
        echo "<p>" . xlt("Access denied") . "</p>";
        return;
    }

    /** @var array<string, string|int|null>|false $result */
    $result = QueryUtils::querySingleRow(
        "SELECT * FROM form_escala_sofa WHERE id = ? AND pid = ? LIMIT 1",
        [$id, $pid]
    );

    if (!$result) {
        echo "<div style='padding:10px;color:#c0392b;'>" . xlt("No data found") . "</div>";
        return;
    }

    $fmt = static function (mixed $value): string {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return '';
        }
        return rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.');
    };
    $join = static fn(array $parts): string => implode(' · ', array_filter($parts, static fn($p) => $p !== ''));
    $with = static fn(string $prefix, mixed $value, string $unit = ''): string
        => $fmt($value) !== '' ? trim($prefix . ' ' . $fmt($value) . ' ' . $unit) : '';
    $points = static fn(mixed $value): ?int => ($value === null || $value === '') ? null : (int) $value;

    $systems = [
        [xl('Respiratory system'), $join([
            $with('PaO2', $result['pao2'] ?? null, 'mmHg'),
            $with('FiO2', $result['fio2'] ?? null),
            ((int)($result['soporte_respiratorio'] ?? 0) === 1) ? xl('Mechanical ventilation / respiratory support') : '',
        ]), $points($result['sofa_resp'] ?? null)],
        [xl('Coagulation'), $with(xl('Platelets'), $result['plaquetas'] ?? null, 'x10³/µL'), $points($result['sofa_coag'] ?? null)],
        [xl('Liver'), $with(xl('Bilirubin'), $result['bilirrubina'] ?? null, 'mg/dL'), $points($result['sofa_hepatico'] ?? null)],
        [xl('Cardiovascular'), $join([
            $with(xl('MAP'), $result['pam'] ?? null, 'mmHg'),
            $with(xl('Dopamine'), $result['dopamina'] ?? null, 'µg/kg/min'),
            ((int)($result['dobutamina'] ?? 0) === 1) ? xl('Dobutamine') : '',
            $with(xl('Epinephrine'), $result['epinefrina'] ?? null, 'µg/kg/min'),
            $with(xl('Norepinephrine'), $result['norepinefrina'] ?? null, 'µg/kg/min'),
        ]), $points($result['sofa_cardio'] ?? null)],
        [xl('Central nervous system'), $with('Glasgow', $result['glasgow'] ?? null), $points($result['sofa_snc'] ?? null)],
        [xl('Renal'), $join([
            $with(xl('Creatinine'), $result['creatinina'] ?? null, 'mg/dL'),
            $with(xl('Urine output'), $result['diuresis_24h'] ?? null, 'mL/24h'),
        ]), $points($result['sofa_renal'] ?? null)],
    ];

    $total     = (int)($result['sofa_total'] ?? 0);
    $evaluados = (int)($result['sofa_evaluados'] ?? 0);
    if ($total >= 12) {
        $level_text  = xl('High');
        $level_color = '#e74c3c';
        $level_bg    = 'rgba(231, 76, 60, 0.10)';
    } elseif ($total >= 7) {
        $level_text  = xl('Intermediate');
        $level_color = '#e67e22';
        $level_bg    = 'rgba(230, 126, 34, 0.10)';
    } else {
        $level_text  = xl('Low');
        $level_color = '#27ae60';
        $level_bg    = 'rgba(39, 174, 96, 0.10)';
    }

    $pafi     = $fmt($result['pafi'] ?? null);
    $obs      = trim((string)($result['observaciones'] ?? ''));
    $hora_raw = substr((string)($result['hora_registro'] ?? ''), 0, 5);
    $date_raw = (string)($result['date'] ?? '');
    $ts_fecha = $date_raw !== '' ? strtotime($date_raw) : false;
    $fecha    = $ts_fecha !== false ? date('d/m/Y H:i', $ts_fecha) : '-';
    $user     = (string)($result['user'] ?? '-');
    ?>

    <style>
        .rpt-sofa * { box-sizing: border-box; }
        .rpt-sofa {
            font-family: Arial, sans-serif;
            font-size: 12px;
            padding: 10px 0;
        }
        .rpt-sofa .meta-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            background: rgba(128,128,128,0.08);
            border: 1px solid rgba(128,128,128,0.2);
            border-radius: 4px;
            padding: 8px 14px;
            margin-bottom: 14px;
            font-size: 11px;
        }
        .rpt-sofa .sec-header {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 7px 12px;
            border-radius: 4px 4px 0 0;
            margin-top: 14px;
            color: #fff;
            background: #2c3e50;
        }
        .rpt-sofa table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .rpt-sofa table thead th {
            background: #34495e;
            color: #fff;
            padding: 8px 12px;
            font-size: 11px;
            font-weight: bold;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .rpt-sofa table tbody tr:nth-child(even) td { background: rgba(128,128,128,0.05); }
        .rpt-sofa table tbody td {
            padding: 9px 12px;
            border-bottom: 1px solid rgba(128,128,128,0.15);
            font-size: 12px;
            vertical-align: top;
        }
        .rpt-sofa .td-item { font-weight: 600; width: 28%; }
        .rpt-sofa .td-pts  { width: 16%; text-align: center; }
        .rpt-sofa .val-badge {
            display: inline-block;
            background: #2980b9;
            color: #fff;
            font-size: 10px;
            font-weight: bold;
            padding: 3px 10px;
            border-radius: 3px;
        }
        .rpt-sofa .obs-vacia {
            opacity: 0.45;
            font-style: italic;
            font-size: 11px;
        }
        .rpt-sofa .sofa-card {
            margin-top: 14px;
            border-radius: 6px;
            overflow: hidden;
            border: 2px solid;
        }
        .rpt-sofa .sofa-card-header {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 7px 14px;
            color: #fff;
        }
        .rpt-sofa .sofa-card-body {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 14px 18px;
        }
        .rpt-sofa .sofa-big {
            font-size: 40px;
            font-weight: bold;
            line-height: 1;
        }
        .rpt-sofa .sofa-big small {
            font-size: 16px;
            font-weight: normal;
            opacity: 0.6;
        }
        .rpt-sofa .sofa-label { font-size: 16px; font-weight: bold; }
        .rpt-sofa .sofa-legend {
            margin-left: auto;
            font-size: 10px;
            opacity: 0.7;
            line-height: 1.7;
            text-align: right;
        }
        .rpt-sofa .obs-box {
            margin-top: 12px;
            padding: 8px 12px;
            border-left: 4px solid #0d6efd;
            background: rgba(13, 110, 253, 0.08);
            border-radius: 4px;
        }
        @media print {
            .rpt-sofa .sec-header { background: #000 !important; -webkit-print-color-adjust: exact; }
        }
    </style>

    <div class="rpt-sofa">

        <!-- META BAR -->
        <div class="meta-bar">
            <span><strong><?php echo xlt('Record Time'); ?>:</strong> <?php echo text($hora_raw !== '' ? $hora_raw : '-'); ?></span>
            <span><strong><?php echo xlt('Recorded'); ?>:</strong> <?php echo text($fecha); ?></span>
            <span><strong><?php echo xlt('User'); ?>:</strong> <?php echo text($user); ?></span>
        </div>

        <!-- SYSTEMS -->
        <div class="sec-header"><?php echo xlt('Sequential Organ Failure Assessment'); ?></div>
        <table>
            <thead>
                <tr>
                    <th class="td-item"><?php echo xlt('System'); ?></th>
                    <th><?php echo xlt('Values'); ?></th>
                    <th class="td-pts"><?php echo xlt('Points'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($systems as [$label, $values_txt, $pts]) : ?>
                <tr>
                    <td class="td-item"><?php echo text($label); ?></td>
                    <td><?php echo $values_txt !== '' ? text($values_txt) : '<span class="obs-vacia">—</span>'; ?></td>
                    <td class="td-pts">
                        <?php if ($pts === null) : ?>
                            <span class="obs-vacia"><?php echo xlt('Not evaluated'); ?></span>
                        <?php else : ?>
                            <span class="val-badge"><?php echo text((string)$pts); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
                <tr>
                    <td class="td-item"><?php echo xlt('PaO2/FiO2 ratio'); ?></td>
                    <td colspan="2"><?php echo $pafi !== '' ? text($pafi) : '<span class="obs-vacia">—</span>'; ?></td>
                </tr>
            </tbody>
        </table>

        <!-- CARD: SOFA TOTAL -->
        <div class="sofa-card" style="border-color:<?php echo attr($level_color); ?>">
            <div class="sofa-card-header" style="background:<?php echo attr($level_color); ?>">
                <?php echo xlt('SOFA Score'); ?> — <?php echo xlt('Total Score'); ?>
            </div>
            <div class="sofa-card-body" style="background:<?php echo attr($level_bg); ?>">
                <div class="sofa-big" style="color:<?php echo attr($level_color); ?>">
                    <?php echo text((string)$total); ?><small>/24</small>
                </div>
                <div>
                    <div class="sofa-label" style="color:<?php echo attr($level_color); ?>"><?php echo text($level_text); ?></div>
                    <div>(<?php echo text((string)$evaluados); ?>/6 <?php echo xlt('systems evaluated'); ?>)</div>
                </div>
                <div class="sofa-legend">
                    0–6: <?php echo xlt('Low'); ?><br>
                    7–11: <?php echo xlt('Intermediate'); ?><br>
                    ≥12: <?php echo xlt('High'); ?><br>
                    Vincent et al., 1996
                </div>
            </div>
        </div>

        <!-- OBSERVATIONS -->
        <div class="obs-box">
            <strong><?php echo xlt('Observations'); ?>:</strong>
            <?php echo $obs !== '' ? nl2br(text($obs)) : '<span class="obs-vacia">' . xlt('No observations recorded') . '</span>'; ?>
        </div>

    </div>
    <?php
}
?>
