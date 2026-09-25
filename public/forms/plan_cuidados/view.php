<?php

/**
 * Nursing Care Plan Form - view.php
 * Displays care plan / shift progress note records for a patient encounter.
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
    $age = (new DateTime())->diff($dob)->y . ' ' . xl('years');
}

if ($id > 0) {
    /** @var list<array<string, string|int|null>> $rows */
    $rows = QueryUtils::fetchRecords("SELECT * FROM form_plan_cuidados WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
} else {
    /** @var list<array<string, string|int|null>> $rows */
    $rows = QueryUtils::fetchRecords("SELECT * FROM form_plan_cuidados WHERE pid = ? AND encounter = ? ORDER BY date DESC", [$pid, $encounter]);
}

$turno_labels = [
    'MANANA' => xl('Morning'),
    'TARDE'  => xl('Afternoon'),
    'NOCHE'  => xl('Night'),
];
// value => [label, bootstrap contextual class]
$evaluacion_badges = [
    'LOGRADO'    => [xl('Achieved'),           'success'],
    'PARCIAL'    => [xl('Partially achieved'), 'warning'],
    'NO_LOGRADO' => [xl('Not achieved'),       'danger'],
    'EN_CURSO'   => [xl('In progress'),        'info'],
];
$form_url = OEGlobalsBag::getInstance()->getString('webroot')
    . '/interface/modules/custom_modules/oe-module-nursing/public/forms/plan_cuidados/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo xlt('Nursing Care Plan'); ?></title>
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
        .plan-cuidados-view * { box-sizing: border-box; }
        .plan-cuidados-view .registro-card { border: 1px solid rgba(128, 128, 128, 0.35); border-radius: 6px; padding: 20px; margin-bottom: 20px; }
        .plan-cuidados-view .eval-badge { font-size: 0.95em; padding: 5px 12px; }
        /* Translucent overlays instead of fixed light colors, so the detail
           panels stay readable under both the light and dark themes. */
        .plan-cuidados-view .field-detail { background: rgba(128, 128, 128, 0.08); border-radius: 4px; padding: 10px 14px; margin-bottom: 10px; border-left: 4px solid #6c757d; }
        .plan-cuidados-view .field-detail.has-value { border-left-color: #0d6efd; background: rgba(13, 110, 253, 0.10); }
        .plan-cuidados-view .field-detail .field-text { margin-top: 4px; }
    </style>
</head>
<body class="body_top">
<div class="plan-cuidados-view container-fluid mt-3">
    <h5 class="border-bottom pb-2">
        <?php echo $id > 0 ? xlt('Care Plan Detail') : xlt('Nursing Care Plan'); ?>
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
    <div class="alert alert-info"><?php echo xlt('No care plans recorded'); ?></div>
    <?php endif; ?>

    <?php foreach ($rows as $row) :
        $row_id    = (int)($row['id'] ?? 0);
        $turno_val = (string)($row['turno'] ?? '');
        $hora_val  = substr((string)($row['hora_registro'] ?? ''), 0, 5);
        $eval_val  = (string)($row['evaluacion_resultado'] ?? '');
        $ts_date   = strtotime((string)($row['date'] ?? ''));
        ?>
    <div class="registro-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <strong><?php echo text(date('d/m/Y H:i', $ts_date !== false ? $ts_date : time())); ?></strong>
                <small class="text-muted ml-2"><?php echo xlt('User'); ?>: <?php echo text((string)($row['user'] ?? '')); ?></small>
            </div>
            <div>
                <?php if (!\OpenEMR\Modules\Nursing\FormLock::isLocked('plan_cuidados', (int)($row['id'] ?? 0), $encounter)) : ?>
                <a href="<?php echo attr($form_url . 'new.php?pid=' . $pid . '&encounter=' . $encounter . '&id=' . $row_id); ?>"
                   class="btn btn-primary mr-1" onclick="top.restoreSession()"><i class="fas fa-pen mr-1"></i><?php echo xlt('Edit'); ?></a>
                <?php endif; ?>
                <a href="<?php echo attr($form_url . 'print.php?pid=' . $pid . '&encounter=' . $encounter . '&id=' . $row_id); ?>"
                   target="_blank" class="btn btn-success" onclick="top.restoreSession()"><i class="fas fa-print mr-1"></i><?php echo xlt('Print'); ?></a>
            </div>
        </div>

        <div class="mb-3">
            <strong><?php echo xlt('Shift'); ?>:</strong>
            <?php echo text($turno_labels[$turno_val] ?? '—'); ?>
            &nbsp;|&nbsp;
            <strong><?php echo xlt('Record Time'); ?>:</strong>
            <?php echo text($hora_val !== '' ? $hora_val : '—'); ?>
        </div>

        <?php
        $sections = [
            'diagnostico_enfermeria' => xl('Nursing diagnosis'),
            'codigo_diagnostico'     => xl('Diagnosis code'),
            'objetivo'               => xl('Goal / expected outcome'),
            'intervenciones'         => xl('Nursing interventions'),
        ];
        foreach ($sections as $field => $label) :
            $val = trim((string)($row[$field] ?? ''));
            ?>
        <div class="field-detail <?php echo ($val !== '') ? 'has-value' : ''; ?>">
            <strong><?php echo text($label); ?>:</strong>
            <div class="field-text"><?php echo $val !== '' ? nl2br(text($val)) : text('—'); ?></div>
        </div>
        <?php endforeach; ?>

        <div class="field-detail <?php echo isset($evaluacion_badges[$eval_val]) ? 'has-value' : ''; ?>">
            <strong><?php echo xlt('Outcome evaluation'); ?>:</strong>
            <?php if (isset($evaluacion_badges[$eval_val])) : ?>
            <span class="badge badge-<?php echo attr($evaluacion_badges[$eval_val][1]); ?> eval-badge ml-1"><?php echo text($evaluacion_badges[$eval_val][0]); ?></span>
            <?php else : ?>
            <?php echo text('—'); ?>
            <?php endif; ?>
        </div>

        <?php
        $sections_after = [
            'evolucion'     => xl('Shift progress note'),
            'observaciones' => xl('Observations'),
        ];
        foreach ($sections_after as $field => $label) :
            $val = trim((string)($row[$field] ?? ''));
            ?>
        <div class="field-detail <?php echo ($val !== '') ? 'has-value' : ''; ?>">
            <strong><?php echo text($label); ?>:</strong>
            <div class="field-text"><?php echo $val !== '' ? nl2br(text($val)) : text('—'); ?></div>
        </div>
        <?php endforeach; ?>
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
