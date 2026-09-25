<?php

/**
 * SOFA Score Form - print.php
 * Generates a PDF report using mPDF for a single SOFA score record.
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

// Load SOFA record
/** @var array<string, string|int|null>|false $row */
$row = QueryUtils::querySingleRow("SELECT * FROM form_escala_sofa WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
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

// Trims trailing zeros of DECIMAL columns ("0.40" -> "0.4", "80.0" -> "80").
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
        $with('PaO2', $row['pao2'] ?? null, 'mmHg'),
        $with('FiO2', $row['fio2'] ?? null),
        ((int)($row['soporte_respiratorio'] ?? 0) === 1) ? xl('Mechanical ventilation / respiratory support') : '',
    ]), $points($row['sofa_resp'] ?? null)],
    [xl('Coagulation'), $with(xl('Platelets'), $row['plaquetas'] ?? null, 'x10³/µL'), $points($row['sofa_coag'] ?? null)],
    [xl('Liver'), $with(xl('Bilirubin'), $row['bilirrubina'] ?? null, 'mg/dL'), $points($row['sofa_hepatico'] ?? null)],
    [xl('Cardiovascular'), $join([
        $with(xl('MAP'), $row['pam'] ?? null, 'mmHg'),
        $with(xl('Dopamine'), $row['dopamina'] ?? null, 'µg/kg/min'),
        ((int)($row['dobutamina'] ?? 0) === 1) ? xl('Dobutamine') : '',
        $with(xl('Epinephrine'), $row['epinefrina'] ?? null, 'µg/kg/min'),
        $with(xl('Norepinephrine'), $row['norepinefrina'] ?? null, 'µg/kg/min'),
    ]), $points($row['sofa_cardio'] ?? null)],
    [xl('Central nervous system'), $with('Glasgow', $row['glasgow'] ?? null), $points($row['sofa_snc'] ?? null)],
    [xl('Renal'), $join([
        $with(xl('Creatinine'), $row['creatinina'] ?? null, 'mg/dL'),
        $with(xl('Urine output'), $row['diuresis_24h'] ?? null, 'mL/24h'),
    ]), $points($row['sofa_renal'] ?? null)],
];

// SOFA level — soft, print-friendly colors
$total     = (int)($row['sofa_total'] ?? 0);
$evaluados = (int)($row['sofa_evaluados'] ?? 0);
if ($total >= 12) {
    $sofa_level  = xl('High');
    $sofa_color  = '#b71c1c';
    $sofa_bg     = '#fce4ec';
    $sofa_border = '#ef9a9a';
} elseif ($total >= 7) {
    $sofa_level  = xl('Intermediate');
    $sofa_color  = '#e65100';
    $sofa_bg     = '#fff8e1';
    $sofa_border = '#ffcc80';
} else {
    $sofa_level  = xl('Low');
    $sofa_color  = '#388e3c';
    $sofa_bg     = '#f9fbe7';
    $sofa_border = '#c5e1a5';
}

$pafi     = $fmt($row['pafi'] ?? null);
$obs      = trim((string)($row['observaciones'] ?? ''));
$date_raw = (string)($row['date'] ?? '');
$ts_eval  = $date_raw !== '' ? strtotime($date_raw) : false;
$eval_date = $ts_eval !== false ? date('d/m/Y', $ts_eval) : '-';
$hora_raw  = substr((string)($row['hora_registro'] ?? ''), 0, 5);
$eval_time = ($hora_raw !== '') ? $hora_raw : '-';

$th = 'background:#34495e;color:#fff;padding:7px 10px;text-align:left;font-size:9px;text-transform:uppercase;letter-spacing:0.5px;';
$td = 'padding:7px 10px;border-bottom:1px solid #e4e9ef;font-size:9px;';

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
            <?php echo xlt('SOFA SCORE RECORD'); ?>
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

    <!-- SYSTEMS -->
    <div style="background:#2c3e50;color:#fff;font-size:9px;font-weight:bold;text-transform:uppercase;letter-spacing:1px;padding:7px 12px;margin:12px 0 0 0;">
        <?php echo xlt('Sequential Organ Failure Assessment'); ?>
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:10px;">
        <thead>
            <tr>
                <th style="<?php echo attr($th); ?>width:28%;"><?php echo xlt('System'); ?></th>
                <th style="<?php echo attr($th); ?>"><?php echo xlt('Values'); ?></th>
                <th style="<?php echo attr($th); ?>width:18%;text-align:center;"><?php echo xlt('Points'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($systems as [$label, $values_txt, $pts]) : ?>
            <tr>
                <td style="<?php echo attr($td); ?>font-weight:600;color:#2c3e50;"><?php echo text($label); ?></td>
                <td style="<?php echo attr($td); ?>color:#444;">
                    <?php echo $values_txt !== '' ? text($values_txt) : '<span style="color:#ccc;">—</span>'; ?>
                </td>
                <td style="<?php echo attr($td); ?>text-align:center;">
                    <?php if ($pts === null) : ?>
                    <span style="color:#999;font-style:italic;"><?php echo xlt('Not evaluated'); ?></span>
                    <?php else : ?>
                    <span style="background:#2980b9;color:#fff;padding:2px 9px;border-radius:3px;font-size:8px;font-weight:bold;"><?php echo text((string)$pts); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
            <tr>
                <td style="<?php echo attr($td); ?>font-weight:600;color:#2c3e50;"><?php echo xlt('PaO2/FiO2 ratio'); ?></td>
                <td colspan="2" style="<?php echo attr($td); ?>color:#444;">
                    <?php echo $pafi !== '' ? text($pafi) : '<span style="color:#ccc;">—</span>'; ?>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- SOFA TOTAL CARD -->
    <div style="margin-top:14px; border:1px solid <?php echo attr($sofa_border); ?>; border-top:3px solid <?php echo attr($sofa_color); ?>; border-radius:4px; overflow:hidden; background:<?php echo attr($sofa_bg); ?>;">
        <div style="background:#34495e; color:#fff; font-size:9px; font-weight:bold;
                    text-transform:uppercase; letter-spacing:1px; padding:7px 14px;">
            <?php echo xlt('SOFA Score'); ?> — <?php echo xlt('Total Score'); ?>
        </div>
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="width:22%; text-align:center; padding:16px 10px; border-right:1px solid <?php echo attr($sofa_border); ?>; vertical-align:middle;">
                    <div style="font-size:44px; font-weight:bold; color:#2c3e50; line-height:1;">
                        <?php echo text((string)$total); ?>
                    </div>
                    <div style="font-size:11px; color:#999; margin-top:2px;">/ 24</div>
                </td>
                <td style="width:32%; text-align:center; padding:16px 10px; border-right:1px solid <?php echo attr($sofa_border); ?>; vertical-align:middle;">
                    <div style="display:inline-block; background:<?php echo attr($sofa_color); ?>; color:#fff;
                                font-size:13px; font-weight:bold; padding:6px 18px; border-radius:4px; letter-spacing:0.5px;">
                        <?php echo text($sofa_level); ?>
                    </div>
                    <div style="font-size:9px; color:#555; margin-top:6px;">
                        (<?php echo text((string)$evaluados); ?>/6 <?php echo xlt('systems evaluated'); ?>)
                    </div>
                </td>
                <td style="padding:12px 16px; vertical-align:middle;">
                    <div style="font-size:8px; color:#555; font-weight:bold; text-transform:uppercase;
                                letter-spacing:0.5px; margin-bottom:8px;">
                        <?php echo xlt('Reference scale'); ?>
                    </div>
                    <table style="width:100%; border-collapse:collapse; font-size:9px;">
                        <tr>
                            <td style="padding:4px 8px; background:#<?php echo $total <= 6 ? '66bb6a' : 'e0e0e0'; ?>;
                                       color:#<?php echo $total <= 6 ? '1b5e20' : '777'; ?>;
                                       font-weight:<?php echo $total <= 6 ? 'bold' : 'normal'; ?>;
                                       border-bottom:1px solid #ccc;">
                                &nbsp;0 – 6 &nbsp; <?php echo xlt('Low'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:4px 8px; background:#<?php echo ($total >= 7 && $total <= 11) ? 'e65100' : 'e0e0e0'; ?>;
                                       color:#<?php echo ($total >= 7 && $total <= 11) ? 'fff' : '777'; ?>;
                                       font-weight:<?php echo ($total >= 7 && $total <= 11) ? 'bold' : 'normal'; ?>;
                                       border-bottom:1px solid #ccc;">
                                &nbsp;7 – 11 &nbsp; <?php echo xlt('Intermediate'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:4px 8px; background:#<?php echo $total >= 12 ? 'b71c1c' : 'e0e0e0'; ?>;
                                       color:#<?php echo $total >= 12 ? 'fff' : '777'; ?>;
                                       font-weight:<?php echo $total >= 12 ? 'bold' : 'normal'; ?>;">
                                &nbsp;≥ 12 &nbsp; <?php echo xlt('High'); ?>
                            </td>
                        </tr>
                    </table>
                    <div style="font-size:7px; color:#888; margin-top:6px;">Vincent et al., 1996</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- OBSERVATIONS -->
    <div style="margin-top:14px; border:1px solid #d0d8e4; border-left:4px solid #2980b9; padding:8px 12px; font-size:9px;">
        <div style="font-weight:bold; color:#2c3e50; margin-bottom:4px;"><?php echo xlt('Observations'); ?></div>
        <?php echo $obs !== '' ? nl2br(text($obs)) : '<span style="color:#bbb;font-style:italic;">' . xlt('No observations recorded') . '</span>'; ?>
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
$mpdf->SetTitle(xl('SOFA Score') . ' - ' . ($paciente !== false ? (string)($paciente['full_name'] ?? '') : ''));
$mpdf->WriteHTML((string)$html);
$filename = 'SOFA_' . preg_replace('/\s+/', '_', $paciente !== false ? (string)($paciente['full_name'] ?? 'paciente') : 'paciente') . '_' . date('Ymd_His') . '.pdf';
$mpdf->Output($filename, 'D');
exit;
