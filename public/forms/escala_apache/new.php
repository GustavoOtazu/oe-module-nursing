<?php

/**
 * APACHE II Score Form - new.php
 * Create/edit form for the APACHE II severity score (Knaus et al., 1985).
 * The score shown here is only a preview; the saved values are always
 * calculated server-side by ApacheIIScore::calculate().
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
use OpenEMR\Modules\Nursing\Scoring\ApacheIIScore;

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

$numeric_fields = [
    'temperatura', 'pam', 'frecuencia_cardiaca', 'frecuencia_respiratoria',
    'fio2', 'pao2', 'paco2', 'ph', 'bicarbonato', 'sodio', 'potasio',
    'creatinina', 'hematocrito', 'leucocitos', 'glasgow', 'edad',
];

/** @var array<string, string> $vals */
$vals = array_fill_keys($numeric_fields, '');
$insuficiencia_renal_aguda = 0;
$enfermedad_cronica        = 0;
$tipo_ingreso              = '';
$hora_registro             = '';
$observaciones             = '';

if ($is_edit) {
    /** @var array<string, string|int|null>|false $row */
    $row = QueryUtils::querySingleRow("SELECT * FROM form_escala_apache WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
    if ($row) {
        foreach ($numeric_fields as $field) {
            $vals[$field] = $num($row[$field] ?? null);
        }
        $insuficiencia_renal_aguda = (int)($row['insuficiencia_renal_aguda'] ?? 0);
        $enfermedad_cronica        = (int)($row['enfermedad_cronica'] ?? 0);
        $tipo_ingreso              = (string)($row['tipo_ingreso'] ?? '');
        $hora_registro             = substr((string)($row['hora_registro'] ?? ''), 0, 5);
        $observaciones             = (string)($row['observaciones'] ?? '');
    } else {
        die(xlt("Error: Record not found or insufficient permissions."));
    }
} else {
    // Prefill age from the date of birth
    /** @var array<string, string|int|null>|false $paciente */
    $paciente = QueryUtils::querySingleRow("SELECT DOB FROM patient_data WHERE pid = ?", [$pid]);
    $dob_val  = $paciente !== false ? (string)($paciente['DOB'] ?? '') : '';
    if ($dob_val !== '' && $dob_val !== '0000-00-00') {
        try {
            $vals['edad'] = (string)(new DateTime())->diff(new DateTime($dob_val))->y;
        } catch (\Exception) {
            $vals['edad'] = '';
        }
    }

    // Prefill Glasgow from the latest nursing evaluation of this encounter
    /** @var array<string, string|int|null>|false $gcsRow */
    $gcsRow = QueryUtils::querySingleRow(
        "SELECT e.glasgow_total
           FROM form_evaluaciones e
           JOIN forms f ON f.form_id = e.id AND f.formdir = 'evaluaciones' AND f.deleted = 0
          WHERE e.pid = ? AND e.encounter = ? AND e.glasgow_total >= 3 AND e.glasgow_total <= 15
          ORDER BY e.date DESC, e.id DESC
          LIMIT 1",
        [$pid, $encounter]
    );
    if ($gcsRow !== false && (int)($gcsRow['glasgow_total'] ?? 0) > 0) {
        $vals['glasgow'] = (string)(int)$gcsRow['glasgow_total'];
    }
}

$admission_types = [
    ApacheIIScore::ADMISSION_NON_OPERATIVE     => xl('Non-operative'),
    ApacheIIScore::ADMISSION_EMERGENCY_POSTOP  => xl('Emergency postoperative'),
    ApacheIIScore::ADMISSION_ELECTIVE_POSTOP   => xl('Elective postoperative'),
];

$page_title = $is_edit ? xlt('Edit APACHE II Score') : xlt('New APACHE II Score');
$from      = (filter_input(INPUT_GET, 'from') === 'list') ? 'list' : 'encounter';
$cancel_url = OEGlobalsBag::getInstance()->getString('webroot')
    . "/interface/modules/custom_modules/oe-module-nursing/public/dashboard/lista_internados.php";
// Absolute: core load_form.php includes this file from another directory,
// so a relative action would resolve against /interface/patient_file/encounter/.
$save_url  = OEGlobalsBag::getInstance()->getString('webroot')
    . "/interface/modules/custom_modules/oe-module-nursing/public/forms/" . basename(__DIR__) . "/save.php";

// Renders one numeric input. Labels, units and help come from xl() and are escaped here.
$numInput = static function (string $name, string $label, string $unit, string $value, string $min, string $max, string $step, string $help = ''): void {
    ?>
    <div class="col-lg-3 col-md-4 col-sm-6 form-group">
        <label for="<?php echo attr($name); ?>" class="font-weight-bold"><?php echo text($label); ?></label>
        <div class="input-group">
            <input type="number" name="<?php echo attr($name); ?>" id="<?php echo attr($name); ?>"
                   class="form-control apache-input" value="<?php echo attr($value); ?>"
                   min="<?php echo attr($min); ?>" max="<?php echo attr($max); ?>" step="<?php echo attr($step); ?>">
            <?php if ($unit !== '') : ?>
            <div class="input-group-append"><span class="input-group-text"><?php echo text($unit); ?></span></div>
            <?php endif; ?>
        </div>
        <?php if ($help !== '') : ?>
        <small class="form-text text-muted"><?php echo text($help); ?></small>
        <?php endif; ?>
    </div>
    <?php
};
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
        .apache-form * { box-sizing: border-box; }
        /* No background/color here: inherit the active theme so the form works
           in both light and dark mode. Emphasis uses translucent overlays. */
        .apache-form .form-section {
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .apache-form .form-section.oxygen-section { border-left-color: #16a085; }
        .apache-form .form-section.neuro-section  { border-left-color: #e74c3c; background: rgba(231, 76, 60, 0.08); }
        .apache-form .form-section.chronic-section { border-left-color: #8e44ad; }
        .apache-form .radio-group { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 12px; }
        .apache-form .radio-group label { display: flex; align-items: center; gap: 6px; cursor: pointer; min-width: 200px; }
        .apache-form .check-label { display: flex; align-items: center; gap: 6px; cursor: pointer; }
        .apache-form .apache-info {
            background: rgba(52, 152, 219, 0.10); border: 1px solid rgba(52, 152, 219, 0.35);
            border-radius: 6px; padding: 12px 15px; margin-bottom: 20px;
        }
        .apache-form .apache-preview {
            border-radius: 6px; padding: 15px; margin-bottom: 20px;
            border: 1px solid rgba(128, 128, 128, 0.35); background: rgba(128, 128, 128, 0.08);
        }
        .apache-form .apache-preview.sev-success { border-color: rgba(40, 167, 69, 0.6);  background: rgba(40, 167, 69, 0.10); }
        .apache-form .apache-preview.sev-warning { border-color: rgba(255, 193, 7, 0.7);  background: rgba(255, 193, 7, 0.12); }
        .apache-form .apache-preview.sev-danger  { border-color: rgba(220, 53, 69, 0.6);  background: rgba(220, 53, 69, 0.10); }
        .apache-form .mode-badge {
            display: inline-block; padding: 3px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600; margin-left: 8px;
        }
        .apache-form .mode-create { background: #28a745; color: #fff; }
        .apache-form .mode-edit   { background: #ffc107; color: #000; }
    </style>
</head>
<body class="body_top">
<div class="apache-form container-fluid mt-3">
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

    <div class="apache-info">
        <i class="fas fa-info-circle mr-1"></i><?php echo xlt('Use the worst value of the first 24 hours'); ?>.
        <?php echo xlt('Variables left empty add 0 points and are reported as not evaluated'); ?>.
        <br><small class="text-muted"><?php echo xlt('Reference'); ?>: Knaus et al., 1985</small>
    </div>

    <form method="POST" action="<?php echo attr($save_url); ?>" id="formEscalaApache" onsubmit="top.restoreSession();">
        <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken(session: $session)); ?>">
        <input type="hidden" name="pid"       value="<?php echo attr((string)$pid); ?>">
        <input type="hidden" name="encounter" value="<?php echo attr((string)$encounter); ?>">
        <input type="hidden" name="from"      value="<?php echo attr($from); ?>">
        <?php if ($is_edit) : ?>
        <input type="hidden" name="id" value="<?php echo attr((string)$id); ?>">
        <?php endif; ?>

        <!-- VITAL SIGNS -->
        <div class="form-section">
            <h6 class="font-weight-bold"><?php echo xlt('Vital signs'); ?></h6>
            <div class="row">
                <?php
                $numInput('temperatura', xl('Rectal temperature'), '°C', $vals['temperatura'], '20', '45', '0.1');
                $numInput('pam', xl('Mean arterial pressure'), 'mmHg', $vals['pam'], '0', '300', '0.1');
                $numInput('frecuencia_cardiaca', xl('Heart rate'), 'bpm', $vals['frecuencia_cardiaca'], '0', '300', '1');
                $numInput('frecuencia_respiratoria', xl('Respiratory rate'), 'rpm', $vals['frecuencia_respiratoria'], '0', '100', '1');
                ?>
            </div>
        </div>

        <!-- OXYGENATION -->
        <div class="form-section oxygen-section">
            <h6 class="font-weight-bold"><?php echo xlt('Oxygenation'); ?></h6>
            <div class="row">
                <?php
                $numInput('fio2', 'FiO2', '', $vals['fio2'], '0.21', '100', 'any', xl('Fraction (0.21-1) or percentage (21-100)'));
                $numInput('pao2', 'PaO2', 'mmHg', $vals['pao2'], '0', '800', '0.1');
                $numInput('paco2', 'PaCO2', 'mmHg', $vals['paco2'], '0', '250', '0.1');
                ?>
            </div>
            <small class="text-muted"><?php echo xlt('With FiO2 ≥ 0.5 the A-aDO2 gradient is used; otherwise PaO2'); ?>.</small>
        </div>

        <!-- ACID-BASE -->
        <div class="form-section">
            <h6 class="font-weight-bold"><?php echo xlt('Acid-base'); ?></h6>
            <div class="row">
                <?php
                $numInput('ph', xl('Arterial pH'), '', $vals['ph'], '6.5', '8', '0.01');
                $numInput('bicarbonato', xl('Serum HCO3'), 'mmol/L', $vals['bicarbonato'], '0', '80', '0.1', xl('Only if no arterial blood gas'));
                ?>
            </div>
        </div>

        <!-- LABORATORY -->
        <div class="form-section">
            <h6 class="font-weight-bold"><?php echo xlt('Laboratory'); ?></h6>
            <div class="row">
                <?php
                $numInput('sodio', xl('Sodium'), 'mmol/L', $vals['sodio'], '80', '220', '0.1');
                $numInput('potasio', xl('Potassium'), 'mmol/L', $vals['potasio'], '0.5', '15', '0.1');
                $numInput('creatinina', xl('Creatinine'), 'mg/dL', $vals['creatinina'], '0', '40', '0.01');
                ?>
                <div class="col-lg-3 col-md-4 col-sm-6 form-group d-flex align-items-end">
                    <label class="check-label mb-2">
                        <input type="checkbox" name="insuficiencia_renal_aguda" id="insuficiencia_renal_aguda" value="1"
                               class="apache-input" <?php echo ($insuficiencia_renal_aguda === 1) ? 'checked' : ''; ?>>
                        <?php echo xlt('Acute renal failure'); ?>
                        <small class="text-muted">(<?php echo xlt('creatinine points doubled'); ?>)</small>
                    </label>
                </div>
            </div>
            <div class="row">
                <?php
                $numInput('hematocrito', xl('Hematocrit'), '%', $vals['hematocrito'], '0', '90', '0.1');
                $numInput('leucocitos', xl('White blood cells'), 'x10³/µL', $vals['leucocitos'], '0', '999', '0.1');
                ?>
            </div>
        </div>

        <!-- NEUROLOGICAL -->
        <div class="form-section neuro-section">
            <h6 class="font-weight-bold"><?php echo xlt('Neurological'); ?></h6>
            <div class="row">
                <?php
                $numInput('glasgow', xl('Glasgow Coma Scale'), '3-15', $vals['glasgow'], '3', '15', '1', $is_edit ? '' : xl('Prefilled from the latest nursing evaluation, if any'));
                ?>
            </div>
        </div>

        <!-- AGE AND CHRONIC HEALTH -->
        <div class="form-section chronic-section">
            <h6 class="font-weight-bold"><?php echo xlt('Age and chronic health'); ?></h6>
            <div class="row">
                <?php
                $numInput('edad', xl('Age'), xl('years'), $vals['edad'], '0', '130', '1');
                ?>
            </div>
            <label class="check-label mb-3">
                <input type="checkbox" name="enfermedad_cronica" id="enfermedad_cronica" value="1"
                       class="apache-input" <?php echo ($enfermedad_cronica === 1) ? 'checked' : ''; ?>>
                <?php echo xlt('History of severe organ insufficiency or immunocompromise'); ?>
            </label>
            <p class="font-weight-bold mb-2"><?php echo xlt('Admission type'); ?></p>
            <div class="radio-group">
                <?php foreach ($admission_types as $val => $label) : ?>
                <label>
                    <input type="radio" name="tipo_ingreso" value="<?php echo attr($val); ?>" class="apache-input"
                           <?php echo ($tipo_ingreso === $val) ? 'checked' : ''; ?>>
                    <?php echo text($label); ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- LIVE PREVIEW (the saved score is always calculated by the server) -->
        <div class="apache-preview" id="apachePreview">
            <h6 class="mb-1">
                <strong>APACHE II = <span id="apacheTotal">0</span></strong>
                <small>(<span id="apacheEvaluados">0</span>/12 <?php echo xlt('variables evaluated'); ?>)</small>
                &nbsp;— <span id="apacheLevel"></span>
            </h6>
            <small>
                <?php echo xlt('Acute physiology'); ?>: <span id="apacheFisio">0</span>
                &nbsp;|&nbsp; <?php echo xlt('Age points'); ?>: <span id="apacheEdad">0</span>
                &nbsp;|&nbsp; <?php echo xlt('Chronic health points'); ?>: <span id="apacheCronicos">0</span>
                <span id="apacheAado2Wrap" style="display:none;">&nbsp;|&nbsp; A-aDO2: <span id="apacheAado2"></span> mmHg</span>
            </small>
            <div><small class="text-muted">0-14: <?php echo xlt('Lower severity'); ?> &nbsp;|&nbsp; 15-24: <?php echo xlt('Moderate severity'); ?> &nbsp;|&nbsp; ≥25: <?php echo xlt('Higher severity'); ?></small></div>
        </div>

        <!-- OBSERVATIONS -->
        <div class="form-group">
            <label for="observaciones" class="font-weight-bold"><?php echo xlt('Observations'); ?>:</label>
            <textarea name="observaciones" id="observaciones" class="form-control" rows="3"
                      placeholder="<?php echo attr(xlt('Observations...')); ?>"><?php echo text($observaciones); ?></textarea>
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
(function () {
    'use strict';

    var LEVELS = {
        success: <?php echo js_escape(xl('Lower severity')); ?>,
        warning: <?php echo js_escape(xl('Moderate severity')); ?>,
        danger:  <?php echo js_escape(xl('Higher severity')); ?>
    };

    // Mirrors OpenEMR\Modules\Nursing\Scoring\Num::parse()
    function parseNum(value) {
        if (value === null || value === undefined) { return null; }
        var s = String(value).trim().replace(/,/g, '.');
        if (s === '' || !/^[+-]?(\d+(\.\d*)?|\.\d+)([eE][+-]?\d+)?$/.test(s)) { return null; }
        return parseFloat(s);
    }

    // Mirrors SofaScore::normalizeFio2()
    function normalizeFio2(f) {
        if (f === null || f <= 0) { return null; }
        return f > 1 ? f / 100 : f;
    }

    function round1(x) {
        var r = Math.round(Math.abs(x) * 10) / 10;
        return x < 0 ? -r : r;
    }

    // Descending ">=" bands, same order as the PHP match(true) blocks.
    function band(x, bands, fallback) {
        if (x === null) { return null; }
        for (var i = 0; i < bands.length; i++) {
            if (x >= bands[i][0]) { return bands[i][1]; }
        }
        return fallback;
    }

    // The thresholds below are a copy of ApacheIIScore; keep them in sync.
    function aaGradient(fio2, pao2, paco2) {
        if (fio2 === null || pao2 === null || paco2 === null) { return null; }
        return round1(fio2 * (760 - 47) - paco2 / 0.8 - pao2);
    }
    function oxygenation(fio2, pao2, aado2) {
        if (fio2 === null) { return null; }
        if (fio2 >= 0.5) {
            if (aado2 === null) { return null; }
            return band(aado2, [[500, 4], [350, 3], [200, 2]], 0);
        }
        if (pao2 === null) { return null; }
        if (pao2 > 70) { return 0; }
        return band(pao2, [[61, 1], [55, 3]], 4);
    }
    function creatinine(cr, arf) {
        var p = band(cr, [[3.5, 4], [2, 3], [1.5, 2], [0.6, 0]], 2);
        if (p === null) { return null; }
        return arf ? p * 2 : p;
    }
    function glasgow(gcs) {
        if (gcs === null || gcs < 3 || gcs > 15) { return null; }
        return 15 - Math.trunc(gcs);
    }
    function age(years) {
        if (years === null || years < 0) { return 0; }
        return band(years, [[75, 6], [65, 5], [55, 3], [45, 2]], 0);
    }
    function chronicHealth(hasChronic, admissionType) {
        if (!hasChronic) { return 0; }
        return admissionType === <?php echo js_escape(ApacheIIScore::ADMISSION_ELECTIVE_POSTOP); ?> ? 2 : 5;
    }

    function val(id) {
        var el = document.getElementById(id);
        return el ? parseNum(el.value) : null;
    }

    function calculate() {
        var fio2 = normalizeFio2(val('fio2'));
        var pao2 = val('pao2');
        var paco2 = val('paco2');
        var aado2 = aaGradient(fio2, pao2, paco2);
        var ph = val('ph');

        var detail = [
            band(val('temperatura'), [[41, 4], [39, 3], [38.5, 1], [36, 0], [34, 1], [32, 2], [30, 3]], 4),
            band(val('pam'), [[160, 4], [130, 3], [110, 2], [70, 0], [50, 2]], 4),
            band(val('frecuencia_cardiaca'), [[180, 4], [140, 3], [110, 2], [70, 0], [55, 2], [40, 3]], 4),
            band(val('frecuencia_respiratoria'), [[50, 4], [35, 3], [25, 1], [12, 0], [10, 1], [6, 2]], 4),
            oxygenation(fio2, pao2, aado2),
            ph !== null
                ? band(ph, [[7.7, 4], [7.6, 3], [7.5, 1], [7.33, 0], [7.25, 2], [7.15, 3]], 4)
                : band(val('bicarbonato'), [[52, 4], [41, 3], [32, 1], [22, 0], [18, 2], [15, 3]], 4),
            band(val('sodio'), [[180, 4], [160, 3], [155, 2], [150, 1], [130, 0], [120, 2], [111, 3]], 4),
            band(val('potasio'), [[7, 4], [6, 3], [5.5, 1], [3.5, 0], [3, 1], [2.5, 2]], 4),
            creatinine(val('creatinina'), document.getElementById('insuficiencia_renal_aguda').checked),
            band(val('hematocrito'), [[60, 4], [50, 2], [46, 1], [30, 0], [20, 2]], 4),
            band(val('leucocitos'), [[40, 4], [20, 2], [15, 1], [3, 0], [1, 2]], 4),
            glasgow(val('glasgow'))
        ];

        var physiology = 0, evaluated = 0;
        detail.forEach(function (p) {
            if (p !== null) { physiology += p; evaluated++; }
        });
        var tipo = document.querySelector('input[name="tipo_ingreso"]:checked');
        var agePts = age(val('edad'));
        var chronicPts = chronicHealth(document.getElementById('enfermedad_cronica').checked, tipo ? tipo.value : '');
        var total = physiology + agePts + chronicPts;
        var sev = total >= 25 ? 'danger' : (total >= 15 ? 'warning' : 'success');

        document.getElementById('apacheTotal').textContent = total;
        document.getElementById('apacheEvaluados').textContent = evaluated;
        document.getElementById('apacheFisio').textContent = physiology;
        document.getElementById('apacheEdad').textContent = agePts;
        document.getElementById('apacheCronicos').textContent = chronicPts;
        document.getElementById('apacheLevel').textContent = LEVELS[sev];
        document.getElementById('apacheAado2Wrap').style.display = aado2 === null ? 'none' : '';
        document.getElementById('apacheAado2').textContent = aado2 === null ? '' : aado2;
        var preview = document.getElementById('apachePreview');
        preview.classList.remove('sev-success', 'sev-warning', 'sev-danger');
        preview.classList.add('sev-' + sev);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var horaInput = document.getElementById('hora_registro');
        if (<?php echo $is_edit ? 'false' : 'true'; ?> && horaInput.value === '') {
            var now = new Date();
            horaInput.value = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
        }
        document.querySelectorAll('.apache-input').forEach(function (el) {
            el.addEventListener('input', calculate);
            el.addEventListener('change', calculate);
        });
        calculate();
    });
})();
</script>
</body>
</html>
