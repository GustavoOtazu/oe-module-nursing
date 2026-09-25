<?php

/**
 * Nursing Nutrition Form - view.php
 * Displays nutrition records for a patient encounter.
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
    $rows = QueryUtils::fetchRecords("SELECT * FROM form_alimentacion WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
} else {
    /** @var list<array<string, string|int|null>> $rows */
    $rows = QueryUtils::fetchRecords("SELECT * FROM form_alimentacion WHERE pid = ? AND encounter = ? ORDER BY date DESC", [$pid, $encounter]);
}

// Label maps hold xlt() output, which is already HTML-escaped.
$tipo_labels = [
    'ORAL'       => xlt('Oral'),
    'ENTERAL'    => xlt('Enteral'),
    'PARENTERAL' => xlt('Parenteral'),
    'MIXTA'      => xlt('Mixed feeding'),
    'AYUNO'      => xlt('Fasting'),
];
$via_labels = [
    'SNG'          => xlt('Nasogastric tube'),
    'SNY'          => xlt('Nasojejunal tube'),
    'GASTROSTOMIA' => xlt('Gastrostomy'),
    'YEYUNOSTOMIA' => xlt('Jejunostomy'),
];
$modalidad_labels = [
    'CONTINUA'     => xlt('Continuous'),
    'INTERMITENTE' => xlt('Intermittent'),
    'BOLO'         => xlt('Bolus'),
];
$tolerancia_labels = [
    'BUENA'   => xlt('Good'),
    'REGULAR' => xlt('Fair'),
    'MALA'    => xlt('Poor'),
];
$signos_labels = [
    'VOMITOS'      => xlt('Vomiting'),
    'DISTENSION'   => xlt('Abdominal distension'),
    'DIARREA'      => xlt('Diarrhea'),
    'RESIDUO_ALTO' => xlt('High gastric residual'),
];

// Returns safe HTML: the translated label, or the escaped raw code, or a dash.
$label = static function (array $map, string $code): string {
    if ($code === '') {
        return '—';
    }
    return $map[$code] ?? text($code);
};
// Returns safe HTML for a decimal with its unit, or a dash when empty.
$qty = static function (mixed $value, string $unit): string {
    if ($value === null || $value === '') {
        return '—';
    }
    $s = (string) $value;
    $s = str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    return text($s . ' ' . $unit);
};

$webroot = OEGlobalsBag::getInstance()->getString('webroot');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo xlt('Nursing Nutrition'); ?></title>
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
        .alimentacion-view * { box-sizing: border-box; }
        .alimentacion-view .registro-card { border: 1px solid #dee2e6; border-radius: 6px; padding: 20px; margin-bottom: 20px; }
        /* Translucent overlays instead of fixed light colors, so the detail
           panels stay readable under both the light and dark themes. */
        .alimentacion-view .field-detail { background: rgba(128, 128, 128, 0.08); border-radius: 4px; padding: 10px 14px; margin-bottom: 10px; border-left: 4px solid #6c757d; }
        .alimentacion-view .field-detail.has-obs { border-left-color: #0d6efd; background: rgba(13, 110, 253, 0.10); }
        .alimentacion-view .field-detail.has-alert { border-left-color: #e67e22; background: rgba(230, 126, 34, 0.10); }
    </style>
</head>
<body class="body_top">
<div class="alimentacion-view container-fluid mt-3">
    <h5 class="border-bottom pb-2">
        <?php echo $id > 0 ? xlt('Nutrition Detail') : xlt('Nursing Nutrition'); ?>
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
    <div class="alert alert-info"><?php echo xlt('No nutrition records'); ?></div>
    <?php endif; ?>

    <?php foreach ($rows as $row) :
        $row_id = (int)($row['id'] ?? 0);
        $tipo   = (string)($row['tipo_alimentacion'] ?? '');
        $usa_via      = ($tipo === 'ENTERAL' || $tipo === 'MIXTA');
        $sin_infusion = ($tipo === 'ORAL' || $tipo === 'AYUNO');

        $signos_raw = (string)($row['signos_intolerancia'] ?? '');
        $signos_html = [];
        foreach (($signos_raw !== '') ? explode(',', $signos_raw) : [] as $code) {
            $signos_html[] = $label($signos_labels, $code);
        }
        $obs = (string)($row['observaciones'] ?? '');

        // [label, safe HTML value, highlight class]
        $fields = [];
        $fields[] = [xlt('Feeding Type'), $label($tipo_labels, $tipo), ''];
        if ($usa_via) {
            $fields[] = [xlt('Enteral Route'), $label($via_labels, (string)($row['via_enteral'] ?? '')), ''];
        }
        if (!$sin_infusion) {
            $fields[] = [xlt('Modality'), $label($modalidad_labels, (string)($row['modalidad'] ?? '')), ''];
        }
        $formula = (string)($row['formula'] ?? '');
        $fields[] = [xlt('Formula / Diet'), ($formula !== '') ? text($formula) : '—', ''];
        if ($tipo !== 'AYUNO') {
            $fields[] = [xlt('Volume'), $qty($row['volumen_ml'] ?? null, 'mL'), ''];
        }
        if (!$sin_infusion) {
            $fields[] = [xlt('Infusion Rate'), $qty($row['velocidad_ml_h'] ?? null, 'mL/h'), ''];
        }
        $fields[] = [xlt('Gastric Residual'), $qty($row['residuo_gastrico_ml'] ?? null, 'mL'), ''];
        $fields[] = [xlt('Tolerance'), $label($tolerancia_labels, (string)($row['tolerancia'] ?? '')), ''];
        $fields[] = [
            xlt('Signs of Intolerance'),
            ($signos_html !== []) ? implode(', ', $signos_html) : xlt('None'),
            ($signos_html !== []) ? 'has-alert' : '',
        ];
        ?>
    <div class="registro-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <?php $ts_date = strtotime((string)($row['date'] ?? '')); ?>
                <strong><?php echo text(date('d/m/Y H:i', $ts_date !== false ? $ts_date : time())); ?></strong>
                <small class="text-muted ml-2"><?php echo xlt('User'); ?>: <?php echo text((string)($row['user'] ?? '')); ?></small>
            </div>
            <div>
                <a href="<?php echo attr($webroot . '/interface/modules/custom_modules/oe-module-nursing/public/forms/alimentacion/new.php?pid=' . $pid . '&encounter=' . $encounter . '&id=' . $row_id); ?>"
                   class="btn btn-primary mr-1" onclick="top.restoreSession()"><i class="fas fa-pen mr-1"></i><?php echo xlt('Edit'); ?></a>
                <a href="<?php echo attr($webroot . '/interface/modules/custom_modules/oe-module-nursing/public/forms/alimentacion/print.php?pid=' . $pid . '&encounter=' . $encounter . '&id=' . $row_id); ?>"
                   target="_blank" class="btn btn-success" onclick="top.restoreSession()"><i class="fas fa-print mr-1"></i><?php echo xlt('Print'); ?></a>
            </div>
        </div>

        <?php foreach ($fields as [$f_label, $f_value, $f_class]) : ?>
        <div class="field-detail <?php echo attr($f_class); ?>">
            <strong><?php echo $f_label; ?>:</strong> <?php echo $f_value; ?>
        </div>
        <?php endforeach; ?>

        <div class="field-detail <?php echo ($obs !== '') ? 'has-obs' : ''; ?>">
            <strong><?php echo xlt('Observations'); ?>:</strong>
            <?php if ($obs !== '') : ?>
            <div class="mt-1 small text-muted"><?php echo nl2br(text($obs)); ?></div>
            <?php else : ?>
            <span class="text-muted"><?php echo xlt('No observations recorded'); ?></span>
            <?php endif; ?>
        </div>

        <?php $hora_row = (string)($row['hora_registro'] ?? ''); ?>
        <?php if ($hora_row !== '') : ?>
        <small class="text-muted"><?php echo xlt('Record Time'); ?>: <?php echo text(substr($hora_row, 0, 5)); ?></small>
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
