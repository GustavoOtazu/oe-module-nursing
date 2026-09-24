<?php

/**
 * APACHE II Score Form - view.php
 * Displays APACHE II records for a patient encounter.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once(dirname(__DIR__, 6) . "/globals.php");
/** @var string $srcdir */
// ESign is not autoloaded; core does the same require in forms.php
require_once("$srcdir/ESign/Api.php");

use ESign\Api as ESignApi;
use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Session\SessionWrapperFactory;
use OpenEMR\Core\Header;
use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Modules\Nursing\Scoring\ApacheIIScore;

$session   = SessionWrapperFactory::getInstance()->getActiveSession();
$pid       = (is_numeric($v = filter_input(INPUT_GET, 'pid', FILTER_SANITIZE_NUMBER_INT)) ? (int) $v : 0)
    ?: (is_numeric($v = $session->get('pid')) ? (int) $v : 0);
$encounter = (is_numeric($v = filter_input(INPUT_GET, 'encounter', FILTER_SANITIZE_NUMBER_INT)) ? (int) $v : 0)
    ?: (is_numeric($v = $session->get('encounter')) ? (int) $v : 0);
$id        = is_numeric($v = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT)) ? (int) $v : 0;
$from      = (filter_input(INPUT_GET, 'from') === 'list') ? 'list' : 'encounter';
$back_url  = OEGlobalsBag::getInstance()->getString('webroot')
    . "/interface/modules/custom_modules/oe-module-nursing/public/dashboard/lista_internados.php";

if (!$pid || !$encounter) {
    echo "<div class='alert alert-danger m-3'>" . xlt("Could not retrieve PID or Encounter.") . "</div>";
    exit;
}

if (!AclMain::aclCheckCore('encounters', 'notes')) {
    die(xlt('Access denied'));
}

// By OpenEMR convention view.php opens an existing record for editing, which is
// why core labels the button "Edit". Hand off to new.php whenever core would
// have said "Edit"; the read-only detail below is what core calls "View", shown
// for locked forms and for users without write access.
if ($id > 0 && AclMain::aclCheckCore('encounters', 'notes', '', 'write')) {
    $formdir = basename(__DIR__);
    // core's ESign api keys off forms.id, while $id here is forms.form_id
    /** @var array<string, string|int|null>|false $formRow */
    $formRow = QueryUtils::querySingleRow(
        "SELECT id FROM forms WHERE form_id = ? AND formdir = ? AND encounter = ? AND deleted = 0 LIMIT 1",
        [$id, $formdir, $encounter]
    );
    $isLocked = $formRow !== false
        && (new ESignApi())->createFormESign((int) $formRow['id'], $formdir, $encounter)->isLocked();
    if (!$isLocked) {
        require __DIR__ . '/new.php';
        return;
    }
}

/** @var array<string, string|int|null>|false $paciente */
$paciente = QueryUtils::querySingleRow("SELECT CONCAT(fname, ' ', lname) AS full_name, pubpid, DOB FROM patient_data WHERE pid = ?", [$pid]);
$age = '';
$dob_val = $paciente !== false ? (string)($paciente['DOB'] ?? '') : '';
if ($dob_val !== '') {
    $dob = new DateTime($dob_val);
    $age = (new DateTime())->diff($dob)->y . ' ' . xlt('years');
}

if ($id > 0) {
    /** @var list<array<string, string|int|null>> $rows */
    $rows = QueryUtils::fetchRecords("SELECT * FROM form_escala_apache WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
} else {
    /** @var list<array<string, string|int|null>> $rows */
    $rows = QueryUtils::fetchRecords("SELECT * FROM form_escala_apache WHERE pid = ? AND encounter = ? ORDER BY date DESC", [$pid, $encounter]);
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

$admission_types = [
    ApacheIIScore::ADMISSION_NON_OPERATIVE    => xl('Non-operative'),
    ApacheIIScore::ADMISSION_EMERGENCY_POSTOP => xl('Emergency postoperative'),
    ApacheIIScore::ADMISSION_ELECTIVE_POSTOP  => xl('Elective postoperative'),
];

/**
 * Per-variable rows (label, value, points). Points are recalculated from the
 * stored raw values with the same class used by save.php.
 *
 * @param array<string, string|int|null> $row
 * @param array{detalle: array<string, ?int>} $calc
 * @return list<array{0: string, 1: string, 2: ?int}>
 */
$buildRows = static function (array $row, array $calc) use ($num, $withUnit): array {
    $d = $calc['detalle'];

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

    $ph   = $num($row['ph'] ?? null);
    $hco3 = $num($row['bicarbonato'] ?? null);
    $useHco3 = ($ph === '' && $hco3 !== '');

    $creat = $withUnit($num($row['creatinina'] ?? null), 'mg/dL');
    if ($creat !== '' && !empty($row['insuficiencia_renal_aguda'])) {
        $creat .= ' (' . xl('Acute renal failure') . ')';
    }

    return [
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
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo xlt('APACHE II Score'); ?></title>
    <?php Header::setupHeader(); ?>
    <script>
        function backClicked() {
<?php if ($from === 'list') : ?>
            top.RTop.location = <?php echo js_escape($back_url); ?>;
<?php else : ?>
            // Opened from the encounter as a frame tab: closing it returns to
            // the encounter's form list, which is the core convention.
            parent.closeTab(window.name, true);
<?php endif; ?>
            return false;
        }
    </script>
    <style>
        .apache-view * { box-sizing: border-box; }
        .apache-view .registro-card { border: 1px solid rgba(128, 128, 128, 0.35); border-radius: 6px; padding: 20px; margin-bottom: 20px; }
        /* Translucent overlays instead of fixed light colors, so the detail
           panels stay readable under both the light and dark themes. */
        .apache-view .apache-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .apache-view .apache-table th { padding: 8px 12px; border-bottom: 2px solid rgba(128, 128, 128, 0.4); text-align: left; }
        .apache-view .apache-table td { padding: 7px 12px; border-bottom: 1px solid rgba(128, 128, 128, 0.2); }
        .apache-view .apache-table tbody tr:nth-child(even) td { background: rgba(128, 128, 128, 0.06); }
        .apache-view .apache-table .pts { width: 18%; text-align: center; font-weight: 600; }
        .apache-view .apache-table .subtotal td { font-weight: 600; background: rgba(13, 110, 253, 0.08); }
        .apache-view .not-evaluated { opacity: 0.6; font-style: italic; font-weight: normal; }
        .apache-view .field-detail { background: rgba(128, 128, 128, 0.08); border-radius: 4px; padding: 10px 14px; margin-bottom: 10px; border-left: 4px solid #0d6efd; }
    </style>
</head>
<body class="body_top">
<div class="apache-view container-fluid mt-3">
    <h5 class="border-bottom pb-2">
        <?php echo $id > 0 ? xlt('APACHE II Detail') : xlt('APACHE II Score'); ?>
    </h5>

    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row">
                <div class="col-sm-4"><strong><?php echo xlt('Patient'); ?>:</strong> <?php echo text($paciente !== false ? (string)($paciente['full_name'] ?? '') : ''); ?></div>
                <div class="col-sm-3"><strong><?php echo xlt('ID'); ?>:</strong> <?php echo text($paciente !== false ? (string)($paciente['pubpid'] ?? '') : ''); ?></div>
                <?php if ($age !== '') : ?>
                <div class="col-sm-3"><strong><?php echo xlt('Age'); ?>:</strong> <?php echo text($age); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($rows === []) : ?>
    <div class="alert alert-info"><?php echo xlt('No APACHE II scores recorded'); ?></div>
    <?php endif; ?>

    <?php foreach ($rows as $row) :
        $calc      = ApacheIIScore::calculate($row);
        $detail    = $buildRows($row, $calc);
        $total     = (int)($row['apache_total'] ?? $calc['total']);
        $evaluados = (int)($row['apache_evaluados'] ?? $calc['evaluados']);
        $fisio     = (int)($row['pts_fisiologicos'] ?? $calc['fisiologicos']);
        $pts_edad  = (int)($row['pts_edad'] ?? $calc['edad']);
        $pts_cron  = (int)($row['pts_cronicos'] ?? $calc['cronicos']);
        $aado2     = $num($row['a_ado2'] ?? null);
        if ($total >= 25) {
            $a_class = 'danger';  $a_level = xl('Higher severity');
        } elseif ($total >= 15) {
            $a_class = 'warning'; $a_level = xl('Moderate severity');
        } else {
            $a_class = 'success'; $a_level = xl('Lower severity');
        }
        $tipo       = (string)($row['tipo_ingreso'] ?? '');
        $cronica    = !empty($row['enfermedad_cronica']);
        $cron_value = $cronica
            ? xl('Yes') . ($tipo !== '' && isset($admission_types[$tipo]) ? ' — ' . $admission_types[$tipo] : '')
            : xl('No');
        $obs        = (string)($row['observaciones'] ?? '');
        $row_id     = (int)($row['id'] ?? 0);
        ?>
    <div class="registro-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <?php $ts_date = strtotime((string)($row['date'] ?? '')); ?>
                <strong><?php echo text(date('d/m/Y H:i', $ts_date !== false ? $ts_date : time())); ?></strong>
                <small class="text-muted ml-2"><?php echo xlt('User'); ?>: <?php echo text((string)($row['user'] ?? '')); ?></small>
            </div>
            <div>
                <a href="<?php echo attr(OEGlobalsBag::getInstance()->getString('webroot') . '/interface/modules/custom_modules/oe-module-nursing/public/forms/escala_apache/new.php?pid=' . $pid . '&encounter=' . $encounter . '&id=' . $row_id); ?>"
                   class="btn btn-primary mr-1" onclick="top.restoreSession()"><i class="fas fa-pen mr-1"></i><?php echo xlt('Edit'); ?></a>
                <a href="<?php echo attr(OEGlobalsBag::getInstance()->getString('webroot') . '/interface/modules/custom_modules/oe-module-nursing/public/forms/escala_apache/print.php?pid=' . $pid . '&encounter=' . $encounter . '&id=' . $row_id); ?>"
                   target="_blank" class="btn btn-success" onclick="top.restoreSession()"><i class="fas fa-print mr-1"></i><?php echo xlt('Print'); ?></a>
            </div>
        </div>

        <div class="alert alert-<?php echo attr($a_class); ?> py-2">
            <strong>APACHE II = <?php echo text((string)$total); ?></strong>
            <small>(<?php echo text((string)$evaluados); ?>/12 <?php echo xlt('variables evaluated'); ?>)</small>
            &nbsp;— <?php echo text($a_level); ?>
        </div>

        <table class="apache-table">
            <thead>
                <tr>
                    <th><?php echo xlt('Variable'); ?></th>
                    <th><?php echo xlt('Value'); ?></th>
                    <th class="pts"><?php echo xlt('Points'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($detail as [$label, $value, $points]) : ?>
                <tr>
                    <td><?php echo text($label); ?></td>
                    <td><?php echo text($value !== '' ? $value : '—'); ?></td>
                    <td class="pts">
                        <?php if ($points === null) : ?>
                        <span class="not-evaluated"><?php echo xlt('Not evaluated'); ?></span>
                        <?php else : ?>
                            <?php echo text((string)$points); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($aado2 !== '') : ?>
                <tr>
                    <td class="pl-4"><small>A-aDO2</small></td>
                    <td><small><?php echo text($aado2 . ' mmHg'); ?></small></td>
                    <td class="pts"></td>
                </tr>
            <?php endif; ?>
                <tr class="subtotal">
                    <td colspan="2"><?php echo xlt('Acute physiology subtotal'); ?></td>
                    <td class="pts"><?php echo text((string)$fisio); ?></td>
                </tr>
                <tr>
                    <td><?php echo xlt('Age'); ?></td>
                    <td><?php echo text($withUnit($num($row['edad'] ?? null), xl('years')) ?: '—'); ?></td>
                    <td class="pts"><?php echo text((string)$pts_edad); ?></td>
                </tr>
                <tr>
                    <td><?php echo xlt('Chronic health'); ?></td>
                    <td><?php echo text($cron_value); ?></td>
                    <td class="pts"><?php echo text((string)$pts_cron); ?></td>
                </tr>
                <tr class="subtotal">
                    <td colspan="2">APACHE II</td>
                    <td class="pts"><?php echo text((string)$total); ?></td>
                </tr>
            </tbody>
        </table>

        <?php if ($obs !== '') : ?>
        <div class="field-detail">
            <strong><?php echo xlt('Observations'); ?>:</strong>
            <div class="mt-1 small"><?php echo nl2br(text($obs)); ?></div>
        </div>
        <?php endif; ?>

        <?php if ((string)($row['hora_registro'] ?? '') !== '') : ?>
        <small class="text-muted"><?php echo xlt('Record Time'); ?>: <?php echo text(substr((string)$row['hora_registro'], 0, 5)); ?></small>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="form-group mt-3">
        <button type="button" onclick="return backClicked()" class="btn btn-outline-secondary">
            <i class="fas fa-chevron-left mr-1"></i><?php echo xlt('Back'); ?>
        </button>
    </div>
</div>
</body>
</html>
