<?php

/**
 * APACHE II Score Form - report.php
 * Renders a summary of the APACHE II score for the OpenEMR encounter report view.
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
use OpenEMR\Modules\Nursing\Scoring\ApacheIIScore;

function escala_apache_report(int $pid, int $encounter, int $cols, int $id): void
{
    if (!AclMain::aclCheckCore('encounters', 'notes')) {
        echo "<p>" . xlt("Access denied") . "</p>";
        return;
    }

    /** @var array<string, string|int|null>|false $result */
    $result = QueryUtils::querySingleRow(
        "SELECT * FROM form_escala_apache WHERE id = ? AND pid = ? LIMIT 1",
        [$id, $pid]
    );

    if (!$result) {
        echo "<div style='padding:10px;color:#c0392b;'>" . xlt("No data found") . "</div>";
        return;
    }

    // Drops the trailing zeros MySQL adds to DECIMAL columns ("37.0" -> "37").
    $num = static function (mixed $value): string {
        if ($value === null || $value === '') {
            return '';
        }
        $s = (string) $value;
        if (str_contains($s, '.')) {
            $s = rtrim(rtrim($s, '0'), '.');
        }
        return $s;
    };
    $withUnit = static fn(string $value, string $unit): string => ($value === '') ? '' : $value . ' ' . $unit;

    // Per-variable points are recalculated from the stored raw values.
    $calc = ApacheIIScore::calculate($result);
    $d    = $calc['detalle'];

    $ox = [];
    if (($f = $num($result['fio2'] ?? null)) !== '') {
        $ox[] = 'FiO2 ' . $f;
    }
    if (($p = $num($result['pao2'] ?? null)) !== '') {
        $ox[] = 'PaO2 ' . $p . ' mmHg';
    }
    if (($c = $num($result['paco2'] ?? null)) !== '') {
        $ox[] = 'PaCO2 ' . $c . ' mmHg';
    }
    $ph      = $num($result['ph'] ?? null);
    $hco3    = $num($result['bicarbonato'] ?? null);
    $useHco3 = ($ph === '' && $hco3 !== '');
    $creat   = $withUnit($num($result['creatinina'] ?? null), 'mg/dL');
    if ($creat !== '' && !empty($result['insuficiencia_renal_aguda'])) {
        $creat .= ' (' . xl('Acute renal failure') . ')';
    }

    $rows = [
        [xl('Rectal temperature'), $withUnit($num($result['temperatura'] ?? null), '°C'), $d['temperatura'] ?? null],
        [xl('Mean arterial pressure'), $withUnit($num($result['pam'] ?? null), 'mmHg'), $d['pam'] ?? null],
        [xl('Heart rate'), $withUnit($num($result['frecuencia_cardiaca'] ?? null), 'bpm'), $d['fc'] ?? null],
        [xl('Respiratory rate'), $withUnit($num($result['frecuencia_respiratoria'] ?? null), 'rpm'), $d['fr'] ?? null],
        [xl('Oxygenation'), implode(' / ', $ox), $d['oxigenacion'] ?? null],
        $useHco3
            ? [xl('Serum HCO3'), $withUnit($hco3, 'mmol/L'), $d['ph'] ?? null]
            : [xl('Arterial pH'), $ph, $d['ph'] ?? null],
        [xl('Sodium'), $withUnit($num($result['sodio'] ?? null), 'mmol/L'), $d['sodio'] ?? null],
        [xl('Potassium'), $withUnit($num($result['potasio'] ?? null), 'mmol/L'), $d['potasio'] ?? null],
        [xl('Creatinine'), $creat, $d['creatinina'] ?? null],
        [xl('Hematocrit'), $withUnit($num($result['hematocrito'] ?? null), '%'), $d['hematocrito'] ?? null],
        [xl('White blood cells'), $withUnit($num($result['leucocitos'] ?? null), 'x10³/µL'), $d['leucocitos'] ?? null],
        [xl('Glasgow Coma Scale'), $num($result['glasgow'] ?? null), $d['glasgow'] ?? null],
    ];

    $total     = (int)($result['apache_total'] ?? $calc['total']);
    $evaluados = (int)($result['apache_evaluados'] ?? $calc['evaluados']);
    $fisio     = (int)($result['pts_fisiologicos'] ?? $calc['fisiologicos']);
    $pts_edad  = (int)($result['pts_edad'] ?? $calc['edad']);
    $pts_cron  = (int)($result['pts_cronicos'] ?? $calc['cronicos']);
    $aado2     = $num($result['a_ado2'] ?? null);

    if ($total >= 25) {
        $level_text  = xl('Higher severity');
        $level_color = '#e74c3c';
        $level_bg    = 'rgba(231, 76, 60, 0.10)';
    } elseif ($total >= 15) {
        $level_text  = xl('Moderate severity');
        $level_color = '#e67e22';
        $level_bg    = 'rgba(230, 126, 34, 0.10)';
    } else {
        $level_text  = xl('Lower severity');
        $level_color = '#27ae60';
        $level_bg    = 'rgba(39, 174, 96, 0.10)';
    }

    $admission_types = [
        ApacheIIScore::ADMISSION_NON_OPERATIVE    => xl('Non-operative'),
        ApacheIIScore::ADMISSION_EMERGENCY_POSTOP => xl('Emergency postoperative'),
        ApacheIIScore::ADMISSION_ELECTIVE_POSTOP  => xl('Elective postoperative'),
    ];
    $tipo       = (string)($result['tipo_ingreso'] ?? '');
    $cron_value = !empty($result['enfermedad_cronica'])
        ? xl('Yes') . ($tipo !== '' && isset($admission_types[$tipo]) ? ' — ' . $admission_types[$tipo] : '')
        : xl('No');
    $edad_value = $withUnit($num($result['edad'] ?? null), xl('years'));

    $hora_raw = (string)($result['hora_registro'] ?? '');
    $hora     = $hora_raw !== '' ? substr($hora_raw, 0, 5) : '-';
    $date_raw = (string)($result['date'] ?? '');
    $ts_fecha = $date_raw !== '' ? strtotime($date_raw) : false;
    $fecha    = $ts_fecha !== false ? date('d/m/Y H:i', $ts_fecha) : '-';
    $user     = (string)($result['user'] ?? '-');
    $obs      = trim((string)($result['observaciones'] ?? ''));
    ?>

    <style>
        .rpt-apache * { box-sizing: border-box; }
        .rpt-apache {
            font-family: Arial, sans-serif;
            font-size: 12px;
            padding: 10px 0;
        }
        .rpt-apache .meta-bar {
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
        .rpt-apache .sec-header {
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
        .rpt-apache table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .rpt-apache table thead th {
            background: #34495e;
            color: #fff;
            padding: 8px 12px;
            font-size: 11px;
            font-weight: bold;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .rpt-apache table tbody tr:nth-child(even) td { background: rgba(128,128,128,0.05); }
        .rpt-apache table tbody td {
            padding: 7px 12px;
            border-bottom: 1px solid rgba(128,128,128,0.15);
            font-size: 12px;
            vertical-align: top;
        }
        .rpt-apache .td-item { font-weight: 600; width: 40%; }
        .rpt-apache .td-pts  { width: 18%; text-align: center; font-weight: bold; }
        .rpt-apache .td-sub  { padding-left: 24px; opacity: 0.75; font-style: italic; }
        .rpt-apache tr.subtotal td { font-weight: bold; background: rgba(41, 128, 185, 0.10) !important; }
        .rpt-apache .obs-vacia { opacity: 0.45; font-style: italic; font-size: 11px; font-weight: normal; }
        .rpt-apache .apache-card {
            margin-top: 14px;
            border-radius: 6px;
            overflow: hidden;
            border: 2px solid;
        }
        .rpt-apache .apache-card-header {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 7px 14px;
            color: #fff;
        }
        .rpt-apache .apache-card-body {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 14px 18px;
        }
        .rpt-apache .apache-big { font-size: 34px; font-weight: bold; line-height: 1; }
        .rpt-apache .apache-big small { font-size: 12px; font-weight: normal; opacity: 0.7; }
        .rpt-apache .apache-label { font-size: 16px; font-weight: bold; }
        .rpt-apache .apache-legend { margin-left: auto; font-size: 10px; opacity: 0.7; line-height: 1.7; text-align: right; }
        .rpt-apache .obs-box { padding: 9px 12px; border: 1px solid rgba(128,128,128,0.2); border-top: none; }

        @media print {
            .rpt-apache .sec-header { background: #000 !important; -webkit-print-color-adjust: exact; }
        }
    </style>

    <div class="rpt-apache">

        <div class="meta-bar">
            <span><strong><?php echo xlt('Record Time'); ?>:</strong> <?php echo text($hora); ?></span>
            <span><strong><?php echo xlt('Recorded'); ?>:</strong> <?php echo text($fecha); ?></span>
            <span><strong><?php echo xlt('User'); ?>:</strong> <?php echo text($user); ?></span>
        </div>

        <div class="sec-header"><?php echo xlt('APACHE II Score'); ?></div>
        <table>
            <thead>
                <tr>
                    <th class="td-item"><?php echo xlt('Variable'); ?></th>
                    <th><?php echo xlt('Value'); ?></th>
                    <th class="td-pts"><?php echo xlt('Points'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as [$label, $value, $points]) : ?>
                <tr>
                    <td class="td-item"><?php echo text($label); ?></td>
                    <td><?php echo $value !== '' ? text($value) : '<span class="obs-vacia">—</span>'; ?></td>
                    <td class="td-pts">
                        <?php echo $points === null
                            ? '<span class="obs-vacia">' . xlt('Not evaluated') . '</span>'
                            : text((string)$points); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($aado2 !== '') : ?>
                <tr>
                    <td class="td-item td-sub">A-aDO2</td>
                    <td><?php echo text($aado2 . ' mmHg'); ?></td>
                    <td class="td-pts"></td>
                </tr>
            <?php endif; ?>
                <tr class="subtotal">
                    <td colspan="2"><?php echo xlt('Acute physiology subtotal'); ?></td>
                    <td class="td-pts"><?php echo text((string)$fisio); ?></td>
                </tr>
                <tr>
                    <td class="td-item"><?php echo xlt('Age'); ?></td>
                    <td><?php echo $edad_value !== '' ? text($edad_value) : '<span class="obs-vacia">—</span>'; ?></td>
                    <td class="td-pts"><?php echo text((string)$pts_edad); ?></td>
                </tr>
                <tr>
                    <td class="td-item"><?php echo xlt('Chronic health'); ?></td>
                    <td><?php echo text($cron_value); ?></td>
                    <td class="td-pts"><?php echo text((string)$pts_cron); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="apache-card" style="border-color:<?php echo attr($level_color); ?>">
            <div class="apache-card-header" style="background:<?php echo attr($level_color); ?>">
                <?php echo xlt('APACHE II Score'); ?>
            </div>
            <div class="apache-card-body" style="background:<?php echo attr($level_bg); ?>">
                <div class="apache-big" style="color:<?php echo attr($level_color); ?>">
                    APACHE II = <?php echo text((string)$total); ?>
                    <small>(<?php echo text((string)$evaluados); ?>/12 <?php echo xlt('variables evaluated'); ?>)</small>
                </div>
                <div class="apache-label" style="color:<?php echo attr($level_color); ?>">
                    <?php echo text($level_text); ?>
                </div>
                <div class="apache-legend">
                    0–14: <?php echo xlt('Lower severity'); ?><br>
                    15–24: <?php echo xlt('Moderate severity'); ?><br>
                    ≥25: <?php echo xlt('Higher severity'); ?>
                </div>
            </div>
        </div>

        <div class="sec-header"><?php echo xlt('Observations'); ?></div>
        <div class="obs-box">
            <?php echo $obs !== '' ? nl2br(text($obs)) : '<span class="obs-vacia">' . xlt('No observations recorded') . '</span>'; ?>
        </div>

    </div>
    <?php
}
?>
