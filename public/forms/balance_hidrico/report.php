<?php

/**
 * Nursing Fluid Balance Form - report.php
 * Renders a summary of the fluid balance for the OpenEMR encounter report view.
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

function balance_hidrico_report(int $pid, int $encounter, int $cols, int $id): void
{
    // Adds "Print" next to "Delete" in the encounter form list (see EncounterFormButtons).
    \OpenEMR\Modules\Nursing\EncounterFormButtons::render('balance_hidrico', 'Nursing Fluid Balance', (int) $pid, (int) $encounter, (int) $id);

    if (!AclMain::aclCheckCore('encounters', 'notes')) {
        echo "<p>" . xlt("Access denied") . "</p>";
        return;
    }

    /** @var array<string, string|int|null>|false $result */
    $result = QueryUtils::querySingleRow(
        "SELECT * FROM form_balance_hidrico WHERE id = ? AND pid = ? LIMIT 1",
        [$id, $pid]
    );

    if (!$result) {
        echo "<div style='padding:10px;color:#c0392b;'>" . xlt("No data found") . "</div>";
        return;
    }

    // "250.00" -> "250", "12.50" -> "12.5"; empty string when there is no value.
    $fmtMl = static function (mixed $value): string {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return '';
        }
        $s = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
        return ($s === '-0' || $s === '') ? '0' : $s;
    };

    $intake_labels = [
        'ing_via_oral'      => xlt('Oral intake'),
        'ing_enteral'       => xlt('Enteral nutrition'),
        'ing_parenteral'    => xlt('Parenteral nutrition'),
        'ing_sueros'        => xlt('IV fluids'),
        'ing_medicacion'    => xlt('IV medication'),
        'ing_hemoderivados' => xlt('Blood products'),
        'ing_otros'         => xlt('Other intake'),
    ];
    $output_labels = [
        'eg_diuresis'             => xlt('Urine output'),
        'eg_drenajes'             => xlt('Drains'),
        'eg_sng'                  => xlt('Nasogastric tube'),
        'eg_vomitos'              => xlt('Vomiting'),
        'eg_deposiciones'         => xlt('Stools'),
        'eg_perdidas_insensibles' => xlt('Insensible losses'),
        'eg_otros'                => xlt('Other output'),
    ];
    $shift_labels = [
        'MANANA' => xlt('Morning'),
        'TARDE'  => xlt('Afternoon'),
        'NOCHE'  => xlt('Night'),
    ];

    $balance = (float)($result['balance'] ?? 0);
    if ($balance > 0) {
        $bal_text  = '+' . $fmtMl($balance);
        $bal_color = '#e67e22';
        $bal_bg    = 'rgba(230, 126, 34, 0.12)';
        $bal_label = xlt('Positive balance');
    } elseif ($balance < 0) {
        $bal_text  = $fmtMl($balance);
        $bal_color = '#2980b9';
        $bal_bg    = 'rgba(41, 128, 185, 0.12)';
        $bal_label = xlt('Negative balance');
    } else {
        $bal_text  = '0';
        $bal_color = '#7f8c8d';
        $bal_bg    = 'rgba(128, 128, 128, 0.10)';
        $bal_label = xlt('Neutral balance');
    }

    $turno    = (string)($result['turno'] ?? '');
    $shift    = text($shift_labels[$turno] ?? '-');
    $hora_raw = substr((string)($result['hora_registro'] ?? ''), 0, 5);
    $hora     = text($hora_raw !== '' ? $hora_raw : '-');
    $date_raw = $result['date'] ?? '';
    $ts_fecha = $date_raw !== '' ? strtotime((string) $date_raw) : false;
    $fecha    = text($ts_fecha !== false ? date('d/m/Y H:i', $ts_fecha) : '-');
    $user     = text((string)($result['user'] ?? '-'));
    $obs      = trim((string)($result['observaciones'] ?? ''));
    $total_in  = $fmtMl($result['total_ingresos'] ?? null) ?: '0';
    $total_out = $fmtMl($result['total_egresos'] ?? null) ?: '0';

    $sections = [
        ['title' => xlt('Intake'), 'class' => 'intake', 'labels' => $intake_labels,
         'total_label' => xlt('Total intake'), 'total' => $total_in],
        ['title' => xlt('Output'), 'class' => 'output', 'labels' => $output_labels,
         'total_label' => xlt('Total output'), 'total' => $total_out],
    ];
    ?>

    <style>
        .rpt-bh * { box-sizing: border-box; }
        .rpt-bh {
            font-family: Arial, sans-serif;
            font-size: 12px;
            padding: 10px 0;
        }
        .rpt-bh .meta-bar {
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
        .rpt-bh .sec-header {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 7px 12px;
            border-radius: 4px 4px 0 0;
            margin-top: 14px;
            color: #fff;
        }
        .rpt-bh .sec-header.intake { background: #17a2b8; }
        .rpt-bh .sec-header.output { background: #e67e22; }
        .rpt-bh table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .rpt-bh table tbody tr:nth-child(even) td { background: rgba(128,128,128,0.05); }
        .rpt-bh table tbody td {
            padding: 7px 12px;
            border-bottom: 1px solid rgba(128,128,128,0.15);
            font-size: 12px;
        }
        .rpt-bh .td-item { font-weight: 600; width: 60%; }
        .rpt-bh .td-val  { text-align: right; }
        .rpt-bh tr.total-row td { font-weight: bold; background: rgba(128,128,128,0.12) !important; }
        .rpt-bh .obs-vacia { opacity: 0.45; font-style: italic; font-size: 11px; }
        .rpt-bh .balance-card {
            margin-top: 14px;
            border-radius: 6px;
            border: 2px solid;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .rpt-bh .balance-big { font-size: 32px; font-weight: bold; line-height: 1; }
        .rpt-bh .balance-big small { font-size: 14px; font-weight: normal; }
        .rpt-bh .balance-label { font-size: 14px; font-weight: bold; }
        .rpt-bh .obs-box {
            margin-top: 14px;
            background: rgba(128,128,128,0.08);
            border-left: 4px solid #6c757d;
            border-radius: 4px;
            padding: 8px 14px;
        }

        @media print {
            .rpt-bh .sec-header.intake { background: #000 !important; -webkit-print-color-adjust: exact; }
            .rpt-bh .sec-header.output { background: #555 !important; -webkit-print-color-adjust: exact; }
        }
    </style>

    <div class="rpt-bh">

        <!-- META BAR -->
        <div class="meta-bar">
            <span><strong><?php echo xlt('Shift'); ?>:</strong> <?php echo $shift; ?></span>
            <span><strong><?php echo xlt('Record Time'); ?>:</strong> <?php echo $hora; ?></span>
            <span><strong><?php echo xlt('Recorded'); ?>:</strong> <?php echo $fecha; ?></span>
            <span><strong><?php echo xlt('User'); ?>:</strong> <?php echo $user; ?></span>
        </div>

        <?php foreach ($sections as $section) : ?>
        <div class="sec-header <?php echo attr($section['class']); ?>"><?php echo text($section['title']); ?> (mL)</div>
        <table>
            <tbody>
            <?php
            $has_rows = false;
            foreach ($section['labels'] as $field => $label) :
                $val = $fmtMl($result[$field] ?? null);
                if ($val === '') {
                    continue;
                }
                $has_rows = true;
                ?>
                <tr>
                    <td class="td-item"><?php echo text($label); ?></td>
                    <td class="td-val"><?php echo text($val . ' mL'); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$has_rows) : ?>
                <tr>
                    <td class="td-item" colspan="2"><span class="obs-vacia"><?php echo xlt('No values recorded'); ?></span></td>
                </tr>
            <?php endif; ?>
                <tr class="total-row">
                    <td class="td-item"><?php echo text($section['total_label']); ?></td>
                    <td class="td-val"><?php echo text($section['total'] . ' mL'); ?></td>
                </tr>
            </tbody>
        </table>
        <?php endforeach; ?>

        <!-- CARD: FLUID BALANCE -->
        <div class="balance-card" style="border-color:<?php echo attr($bal_color); ?>; background:<?php echo attr($bal_bg); ?>;">
            <div class="balance-big" style="color:<?php echo attr($bal_color); ?>">
                <?php echo text($bal_text); ?><small> mL</small>
            </div>
            <div>
                <div><?php echo xlt('Fluid balance'); ?></div>
                <div class="balance-label" style="color:<?php echo attr($bal_color); ?>"><?php echo text($bal_label); ?></div>
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
