<?php

/**
 * Nursing Fluid Balance Form - view.php
 * Displays fluid balance records for a patient encounter.
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
    $rows = QueryUtils::fetchRecords("SELECT * FROM form_balance_hidrico WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
} else {
    /** @var list<array<string, string|int|null>> $rows */
    $rows = QueryUtils::fetchRecords("SELECT * FROM form_balance_hidrico WHERE pid = ? AND encounter = ? ORDER BY date DESC", [$pid, $encounter]);
}

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
$shift_labels = [
    'MANANA' => xlt('Morning'),
    'TARDE'  => xlt('Afternoon'),
    'NOCHE'  => xlt('Night'),
];

// "250.00" -> "250", "12.50" -> "12.5"; empty string when there is no value.
$fmtMl = static function (mixed $value): string {
    if ($value === null || $value === '' || !is_numeric($value)) {
        return '';
    }
    $s = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    return ($s === '-0' || $s === '') ? '0' : $s;
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo xlt('Nursing Fluid Balance'); ?></title>
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
        .balance-hidrico-view * { box-sizing: border-box; }
        .balance-hidrico-view .registro-card { border: 1px solid #dee2e6; border-radius: 6px; padding: 20px; margin-bottom: 20px; }
        /* Translucent overlays instead of fixed light colors, so the detail
           panels stay readable under both the light and dark themes. */
        .balance-hidrico-view .fluid-table { width: 100%; margin-bottom: 14px; border-collapse: collapse; }
        .balance-hidrico-view .fluid-table th { padding: 6px 10px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid rgba(128, 128, 128, 0.35); }
        .balance-hidrico-view .fluid-table td { padding: 6px 10px; border-bottom: 1px solid rgba(128, 128, 128, 0.15); }
        .balance-hidrico-view .fluid-table .td-val { text-align: right; width: 30%; }
        .balance-hidrico-view .fluid-table.intake th { background: rgba(23, 162, 184, 0.12); }
        .balance-hidrico-view .fluid-table.output th { background: rgba(253, 126, 20, 0.12); }
        .balance-hidrico-view .fluid-table tr.total-row td { font-weight: bold; background: rgba(128, 128, 128, 0.10); }
        .balance-hidrico-view .field-detail { background: rgba(128, 128, 128, 0.08); border-radius: 4px; padding: 10px 14px; margin-bottom: 10px; border-left: 4px solid #6c757d; }
        .balance-hidrico-view .field-detail.has-obs { border-left-color: #0d6efd; background: rgba(13, 110, 253, 0.10); }
        .balance-hidrico-view .balance-value { font-size: 1.2em; font-weight: bold; }
    </style>
</head>
<body class="body_top">
<div class="balance-hidrico-view container-fluid mt-3">
    <h5 class="border-bottom pb-2">
        <?php echo $id > 0 ? xlt('Fluid Balance Detail') : xlt('Nursing Fluid Balance'); ?>
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
    <div class="alert alert-info"><?php echo xlt('No fluid balance records'); ?></div>
    <?php endif; ?>

    <?php foreach ($rows as $row) :
        $balance = (float)($row['balance'] ?? 0);
        if ($balance > 0) {
            $b_class = 'warning';
            $b_text  = '+' . $fmtMl($balance);
        } elseif ($balance < 0) {
            $b_class = 'info';
            $b_text  = $fmtMl($balance);
        } else {
            $b_class = 'secondary';
            $b_text  = '0';
        }
        $turno       = (string)($row['turno'] ?? '');
        $turno_label = $shift_labels[$turno] ?? '';
        $hora        = substr((string)($row['hora_registro'] ?? ''), 0, 5);
        $obs         = (string)($row['observaciones'] ?? '');
        ?>
    <div class="registro-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <?php $ts_date = strtotime((string)($row['date'] ?? '')); ?>
                <strong><?php echo text(date('d/m/Y H:i', $ts_date !== false ? $ts_date : time())); ?></strong>
                <small class="text-muted ml-2"><?php echo xlt('User'); ?>: <?php echo text((string)($row['user'] ?? '')); ?></small>
            </div>
            <div>
                <a href="<?php echo attr(OEGlobalsBag::getInstance()->getString('webroot') . '/interface/modules/custom_modules/oe-module-nursing/public/forms/balance_hidrico/new.php?pid=' . $pid . '&encounter=' . $encounter . '&id=' . (int)($row['id'] ?? 0)); ?>"
                   class="btn btn-primary mr-1" onclick="top.restoreSession()"><i class="fas fa-pen mr-1"></i><?php echo xlt('Edit'); ?></a>
                <a href="<?php echo attr(OEGlobalsBag::getInstance()->getString('webroot') . '/interface/modules/custom_modules/oe-module-nursing/public/forms/balance_hidrico/print.php?pid=' . $pid . '&encounter=' . $encounter . '&id=' . (int)($row['id'] ?? 0)); ?>"
                   target="_blank" class="btn btn-success" onclick="top.restoreSession()"><i class="fas fa-print mr-1"></i><?php echo xlt('Print'); ?></a>
            </div>
        </div>

        <div class="field-detail">
            <strong><?php echo xlt('Shift'); ?>:</strong> <?php echo text($turno_label !== '' ? $turno_label : '—'); ?>
            &nbsp;|&nbsp;
            <strong><?php echo xlt('Record Time'); ?>:</strong> <?php echo text($hora !== '' ? $hora : '—'); ?>
        </div>

        <div class="row">
            <div class="col-md-6">
                <table class="fluid-table intake">
                    <thead>
                        <tr>
                            <th><?php echo xlt('Intake'); ?></th>
                            <th class="td-val">mL</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($intake_labels as $field => $label) :
                        $val = $fmtMl($row[$field] ?? null);
                        ?>
                        <tr>
                            <td><?php echo text($label); ?></td>
                            <td class="td-val"><?php echo text($val !== '' ? $val : '—'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                        <tr class="total-row">
                            <td><?php echo xlt('Total intake'); ?></td>
                            <td class="td-val"><?php echo text(($fmtMl($row['total_ingresos'] ?? null) ?: '0') . ' mL'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <table class="fluid-table output">
                    <thead>
                        <tr>
                            <th><?php echo xlt('Output'); ?></th>
                            <th class="td-val">mL</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($output_labels as $field => $label) :
                        $val = $fmtMl($row[$field] ?? null);
                        ?>
                        <tr>
                            <td><?php echo text($label); ?></td>
                            <td class="td-val"><?php echo text($val !== '' ? $val : '—'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                        <tr class="total-row">
                            <td><?php echo xlt('Total output'); ?></td>
                            <td class="td-val"><?php echo text(($fmtMl($row['total_egresos'] ?? null) ?: '0') . ' mL'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="alert alert-<?php echo attr($b_class); ?> py-2">
            <strong><?php echo xlt('Fluid balance'); ?>:</strong>
            <span class="balance-value"><?php echo text($b_text); ?> mL</span>
        </div>

        <div class="field-detail <?php echo ($obs !== '') ? 'has-obs' : ''; ?>">
            <strong><?php echo xlt('Observations'); ?>:</strong>
            <?php if ($obs !== '') : ?>
            <div class="mt-1 small"><?php echo nl2br(text($obs)); ?></div>
            <?php else : ?>
            <span class="text-muted"><?php echo xlt('No observations recorded'); ?></span>
            <?php endif; ?>
        </div>
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
