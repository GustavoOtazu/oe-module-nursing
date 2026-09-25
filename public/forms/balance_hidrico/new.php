<?php

/**
 * Nursing Fluid Balance Form - new.php
 * Intake/output record for a nursing shift, with a live fluid balance summary.
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
use OpenEMR\Modules\Nursing\Prefill\ClinicalPrefill;
use OpenEMR\Modules\Nursing\Scoring\FluidBalance;

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

$intake_labels = [
    'ing_via_oral'      => xlt('Oral intake'),
    'ing_enteral'       => xlt('Enteral nutrition'),
    'ing_parenteral'    => xlt('Parenteral nutrition'),
    'ing_sueros'        => xlt('IV fluids'),
    'ing_medicacion'    => xlt('IV medication'),
    'ing_hemoderivados' => xlt('Blood products'),
    'ing_otros'         => xlt('Other intake'),
];
$output_labels = [
    'eg_diuresis'             => xlt('Urine output'),
    'eg_drenajes'             => xlt('Drains'),
    'eg_sng'                  => xlt('Nasogastric tube'),
    'eg_vomitos'              => xlt('Vomiting'),
    'eg_deposiciones'         => xlt('Stools'),
    'eg_perdidas_insensibles' => xlt('Insensible losses'),
    'eg_otros'                => xlt('Other output'),
];
$shift_options = [
    'MANANA' => xlt('Morning'),
    'TARDE'  => xlt('Afternoon'),
    'NOCHE'  => xlt('Night'),
];

/** @var array<string, string> $values */
$values = [];
foreach (array_merge(FluidBalance::INTAKE_FIELDS, FluidBalance::OUTPUT_FIELDS) as $field) {
    $values[$field] = '';
}
$turno         = '';
$hora_registro = '';
$observaciones = '';
/** @var array<string, array{value: string, note: string}> $prefill */
$prefill       = [];

if ($is_edit) {
    /** @var array<string, string|int|null>|false $row */
    $row = QueryUtils::querySingleRow("SELECT * FROM form_balance_hidrico WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
    if ($row) {
        foreach (array_keys($values) as $field) {
            $values[$field] = (string)($row[$field] ?? '');
        }
        $turno         = (string)($row['turno']         ?? '');
        $hora_registro = substr((string)($row['hora_registro'] ?? ''), 0, 5);
        $observaciones = (string)($row['observaciones'] ?? '');
    } else {
        die(xlt("Error: Record not found or insufficient permissions."));
    }
} else {
    // Oral, enteral and parenteral intake from the nutrition records entered
    // since the previous fluid balance, so they are not typed twice.
    $prefill = (new ClinicalPrefill($pid, $encounter))->forFluidBalance();
    foreach ($prefill as $field => $data) {
        if (array_key_exists($field, $values)) {
            $values[$field] = $data['value'];
        }
    }
}

$page_title = $is_edit ? xlt('Edit Nursing Fluid Balance') : xlt('New Nursing Fluid Balance');
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
        .balance-hidrico-form * { box-sizing: border-box; }
        /* No background/color here: inherit the active theme so the form works
           in both light and dark mode. Emphasis uses translucent overlays. */
        .balance-hidrico-form .form-section {
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .balance-hidrico-form .form-section.intake-section { border-left-color: #17a2b8; background: rgba(23, 162, 184, 0.06); }
        .balance-hidrico-form .form-section.output-section { border-left-color: #fd7e14; background: rgba(253, 126, 20, 0.06); }
        .balance-hidrico-form .radio-group { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 12px; }
        .balance-hidrico-form .radio-group label { display: flex; align-items: center; gap: 6px; cursor: pointer; min-width: 140px; }
        .balance-hidrico-form .fluid-grid { display: flex; flex-wrap: wrap; gap: 12px 20px; }
        .balance-hidrico-form .fluid-item { flex: 1 1 220px; max-width: 320px; }
        .balance-hidrico-form .fluid-item label { font-weight: 600; margin-bottom: 4px; }
        .balance-hidrico-form .summary-card {
            display: flex; flex-wrap: wrap; gap: 12px;
            border-radius: 6px; padding: 15px; margin-bottom: 20px;
            background: rgba(128, 128, 128, 0.08); border: 1px solid rgba(128, 128, 128, 0.25);
        }
        .balance-hidrico-form .summary-item {
            flex: 1 1 180px; border-radius: 6px; padding: 10px 14px;
            border: 1px solid rgba(128, 128, 128, 0.25);
        }
        .balance-hidrico-form .summary-item .summary-label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8; }
        .balance-hidrico-form .summary-item .summary-value { font-size: 1.6em; font-weight: bold; }
        .balance-hidrico-form .summary-item.bh-positive { background: rgba(253, 126, 20, 0.15); border-color: rgba(253, 126, 20, 0.6); }
        .balance-hidrico-form .summary-item.bh-positive .summary-value { color: #fd7e14; }
        .balance-hidrico-form .summary-item.bh-negative { background: rgba(23, 162, 184, 0.15); border-color: rgba(23, 162, 184, 0.6); }
        .balance-hidrico-form .summary-item.bh-negative .summary-value { color: #17a2b8; }
        .balance-hidrico-form .summary-item.bh-zero { background: rgba(128, 128, 128, 0.10); }
        .balance-hidrico-form .mode-badge {
            display: inline-block; padding: 3px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600; margin-left: 8px;
        }
        .balance-hidrico-form .mode-create { background: #28a745; color: #fff; }
        .balance-hidrico-form .mode-edit   { background: #ffc107; color: #000; }
            .prefill-hint { color: #0d6efd; opacity: 0.9; font-size: 11px; }
    </style>
</head>
<body class="body_top">
<div class="balance-hidrico-form container-fluid mt-3">
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

    <form method="POST" action="<?php echo attr($save_url); ?>" id="formBalanceHidrico" onsubmit="top.restoreSession();">
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
                <?php foreach ($shift_options as $opt => $label) : ?>
                <label>
                    <input type="radio" name="turno" value="<?php echo attr($opt); ?>"
                           <?php echo ($turno === $opt) ? 'checked' : ''; ?>>
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

        <!-- INTAKE -->
        <div class="form-section intake-section">
            <h6 class="font-weight-bold"><?php echo xlt('Intake'); ?> (mL)</h6>
            <div class="fluid-grid">
                <?php foreach ($intake_labels as $field => $label) : ?>
                <div class="fluid-item">
                    <label for="<?php echo attr($field); ?>"><?php echo text($label); ?></label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" inputmode="decimal"
                               name="<?php echo attr($field); ?>" id="<?php echo attr($field); ?>"
                               class="form-control bh-input bh-intake" value="<?php echo attr($values[$field]); ?>">
                        <div class="input-group-append"><span class="input-group-text">mL</span></div>
                    </div>
                    <?php if (isset($prefill[$field])) : ?>
                    <small class="form-text prefill-hint"><i class="fa fa-history mr-1"></i><?php echo text($prefill[$field]['note']); ?> — <?php echo xlt('Review before saving'); ?></small>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- OUTPUT -->
        <div class="form-section output-section">
            <h6 class="font-weight-bold"><?php echo xlt('Output'); ?> (mL)</h6>
            <div class="fluid-grid">
                <?php foreach ($output_labels as $field => $label) : ?>
                <div class="fluid-item">
                    <label for="<?php echo attr($field); ?>"><?php echo text($label); ?></label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" inputmode="decimal"
                               name="<?php echo attr($field); ?>" id="<?php echo attr($field); ?>"
                               class="form-control bh-input bh-output" value="<?php echo attr($values[$field]); ?>">
                        <div class="input-group-append"><span class="input-group-text">mL</span></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- LIVE SUMMARY -->
        <div class="summary-card">
            <div class="summary-item">
                <div class="summary-label"><?php echo xlt('Total intake'); ?></div>
                <div class="summary-value"><span id="bhTotalIntake">0</span> mL</div>
            </div>
            <div class="summary-item">
                <div class="summary-label"><?php echo xlt('Total output'); ?></div>
                <div class="summary-value"><span id="bhTotalOutput">0</span> mL</div>
            </div>
            <div class="summary-item bh-zero" id="bhBalanceCard">
                <div class="summary-label"><?php echo xlt('Fluid balance'); ?></div>
                <div class="summary-value"><span id="bhBalance">0</span> mL</div>
            </div>
        </div>

        <!-- OBSERVATIONS -->
        <div class="form-group">
            <label for="observaciones" class="font-weight-bold"><?php echo xlt('Observations'); ?>:</label>
            <textarea name="observaciones" id="observaciones" class="form-control" rows="3"
                      placeholder="<?php echo attr(xlt('Observations...')); ?>"><?php echo text($observaciones); ?></textarea>
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
    calcularBalance();
    document.querySelectorAll('.bh-input').forEach(function (inp) {
        inp.addEventListener('input', calcularBalance);
        inp.addEventListener('change', calcularBalance);
    });
});

// Mirrors OpenEMR\Modules\Nursing\Scoring\FluidBalance: empty, non-numeric
// and negative values are ignored. The server recalculates on save.
function bhSum(selector) {
    var total = 0;
    document.querySelectorAll(selector).forEach(function (inp) {
        var v = parseFloat(String(inp.value).replace(',', '.'));
        if (!isNaN(v) && v > 0) { total += v; }
    });
    return Math.round(total * 100) / 100;
}

function bhFormat(n) {
    var r = Math.round(n * 100) / 100;
    return (r === 0) ? '0' : String(r);
}

function calcularBalance() {
    var totalIn  = bhSum('.bh-intake');
    var totalOut = bhSum('.bh-output');
    var balance  = Math.round((totalIn - totalOut) * 100) / 100;

    document.getElementById('bhTotalIntake').textContent = bhFormat(totalIn);
    document.getElementById('bhTotalOutput').textContent = bhFormat(totalOut);
    document.getElementById('bhBalance').textContent = (balance > 0 ? '+' : '') + bhFormat(balance);

    var card = document.getElementById('bhBalanceCard');
    card.classList.remove('bh-positive', 'bh-negative', 'bh-zero');
    card.classList.add(balance > 0 ? 'bh-positive' : (balance < 0 ? 'bh-negative' : 'bh-zero'));
}
</script>
</body>
</html>
