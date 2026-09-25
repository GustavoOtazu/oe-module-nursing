<?php

/**
 * SOFA Score Form - new.php
 * Create/edit form for the Sequential Organ Failure Assessment (SOFA) score.
 * The saved score is always calculated server-side by SofaScore; the preview
 * shown here only mirrors it for convenience.
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

// Numeric fields are kept as strings so an empty value stays empty in the input.
$pao2                 = '';
$fio2                 = '';
$soporte_respiratorio = 0;
$plaquetas            = '';
$bilirrubina          = '';
$pam                  = '';
$dopamina             = '';
$dobutamina           = 0;
$epinefrina           = '';
$norepinefrina        = '';
$glasgow              = '';
$creatinina           = '';
$diuresis_24h         = '';
$hora_registro        = '';
$observaciones        = '';
/** @var array<string, array{value: string, note: string}> $prefill */
$prefill              = [];

if ($is_edit) {
    /** @var array<string, string|int|null>|false $row */
    $row = QueryUtils::querySingleRow("SELECT * FROM form_escala_sofa WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
    if ($row) {
        $pao2                 = (string)($row['pao2']          ?? '');
        $fio2                 = (string)($row['fio2']          ?? '');
        $soporte_respiratorio = (int)($row['soporte_respiratorio'] ?? 0);
        $plaquetas            = (string)($row['plaquetas']     ?? '');
        $bilirrubina          = (string)($row['bilirrubina']   ?? '');
        $pam                  = (string)($row['pam']           ?? '');
        $dopamina             = (string)($row['dopamina']      ?? '');
        $dobutamina           = (int)($row['dobutamina'] ?? 0);
        $epinefrina           = (string)($row['epinefrina']    ?? '');
        $norepinefrina        = (string)($row['norepinefrina'] ?? '');
        $glasgow              = (string)($row['glasgow']       ?? '');
        $creatinina           = (string)($row['creatinina']    ?? '');
        $diuresis_24h         = (string)($row['diuresis_24h']  ?? '');
        $hora_registro        = substr((string)($row['hora_registro'] ?? ''), 0, 5);
        $observaciones        = (string)($row['observaciones'] ?? '');
    } else {
        die(xlt("Error: Record not found or insufficient permissions."));
    }
} else {
    // Prefill with the worst value of each system in the last 24 h, taken from
    // vital signs, nursing evaluations, ventilation records, fluid balances and
    // previous scores (see ClinicalPrefill). The nurse reviews it before saving.
    $prefill = (new ClinicalPrefill($pid, $encounter))->forSofa();
    $pao2         = $prefill['pao2']['value']         ?? $pao2;
    $fio2         = $prefill['fio2']['value']         ?? $fio2;
    $plaquetas    = $prefill['plaquetas']['value']    ?? $plaquetas;
    $bilirrubina  = $prefill['bilirrubina']['value']  ?? $bilirrubina;
    $pam          = $prefill['pam']['value']          ?? $pam;
    $glasgow      = $prefill['glasgow']['value']      ?? $glasgow;
    $creatinina   = $prefill['creatinina']['value']   ?? $creatinina;
    $diuresis_24h = $prefill['diuresis_24h']['value'] ?? $diuresis_24h;
    if (isset($prefill['soporte_respiratorio'])) {
        $soporte_respiratorio = 1;
    }
}

// Shows where a prefilled value came from, so it can be checked before saving.
$hint = static function (string $field) use ($prefill): void {
    if (!isset($prefill[$field])) {
        return;
    }
    echo '<span class="field-hint prefill-hint"><i class="fa fa-history mr-1"></i>'
        . text($prefill[$field]['note']) . ' — ' . xlt('Review before saving') . '</span>';
};

$page_title = $is_edit ? xlt('Edit SOFA Score') : xlt('New SOFA Score');
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
        .sofa-form * { box-sizing: border-box; }
        /* No background/color here: inherit the active theme so the form works
           in both light and dark mode. Emphasis uses translucent overlays. */
        .sofa-form .form-section {
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .sofa-form .section-title { display: flex; justify-content: space-between; align-items: center; }
        .sofa-form .sub-badge {
            display: inline-block; min-width: 110px; text-align: center;
            padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;
            background: rgba(128, 128, 128, 0.15);
        }
        .sofa-form .sub-badge.scored { background: rgba(52, 152, 219, 0.20); }
        .sofa-form .field-hint { font-size: 11px; opacity: 0.75; }
        .sofa-form .prefill-hint { display: block; color: #0d6efd; opacity: 0.9; }
        .sofa-form .sofa-summary {
            border-radius: 6px; padding: 15px 20px; margin-bottom: 20px;
            border: 1px solid rgba(128, 128, 128, 0.35); border-left: 6px solid #6c757d;
            background: rgba(128, 128, 128, 0.08);
        }
        .sofa-form .sofa-summary.level-success { border-left-color: #28a745; background: rgba(40, 167, 69, 0.10); }
        .sofa-form .sofa-summary.level-warning { border-left-color: #ffc107; background: rgba(255, 193, 7, 0.12); }
        .sofa-form .sofa-summary.level-danger  { border-left-color: #dc3545; background: rgba(220, 53, 69, 0.10); }
        .sofa-form .sofa-total { font-size: 1.6em; font-weight: bold; }
        .sofa-form .mode-badge {
            display: inline-block; padding: 3px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600; margin-left: 8px;
        }
        .sofa-form .mode-create { background: #28a745; color: #fff; }
        .sofa-form .mode-edit   { background: #ffc107; color: #000; }
    </style>
</head>
<body class="body_top">
<div class="sofa-form container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-12">
            <h4>
                <?php echo text($page_title); ?>
                <span class="mode-badge <?php echo attr($is_edit ? 'mode-edit' : 'mode-create'); ?>">
                    <?php echo $is_edit ? xlt('Edit Mode') : xlt('Create Mode'); ?>
                </span>
            </h4>
            <small class="text-muted"><?php echo xlt('Encounter'); ?>: <?php echo text((string)$encounter); ?></small>
            <br>
            <small class="text-muted"><?php echo xlt('Sequential Organ Failure Assessment'); ?> — <?php echo xlt('Reference'); ?>: Vincent et al., 1996</small>
        </div>
    </div>

    <form method="POST" action="<?php echo attr($save_url); ?>" id="formEscalaSofa" onsubmit="top.restoreSession();">
        <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken(session: $session)); ?>">
        <input type="hidden" name="pid"       value="<?php echo attr((string)$pid); ?>">
        <input type="hidden" name="encounter" value="<?php echo attr((string)$encounter); ?>">
        <input type="hidden" name="from"      value="<?php echo attr($from); ?>">
        <?php if ($is_edit) : ?>
        <input type="hidden" name="id" value="<?php echo attr((string)$id); ?>">
        <?php endif; ?>

        <!-- RESPIRATORY -->
        <div class="form-section">
            <div class="section-title mb-2">
                <h6 class="font-weight-bold mb-0"><?php echo xlt('Respiratory system'); ?></h6>
                <span class="sub-badge" id="sub_resp"></span>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-4">
                    <label for="pao2">PaO2 (mmHg)</label>
                    <input type="number" class="form-control" name="pao2" id="pao2" min="0" max="999.9" step="0.1"
                           inputmode="decimal" value="<?php echo attr($pao2); ?>">
                    <?php $hint('pao2'); ?>
                </div>
                <div class="form-group col-sm-4">
                    <label for="fio2">FiO2</label>
                    <input type="number" class="form-control" name="fio2" id="fio2" min="0.21" max="100" step="0.01"
                           inputmode="decimal" value="<?php echo attr($fio2); ?>">
                    <?php $hint('fio2'); ?>
                    <span class="field-hint"><?php echo xlt('Fraction 0.21-1.0 or percentage 21-100'); ?></span>
                </div>
                <div class="form-group col-sm-4">
                    <label><?php echo xlt('PaO2/FiO2 ratio'); ?></label>
                    <div class="form-control-plaintext font-weight-bold" id="pafi_preview">—</div>
                </div>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="soporte_respiratorio" id="soporte_respiratorio" value="1"
                       <?php echo ($soporte_respiratorio === 1) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="soporte_respiratorio"><?php echo xlt('Mechanical ventilation / respiratory support'); ?></label>
            </div>
            <?php $hint('soporte_respiratorio'); ?>
            <span class="field-hint"><?php echo xlt('Scores 3 and 4 require respiratory support'); ?></span>
        </div>

        <!-- COAGULATION -->
        <div class="form-section">
            <div class="section-title mb-2">
                <h6 class="font-weight-bold mb-0"><?php echo xlt('Coagulation'); ?></h6>
                <span class="sub-badge" id="sub_coag"></span>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-4">
                    <label for="plaquetas"><?php echo xlt('Platelets'); ?> (x10³/µL)</label>
                    <input type="number" class="form-control" name="plaquetas" id="plaquetas" min="0" max="9999" step="1"
                           inputmode="decimal" value="<?php echo attr($plaquetas); ?>">
                    <?php $hint('plaquetas'); ?>
                </div>
            </div>
        </div>

        <!-- LIVER -->
        <div class="form-section">
            <div class="section-title mb-2">
                <h6 class="font-weight-bold mb-0"><?php echo xlt('Liver'); ?></h6>
                <span class="sub-badge" id="sub_hepatico"></span>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-4">
                    <label for="bilirrubina"><?php echo xlt('Bilirubin'); ?> (mg/dL)</label>
                    <input type="number" class="form-control" name="bilirrubina" id="bilirrubina" min="0" max="99.99" step="0.01"
                           inputmode="decimal" value="<?php echo attr($bilirrubina); ?>">
                    <?php $hint('bilirrubina'); ?>
                </div>
            </div>
        </div>

        <!-- CARDIOVASCULAR -->
        <div class="form-section">
            <div class="section-title mb-2">
                <h6 class="font-weight-bold mb-0"><?php echo xlt('Cardiovascular'); ?></h6>
                <span class="sub-badge" id="sub_cardio"></span>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-4">
                    <label for="pam"><?php echo xlt('Mean arterial pressure (MAP)'); ?> (mmHg)</label>
                    <input type="number" class="form-control" name="pam" id="pam" min="0" max="300" step="1"
                           inputmode="decimal" value="<?php echo attr($pam); ?>">
                    <?php $hint('pam'); ?>
                </div>
                <div class="form-group col-sm-4">
                    <label for="dopamina"><?php echo xlt('Dopamine'); ?> (µg/kg/min)</label>
                    <input type="number" class="form-control" name="dopamina" id="dopamina" min="0" max="100" step="0.1"
                           inputmode="decimal" value="<?php echo attr($dopamina); ?>">
                </div>
                <div class="form-group col-sm-4 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" name="dobutamina" id="dobutamina" value="1"
                               <?php echo ($dobutamina === 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="dobutamina"><?php echo xlt('Dobutamine (any dose)'); ?></label>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-4">
                    <label for="epinefrina"><?php echo xlt('Epinephrine'); ?> (µg/kg/min)</label>
                    <input type="number" class="form-control" name="epinefrina" id="epinefrina" min="0" max="99.999" step="0.001"
                           inputmode="decimal" value="<?php echo attr($epinefrina); ?>">
                </div>
                <div class="form-group col-sm-4">
                    <label for="norepinefrina"><?php echo xlt('Norepinephrine'); ?> (µg/kg/min)</label>
                    <input type="number" class="form-control" name="norepinefrina" id="norepinefrina" min="0" max="99.999" step="0.001"
                           inputmode="decimal" value="<?php echo attr($norepinefrina); ?>">
                </div>
            </div>
            <span class="field-hint"><?php echo xlt('Vasopressor doses administered for at least 1 hour'); ?></span>
        </div>

        <!-- CENTRAL NERVOUS SYSTEM -->
        <div class="form-section">
            <div class="section-title mb-2">
                <h6 class="font-weight-bold mb-0"><?php echo xlt('Central nervous system'); ?></h6>
                <span class="sub-badge" id="sub_snc"></span>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-4">
                    <label for="glasgow"><?php echo xlt('Glasgow Coma Scale'); ?> (3-15)</label>
                    <input type="number" class="form-control" name="glasgow" id="glasgow" min="3" max="15" step="1"
                           inputmode="numeric" value="<?php echo attr($glasgow); ?>">
                    <?php $hint('glasgow'); ?>
                </div>
            </div>
        </div>

        <!-- RENAL -->
        <div class="form-section">
            <div class="section-title mb-2">
                <h6 class="font-weight-bold mb-0"><?php echo xlt('Renal'); ?></h6>
                <span class="sub-badge" id="sub_renal"></span>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-4">
                    <label for="creatinina"><?php echo xlt('Creatinine'); ?> (mg/dL)</label>
                    <input type="number" class="form-control" name="creatinina" id="creatinina" min="0" max="99.99" step="0.01"
                           inputmode="decimal" value="<?php echo attr($creatinina); ?>">
                    <?php $hint('creatinina'); ?>
                </div>
                <div class="form-group col-sm-4">
                    <label for="diuresis_24h"><?php echo xlt('Urine output'); ?> (mL/24h)</label>
                    <input type="number" class="form-control" name="diuresis_24h" id="diuresis_24h" min="0" max="99999" step="1"
                           inputmode="decimal" value="<?php echo attr($diuresis_24h); ?>">
                    <?php $hint('diuresis_24h'); ?>
                </div>
            </div>
        </div>

        <!-- LIVE PREVIEW (the saved value is always recalculated on the server) -->
        <div class="sofa-summary" id="sofaSummary">
            <div class="sofa-total">SOFA: <span id="sofaTotal">0</span>/24 <small id="sofaLevel"></small></div>
            <small>(<span id="sofaEvaluated">0</span>/6 <?php echo xlt('systems evaluated'); ?>)</small>
            <div class="field-hint mt-1">
                0-6: <?php echo xlt('Low'); ?> &nbsp;|&nbsp; 7-11: <?php echo xlt('Intermediate'); ?> &nbsp;|&nbsp; ≥12: <?php echo xlt('High'); ?>
                &nbsp;—&nbsp; Vincent et al., 1996
            </div>
        </div>

        <!-- OBSERVATIONS -->
        <div class="form-group">
            <label for="observaciones" class="font-weight-bold"><?php echo xlt('Observations'); ?>:</label>
            <textarea name="observaciones" id="observaciones" class="form-control" rows="3"
                      placeholder="<?php echo attr(xl('Observations...')); ?>"><?php echo text($observaciones); ?></textarea>
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
// Mirrors OpenEMR\Modules\Nursing\Scoring\SofaScore exactly (Vincent et al., 1996).
// Preview only: save.php recalculates with the PHP class.
var SOFA_TXT = {
    notEvaluated: <?php echo js_escape(xl('Not evaluated')); ?>,
    points: <?php echo js_escape(xl('points')); ?>,
    low: <?php echo js_escape(xl('Low')); ?>,
    intermediate: <?php echo js_escape(xl('Intermediate')); ?>,
    high: <?php echo js_escape(xl('High')); ?>,
    fio2Range: <?php echo js_escape(xl('Fraction 0.21-1.0 or percentage 21-100')); ?>
};

var SofaCalc = {
    // Num::parse: empty or non numeric -> null; comma accepted as decimal separator.
    num: function (id) {
        var el = document.getElementById(id);
        if (!el) { return null; }
        var s = String(el.value).trim().replace(/,/g, '.');
        if (s === '') { return null; }
        var n = Number(s);
        return isFinite(n) ? n : null;
    },
    normalizeFio2: function (fio2) {
        if (fio2 === null || fio2 <= 0) { return null; }
        return fio2 > 1 ? fio2 / 100 : fio2;
    },
    pafi: function (pao2, fio2) {
        fio2 = SofaCalc.normalizeFio2(fio2);
        if (pao2 === null || pao2 <= 0 || fio2 === null) { return null; }
        return Math.round((pao2 / fio2) * 10) / 10;
    },
    respiratory: function (pafi, support) {
        if (pafi === null) { return null; }
        if (pafi < 100 && support) { return 4; }
        if (pafi < 200 && support) { return 3; }
        if (pafi < 300) { return 2; }
        if (pafi < 400) { return 1; }
        return 0;
    },
    coagulation: function (p) {
        if (p === null || p < 0) { return null; }
        if (p < 20) { return 4; }
        if (p < 50) { return 3; }
        if (p < 100) { return 2; }
        if (p < 150) { return 1; }
        return 0;
    },
    liver: function (b) {
        if (b === null || b < 0) { return null; }
        if (b >= 12.0) { return 4; }
        if (b >= 6.0) { return 3; }
        if (b >= 2.0) { return 2; }
        if (b >= 1.2) { return 1; }
        return 0;
    },
    cardiovascular: function (map, dopamine, dobutamine, epinephrine, norepinephrine) {
        dopamine = (dopamine === null) ? 0 : dopamine;
        epinephrine = (epinephrine === null) ? 0 : epinephrine;
        norepinephrine = (norepinephrine === null) ? 0 : norepinephrine;
        var anyDrug = dopamine > 0 || dobutamine || epinephrine > 0 || norepinephrine > 0;
        if (map === null && !anyDrug) { return null; }
        if (dopamine > 15 || epinephrine > 0.1 || norepinephrine > 0.1) { return 4; }
        if (dopamine > 5 || epinephrine > 0 || norepinephrine > 0) { return 3; }
        if (dopamine > 0 || dobutamine) { return 2; }
        if (map !== null && map < 70) { return 1; }
        return 0;
    },
    cns: function (g) {
        if (g === null || g < 3 || g > 15) { return null; }
        if (g < 6) { return 4; }
        if (g < 10) { return 3; }
        if (g < 13) { return 2; }
        if (g < 15) { return 1; }
        return 0;
    },
    renal: function (creatinine, urine) {
        if (creatinine === null && urine === null) { return null; }
        var byCreatinine = 0;
        if (creatinine !== null) {
            if (creatinine >= 5.0) { byCreatinine = 4; }
            else if (creatinine >= 3.5) { byCreatinine = 3; }
            else if (creatinine >= 2.0) { byCreatinine = 2; }
            else if (creatinine >= 1.2) { byCreatinine = 1; }
        }
        var byUrine = 0;
        if (urine !== null && urine >= 0) {
            if (urine < 200) { byUrine = 4; }
            else if (urine < 500) { byUrine = 3; }
        }
        return Math.max(byCreatinine, byUrine);
    }
};

function setSubBadge(id, score) {
    var el = document.getElementById(id);
    if (score === null) {
        el.textContent = SOFA_TXT.notEvaluated;
        el.classList.remove('scored');
    } else {
        el.textContent = score + ' ' + SOFA_TXT.points;
        el.classList.add('scored');
    }
}

function calcularSofa() {
    var num = SofaCalc.num;
    var pafi = SofaCalc.pafi(num('pao2'), num('fio2'));
    var scores = {
        sub_resp: SofaCalc.respiratory(pafi, document.getElementById('soporte_respiratorio').checked),
        sub_coag: SofaCalc.coagulation(num('plaquetas')),
        sub_hepatico: SofaCalc.liver(num('bilirrubina')),
        sub_cardio: SofaCalc.cardiovascular(
            num('pam'),
            num('dopamina'),
            document.getElementById('dobutamina').checked,
            num('epinefrina'),
            num('norepinefrina')
        ),
        sub_snc: SofaCalc.cns(num('glasgow')),
        sub_renal: SofaCalc.renal(num('creatinina'), num('diuresis_24h'))
    };
    var total = 0;
    var evaluated = 0;
    Object.keys(scores).forEach(function (key) {
        setSubBadge(key, scores[key]);
        if (scores[key] !== null) {
            total += scores[key];
            evaluated++;
        }
    });
    document.getElementById('pafi_preview').textContent = (pafi === null) ? '—' : String(pafi);
    document.getElementById('sofaTotal').textContent = total;
    document.getElementById('sofaEvaluated').textContent = evaluated;

    var level;
    var cls;
    if (total >= 12) {
        level = SOFA_TXT.high; cls = 'level-danger';
    } else if (total >= 7) {
        level = SOFA_TXT.intermediate; cls = 'level-warning';
    } else {
        level = SOFA_TXT.low; cls = 'level-success';
    }
    var summary = document.getElementById('sofaSummary');
    summary.classList.remove('level-success', 'level-warning', 'level-danger');
    summary.classList.add(cls);
    document.getElementById('sofaLevel').textContent = '— ' + level;
}

document.addEventListener('DOMContentLoaded', function () {
    var horaInput = document.getElementById('hora_registro');
    if (<?php echo $is_edit ? 'false' : 'true'; ?> && horaInput.value === '') {
        var now = new Date();
        horaInput.value = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
    }
    // FiO2 is accepted either as a fraction (0.21-1.0) or as a percentage (21-100).
    var fio2Input = document.getElementById('fio2');
    fio2Input.addEventListener('input', function () {
        var f = SofaCalc.num('fio2');
        var ok = (f === null) || (f >= 0.21 && f <= 1) || (f >= 21 && f <= 100);
        fio2Input.setCustomValidity(ok ? '' : SOFA_TXT.fio2Range);
    });
    document.querySelectorAll('#formEscalaSofa input[type="number"], #formEscalaSofa input[type="checkbox"]').forEach(function (el) {
        el.addEventListener('input', calcularSofa);
        el.addEventListener('change', calcularSofa);
    });
    calcularSofa();
});
</script>
</body>
</html>
