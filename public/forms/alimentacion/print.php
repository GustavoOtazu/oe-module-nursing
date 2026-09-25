<?php

/**
 * Nursing Nutrition Form - print.php
 * Generates a PDF report using mPDF for a single nutrition record.
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

// Load nutrition record
/** @var array<string, string|int|null>|false $row */
$row = QueryUtils::querySingleRow("SELECT * FROM form_alimentacion WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
if (!$row) {
    die(xlt("Error: Record not found or insufficient permissions."));
}

// Load patient data
/** @var array<string, string|int|null>|false $paciente */
$paciente = QueryUtils::querySingleRow("SELECT CONCAT(fname, ' ', lname) AS full_name, pubpid, DOB FROM patient_data WHERE pid = ?", [$pid]);
$age = '';
$dob_val = $paciente !== false ? (string)($paciente['DOB'] ?? '') : '';
if ($dob_val !== '') {
    $dob = new DateTime($dob_val);
    $age = (new DateTime())->diff($dob)->y . ' ' . xlt('years');
}

// Label maps hold xlt() output, which is already HTML-escaped.
$tipo_labels = [
    'ORAL'       => xlt('Oral'),
    'ENTERAL'    => xlt('Enteral'),
    'PARENTERAL' => xlt('Parenteral'),
    'MIXTA'      => xlt('Mixed feeding'),
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

$tipo         = (string)($row['tipo_alimentacion'] ?? '');
$usa_via      = ($tipo === 'ENTERAL' || $tipo === 'MIXTA');
$sin_infusion = ($tipo === 'ORAL' || $tipo === 'AYUNO');

$signos_raw  = (string)($row['signos_intolerancia'] ?? '');
$signos_html = [];
foreach (($signos_raw !== '') ? explode(',', $signos_raw) : [] as $code) {
    $signos_html[] = $label($signos_labels, $code);
}

// [label, safe HTML value]; irrelevant or empty fields are left out.
$candidates = [
    [xlt('Feeding Type'), $label($tipo_labels, $tipo)],
    [xlt('Enteral Route'), $usa_via ? $label($via_labels, (string)($row['via_enteral'] ?? '')) : ''],
    [xlt('Modality'), $sin_infusion ? '' : $label($modalidad_labels, (string)($row['modalidad'] ?? ''))],
    [xlt('Formula / Diet'), text(trim((string)($row['formula'] ?? '')))],
    [xlt('Volume'), ($tipo === 'AYUNO') ? '' : $qty($row['volumen_ml'] ?? null, 'mL')],
    [xlt('Infusion Rate'), $sin_infusion ? '' : $qty($row['velocidad_ml_h'] ?? null, 'mL/h')],
    [xlt('Gastric Residual'), $qty($row['residuo_gastrico_ml'] ?? null, 'mL')],
    [xlt('Tolerance'), $label($tolerancia_labels, (string)($row['tolerancia'] ?? ''))],
    [xlt('Signs of Intolerance'), implode(', ', $signos_html)],
];
$fields = array_filter($candidates, static fn(array $r): bool => $r[1] !== '');

$obs = trim((string)($row['observaciones'] ?? ''));

// Record date/time
$date_raw  = (string)($row['date'] ?? '');
$ts_rec    = $date_raw !== '' ? strtotime($date_raw) : false;
$rec_date  = $ts_rec !== false ? date('d/m/Y', $ts_rec) : '-';
$hora_raw  = substr((string)($row['hora_registro'] ?? ''), 0, 5);
$rec_time  = ($hora_raw !== '') ? $hora_raw : '-';

// Section header helper ($title must already be safe HTML)
$secHeader = (fn(string $title, string $bg): string => '<div style="background:' . $bg . ';color:#fff;font-size:9px;font-weight:bold;'
     . 'text-transform:uppercase;letter-spacing:1px;padding:7px 12px;margin:12px 0 0 0;">'
     . $title . '</div>');

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
    tr:nth-child(even) td { background: #f7f9fb; }
</style>
</head>
<body>
<div class="page">

    <!-- HEADER -->
    <div style="border-bottom:3px solid #2c3e50; padding-bottom:10px; margin-bottom:12px; text-align:center;">
        <div style="font-size:15px; font-weight:bold; color:#2c3e50; text-transform:uppercase; letter-spacing:2px;">
            <?php echo xlt('NURSING NUTRITION RECORD'); ?>
        </div>
        <div style="font-size:8px; color:#7f8c8d; margin-top:5px;">
            <?php echo xlt('Encounter'); ?>: <strong><?php echo text((string)$encounter); ?></strong>
            &nbsp;|&nbsp;
            <?php echo xlt('Date'); ?>: <strong><?php echo text($rec_date); ?></strong>
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

    <!-- NUTRITION DETAILS -->
    <?php echo $secHeader(xlt('Nursing Nutrition'), '#2c3e50'); ?>
    <table style="width:100%;border-collapse:collapse;margin-bottom:10px;">
        <thead><tr>
            <th style="background:#34495e;color:#fff;padding:7px 10px;text-align:left;font-size:9px;text-transform:uppercase;letter-spacing:0.5px;width:35%;"><?php echo xlt('Item'); ?></th>
            <th style="background:#34495e;color:#fff;padding:7px 10px;text-align:left;font-size:9px;text-transform:uppercase;letter-spacing:0.5px;"><?php echo xlt('Value'); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($fields as [$f_label, $f_value]) : ?>
            <tr>
                <td style="padding:7px 10px;border-bottom:1px solid #e4e9ef;font-weight:600;font-size:9px;color:#2c3e50;width:35%;"><?php echo $f_label; ?></td>
                <td style="padding:7px 10px;border-bottom:1px solid #e4e9ef;font-size:9px;color:#222;"><?php echo $f_value; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- OBSERVATIONS -->
    <?php echo $secHeader(xlt('Observations'), '#34495e'); ?>
    <div style="border:1px solid #e4e9ef; border-top:none; padding:8px 10px; font-size:9px; color:#444;">
        <?php echo $obs !== ''
            ? nl2br(text($obs))
            : '<span style="color:#bbb;font-style:italic;">' . xlt('No observations recorded') . '</span>'; ?>
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
$mpdf->SetTitle(xl('Nursing Nutrition') . ' - ' . ($paciente !== false ? (string)($paciente['full_name'] ?? '') : ''));
$mpdf->WriteHTML((string)$html);
$filename = 'Alimentacion_' . preg_replace('/\s+/', '_', $paciente !== false ? (string)($paciente['full_name'] ?? 'paciente') : 'paciente') . '_' . date('Ymd_His') . '.pdf';
$mpdf->Output($filename, 'D');
exit;
