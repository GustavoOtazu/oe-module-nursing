<?php

/**
 * Nursing Nutrition Form - report.php
 * Renders a summary of the nutrition record for the OpenEMR encounter report view.
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

function alimentacion_report(int $pid, int $encounter, int $cols, int $id): void
{
    if (!AclMain::aclCheckCore('encounters', 'notes')) {
        echo "<p>" . xlt("Access denied") . "</p>";
        return;
    }

    /** @var array<string, string|int|null>|false $result */
    $result = QueryUtils::querySingleRow(
        "SELECT * FROM form_alimentacion WHERE id = ? AND pid = ? LIMIT 1",
        [$id, $pid]
    );

    if (!$result) {
        echo "<div style='padding:10px;color:#c0392b;'>" . xlt("No data found") . "</div>";
        return;
    }

    // Label maps hold xlt() output, which is already HTML-escaped.
    $tipo_labels = [
        'ORAL'       => xlt('Oral'),
        'ENTERAL'    => xlt('Enteral'),
        'PARENTERAL' => xlt('Parenteral'),
        'MIXTA'      => xlt('Mixed'),
        'AYUNO'      => xlt('Fasting'),
    ];
    $via_labels = [
        'SNG'          => xlt('Nasogastric tube'),
        'SNY'          => xlt('Nasojejunal tube'),
        'GASTROSTOMIA' => xlt('Gastrostomy'),
        'YEYUNOSTOMIA' => xlt('Jejunostomy'),
    ];
    $modalidad_labels = [
        'CONTINUA'     => xlt('Continuous'),
        'INTERMITENTE' => xlt('Intermittent'),
        'BOLO'         => xlt('Bolus'),
    ];
    $tolerancia_labels = [
        'BUENA'   => xlt('Good'),
        'REGULAR' => xlt('Fair'),
        'MALA'    => xlt('Poor'),
    ];
    $signos_labels = [
        'VOMITOS'      => xlt('Vomiting'),
        'DISTENSION'   => xlt('Abdominal distension'),
        'DIARREA'      => xlt('Diarrhea'),
        'RESIDUO_ALTO' => xlt('High gastric residual'),
    ];

    // Safe HTML for a coded value ('' when empty, so the row is skipped).
    $label = static function (array $map, string $code): string {
        if ($code === '') {
            return '';
        }
        return $map[$code] ?? text($code);
    };
    // Safe HTML for a decimal with its unit ('' when empty).
    $qty = static function (mixed $value, string $unit): string {
        if ($value === null || $value === '') {
            return '';
        }
        $s = (string) $value;
        $s = str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
        return text($s . ' ' . $unit);
    };

    $tipo         = (string)($result['tipo_alimentacion'] ?? '');
    $usa_via      = ($tipo === 'ENTERAL' || $tipo === 'MIXTA');
    $sin_infusion = ($tipo === 'ORAL' || $tipo === 'AYUNO');

    $signos_raw  = (string)($result['signos_intolerancia'] ?? '');
    $signos_html = [];
    foreach (($signos_raw !== '') ? explode(',', $signos_raw) : [] as $code) {
        $signos_html[] = $label($signos_labels, $code);
    }

    // [label, safe HTML value]; irrelevant or empty fields are left out.
    $candidates = [
        [xlt('Feeding Type'), $label($tipo_labels, $tipo)],
        [xlt('Enteral Route'), $usa_via ? $label($via_labels, (string)($result['via_enteral'] ?? '')) : ''],
        [xlt('Modality'), $sin_infusion ? '' : $label($modalidad_labels, (string)($result['modalidad'] ?? ''))],
        [xlt('Formula / Diet'), text(trim((string)($result['formula'] ?? '')))],
        [xlt('Volume'), ($tipo === 'AYUNO') ? '' : $qty($result['volumen_ml'] ?? null, 'mL')],
        [xlt('Infusion Rate'), $sin_infusion ? '' : $qty($result['velocidad_ml_h'] ?? null, 'mL/h')],
        [xlt('Gastric Residual'), $qty($result['residuo_gastrico_ml'] ?? null, 'mL')],
        [xlt('Tolerance'), $label($tolerancia_labels, (string)($result['tolerancia'] ?? ''))],
        [xlt('Signs of Intolerance'), implode(', ', $signos_html)],
    ];
    $rows = array_filter($candidates, static fn(array $r): bool => $r[1] !== '');

    $obs = trim((string)($result['observaciones'] ?? ''));

    $hora_raw = substr((string)($result['hora_registro'] ?? ''), 0, 5);
    $hora     = text($hora_raw !== '' ? $hora_raw : '-');
    $date_raw = (string)($result['date'] ?? '');
    $ts_fecha = $date_raw !== '' ? strtotime($date_raw) : false;
    $fecha    = text($ts_fecha !== false ? date('d/m/Y H:i', $ts_fecha) : '-');
    $user     = text((string)($result['user'] ?? '-'));
    ?>

    <style>
        .rpt-alim * { box-sizing: border-box; }
        .rpt-alim {
            font-family: Arial, sans-serif;
            font-size: 12px;
            padding: 10px 0;
        }
        .rpt-alim .meta-bar {
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
        .rpt-alim .sec-header {
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
        .rpt-alim table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .rpt-alim table thead th {
            background: #34495e;
            color: #fff;
            padding: 8px 12px;
            font-size: 11px;
            font-weight: bold;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .rpt-alim table tbody tr:nth-child(even) td { background: rgba(128,128,128,0.05); }
        .rpt-alim table tbody td {
            padding: 9px 12px;
            border-bottom: 1px solid rgba(128,128,128,0.15);
            font-size: 12px;
            vertical-align: top;
        }
        .rpt-alim .td-item { font-weight: 600; color: inherit; width: 30%; }
        .rpt-alim .obs-box {
            border: 1px solid rgba(128,128,128,0.2);
            border-top: none;
            padding: 9px 12px;
        }
        .rpt-alim .obs-vacia { color: inherit; opacity: 0.45; font-style: italic; font-size: 11px; }
        @media print {
            .rpt-alim .sec-header { background: #000 !important; -webkit-print-color-adjust: exact; }
        }
    </style>

    <div class="rpt-alim">

        <div class="meta-bar">
            <span><strong><?php echo xlt('Record Time'); ?>:</strong> <?php echo $hora; ?></span>
            <span><strong><?php echo xlt('Recorded'); ?>:</strong> <?php echo $fecha; ?></span>
            <span><strong><?php echo xlt('User'); ?>:</strong> <?php echo $user; ?></span>
        </div>

        <div class="sec-header"><?php echo xlt('Nursing Nutrition'); ?></div>
        <?php if ($rows !== []) : ?>
        <table>
            <thead>
                <tr>
                    <th class="td-item"><?php echo xlt('Item'); ?></th>
                    <th><?php echo xlt('Value'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as [$r_label, $r_value]) : ?>
                <tr>
                    <td class="td-item"><?php echo $r_label; ?></td>
                    <td><?php echo $r_value; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <div class="sec-header"><?php echo xlt('Observations'); ?></div>
        <div class="obs-box">
            <?php echo $obs !== '' ? nl2br(text($obs)) : '<span class="obs-vacia">' . xlt('No observations recorded') . '</span>'; ?>
        </div>

    </div>
    <?php
}
