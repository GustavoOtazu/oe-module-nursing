<?php

/**
 * Nursing Fluid Balance Form - print.php
 * Generates a PDF report using mPDF for a single fluid balance record.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once(dirname(__DIR__, 6) . "/globals.php");

use Mpdf\Mpdf;
use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Session\SessionWrapperFactory;

$_print_session = SessionWrapperFactory::getInstance()->getActiveSession();
$pid       = (is_numeric($v = filter_input(INPUT_GET, 'pid', FILTER_SANITIZE_NUMBER_INT)) ? (int) $v : 0)
    ?: (is_numeric($v = $_print_session->get('pid')) ? (int) $v : 0);
$encounter = (is_numeric($v = filter_input(INPUT_GET, 'encounter', FILTER_SANITIZE_NUMBER_INT)) ? (int) $v : 0)
    ?: (is_numeric($v = $_print_session->get('encounter')) ? (int) $v : 0);
$id        = is_numeric($v = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT)) ? (int) $v : 0;
if (!$pid || !$encounter || !$id) {
    die(xlt("Error: Missing required parameters."));
}

if (!AclMain::aclCheckCore('encounters', 'notes')) {
    die(xlt('Access denied'));
}

// Load fluid balance record
/** @var array<string, string|int|null>|false $row */
$row = QueryUtils::querySingleRow("SELECT * FROM form_balance_hidrico WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
if (!$row) {
    die(xlt("Error: Record not found or insufficient permissions."));
}

// Load patient data
/** @var array<string, string|int|null>|false $paciente */
$paciente = QueryUtils::querySingleRow("SELECT CONCAT(fname, ' ', lname) AS full_name, pubpid, DOB FROM patient_data WHERE pid = ?", [$pid]);
// Calculate age
$age = '';
$dob_val = $paciente !== false ? (string)($paciente['DOB'] ?? '') : '';
if ($dob_val !== '') {
    $dob = new DateTime($dob_val);
    $age = (new DateTime())->diff($dob)->y . ' ' . xlt('years');
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

// Balance colors — soft, print-friendly
$balance = (float)($row['balance'] ?? 0);
if ($balance > 0) {
    $bal_text   = '+' . $fmtMl($balance);
    $bal_label  = xlt('Positive balance');
    $bal_color  = '#e65100';
    $bal_bg     = '#fff8e1';
    $bal_border = '#ffcc80';
} elseif ($balance < 0) {
    $bal_text   = $fmtMl($balance);
    $bal_label  = xlt('Negative balance');
    $bal_color  = '#1565c0';
    $bal_bg     = '#e3f2fd';
    $bal_border = '#90caf9';
} else {
    $bal_text   = '0';
    $bal_label  = xlt('Neutral balance');
    $bal_color  = '#546e7a';
    $bal_bg     = '#f5f5f5';
    $bal_border = '#cfd8dc';
}
$total_in  = $fmtMl($row['total_ingresos'] ?? null) ?: '0';
$total_out = $fmtMl($row['total_egresos'] ?? null) ?: '0';

// Record date/time/shift
$date_raw    = $row['date'] ?? '';
$ts_rec      = $date_raw !== '' ? strtotime((string) $date_raw) : false;
$rec_date    = $ts_rec !== false ? date('d/m/Y', $ts_rec) : '-';
$hora_raw    = substr((string)($row['hora_registro'] ?? ''), 0, 5);
$rec_time    = ($hora_raw !== '') ? $hora_raw : '-';
$turno       = (string)($row['turno'] ?? '');
$shift_label = $shift_labels[$turno] ?? '-';
$obs         = trim((string)($row['observaciones'] ?? ''));

// Section table: label/value rows, empty rows skipped, bold total row at the end.
// Labels arrive already escaped by xlt(); values are numeric strings.
$fluidTable = function (string $title, string $bg, array $labels, string $totalLabel, string $total) use ($row, $fmtMl): string {
    $html = '<div style="background:' . $bg . ';color:#fff;font-size:9px;font-weight:bold;'
        . 'text-transform:uppercase;letter-spacing:1px;padding:7px 12px;margin:12px 0 0 0;">'
        . $title . ' (mL)</div>'
        . '<table style="width:100%;border-collapse:collapse;margin-bottom:10px;"><tbody>';
    $has_rows = false;
    foreach ($labels as $field => $label) {
        $val = $fmtMl($row[$field] ?? null);
        if ($val === '') {
            continue;
        }
        $has_rows = true;
        $html .= '<tr>'
            . '<td style="padding:6px 10px;border-bottom:1px solid #e4e9ef;font-weight:600;font-size:9px;color:#2c3e50;width:65%;">' . $label . '</td>'
            . '<td style="padding:6px 10px;border-bottom:1px solid #e4e9ef;font-size:9px;text-align:right;">' . text($val) . ' mL</td>'
            . '</tr>';
    }
    if (!$has_rows) {
        $html .= '<tr><td colspan="2" style="padding:6px 10px;border-bottom:1px solid #e4e9ef;font-size:9px;color:#bbb;font-style:italic;">'
            . xlt('No values recorded') . '</td></tr>';
    }
    $html .= '<tr>'
        . '<td style="padding:7px 10px;background:#eceff1;font-weight:bold;font-size:9px;color:#2c3e50;">' . $totalLabel . '</td>'
        . '<td style="padding:7px 10px;background:#eceff1;font-weight:bold;font-size:9px;text-align:right;">' . text($total) . ' mL</td>'
        . '</tr></tbody></table>';
    return $html;
};

// ---------------------------------------------------------------
// Build HTML
// ---------------------------------------------------------------
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: Arial, sans-serif; font-size: 10px; color: #222; margin: 0; padding: 0; }
    .page { padding: 5px; }
    table { border-collapse: collapse; width: 100%; }
    td, th { padding: 0; }
</style>
</head>
<body>
<div class="page">

    <!-- HEADER -->
    <div style="border-bottom:3px solid #2c3e50; padding-bottom:10px; margin-bottom:12px; text-align:center;">
        <div style="font-size:15px; font-weight:bold; color:#2c3e50; text-transform:uppercase; letter-spacing:2px;">
            <?php echo xlt('NURSING FLUID BALANCE RECORD'); ?>
        </div>
        <div style="font-size:8px; color:#7f8c8d; margin-top:5px;">
            <?php echo xlt('Encounter'); ?>: <strong><?php echo text((string)$encounter); ?></strong>
            &nbsp;|&nbsp;
            <?php echo xlt('Date'); ?>: <strong><?php echo text($rec_date); ?></strong>
            &nbsp;|&nbsp;
            <?php echo xlt('Shift'); ?>: <strong><?php echo text($shift_label); ?></strong>
            &nbsp;|&nbsp;
            <?php echo xlt('Record Time'); ?>: <strong><?php echo text($rec_time); ?></strong>
        </div>
    </div>

    <!-- PATIENT INFO -->
    <div style="background:#f8f9fa; border:1px solid #d0d8e4; border-radius:4px; padding:9px 12px; margin-bottom:14px;">
        <div style="font-weight:bold; font-size:8px; color:#495057; text-transform:uppercase; letter-spacing:0.5px;
                    margin-bottom:7px; border-bottom:1px solid #dee2e6; padding-bottom:5px;">
            <?php echo xlt('Patient Information'); ?>
        </div>
        <table>
            <tr>
                <td style="width:38%; padding:3px 8px 3px 0;">
                    <div style="font-size:7px; color:#888; margin-bottom:2px;"><?php echo xlt('Patient'); ?></div>
                    <div style="font-weight:bold; font-size:11px;"><?php echo text($paciente !== false ? (string)($paciente['full_name'] ?? '-') : '-'); ?></div>
                </td>
                <td style="width:18%; padding:3px 8px;">
                    <div style="font-size:7px; color:#888; margin-bottom:2px;"><?php echo xlt('ID'); ?></div>
                    <div style="font-weight:bold; font-size:11px;"><?php echo text($paciente !== false ? (string)($paciente['pubpid'] ?? '-') : '-'); ?></div>
                </td>
                <?php if ($age !== '') : ?>
                <td style="width:18%; padding:3px 8px;">
                    <div style="font-size:7px; color:#888; margin-bottom:2px;"><?php echo xlt('Age'); ?></div>
                    <div style="font-weight:bold; font-size:11px;"><?php echo text($age); ?></div>
                </td>
                <?php endif; ?>
                <td style="padding:3px 0 3px 8px;">
                    <div style="font-size:7px; color:#888; margin-bottom:2px;"><?php echo xlt('User'); ?></div>
                    <div style="font-weight:bold; font-size:11px;"><?php echo text((string)($row['user'] ?? '-')); ?></div>
                </td>
            </tr>
        </table>
    </div>

    <!-- INTAKE / OUTPUT -->
    <?php echo $fluidTable(xlt('Intake'), '#00838f', $intake_labels, xlt('Total intake'), $total_in); ?>
    <?php echo $fluidTable(xlt('Output'), '#e65100', $output_labels, xlt('Total output'), $total_out); ?>

    <!-- FLUID BALANCE CARD -->
    <div style="margin-top:14px; border:1px solid <?php echo attr($bal_border); ?>; border-top:3px solid <?php echo attr($bal_color); ?>; border-radius:4px; background:<?php echo attr($bal_bg); ?>;">
        <div style="background:#34495e; color:#fff; font-size:9px; font-weight:bold;
                    text-transform:uppercase; letter-spacing:1px; padding:7px 14px;">
            <?php echo xlt('Fluid balance'); ?>
        </div>
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="width:22%; text-align:center; padding:12px 10px; border-right:1px solid <?php echo attr($bal_border); ?>; vertical-align:middle;">
                    <div style="font-size:8px; color:#888; text-transform:uppercase; margin-bottom:4px;"><?php echo xlt('Total intake'); ?></div>
                    <div style="font-size:14px; font-weight:bold; color:#2c3e50;"><?php echo text($total_in); ?> mL</div>
                </td>
                <td style="width:22%; text-align:center; padding:12px 10px; border-right:1px solid <?php echo attr($bal_border); ?>; vertical-align:middle;">
                    <div style="font-size:8px; color:#888; text-transform:uppercase; margin-bottom:4px;"><?php echo xlt('Total output'); ?></div>
                    <div style="font-size:14px; font-weight:bold; color:#2c3e50;"><?php echo text($total_out); ?> mL</div>
                </td>
                <td style="text-align:center; padding:12px 10px; vertical-align:middle;">
                    <div style="font-size:30px; font-weight:bold; color:<?php echo attr($bal_color); ?>; line-height:1;">
                        <?php echo text($bal_text); ?> <span style="font-size:12px; font-weight:normal;">mL</span>
                    </div>
                    <div style="font-size:10px; font-weight:bold; color:<?php echo attr($bal_color); ?>; margin-top:4px;">
                        <?php echo text($bal_label); ?>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- OBSERVATIONS -->
    <div style="margin-top:14px; border:1px solid #d0d8e4; border-left:4px solid #6c757d; border-radius:4px; padding:8px 12px;">
        <div style="font-weight:bold; font-size:8px; color:#495057; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:5px;">
            <?php echo xlt('Observations'); ?>
        </div>
        <div style="font-size:9px; color:#444;">
            <?php echo $obs !== '' ? nl2br(text($obs)) : '<span style="color:#bbb;font-style:italic;">' . xlt('No observations recorded') . '</span>'; ?>
        </div>
    </div>

    <!-- SIGNATURE -->
    <table style="width:100%; margin-top:40px; border-top:1px solid #ccc; padding-top:15px;">
        <tr>
            <td style="width:50%; text-align:center; padding:0 25px;">
                <div style="border-top:2px solid #333; margin:50px 15px 6px 15px;"></div>
                <div style="font-size:9px; font-weight:bold; color:#333;"><?php echo xlt('Responsible Signature'); ?></div>
            </td>
            <td style="width:50%; text-align:center; padding:0 25px;">
                <div style="border-top:2px solid #333; margin:50px 15px 6px 15px;"></div>
                <div style="font-size:9px; font-weight:bold; color:#333;"><?php echo xlt('Clarification'); ?></div>
            </td>
        </tr>
    </table>
    <div style="text-align:center; margin-top:12px; font-size:8px; color:#888;">
        <?php echo xlt('Date'); ?>: _____/_____/________
    </div>

</div>
</body>
</html>
<?php
$html = ob_get_clean();
// ---------------------------------------------------------------
// Generate PDF with mPDF
// ---------------------------------------------------------------
$mpdf = new Mpdf([
    'mode'              => 'utf-8',
    'format'            => 'Letter',
    'margin_top'        => 12,
    'margin_bottom'     => 12,
    'margin_left'       => 15,
    'margin_right'      => 15,
    'default_font'      => 'Arial',
    'default_font_size' => 10,
    'tempDir'           => sys_get_temp_dir(),
]);
$mpdf->SetTitle(xl('Nursing Fluid Balance') . ' - ' . ($paciente !== false ? (string)($paciente['full_name'] ?? '') : ''));
$mpdf->WriteHTML((string)$html);
$filename = 'BalanceHidrico_' . preg_replace('/\s+/', '_', $paciente !== false ? (string)($paciente['full_name'] ?? 'paciente') : 'paciente') . '_' . date('Ymd_His') . '.pdf';
$mpdf->Output($filename, 'D');
exit;
