<?php

/**
 * Nursing Care Plan Form - print.php
 * Generates a PDF report using mPDF for a single care plan / shift progress
 * note record.
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

// Load care plan record
/** @var array<string, string|int|null>|false $row */
$row = QueryUtils::querySingleRow("SELECT * FROM form_plan_cuidados WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
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
    $age = (new DateTime())->diff($dob)->y . ' ' . xl('years');
}

$turno_labels = [
    'MANANA' => xl('Morning'),
    'TARDE'  => xl('Afternoon'),
    'NOCHE'  => xl('Night'),
];
// value => [label, background, text color] — print-friendly tones
$evaluacion_badges = [
    'LOGRADO'    => [xl('Achieved'),           '#388e3c', '#ffffff'],
    'PARCIAL'    => [xl('Partially achieved'), '#f9a825', '#000000'],
    'NO_LOGRADO' => [xl('Not achieved'),       '#c62828', '#ffffff'],
    'EN_CURSO'   => [xl('In progress'),        '#0277bd', '#ffffff'],
];

// Record date/time and shift
$date_raw  = (string)($row['date'] ?? '');
$ts_rec    = $date_raw !== '' ? strtotime($date_raw) : false;
$rec_date  = $ts_rec !== false ? date('d/m/Y', $ts_rec) : '-';
$hora_raw  = substr((string)($row['hora_registro'] ?? ''), 0, 5);
$rec_time  = ($hora_raw !== '') ? $hora_raw : '-';
$turno     = $turno_labels[(string)($row['turno'] ?? '')] ?? '-';
$eval_val  = (string)($row['evaluacion_resultado'] ?? '');
$codigo    = trim((string)($row['codigo_diagnostico'] ?? ''));

// Section header helper (title must already be escaped)
$secHeader = (fn(string $title, string $bg): string => '<div style="background:' . $bg . ';color:#fff;font-size:9px;font-weight:bold;'
     . 'text-transform:uppercase;letter-spacing:1px;padding:7px 12px;margin:12px 0 0 0;">'
     . $title . '</div>');

// Section body helper (content must already be escaped)
$secBody = (fn(string $content): string => '<div style="border:1px solid #d0d8e4;border-top:none;padding:8px 12px;'
     . 'font-size:10px;line-height:1.5;color:#222;">' . $content . '</div>');

// Sections in nursing process order; empty ones are skipped
$sections = [
    'diagnostico_enfermeria' => [xl('Nursing diagnosis'),       '#c0392b'],
    'objetivo'               => [xl('Goal / expected outcome'), '#8e44ad'],
    'intervenciones'         => [xl('Nursing interventions'),   '#16a085'],
    'evaluacion_resultado'   => [xl('Outcome evaluation'),      '#e67e22'],
    'evolucion'              => [xl('Shift progress note'),     '#2980b9'],
    'observaciones'          => [xl('Observations'),            '#2c3e50'],
];

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
            <?php echo xlt('NURSING CARE PLAN RECORD'); ?>
        </div>
        <div style="font-size:8px; color:#7f8c8d; margin-top:5px;">
            <?php echo xlt('Encounter'); ?>: <strong><?php echo text((string)$encounter); ?></strong>
            &nbsp;|&nbsp;
            <?php echo xlt('Date'); ?>: <strong><?php echo text($rec_date); ?></strong>
            &nbsp;|&nbsp;
            <?php echo xlt('Shift'); ?>: <strong><?php echo text($turno); ?></strong>
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

    <!-- CARE PLAN SECTIONS -->
    <?php
    foreach ($sections as $field => [$label, $color]) {
        if ($field === 'evaluacion_resultado') {
            if (!isset($evaluacion_badges[$eval_val])) {
                continue;
            }
            [$badge_label, $badge_bg, $badge_fg] = $evaluacion_badges[$eval_val];
            echo $secHeader(text($label), $color);
            echo $secBody('<span style="background:' . attr($badge_bg) . ';color:' . attr($badge_fg) . ';padding:3px 12px;'
                . 'border-radius:3px;font-size:10px;font-weight:bold;">' . text($badge_label) . '</span>');
            continue;
        }
        $val       = trim((string)($row[$field] ?? ''));
        $show_code = ($field === 'diagnostico_enfermeria' && $codigo !== '');
        if ($val === '' && !$show_code) {
            continue;
        }
        $content = ($val !== '') ? nl2br(text($val)) : '';
        if ($show_code) {
            $content .= '<div style="margin-top:6px;font-size:9px;color:#555;"><strong>' . xlt('Diagnosis code') . ':</strong> '
                . text($codigo) . '</div>';
        }
        echo $secHeader(text($label), $color);
        echo $secBody($content);
    }
    ?>

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
$mpdf->SetTitle(xl('Nursing Care Plan') . ' - ' . ($paciente !== false ? (string)($paciente['full_name'] ?? '') : ''));
$mpdf->WriteHTML((string)$html);
$filename = 'PlanCuidados_' . preg_replace('/\s+/', '_', $paciente !== false ? (string)($paciente['full_name'] ?? 'paciente') : 'paciente') . '_' . date('Ymd_His') . '.pdf';
$mpdf->Output($filename, 'D');
exit;
