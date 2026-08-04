<?php

/**
 * Nursing Evaluations Form - new.php
 * Neurological assessment form for inpatients (Glasgow Scale, consciousness, pupils, etc.)
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    OpenEMR Contributors
 * @copyright Copyright (c) 2026 OpenEMR Contributors
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

$conciencia         = '';
$obs_conciencia     = '';
$tono               = '';
$obs_tono           = '';
$pupilas            = '';
$obs_pupilas        = '';
$mucosas            = '';
$obs_mucosas        = '';
$glasgow_ojos       = '';
$obs_glasgow_ojos   = '';
$glasgow_motora     = '';
$obs_glasgow_motora = '';
$glasgow_verbal     = '';
$obs_glasgow_verbal = '';
$glasgow_total      = 0;
$hora_evaluacion    = '';

if ($is_edit) {
    /** @var array<string, string|int|null>|false $row */
    $row = QueryUtils::querySingleRow("SELECT * FROM form_evaluaciones WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
    if ($row) {
        $conciencia         = (string)($row['conciencia']         ?? '');
        $obs_conciencia     = (string)($row['obs_conciencia']     ?? '');
        $tono               = (string)($row['tono']               ?? '');
        $obs_tono           = (string)($row['obs_tono']           ?? '');
        $pupilas            = (string)($row['pupilas']            ?? '');
        $obs_pupilas        = (string)($row['obs_pupilas']        ?? '');
        $mucosas            = (string)($row['mucosas']            ?? '');
        $obs_mucosas        = (string)($row['obs_mucosas']        ?? '');
        $glasgow_ojos       = (string)($row['glasgow_ojos']       ?? '');
        $obs_glasgow_ojos   = (string)($row['obs_glasgow_ojos']   ?? '');
        $glasgow_motora     = (string)($row['glasgow_motora']     ?? '');
        $obs_glasgow_motora = (string)($row['obs_glasgow_motora'] ?? '');
        $glasgow_verbal     = (string)($row['glasgow_verbal']     ?? '');
        $obs_glasgow_verbal = (string)($row['obs_glasgow_verbal'] ?? '');
        $glasgow_total      = (int)($row['glasgow_total'] ?? 0);
        $hora_evaluacion    = (string)($row['hora_evaluacion']    ?? '');
    } else {
        die(xlt("Error: Record not found or insufficient permissions."));
    }
}

$page_title = $is_edit ? xlt('Edit Nursing Evaluation') : xlt('New Nursing Evaluation');
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
        .evaluaciones-form * { box-sizing: border-box; }
        /* No background/color here: inherit the active theme so the form works
           in both light and dark mode. Emphasis uses translucent overlays. */
        .evaluaciones-form .form-section {
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .evaluaciones-form .form-section.glasgow-section { border-left-color: #e74c3c; background: rgba(231, 76, 60, 0.08); }
        .evaluaciones-form .radio-group { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 12px; }
        .evaluaciones-form .radio-group label { display: flex; align-items: center; gap: 6px; cursor: pointer; min-width: 170px; }
        .evaluaciones-form .glasgow-info {
            background: rgba(52, 152, 219, 0.10); border: 1px solid rgba(52, 152, 219, 0.35);
            border-radius: 6px; padding: 15px; margin-top: 15px;
        }
        .evaluaciones-form .mode-badge {
            display: inline-block; padding: 3px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600; margin-left: 8px;
        }
        .evaluaciones-form .mode-create { background: #28a745; color: #fff; }
        .evaluaciones-form .mode-edit   { background: #ffc107; color: #000; }
    </style>
</head>
<body class="body_top">
<div class="evaluaciones-form container-fluid mt-3">
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

    <form method="POST" action="<?php echo attr($save_url); ?>" id="formEvaluaciones" onsubmit="top.restoreSession();">
        <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken(session: $session)); ?>">
        <input type="hidden" name="pid"       value="<?php echo attr((string)$pid); ?>">
        <input type="hidden" name="encounter" value="<?php echo attr((string)$encounter); ?>">
        <input type="hidden" name="from"      value="<?php echo attr($from); ?>">
        <?php if ($is_edit) : ?>
        <input type="hidden" name="id" value="<?php echo attr((string)$id); ?>">
        <?php endif; ?>

        <!-- CONSCIOUSNESS -->
        <div class="form-section">
            <h6 class="font-weight-bold"><?php echo xlt('Consciousness'); ?></h6>
            <div class="radio-group">
                <?php foreach (['VIGIL', 'SOMNOLIENTO', 'ESTUPOROSO', 'COMATOSO'] as $opt) : ?>
                <label>
                    <input type="radio" name="conciencia" value="<?php echo attr($opt); ?>"
                           <?php echo ($conciencia === $opt) ? 'checked' : ''; ?>>
                    <?php echo xlt($opt); ?>
                </label>
                <?php endforeach; ?>
            </div>
            <textarea name="obs_conciencia" class="form-control" rows="2"
                      placeholder="<?php echo attr(xlt('Observations...')); ?>"><?php echo text($obs_conciencia); ?></textarea>
        </div>

        <!-- MUSCLE TONE -->
        <div class="form-section">
            <h6 class="font-weight-bold"><?php echo xlt('Muscle Tone'); ?></h6>
            <div class="radio-group">
                <?php foreach (['NORMAL', 'FLACIDO', 'ESPASTICO'] as $opt) : ?>
                <label>
                    <input type="radio" name="tono" value="<?php echo attr($opt); ?>"
                           <?php echo ($tono === $opt) ? 'checked' : ''; ?>>
                    <?php echo xlt($opt); ?>
                </label>
                <?php endforeach; ?>
            </div>
            <textarea name="obs_tono" class="form-control" rows="2"
                      placeholder="<?php echo attr(xlt('Observations...')); ?>"><?php echo text($obs_tono); ?></textarea>
        </div>

        <!-- PUPILS -->
        <div class="form-section">
            <h6 class="font-weight-bold"><?php echo xlt('Pupils'); ?></h6>
            <div class="radio-group">
                <?php foreach (['NORMAL', 'MIDRIASIS', 'MIOSIS'] as $opt) : ?>
                <label>
                    <input type="radio" name="pupilas" value="<?php echo attr($opt); ?>"
                           <?php echo ($pupilas === $opt) ? 'checked' : ''; ?>>
                    <?php echo xlt($opt); ?>
                </label>
                <?php endforeach; ?>
            </div>
            <textarea name="obs_pupilas" class="form-control" rows="2"
                      placeholder="<?php echo attr(xlt('Observations...')); ?>"><?php echo text($obs_pupilas); ?></textarea>
        </div>

        <!-- MUCOUS MEMBRANES -->
        <div class="form-section">
            <h6 class="font-weight-bold"><?php echo xlt('Mucous Membranes'); ?></h6>
            <div class="radio-group">
                <?php foreach (['SECA', 'HUMEDA', 'PALIDA', 'ICTERICA', 'CIANOSIS'] as $opt) : ?>
                <label>
                    <input type="radio" name="mucosas" value="<?php echo attr($opt); ?>"
                           <?php echo ($mucosas === $opt) ? 'checked' : ''; ?>>
                    <?php echo xlt($opt); ?>
                </label>
                <?php endforeach; ?>
            </div>
            <textarea name="obs_mucosas" class="form-control" rows="2"
                      placeholder="<?php echo attr(xlt('Observations...')); ?>"><?php echo text($obs_mucosas); ?></textarea>
        </div>

        <!-- GLASGOW COMA SCALE -->
        <div class="form-section glasgow-section">
            <h6 class="font-weight-bold"><?php echo xlt('Glasgow Coma Scale'); ?></h6>

            <p class="font-weight-bold mt-3"><?php echo xlt('Eye Opening'); ?></p>
            <div class="radio-group">
                <?php
                $eye_options = [
                    'ESPONTANEAMENTE'       => xlt('Spontaneously') . ' (4)',
                    'A ESTIMULOS AUDITIVOS' => xlt('To auditory stimuli') . ' (3)',
                    'AL DOLOR'              => xlt('To pain') . ' (2)',
                    'SIN RESPUESTA'         => xlt('No response') . ' (1)',
                ];
                foreach ($eye_options as $val => $label) : ?>
                <label>
                    <input type="radio" name="glasgow_ojos" value="<?php echo attr($val); ?>"
                           <?php echo ($glasgow_ojos === $val) ? 'checked' : ''; ?>>
                    <?php echo text($label); ?>
                </label>
                <?php endforeach; ?>
            </div>
            <textarea name="obs_glasgow_ojos" class="form-control" rows="2"
                      placeholder="<?php echo attr(xlt('Observations...')); ?>"><?php echo text($obs_glasgow_ojos); ?></textarea>

            <p class="font-weight-bold mt-3"><?php echo xlt('Motor Response'); ?></p>
            <div class="radio-group">
                <?php
                $motor_options = [
                    'OBEDECE ORDENES'    => xlt('Obeys commands') . ' (6)',
                    'LOCALIZA DOLOR'     => xlt('Localizes pain') . ' (5)',
                    'FLEXION DE DEFENSA' => xlt('Withdrawal') . ' (4)',
                    'FLEXION ANORMAL'    => xlt('Abnormal flexion') . ' (3)',
                    'EXTENSION ANORMAL'  => xlt('Abnormal extension') . ' (2)',
                    'NINGUNA'            => xlt('No response') . ' (1)',
                ];
                foreach ($motor_options as $val => $label) : ?>
                <label>
                    <input type="radio" name="glasgow_motora" value="<?php echo attr($val); ?>"
                           <?php echo ($glasgow_motora === $val) ? 'checked' : ''; ?>>
                    <?php echo text($label); ?>
                </label>
                <?php endforeach; ?>
            </div>
            <textarea name="obs_glasgow_motora" class="form-control" rows="2"
                      placeholder="<?php echo attr(xlt('Observations...')); ?>"><?php echo text($obs_glasgow_motora); ?></textarea>

            <p class="font-weight-bold mt-3"><?php echo xlt('Verbal Response'); ?></p>
            <div class="radio-group">
                <?php
                $verbal_options = [
                    'ORIENTADO Y CONVERSA'    => xlt('Oriented and conversing') . ' (5)',
                    'DESORIENTADO Y CONVERSA' => xlt('Disoriented and conversing') . ' (4)',
                    'LENGUAJE INADECUADO'     => xlt('Inappropriate words') . ' (3)',
                    'SONIDOS INCOMPRENSIBLES' => xlt('Incomprehensible sounds') . ' (2)',
                    'NINGUNA'                 => xlt('No response') . ' (1)',
                ];
                foreach ($verbal_options as $val => $label) : ?>
                <label>
                    <input type="radio" name="glasgow_verbal" value="<?php echo attr($val); ?>"
                           <?php echo ($glasgow_verbal === $val) ? 'checked' : ''; ?>>
                    <?php echo text($label); ?>
                </label>
                <?php endforeach; ?>
            </div>
            <textarea name="obs_glasgow_verbal" class="form-control" rows="2"
                      placeholder="<?php echo attr(xlt('Observations...')); ?>"><?php echo text($obs_glasgow_verbal); ?></textarea>

            <div class="glasgow-info mt-3">
                <h6><?php echo xlt('Glasgow Total Score'); ?>: <strong><span id="glasgowTotal"><?php echo text($glasgow_total > 0 ? (string)$glasgow_total : '0'); ?></span>/15</strong></h6>
                <small>13-15: <?php echo xlt('Mild'); ?> &nbsp;|&nbsp; 9-12: <?php echo xlt('Moderate'); ?> &nbsp;|&nbsp; 3-8: <?php echo xlt('Severe'); ?></small>
            </div>
        </div>

        <!-- EVALUATION TIME -->
        <div class="form-group">
            <label for="hora_evaluacion" class="font-weight-bold"><?php echo xlt('Evaluation Time'); ?>:</label>
            <input type="time" name="hora_evaluacion" id="hora_evaluacion"
                   class="form-control w-auto" value="<?php echo attr($hora_evaluacion); ?>">
        </div>

        <div class="form-group mt-3">
            <button type="submit" onclick="top.restoreSession()" class="btn btn-primary">
                <i class="fas fa-check mr-1"></i><?php echo $is_edit ? xlt('Save Changes') : xlt('Save'); ?>
            </button>
            <button type="button" onclick="return cancelClicked()" class="btn btn-outline-secondary ml-2">
                <i class="fas fa-times mr-1"></i><?php echo xlt('Cancel'); ?>
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var horaInput = document.getElementById('hora_evaluacion');
    if (<?php echo $is_edit ? 'false' : 'true'; ?> && horaInput.value === '') {
        var now = new Date();
        horaInput.value = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
    }
    calcularGlasgow();
    document.querySelectorAll('input[name^="glasgow_"]').forEach(function (r) {
        r.addEventListener('change', calcularGlasgow);
    });
});

function calcularGlasgow() {
    var scores = {
        glasgow_ojos:   { 'ESPONTANEAMENTE': 4, 'A ESTIMULOS AUDITIVOS': 3, 'AL DOLOR': 2, 'SIN RESPUESTA': 1 },
        glasgow_motora: { 'OBEDECE ORDENES': 6, 'LOCALIZA DOLOR': 5, 'FLEXION DE DEFENSA': 4, 'FLEXION ANORMAL': 3, 'EXTENSION ANORMAL': 2, 'NINGUNA': 1 },
        glasgow_verbal: { 'ORIENTADO Y CONVERSA': 5, 'DESORIENTADO Y CONVERSA': 4, 'LENGUAJE INADECUADO': 3, 'SONIDOS INCOMPRENSIBLES': 2, 'NINGUNA': 1 }
    };
    var total = 0;
    Object.keys(scores).forEach(function (field) {
        var checked = document.querySelector('input[name="' + field + '"]:checked');
        if (checked) { total += scores[field][checked.value] || 0; }
    });
    document.getElementById('glasgowTotal').textContent = total;
}
</script>
</body>
</html>
