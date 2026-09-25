<?php

/**
 * Nursing Nutrition Form - new.php
 * Create/edit form for the patient's feeding record (oral, enteral,
 * parenteral, mixed or fasting), volumes, tolerance and intolerance signs.
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

// Stored decimals come back as "250.00"; show them without trailing zeros.
$fmtNum = static function (mixed $value): string {
    if ($value === null || $value === '') {
        return '';
    }
    $s = (string) $value;
    return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
};

$hora_registro       = '';
$tipo_alimentacion   = '';
$via_enteral         = '';
$modalidad           = '';
$formula             = '';
$volumen_ml          = '';
$velocidad_ml_h      = '';
$residuo_gastrico_ml = '';
$tolerancia          = '';
$signos_intolerancia = [];
$observaciones       = '';

if ($is_edit) {
    /** @var array<string, string|int|null>|false $row */
    $row = QueryUtils::querySingleRow("SELECT * FROM form_alimentacion WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
    if ($row) {
        $hora_registro       = substr((string)($row['hora_registro'] ?? ''), 0, 5);
        $tipo_alimentacion   = (string)($row['tipo_alimentacion'] ?? '');
        $via_enteral         = (string)($row['via_enteral']       ?? '');
        $modalidad           = (string)($row['modalidad']         ?? '');
        $formula             = (string)($row['formula']           ?? '');
        $volumen_ml          = $fmtNum($row['volumen_ml']          ?? null);
        $velocidad_ml_h      = $fmtNum($row['velocidad_ml_h']      ?? null);
        $residuo_gastrico_ml = $fmtNum($row['residuo_gastrico_ml'] ?? null);
        $tolerancia          = (string)($row['tolerancia']        ?? '');
        $signos_raw          = (string)($row['signos_intolerancia'] ?? '');
        $signos_intolerancia = ($signos_raw !== '') ? explode(',', $signos_raw) : [];
        $observaciones       = (string)($row['observaciones']     ?? '');
    } else {
        die(xlt("Error: Record not found or insufficient permissions."));
    }
}

$tipo_options = [
    'ORAL'       => xlt('Oral'),
    'ENTERAL'    => xlt('Enteral'),
    'PARENTERAL' => xlt('Parenteral'),
    'MIXTA'      => xlt('Mixed feeding'),
    'AYUNO'      => xlt('Fasting'),
];
$via_options = [
    'SNG'          => xlt('Nasogastric tube'),
    'SNY'          => xlt('Nasojejunal tube'),
    'GASTROSTOMIA' => xlt('Gastrostomy'),
    'YEYUNOSTOMIA' => xlt('Jejunostomy'),
];
$modalidad_options = [
    'CONTINUA'     => xlt('Continuous'),
    'INTERMITENTE' => xlt('Intermittent'),
    'BOLO'         => xlt('Bolus'),
];
$tolerancia_options = [
    'BUENA'   => xlt('Good'),
    'REGULAR' => xlt('Fair'),
    'MALA'    => xlt('Poor'),
];
$signos_options = [
    'VOMITOS'      => xlt('Vomiting'),
    'DISTENSION'   => xlt('Abdominal distension'),
    'DIARREA'      => xlt('Diarrhea'),
    'RESIDUO_ALTO' => xlt('High gastric residual'),
];

$page_title = $is_edit ? xlt('Edit Nursing Nutrition') : xlt('New Nursing Nutrition');
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
    <title><?php echo $page_title; ?></title>
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
        .alimentacion-form * { box-sizing: border-box; }
        /* No background/color here: inherit the active theme so the form works
           in both light and dark mode. Emphasis uses translucent overlays. */
        .alimentacion-form .form-section {
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .alimentacion-form .form-section.tolerance-section { border-left-color: #e67e22; background: rgba(230, 126, 34, 0.08); }
        .alimentacion-form .radio-group { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 12px; }
        .alimentacion-form .radio-group label { display: flex; align-items: center; gap: 6px; cursor: pointer; min-width: 170px; }
        .alimentacion-form .sub-block {
            background: rgba(52, 152, 219, 0.08); border: 1px solid rgba(52, 152, 219, 0.30);
            border-radius: 6px; padding: 12px 15px; margin-top: 12px;
        }
        .alimentacion-form .num-row { display: flex; flex-wrap: wrap; gap: 20px; }
        .alimentacion-form .num-row .form-group { min-width: 200px; }
        .alimentacion-form .num-row .input-group { max-width: 220px; }
        .alimentacion-form .mode-badge {
            display: inline-block; padding: 3px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600; margin-left: 8px;
        }
        .alimentacion-form .mode-create { background: #28a745; color: #fff; }
        .alimentacion-form .mode-edit   { background: #ffc107; color: #000; }
    </style>
</head>
<body class="body_top">
<div class="alimentacion-form container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-12">
            <h4>
                <?php echo $page_title; ?>
                <span class="mode-badge <?php echo attr($is_edit ? 'mode-edit' : 'mode-create'); ?>">
                    <?php echo $is_edit ? xlt('Edit Mode') : xlt('Create Mode'); ?>
                </span>
            </h4>
            <small class="text-muted"><?php echo xlt('Encounter'); ?>: <?php echo text((string)$encounter); ?></small>
        </div>
    </div>

    <form method="POST" action="<?php echo attr($save_url); ?>" id="formAlimentacion" onsubmit="top.restoreSession();">
        <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken(session: $session)); ?>">
        <input type="hidden" name="pid"       value="<?php echo attr((string)$pid); ?>">
        <input type="hidden" name="encounter" value="<?php echo attr((string)$encounter); ?>">
        <input type="hidden" name="from"      value="<?php echo attr($from); ?>">
        <?php if ($is_edit) : ?>
        <input type="hidden" name="id" value="<?php echo attr((string)$id); ?>">
        <?php endif; ?>

        <!-- FEEDING TYPE -->
        <div class="form-section">
            <h6 class="font-weight-bold"><?php echo xlt('Feeding Type'); ?></h6>
            <div class="radio-group">
                <?php foreach ($tipo_options as $val => $label) : ?>
                <label>
                    <input type="radio" name="tipo_alimentacion" value="<?php echo attr($val); ?>"
                           <?php echo ($tipo_alimentacion === $val) ? 'checked' : ''; ?>>
                    <?php echo $label; ?>
                </label>
                <?php endforeach; ?>
            </div>

            <!-- ENTERAL ROUTE (ENTERAL / MIXTA only) -->
            <div class="sub-block" id="bloqueViaEnteral">
                <p class="font-weight-bold mb-2"><?php echo xlt('Enteral Route'); ?></p>
                <div class="radio-group mb-0">
                    <?php foreach ($via_options as $val => $label) : ?>
                    <label>
                        <input type="radio" name="via_enteral" value="<?php echo attr($val); ?>"
                               <?php echo ($via_enteral === $val) ? 'checked' : ''; ?>>
                        <?php echo $label; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- MODALITY (not for ORAL / AYUNO) -->
            <div class="sub-block" id="bloqueModalidad">
                <p class="font-weight-bold mb-2"><?php echo xlt('Modality'); ?></p>
                <div class="radio-group mb-0">
                    <?php foreach ($modalidad_options as $val => $label) : ?>
                    <label>
                        <input type="radio" name="modalidad" value="<?php echo attr($val); ?>"
                               <?php echo ($modalidad === $val) ? 'checked' : ''; ?>>
                        <?php echo $label; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- FORMULA AND VOLUMES -->
        <div class="form-section">
            <div class="form-group">
                <label for="formula" class="font-weight-bold"><?php echo xlt('Formula / Diet'); ?>:</label>
                <input type="text" name="formula" id="formula" class="form-control" maxlength="255"
                       value="<?php echo attr($formula); ?>">
            </div>

            <div class="num-row">
                <div class="form-group" id="grupoVolumen">
                    <label for="volumen_ml" class="font-weight-bold"><?php echo xlt('Volume'); ?>:</label>
                    <div class="input-group">
                        <input type="number" name="volumen_ml" id="volumen_ml" class="form-control"
                               step="0.01" min="0" inputmode="decimal" value="<?php echo attr($volumen_ml); ?>">
                        <div class="input-group-append"><span class="input-group-text">mL</span></div>
                    </div>
                </div>
                <div class="form-group" id="grupoVelocidad">
                    <label for="velocidad_ml_h" class="font-weight-bold"><?php echo xlt('Infusion Rate'); ?>:</label>
                    <div class="input-group">
                        <input type="number" name="velocidad_ml_h" id="velocidad_ml_h" class="form-control"
                               step="0.01" min="0" inputmode="decimal" value="<?php echo attr($velocidad_ml_h); ?>">
                        <div class="input-group-append"><span class="input-group-text">mL/h</span></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="residuo_gastrico_ml" class="font-weight-bold"><?php echo xlt('Gastric Residual'); ?>:</label>
                    <div class="input-group">
                        <input type="number" name="residuo_gastrico_ml" id="residuo_gastrico_ml" class="form-control"
                               step="0.01" min="0" inputmode="decimal" value="<?php echo attr($residuo_gastrico_ml); ?>">
                        <div class="input-group-append"><span class="input-group-text">mL</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TOLERANCE -->
        <div class="form-section tolerance-section">
            <h6 class="font-weight-bold"><?php echo xlt('Tolerance'); ?></h6>
            <div class="radio-group">
                <?php foreach ($tolerancia_options as $val => $label) : ?>
                <label>
                    <input type="radio" name="tolerancia" value="<?php echo attr($val); ?>"
                           <?php echo ($tolerancia === $val) ? 'checked' : ''; ?>>
                    <?php echo $label; ?>
                </label>
                <?php endforeach; ?>
            </div>

            <p class="font-weight-bold mt-3 mb-2"><?php echo xlt('Signs of Intolerance'); ?></p>
            <div class="radio-group">
                <?php foreach ($signos_options as $val => $label) : ?>
                <label>
                    <input type="checkbox" name="signos_intolerancia[]" value="<?php echo attr($val); ?>"
                           <?php echo in_array($val, $signos_intolerancia, true) ? 'checked' : ''; ?>>
                    <?php echo $label; ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- OBSERVATIONS -->
        <div class="form-group">
            <label for="observaciones" class="font-weight-bold"><?php echo xlt('Observations'); ?>:</label>
            <textarea name="observaciones" id="observaciones" class="form-control" rows="3"
                      placeholder="<?php echo xla('Observations...'); ?>"><?php echo text($observaciones); ?></textarea>
        </div>

        <!-- RECORD TIME -->
        <div class="form-group">
            <label for="hora_registro" class="font-weight-bold"><?php echo xlt('Record Time'); ?>:</label>
            <input type="time" name="hora_registro" id="hora_registro"
                   class="form-control w-auto" value="<?php echo attr($hora_registro); ?>">
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
    var horaInput = document.getElementById('hora_registro');
    if (<?php echo $is_edit ? 'false' : 'true'; ?> && horaInput.value === '') {
        var now = new Date();
        horaInput.value = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
    }
    actualizarVisibilidad();
    document.querySelectorAll('input[name="tipo_alimentacion"]').forEach(function (r) {
        r.addEventListener('change', actualizarVisibilidad);
    });
});

// Show only the fields that apply to the selected feeding type. Hidden fields
// keep their value in the page, but save.php discards whatever does not apply.
function actualizarVisibilidad() {
    var checked = document.querySelector('input[name="tipo_alimentacion"]:checked');
    var tipo = checked ? checked.value : '';
    var usaVia = (tipo === 'ENTERAL' || tipo === 'MIXTA');
    var sinInfusion = (tipo === 'ORAL' || tipo === 'AYUNO');
    document.getElementById('bloqueViaEnteral').style.display = usaVia ? '' : 'none';
    document.getElementById('bloqueModalidad').style.display = sinInfusion ? 'none' : '';
    document.getElementById('grupoVelocidad').style.display = sinInfusion ? 'none' : '';
    document.getElementById('grupoVolumen').style.display = (tipo === 'AYUNO') ? 'none' : '';
}
</script>
</body>
</html>
