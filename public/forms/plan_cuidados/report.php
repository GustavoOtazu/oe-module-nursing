<?php

/**
 * Nursing Care Plan Form - report.php
 * Renders a summary of the care plan / shift progress note for the OpenEMR
 * encounter report view.
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

function plan_cuidados_report(int $pid, int $encounter, int $cols, int $id): void
{
    // Adds "Print" next to "Delete" in the encounter form list (see EncounterFormButtons).
    \OpenEMR\Modules\Nursing\EncounterFormButtons::render('plan_cuidados', 'Nursing Care Plan', (int) $pid, (int) $encounter, (int) $id);

    if (!AclMain::aclCheckCore('encounters', 'notes')) {
        echo "<p>" . xlt("Access denied") . "</p>";
        return;
    }

    /** @var array<string, string|int|null>|false $result */
    $result = QueryUtils::querySingleRow(
        "SELECT * FROM form_plan_cuidados WHERE id = ? AND pid = ? LIMIT 1",
        [$id, $pid]
    );

    if (!$result) {
        echo "<div style='padding:10px;color:#c0392b;'>" . xlt("No data found") . "</div>";
        return;
    }

    $turno_labels = [
        'MANANA' => xl('Morning'),
        'TARDE'  => xl('Afternoon'),
        'NOCHE'  => xl('Night'),
    ];
    // value => [label, background, text color]
    $evaluacion_badges = [
        'LOGRADO'    => [xl('Achieved'),           '#28a745', '#fff'],
        'PARCIAL'    => [xl('Partially achieved'), '#ffc107', '#000'],
        'NO_LOGRADO' => [xl('Not achieved'),       '#dc3545', '#fff'],
        'EN_CURSO'   => [xl('In progress'),        '#17a2b8', '#fff'],
    ];

    $turno_val = (string)($result['turno'] ?? '');
    $turno     = $turno_labels[$turno_val] ?? '-';
    $hora_raw  = substr((string)($result['hora_registro'] ?? ''), 0, 5);
    $hora      = $hora_raw !== '' ? $hora_raw : '-';
    $date_raw  = (string)($result['date'] ?? '');
    $ts_fecha  = $date_raw !== '' ? strtotime($date_raw) : false;
    $fecha     = $ts_fecha !== false ? date('d/m/Y H:i', $ts_fecha) : '-';
    $user      = (string)($result['user'] ?? '-');
    $eval_val  = (string)($result['evaluacion_resultado'] ?? '');
    $codigo    = trim((string)($result['codigo_diagnostico'] ?? ''));

    $sections = [
        'diagnostico_enfermeria' => xl('Nursing diagnosis'),
        'objetivo'               => xl('Goal / expected outcome'),
        'intervenciones'         => xl('Nursing interventions'),
        'evaluacion_resultado'   => xl('Outcome evaluation'),
        'evolucion'              => xl('Shift progress note'),
        'observaciones'          => xl('Observations'),
    ];
    ?>

    <style>
        .rpt-plan-cuidados * { box-sizing: border-box; }
        .rpt-plan-cuidados {
            font-family: Arial, sans-serif;
            font-size: 12px;
            padding: 10px 0;
        }
        .rpt-plan-cuidados .meta-bar {
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
        .rpt-plan-cuidados .sec-header {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 7px 12px;
            border-radius: 4px 4px 0 0;
            margin-top: 12px;
            color: #fff;
            background: #2c3e50;
        }
        .rpt-plan-cuidados .sec-body {
            padding: 9px 12px;
            border: 1px solid rgba(128,128,128,0.2);
            border-top: none;
            border-radius: 0 0 4px 4px;
            background: rgba(128,128,128,0.05);
            line-height: 1.5;
        }
        .rpt-plan-cuidados .code-tag {
            display: inline-block;
            margin-top: 6px;
            font-size: 11px;
            opacity: 0.8;
        }
        .rpt-plan-cuidados .eval-badge {
            display: inline-block;
            font-size: 11px;
            font-weight: bold;
            padding: 3px 10px;
            border-radius: 3px;
        }
        @media print {
            .rpt-plan-cuidados .sec-header { background: #000 !important; -webkit-print-color-adjust: exact; }
            .rpt-plan-cuidados .eval-badge { -webkit-print-color-adjust: exact; }
        }
    </style>

    <div class="rpt-plan-cuidados">

        <!-- META BAR -->
        <div class="meta-bar">
            <span><strong><?php echo xlt('Shift'); ?>:</strong> <?php echo text($turno); ?></span>
            <span><strong><?php echo xlt('Record Time'); ?>:</strong> <?php echo text($hora); ?></span>
            <span><strong><?php echo xlt('Recorded'); ?>:</strong> <?php echo text($fecha); ?></span>
            <span><strong><?php echo xlt('User'); ?>:</strong> <?php echo text($user); ?></span>
        </div>

        <?php foreach ($sections as $field => $label) :
            if ($field === 'evaluacion_resultado') :
                if (!isset($evaluacion_badges[$eval_val])) {
                    continue;
                }
                ?>
        <div class="sec-header"><?php echo text($label); ?></div>
        <div class="sec-body">
            <span class="eval-badge" style="background:<?php echo attr($evaluacion_badges[$eval_val][1]); ?>;color:<?php echo attr($evaluacion_badges[$eval_val][2]); ?>;">
                <?php echo text($evaluacion_badges[$eval_val][0]); ?>
            </span>
        </div>
                <?php
                continue;
            endif;
            $val = trim((string)($result[$field] ?? ''));
            // The diagnosis block is also shown when only its code was entered.
            $show_code = ($field === 'diagnostico_enfermeria' && $codigo !== '');
            if ($val === '' && !$show_code) {
                continue;
            }
            ?>
        <div class="sec-header"><?php echo text($label); ?></div>
        <div class="sec-body">
            <?php if ($val !== '') : ?>
            <div><?php echo nl2br(text($val)); ?></div>
            <?php endif; ?>
            <?php if ($show_code) : ?>
            <span class="code-tag"><strong><?php echo xlt('Diagnosis code'); ?>:</strong> <?php echo text($codigo); ?></span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

    </div>
    <?php
}
?>
