<?php

/**
 * APACHE II Score Form - print.php
 * Generates a PDF report using mPDF for a single APACHE II record.
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
use OpenEMR\Modules\Nursing\Scoring\ApacheIIScore;

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

// Load APACHE II record
/** @var array<string, string|int|null>|false $row */
$row = QueryUtils::querySingleRow("SELECT * FROM form_escala_apache WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
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
    $age = (new DateTime())->diff($dob)->y . ' ' . xl('years');
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
$calc = ApacheIIScore::calculate($row);
$d    = $calc['detalle'];

$ox = [];
if (($f = $num($row['fio2'] ?? null)) !== '') {
    $ox[] = 'FiO2 ' . $f;
}
if (($p = $num($row['pao2'] ?? null)) !== '') {
    $ox[] = 'PaO2 ' . $p . ' mmHg';
}
if (($c = $num($row['paco2'] ?? null)) !== '') {
    $ox[] = 'PaCO2 ' . $c . ' mmHg';
}
$ph      = $num($row['ph'] ?? null);
$hco3    = $num($row['bicarbonato'] ?? null);
$useHco3 = ($ph === '' && $hco3 !== '');
$creat   = $withUnit($num($row['creatinina'] ?? null), 'mg/dL');
if ($creat !== '' && !empty($row['insuficiencia_renal_aguda'])) {
    $creat .= ' (' . xl('Acute renal failure') . ')';
}

$detail = [
    [xl('Rectal temperature'), $withUnit($num($row['temperatura'] ?? null), '°C'), $d['temperatura'] ?? null],
    [xl('Mean arterial pressure'), $withUnit($num($row['pam'] ?? null), 'mmHg'), $d['pam'] ?? null],
    [xl('Heart rate'), $withUnit($num($row['frecuencia_cardiaca'] ?? null), 'bpm'), $d['fc'] ?? null],
    [xl('Respiratory rate'), $withUnit($num($row['frecuencia_respiratoria'] ?? null), 'rpm'), $d['fr'] ?? null],
    [xl('Oxygenation'), implode(' / ', $ox), $d['oxigenacion'] ?? null],
    $useHco3
        ? [xl('Serum HCO3'), $withUnit($hco3, 'mmol/L'), $d['ph'] ?? null]
        : [xl('Arterial pH'), $ph, $d['ph'] ?? null],
    [xl('Sodium'), $withUnit($num($row['sodio'] ?? null), 'mmol/L'), $d['sodio'] ?? null],
    [xl('Potassium'), $withUnit($num($row['potasio'] ?? null), 'mmol/L'), $d['potasio'] ?? null],
    [xl('Creatinine'), $creat, $d['creatinina'] ?? null],
    [xl('Hematocrit'), $withUnit($num($row['hematocrito'] ?? null), '%'), $d['hematocrito'] ?? null],
    [xl('White blood cells'), $withUnit($num($row['leucocitos'] ?? null), 'x10³/µL'), $d['leucocitos'] ?? null],
    [xl('Glasgow Coma Scale'), $num($row['glasgow'] ?? null), $d['glasgow'] ?? null],
];

$total     = (int)($row['apache_total'] ?? $calc['total']);
$evaluados = (int)($row['apache_evaluados'] ?? $calc['evaluados']);
$fisio     = (int)($row['pts_fisiologicos'] ?? $calc['fisiologicos']);
$pts_edad  = (int)($row['pts_edad'] ?? $calc['edad']);
$pts_cron  = (int)($row['pts_cronicos'] ?? $calc['cronicos']);
$aado2     = $num($row['a_ado2'] ?? null);

// Severity level — soft colors suitable for printing
if ($total >= 25) {
    $level        = xl('Higher severity');
    $level_color  = '#b71c1c';
    $level_bg     = '#fce4ec';
    $level_border = '#ef9a9a';
} elseif ($total >= 15) {
    $level        = xl('Moderate severity');
    $level_color  = '#e65100';
    $level_bg     = '#fff8e1';
    $level_border = '#ffcc80';
} else {
    $level        = xl('Lower severity');
    $level_color  = '#388e3c';
    $level_bg     = '#f9fbe7';
    $level_border = '#c5e1a5';
}

$admission_types = [
    ApacheIIScore::ADMISSION_NON_OPERATIVE    => xl('Non-operative'),
    ApacheIIScore::ADMISSION_EMERGENCY_POSTOP => xl('Emergency postoperative'),
    ApacheIIScore::ADMISSION_ELECTIVE_POSTOP  => xl('Elective postoperative'),
];
$tipo       = (string)($row['tipo_ingreso'] ?? '');
$cron_value = !empty($row['enfermedad_cronica'])
    ? xl('Yes') . ($tipo !== '' && isset($admission_types[$tipo]) ? ' — ' . $admission_types[$tipo] : '')
    : xl('No');
$edad_value = $withUnit($num($row['edad'] ?? null), xl('years'));
$obs        = trim((string)($row['observaciones'] ?? ''));

// Record date/time
$date_raw  = (string)($row['date'] ?? '');
$ts_eval   = $date_raw !== '' ? strtotime($date_raw) : false;
$eval_date = $ts_eval !== false ? date('d/m/Y', $ts_eval) : '-';
$hora_raw  = (string)($row['hora_registro'] ?? '');
$eval_time = ($hora_raw !== '') ? substr($hora_raw, 0, 5) : '-';

$cell = 'padding:6px 10px;border-bottom:1px solid #e4e9ef;font-size:9px;';
$th   = 'background:#34495e;color:#fff;padding:7px 10px;text-align:left;font-size:9px;text-transform:uppercase;letter-spacing:0.5px;';

// Helper: table row (label, value, points or "Not evaluated")
$apacheRow = function (string $label, string $value, ?int $points, bool $bold = false) use ($cell): string {
    $weight   = $bold ? 'font-weight:bold;background:#eef3f8;' : '';
    $val_html = ($value !== '') ? text($value) : '<span style="color:#ccc;">—</span>';
    $pts_html = ($points === null)
        ? '<span style="color:#999;font-style:italic;font-weight:normal;">' . xlt('Not evaluated') . '</span>'
        : text((string)$points);
    return '
    <tr>
        <td style="' . $cell . $weight . 'font-weight:600;color:#2c3e50;width:40%;">' . text($label) . '</td>
        <td style="' . $cell . $weight . 'color:#444;">' . $val_html . '</td>
        <td style="' . $cell . $weight . 'width:18%;text-align:center;font-weight:bold;">' . $pts_html . '</td>
    </tr>';
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
            <?php echo xlt('APACHE II SCORE RECORD'); ?>
        </div>
        <div style="font-size:8px; color:#7f8c8d; margin-top:5px;">
            <?php echo xlt('Encounter'); ?>: <strong><?php echo text((string)$encounter); ?></strong>
            &nbsp;|&nbsp;
            <?php echo xlt('Date'); ?>: <strong><?php echo text($eval_date); ?></strong>
            &nbsp;|&nbsp;
            <?php echo xlt('Record Time'); ?>: <strong><?php echo text($eval_time); ?></strong>
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

    <!-- VARIABLES -->
    <div style="background:#2c3e50;color:#fff;font-size:9px;font-weight:bold;text-transform:uppercase;letter-spacing:1px;padding:7px 12px;margin:12px 0 0 0;">
        <?php echo xlt('Acute physiology'); ?>
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:10px;">
        <thead>
            <tr>
                <th style="<?php echo attr($th); ?>width:40%;"><?php echo xlt('Variable'); ?></th>
                <th style="<?php echo attr($th); ?>"><?php echo xlt('Value'); ?></th>
                <th style="<?php echo attr($th); ?>width:18%;text-align:center;"><?php echo xlt('Points'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php
        foreach ($detail as [$label, $value, $points]) {
            echo $apacheRow($label, $value, $points);
        }
        if ($aado2 !== '') {
            echo '<tr><td style="' . attr($cell) . 'padding-left:24px;font-style:italic;color:#666;">A-aDO2</td>'
                . '<td style="' . attr($cell) . 'color:#444;">' . text($aado2 . ' mmHg') . '</td>'
                . '<td style="' . attr($cell) . '"></td></tr>';
        }
        echo $apacheRow(xl('Acute physiology subtotal'), '', $fisio, true);
        echo $apacheRow(xl('Age'), $edad_value, $pts_edad);
        echo $apacheRow(xl('Chronic health'), $cron_value, $pts_cron);
        ?>
        </tbody>
    </table>

    <!-- TOTAL CARD -->
    <div style="margin-top:14px; border:1px solid <?php echo attr($level_border); ?>; border-top:3px solid <?php echo attr($level_color); ?>; border-radius:4px; overflow:hidden; background:<?php echo attr($level_bg); ?>;">
        <div style="background:#34495e; color:#fff; font-size:9px; font-weight:bold;
                    text-transform:uppercase; letter-spacing:1px; padding:7px 14px;">
            <?php echo xlt('APACHE II Score'); ?>
        </div>
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="width:40%; text-align:center; padding:14px 10px; border-right:1px solid <?php echo attr($level_border); ?>; vertical-align:middle;">
                    <div style="font-size:26px; font-weight:bold; color:#2c3e50; line-height:1;">
                        APACHE II = <?php echo text((string)$total); ?>
                    </div>
                    <div style="font-size:9px; color:#777; margin-top:4px;">
                        (<?php echo text((string)$evaluados); ?>/12 <?php echo xlt('variables evaluated'); ?>)
                    </div>
                </td>
                <td style="width:28%; text-align:center; padding:14px 10px; border-right:1px solid <?php echo attr($level_border); ?>; vertical-align:middle;">
                    <div style="display:inline-block; background:<?php echo attr($level_color); ?>; color:#fff;
                                font-size:12px; font-weight:bold; padding:6px 14px; border-radius:4px;">
                        <?php echo text($level); ?>
                    </div>
                </td>
                <td style="padding:12px 16px; vertical-align:middle; font-size:9px; color:#555; line-height:1.7;">
                    0 – 14: <?php echo xlt('Lower severity'); ?><br>
                    15 – 24: <?php echo xlt('Moderate severity'); ?><br>
                    ≥ 25: <?php echo xlt('Higher severity'); ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- OBSERVATIONS -->
    <div style="background:#2c3e50;color:#fff;font-size:9px;font-weight:bold;text-transform:uppercase;letter-spacing:1px;padding:7px 12px;margin:14px 0 0 0;">
        <?php echo xlt('Observations'); ?>
    </div>
    <div style="border:1px solid #e4e9ef; padding:8px 10px; font-size:9px; color:#444;">
        <?php echo $obs !== '' ? nl2br(text($obs)) : '<span style="color:#bbb;font-style:italic;">' . xlt('No observations recorded') . '</span>'; ?>
    </div>

    <div style="margin-top:8px; font-size:7px; color:#999;">
        <?php echo xlt('Reference'); ?>: Knaus et al., 1985
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
$mpdf->SetTitle(xl('APACHE II Score') . ' - ' . ($paciente !== false ? (string)($paciente['full_name'] ?? '') : ''));
$mpdf->WriteHTML((string)$html);
$filename = 'APACHE_II_' . preg_replace('/\s+/', '_', $paciente !== false ? (string)($paciente['full_name'] ?? 'paciente') : 'paciente') . '_' . date('Ymd_His') . '.pdf';
$mpdf->Output($filename, 'D');
exit;
