<?php

/**
 * Nursing Care Plan Form - new.php
 * Nursing care plan (diagnosis, goal, interventions, outcome evaluation,
 * following the nursing process / ISO 18104:2023 categories) and the
 * free-text shift progress note.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once(dirname(__DIR__, 6) . "/globals.php");
/** @var string $srcdir */
require_once("$srcdir/api.inc.php");

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Csrf\CsrfUtils;
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

if (!$pid || !$encounter) {
    die(xlt("Error: Missing required parameters (PID or Encounter)"));
}

if (!AclMain::aclCheckCore('encounters', 'notes')) {
    die(xlt('Access denied'));
}

$is_edit = ($id > 0);

$turno                  = '';
$hora_registro          = '';
$diagnostico_enfermeria = '';
$codigo_diagnostico     = '';
$objetivo               = '';
$intervenciones         = '';
$evaluacion_resultado   = '';
$evolucion              = '';
$observaciones          = '';

if ($is_edit) {
    /** @var array<string, string|int|null>|false $row */
    $row = QueryUtils::querySingleRow("SELECT * FROM form_plan_cuidados WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
    if ($row) {
        $turno                  = (string)($row['turno']                  ?? '');
        $hora_registro          = substr((string)($row['hora_registro']   ?? ''), 0, 5);
        $diagnostico_enfermeria = (string)($row['diagnostico_enfermeria'] ?? '');
        $codigo_diagnostico     = (string)($row['codigo_diagnostico']     ?? '');
        $objetivo               = (string)($row['objetivo']               ?? '');
        $intervenciones         = (string)($row['intervenciones']         ?? '');
        $evaluacion_resultado   = (string)($row['evaluacion_resultado']   ?? '');
        $evolucion              = (string)($row['evolucion']              ?? '');
        $observaciones          = (string)($row['observaciones']          ?? '');
    } else {
        die(xlt("Error: Record not found or insufficient permissions."));
    }
}

$turno_options = [
    'MANANA' => xl('Morning'),
    'TARDE'  => xl('Afternoon'),
    'NOCHE'  => xl('Night'),
];
$evaluacion_options = [
    'LOGRADO'    => xl('Achieved'),
    'PARCIAL'    => xl('Partially achieved'),
    'NO_LOGRADO' => xl('Not achieved'),
    'EN_CURSO'   => xl('In progress'),
];

$page_title = $is_edit ? xl('Edit Nursing Care Plan') : xl('New Nursing Care Plan');
$from      = (filter_input(INPUT_GET, 'from') === 'list') ? 'list' : 'encounter';
$cancel_url = OEGlobalsBag::getInstance()->getString('webroot')
    . "/interface/modules/custom_modules/oe-module-nursing/public/dashboard/lista_internados.php";
// Absolute: core load_form.php includes this file from another directory,
// so a relative action would resolve against /interface/patient_file/encounter/.
$save_url  = OEGlobalsBag::getInstance()->getString('webroot')
    . "/interface/modules/custom_modules/oe-module-nursing/public/forms/" . basename(__DIR__) . "/save.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo text($page_title); ?></title>
    <?php Header::setupHeader(); ?>
    <script>
        function cancelClicked() {
<?php if ($from === 'list') : ?>
            top.RTop.location = <?php echo js_escape($cancel_url); ?>;
<?php else : ?>
            // Opened from the encounter as a frame tab: closing it returns to
            // the encounter's form list, which is the core convention.
            parent.closeTab(window.name, true);
<?php endif; ?>
            return false;
        }
    </script>
    <style>
        .plan-cuidados-form * { box-sizing: border-box; }
        /* No background/color here: inherit the active theme so the form works
           in both light and dark mode. Emphasis uses translucent overlays. */
        .plan-cuidados-form .form-section {
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .plan-cuidados-form .form-section.diagnosis-section    { border-left-color: #e74c3c; background: rgba(231, 76, 60, 0.06); }
        .plan-cuidados-form .form-section.goal-section         { border-left-color: #8e44ad; background: rgba(142, 68, 173, 0.06); }
        .plan-cuidados-form .form-section.interventions-section { border-left-color: #16a085; background: rgba(22, 160, 133, 0.06); }
        .plan-cuidados-form .form-section.evaluation-section   { border-left-color: #f39c12; background: rgba(243, 156, 18, 0.06); }
        .plan-cuidados-form .form-section.progress-section     { border-left-color: #2980b9; background: rgba(41, 128, 185, 0.08); }
        .plan-cuidados-form .section-help { display: block; opacity: 0.75; margin-bottom: 8px; }
        .plan-cuidados-form .step-number {
            display: inline-block; min-width: 24px; padding: 1px 7px; margin-right: 6px;
            border-radius: 12px; background: rgba(128, 128, 128, 0.20); text-align: center;
        }
        .plan-cuidados-form .radio-group { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 12px; }
        .plan-cuidados-form .radio-group label { display: flex; align-items: center; gap: 6px; cursor: pointer; min-width: 170px; }
        .plan-cuidados-form .required-info {
            background: rgba(52, 152, 219, 0.10); border: 1px solid rgba(52, 152, 219, 0.35);
            border-radius: 6px; padding: 10px 15px; margin-bottom: 20px;
        }
        .plan-cuidados-form .required-info.is-invalid {
            background: rgba(220, 53, 69, 0.12); border-color: rgba(220, 53, 69, 0.55);
        }
        .plan-cuidados-form .mode-badge {
            display: inline-block; padding: 3px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600; margin-left: 8px;
        }
        .plan-cuidados-form .mode-create { background: #28a745; color: #fff; }
        .plan-cuidados-form .mode-edit   { background: #ffc107; color: #000; }
    </style>
</head>
<body class="body_top">
<div class="plan-cuidados-form container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-12">
            <h4>
                <?php echo text($page_title); ?>
                <span class="mode-badge <?php echo attr($is_edit ? 'mode-edit' : 'mode-create'); ?>">
                    <?php echo $is_edit ? xlt('Edit Mode') : xlt('Create Mode'); ?>
                </span>
            </h4>
            <small class="text-muted"><?php echo xlt('Encounter'); ?>: <?php echo text((string)$encounter); ?></small>
        </div>
    </div>

    <form method="POST" action="<?php echo attr($save_url); ?>" id="formPlanCuidados" onsubmit="return validatePlanCuidados();">
        <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken(session: $session)); ?>">
        <input type="hidden" name="pid"       value="<?php echo attr((string)$pid); ?>">
        <input type="hidden" name="encounter" value="<?php echo attr((string)$encounter); ?>">
        <input type="hidden" name="from"      value="<?php echo attr($from); ?>">
        <?php if ($is_edit) : ?>
        <input type="hidden" name="id" value="<?php echo attr((string)$id); ?>">
        <?php endif; ?>

        <!-- SHIFT AND TIME -->
        <div class="form-section">
            <h6 class="font-weight-bold"><?php echo xlt('Shift'); ?></h6>
            <div class="radio-group">
                <?php foreach ($turno_options as $val => $label) : ?>
                <label>
                    <input type="radio" name="turno" value="<?php echo attr($val); ?>"
                           <?php echo ($turno === $val) ? 'checked' : ''; ?>>
                    <?php echo text($label); ?>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="form-group mb-0">
                <label for="hora_registro" class="font-weight-bold"><?php echo xlt('Record Time'); ?>:</label>
                <input type="time" name="hora_registro" id="hora_registro"
                       class="form-control w-auto" value="<?php echo attr($hora_registro); ?>">
            </div>
        </div>

        <div class="required-info" id="planRequiredInfo">
            <i class="fas fa-info-circle mr-1"></i><?php echo xlt('Enter at least a nursing diagnosis or a shift progress note.'); ?>
        </div>

        <!-- 1. NURSING DIAGNOSIS -->
        <div class="form-section diagnosis-section">
            <h6 class="font-weight-bold"><span class="step-number">1</span><?php echo xlt('Nursing diagnosis'); ?></h6>
            <small class="section-help"><?php echo xlt('What problem or risk did you identify?'); ?></small>
            <textarea name="diagnostico_enfermeria" id="diagnostico_enfermeria" class="form-control" rows="3"><?php echo text($diagnostico_enfermeria); ?></textarea>
            <div class="form-group mt-3 mb-0">
                <label for="codigo_diagnostico" class="font-weight-bold"><?php echo xlt('Diagnosis code (optional)'); ?>:</label>
                <input type="text" name="codigo_diagnostico" id="codigo_diagnostico" maxlength="40"
                       class="form-control w-auto" value="<?php echo attr($codigo_diagnostico); ?>">
                <small class="form-text text-muted"><?php echo xlt('e.g. NANDA-I or ICNP code'); ?></small>
            </div>
        </div>

        <!-- 2. GOAL / EXPECTED OUTCOME -->
        <div class="form-section goal-section">
            <h6 class="font-weight-bold"><span class="step-number">2</span><?php echo xlt('Goal / expected outcome'); ?></h6>
            <small class="section-help"><?php echo xlt('What do you expect to achieve and by when?'); ?></small>
            <textarea name="objetivo" id="objetivo" class="form-control" rows="3"><?php echo text($objetivo); ?></textarea>
        </div>

        <!-- 3. NURSING INTERVENTIONS -->
        <div class="form-section interventions-section">
            <h6 class="font-weight-bold"><span class="step-number">3</span><?php echo xlt('Nursing interventions'); ?></h6>
            <small class="section-help"><?php echo xlt('What care will be/was provided?'); ?></small>
            <textarea name="intervenciones" id="intervenciones" class="form-control" rows="4"><?php echo text($intervenciones); ?></textarea>
        </div>

        <!-- 4. OUTCOME EVALUATION -->
        <div class="form-section evaluation-section">
            <h6 class="font-weight-bold"><span class="step-number">4</span><?php echo xlt('Outcome evaluation'); ?></h6>
            <div class="radio-group mb-0">
                <?php foreach ($evaluacion_options as $val => $label) : ?>
                <label>
                    <input type="radio" name="evaluacion_resultado" value="<?php echo attr($val); ?>"
                           <?php echo ($evaluacion_resultado === $val) ? 'checked' : ''; ?>>
                    <?php echo text($label); ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 5. SHIFT PROGRESS NOTE -->
        <div class="form-section progress-section">
            <h6 class="font-weight-bold"><span class="step-number">5</span><?php echo xlt('Shift progress note'); ?></h6>
            <small class="section-help"><?php echo xlt('Relevant events of the shift'); ?></small>
            <textarea name="evolucion" id="evolucion" class="form-control" rows="5"><?php echo text($evolucion); ?></textarea>
        </div>

        <!-- OBSERVATIONS -->
        <div class="form-section">
            <h6 class="font-weight-bold"><?php echo xlt('Observations'); ?></h6>
            <textarea name="observaciones" id="observaciones" class="form-control" rows="2"
                      placeholder="<?php echo attr(xl('Observations...')); ?>"><?php echo text($observaciones); ?></textarea>
        </div>

        <div class="form-group mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-check mr-1"></i><?php echo $is_edit ? xlt('Save Changes') : xlt('Save'); ?>
            </button>
            <button type="button" onclick="return cancelClicked()" class="btn btn-outline-secondary ml-2">
                <i class="fas fa-times mr-1"></i><?php echo xlt('Cancel'); ?>
            </button>
        </div>
    </form>
</div>

<script>
var planRequiredMsg = <?php echo js_escape(xl('Enter at least a nursing diagnosis or a shift progress note.')); ?>;

// At least one of diagnosis / progress note must be filled. The custom
// validity on the diagnosis field makes the browser block the submit
// natively; validatePlanCuidados() is a second check for older browsers.
function syncPlanRequirement() {
    var diag = document.getElementById('diagnostico_enfermeria');
    var evol = document.getElementById('evolucion');
    var bothEmpty = diag.value.trim() === '' && evol.value.trim() === '';
    diag.setCustomValidity(bothEmpty ? planRequiredMsg : '');
    return !bothEmpty;
}

function validatePlanCuidados() {
    var ok = syncPlanRequirement();
    document.getElementById('planRequiredInfo').classList.toggle('is-invalid', !ok);
    if (!ok) {
        alert(planRequiredMsg);
        document.getElementById('diagnostico_enfermeria').focus();
        return false;
    }
    top.restoreSession();
    return true;
}

document.addEventListener('DOMContentLoaded', function () {
    var horaInput = document.getElementById('hora_registro');
    if (<?php echo $is_edit ? 'false' : 'true'; ?> && horaInput.value === '') {
        var now = new Date();
        horaInput.value = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
    }
    ['diagnostico_enfermeria', 'evolucion'].forEach(function (fieldId) {
        document.getElementById(fieldId).addEventListener('input', function () {
            if (syncPlanRequirement()) {
                document.getElementById('planRequiredInfo').classList.remove('is-invalid');
            }
        });
    });
    // Fired when native validation blocks the submit: highlight the hint too.
    document.getElementById('diagnostico_enfermeria').addEventListener('invalid', function () {
        document.getElementById('planRequiredInfo').classList.add('is-invalid');
    });
    syncPlanRequirement();
});
</script>
</body>
</html>
