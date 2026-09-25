<?php

/**
 * SOFA Score Form - view.php
 * Displays SOFA score records for a patient encounter.
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
    $rows = QueryUtils::fetchRecords("SELECT * FROM form_escala_sofa WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
} else {
    /** @var list<array<string, string|int|null>> $rows */
    $rows = QueryUtils::fetchRecords("SELECT * FROM form_escala_sofa WHERE pid = ? AND encounter = ? ORDER BY date DESC", [$pid, $encounter]);
}

// Trims trailing zeros of DECIMAL columns ("0.40" -> "0.4", "80.0" -> "80").
$fmt = static function (mixed $value): string {
    if ($value === null || $value === '' || !is_numeric($value)) {
        return '';
    }
    return rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.');
};

// Builds the System | Values | Points rows (raw, unescaped strings).
$sofaSystems = static function (array $row) use ($fmt): array {
    $join = static fn(array $parts): string => implode(' · ', array_filter($parts, static fn($p) => $p !== ''));
    $with = static fn(string $prefix, mixed $value, string $unit = ''): string
        => $fmt($value) !== '' ? trim($prefix . ' ' . $fmt($value) . ' ' . $unit) : '';
    $points = static fn(mixed $value): ?int => ($value === null || $value === '') ? null : (int) $value;

    return [
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
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo xlt('SOFA Score'); ?></title>
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
        .sofa-view * { box-sizing: border-box; }
        .sofa-view .registro-card { border: 1px solid rgba(128, 128, 128, 0.35); border-radius: 6px; padding: 20px; margin-bottom: 20px; }
        /* Translucent overlays instead of fixed light colors, so the detail
           panels stay readable under both the light and dark themes. */
        .sofa-view .sofa-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .sofa-view .sofa-table th { padding: 8px 12px; border-bottom: 2px solid rgba(128, 128, 128, 0.35); text-align: left; }
        .sofa-view .sofa-table td { padding: 8px 12px; border-bottom: 1px solid rgba(128, 128, 128, 0.2); vertical-align: top; }
        .sofa-view .sofa-table tbody tr:nth-child(even) td { background: rgba(128, 128, 128, 0.06); }
        .sofa-view .sofa-table .td-points { width: 18%; text-align: center; }
        .sofa-view .not-evaluated { opacity: 0.6; font-style: italic; }
        .sofa-view .field-detail { background: rgba(13, 110, 253, 0.10); border-radius: 4px; padding: 10px 14px; margin-bottom: 10px; border-left: 4px solid #0d6efd; }
    </style>
</head>
<body class="body_top">
<div class="sofa-view container-fluid mt-3">
    <h5 class="border-bottom pb-2">
        <?php echo $id > 0 ? xlt('SOFA Score Detail') : xlt('SOFA Score'); ?>
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
    <div class="alert alert-info"><?php echo xlt('No SOFA scores recorded'); ?></div>
    <?php endif; ?>

    <?php foreach ($rows as $row) :
        $total     = (int)($row['sofa_total'] ?? 0);
        $evaluados = (int)($row['sofa_evaluados'] ?? 0);
        if ($total >= 12) {
            $s_class = 'danger';  $s_level = xl('High');
        } elseif ($total >= 7) {
            $s_class = 'warning'; $s_level = xl('Intermediate');
        } else {
            $s_class = 'success'; $s_level = xl('Low');
        }
        $pafi_txt = $fmt($row['pafi'] ?? null);
        $obs      = trim((string)($row['observaciones'] ?? ''));
        $row_id   = (int)($row['id'] ?? 0);
        ?>
    <div class="registro-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <?php $ts_date = strtotime((string)($row['date'] ?? '')); ?>
                <strong><?php echo text(date('d/m/Y H:i', $ts_date !== false ? $ts_date : time())); ?></strong>
                <small class="text-muted ml-2"><?php echo xlt('User'); ?>: <?php echo text((string)($row['user'] ?? '')); ?></small>
            </div>
            <div>
                <?php if (!\OpenEMR\Modules\Nursing\FormLock::isLocked('escala_sofa', (int)($row['id'] ?? 0), $encounter)) : ?>
                <a href="<?php echo attr(OEGlobalsBag::getInstance()->getString('webroot') . '/interface/modules/custom_modules/oe-module-nursing/public/forms/escala_sofa/new.php?pid=' . $pid . '&encounter=' . $encounter . '&id=' . $row_id); ?>"
                   class="btn btn-primary mr-1" onclick="top.restoreSession()"><i class="fas fa-pen mr-1"></i><?php echo xlt('Edit'); ?></a>
                <?php endif; ?>
                <a href="<?php echo attr(OEGlobalsBag::getInstance()->getString('webroot') . '/interface/modules/custom_modules/oe-module-nursing/public/forms/escala_sofa/print.php?pid=' . $pid . '&encounter=' . $encounter . '&id=' . $row_id); ?>"
                   target="_blank" class="btn btn-success" onclick="top.restoreSession()"><i class="fas fa-print mr-1"></i><?php echo xlt('Print'); ?></a>
            </div>
        </div>

        <div class="alert alert-<?php echo attr($s_class); ?> py-2">
            <strong>SOFA: <?php echo text((string)$total); ?>/24</strong>
            &nbsp;— <?php echo text($s_level); ?>
            <small class="ml-2">(<?php echo text((string)$evaluados); ?>/6 <?php echo xlt('systems evaluated'); ?>)</small>
        </div>

        <table class="sofa-table">
            <thead>
                <tr>
                    <th><?php echo xlt('System'); ?></th>
                    <th><?php echo xlt('Values'); ?></th>
                    <th class="td-points"><?php echo xlt('Points'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sofaSystems($row) as [$label, $values_txt, $pts]) : ?>
                <tr>
                    <td><strong><?php echo text($label); ?></strong></td>
                    <td><?php echo text($values_txt !== '' ? $values_txt : '—'); ?></td>
                    <td class="td-points">
                        <?php if ($pts === null) : ?>
                        <span class="not-evaluated"><?php echo xlt('Not evaluated'); ?></span>
                        <?php else : ?>
                        <strong><?php echo text((string)$pts); ?></strong>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <p class="mb-2"><strong><?php echo xlt('PaO2/FiO2 ratio'); ?>:</strong> <?php echo text($pafi_txt !== '' ? $pafi_txt : '—'); ?></p>

        <?php if ($obs !== '') : ?>
        <div class="field-detail">
            <strong><?php echo xlt('Observations'); ?>:</strong>
            <div class="mt-1 small"><?php echo nl2br(text($obs)); ?></div>
        </div>
        <?php endif; ?>

        <?php if ((string)($row['hora_registro'] ?? '') !== '') : ?>
        <small class="text-muted"><?php echo xlt('Record Time'); ?>: <?php echo text(substr((string)$row['hora_registro'], 0, 5)); ?></small>
        <?php endif; ?>
        <br><small class="text-muted">Vincent et al., 1996</small>
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
