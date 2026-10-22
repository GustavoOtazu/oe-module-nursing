<?php

/**
 * Inpatient Admission Form — new and edit mode.
 *
 * In edit mode the location (department, service, ward, bed, primary team) is
 * not overwritten: it changes through movements (transfer, bed reservation,
 * arrival, correction) and the page shows the admission's location history.
 *
 * Usage:
 *   New admission : editar_internado.php?pid=X
 *   Edit admission: editar_internado.php?id=X  (form_encounter.id)
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    g0tazu
 * @copyright Copyright (c) 2026 g0tazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once(dirname(__DIR__, 5) . "/globals.php");
/** @var string $srcdir */
/** @var string $web_root */

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Session\SessionWrapperFactory;
use OpenEMR\Core\Header;
use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Modules\Nursing\AdmissionMovements;
use OpenEMR\OeUI\OemrUI;

$session = SessionWrapperFactory::getInstance()->getActiveSession();

if (!AclMain::aclCheckCore('patients', 'med')) {
    die(xlt('Access denied'));
}

// Determine mode
$encounter_id = is_numeric($v = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT)) ? (int) $v : 0;
$pid_new      = is_numeric($v = filter_input(INPUT_GET, 'pid', FILTER_SANITIZE_NUMBER_INT)) ? (int) $v : 0;
$done         = (string) filter_input(INPUT_GET, 'done');
$viewmode     = ($encounter_id > 0);

// Data containers
$encounter_row = false;
$patient       = false;
$current       = null;
$reservations  = [];
$history       = [];
$discharged    = false;

if ($viewmode) {
    // Record first any location change made outside the module (e.g. by the SIH).
    $catRow = QueryUtils::querySingleRow(
        "SELECT pc_catid FROM openemr_postcalendar_categories WHERE pc_catname = 'Inpatient' LIMIT 1"
    );
    AdmissionMovements::sync(is_array($catRow) ? (int) ($catRow['pc_catid'] ?? 0) : 0, $encounter_id);

    /** @var array<string, string|int|null>|false $encounter_row */
    $encounter_row = QueryUtils::querySingleRow("SELECT * FROM form_encounter WHERE id = ?", [$encounter_id]);
    if (!$encounter_row) {
        die(xlt('Encounter not found'));
    }
    /** @var array<string, string|int|null>|false $patient */
    $patient  = QueryUtils::querySingleRow("SELECT * FROM patient_data WHERE pid = ?", [$encounter_row['pid']]);
    $pid_form = (int) ($encounter_row['pid'] ?? 0);

    $enc          = (int) ($encounter_row['encounter'] ?? 0);
    $current      = AdmissionMovements::current($enc);
    $reservations = AdmissionMovements::reservations($enc);
    $history      = AdmissionMovements::history($enc);
    $discharged   = ($encounter_row['date_end'] ?? null) !== null;
} else {
    if ($pid_new <= 0) {
        die(xlt('Missing patient ID'));
    }
    /** @var array<string, string|int|null>|false $patient */
    $patient  = QueryUtils::querySingleRow("SELECT * FROM patient_data WHERE pid = ?", [$pid_new]);
    $pid_form = $pid_new;
}

$departamentos = [
    'terapia_adulto' => xl('Adult Therapy'),
    'terapia_kids'   => xl('Pediatric Therapy'),
];
$servicios = [
    'intensivo'  => xl('Intensive'),
    'intermedia' => xl('Intermediate'),
    'minima'     => xl('Minimal'),
];
$cuartos = ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D', 'e' => 'E'];

$statusLabels = [
    AdmissionMovements::CURRENT   => xl('Current'),
    AdmissionMovements::RESERVED  => xl('Reserved'),
    AdmissionMovements::ENDED     => xl('Ended'),
    AdmissionMovements::CANCELLED => xl('Cancelled'),
];
$sourceLabels = [
    AdmissionMovements::SOURCE_ADMISSION => xl('Admission'),
    AdmissionMovements::SOURCE_MANUAL    => xl('Recorded in the module'),
    AdmissionMovements::SOURCE_DETECTED  => xl('Detected (SIH or other system)'),
    AdmissionMovements::SOURCE_SIH       => xl('SIH web service'),
];
$doneMessages = [
    'transfer' => ['success', xl('Transfer recorded.')],
    'reserve'  => ['success', xl('Bed reserved.')],
    'confirm'  => ['success', xl('Arrival at the reserved bed recorded.')],
    'cancel'   => ['success', xl('Reservation cancelled.')],
    'correct'  => ['success', xl('Current location corrected.')],
    'same'     => ['warning', xl('The patient is already in that location with that team. Nothing was recorded.')],
    'error'    => ['danger', xl('The movement could not be recorded. Check the date and time: it cannot be earlier than the start of the current location.')],
];

/** A stored value shown with its label, or as stored if it is not one of the options. */
$label = static function (array $options, mixed $value): string {
    $value = is_scalar($value) ? (string) $value : '';
    return $options[$value] ?? strtoupper($value);
};
$fmt = static function (mixed $date): string {
    $ts = is_string($date) && $date !== '' ? strtotime($date) : false;
    return $ts !== false ? date('d/m/Y H:i', $ts) : '';
};
$saveUrl = $web_root . '/interface/modules/custom_modules/oe-module-nursing/public/dashboard/save_internado.php';
$csrf    = CsrfUtils::collectCsrfToken(session: $session);

$heading_caption = $viewmode ? xl('Edit Admission') : xl('New Admission');

$arrOeUiSettings = [
    'heading_title'        => $heading_caption,
    'include_patient_name' => false,
    'expandable'           => false,
    'expandable_files'     => [],
    'action'               => '',
    'action_title'         => '',
    'action_href'          => '',
    'show_help_icon'       => false,
    'help_file_name'       => '',
];
$oemr_ui = new OemrUI($arrOeUiSettings);

/**
 * The department, service, ward and bed selects (and the team), preselected
 * with $values.
 *
 * @param array<string, mixed> $values
 */
$locationFields = static function (array $values, bool $withTeam) use ($departamentos, $servicios, $cuartos): void {
    $sel = static fn(string $col, string $val): string => ((string) ($values[$col] ?? '')) === $val ? 'selected' : '';
    ?>
    <div class="form-group row mb-3">
        <label class="col-sm-3 col-form-label"><?php echo xlt('Department'); ?></label>
        <div class="col-sm-4">
            <select name="departamento" class="form-control">
                <?php foreach ($departamentos as $val => $text) : ?>
                <option value="<?php echo attr($val); ?>" <?php echo $sel('departamento', $val); ?>><?php echo text($text); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form-group row mb-3">
        <label class="col-sm-3 col-form-label"><?php echo xlt('Service'); ?></label>
        <div class="col-sm-4">
            <select name="servicio" class="form-control">
                <?php foreach ($servicios as $val => $text) : ?>
                <option value="<?php echo attr($val); ?>" <?php echo $sel('servicio', $val); ?>><?php echo text($text); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form-group row mb-3">
        <label class="col-sm-3 col-form-label"><?php echo xlt('Ward'); ?></label>
        <div class="col-sm-4">
            <select name="cuarto" class="form-control">
                <?php foreach ($cuartos as $val => $text) : ?>
                <option value="<?php echo attr($val); ?>" <?php echo $sel('cuarto', $val); ?>><?php echo text($text); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form-group row mb-3">
        <label class="col-sm-3 col-form-label"><?php echo xlt('Bed'); ?></label>
        <div class="col-sm-4">
            <select name="cama" class="form-control">
                <?php for ($i = 1; $i <= 10; $i++) : ?>
                <option value="<?php echo attr((string) $i); ?>" <?php echo $sel('cama', (string) $i); ?>><?php echo xlt('Bed') . ' ' . text((string) $i); ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>
    <?php if ($withTeam) : ?>
    <div class="form-group row mb-3">
        <label class="col-sm-3 col-form-label"><?php echo xlt('Primary team'); ?></label>
        <div class="col-sm-6">
            <input type="text" name="equipo" class="form-control" maxlength="100"
                   placeholder="<?php echo xla('e.g. Internal Medicine, General Surgery'); ?>"
                   value="<?php echo attr((string) ($values['equipo'] ?? '')); ?>">
        </div>
    </div>
    <?php endif; ?>
    <?php
};
?>
<!DOCTYPE html>
<html>
<head>
    <?php Header::setupHeader(['datetime-picker', 'jquery-ui', 'jquery-ui-darkness', 'fontawesome']); ?>
    <title><?php echo xlt('Inpatient Admission'); ?></title>
    <style>
        .mov-current { border: 1px solid rgba(128, 128, 128, 0.35); border-left: 4px solid #2196F3; border-radius: 6px; padding: 10px 14px; }
        .mov-current dl { display: grid; grid-template-columns: max-content 1fr; gap: 2px 16px; margin: 0; }
        .mov-current dt { font-weight: 600; }
        .mov-current dd { margin: 0; }
        .mov-history { font-size: 13px; }
        .mov-history th { background: rgba(128, 128, 128, 0.30); white-space: nowrap; }
        .mov-history td { vertical-align: top; }
        .mov-history tr.mov-current-row td { font-weight: 600; }
        .mov-history tr.mov-cancelled td { opacity: 0.6; }
        .mov-history small { display: block; opacity: 0.8; font-weight: normal; }
    </style>
    <script>
        function cancelClicked() {
            top.RTop.location = <?php echo js_escape($web_root . '/interface/modules/custom_modules/oe-module-nursing/public/dashboard/lista_internados.php'); ?>;
            return false;
        }
    </script>
</head>
<body class="body_top">
<div id="container_div" class="<?php echo attr($oemr_ui->oeContainer()); ?>">
    <div class="row">
        <div class="col-sm-12">
            <div class="page-header clearfix">
                <h2>
                    <?php echo $viewmode ? xlt('Edit Admission') : xlt('New Admission'); ?>
                    — <?php echo text(($patient !== false ? (string)($patient['fname'] ?? '') : '') . ' ' . ($patient !== false ? (string)($patient['lname'] ?? '') : '')); ?>
                </h2>
            </div>
        </div>
    </div>

    <?php if (isset($doneMessages[$done])) : ?>
    <div class="alert alert-<?php echo attr($doneMessages[$done][0]); ?> alert-dismissible show" role="alert">
        <?php echo text($doneMessages[$done][1]); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="<?php echo xla('Close'); ?>"><span aria-hidden="true">&times;</span></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-sm-12">
            <form method="post" action="<?php echo attr($saveUrl); ?>">
                <input type="hidden" name="csrf_token_form" value="<?php echo attr($csrf); ?>">
                <input type="hidden" name="mode"  value="<?php echo $viewmode ? 'edit' : 'new'; ?>">
                <input type="hidden" name="pid"   value="<?php echo attr((string)$pid_form); ?>">
                <?php if ($viewmode) : ?>
                <input type="hidden" name="encounter_id" value="<?php echo attr((string)$encounter_id); ?>">
                <?php endif; ?>

                <fieldset class="p-3 mt-2">
                    <legend class="px-2"><?php echo xlt('Admission Details'); ?></legend>

                    <?php if (!$viewmode) : ?>
                    <div class="form-group row mb-3">
                        <label class="col-sm-3 col-form-label"><?php echo xlt('Date of Admission'); ?></label>
                        <div class="col-sm-4">
                            <input type="text" name="form_date" class="form-control datepicker"
                                   value="<?php echo attr(date('Y-m-d')); ?>">
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group row mb-3">
                        <label class="col-sm-3 col-form-label"><?php echo xlt('Reg. No.'); ?></label>
                        <div class="col-sm-4">
                            <input type="text" name="nro_registro" class="form-control"
                                   value="<?php echo attr($encounter_row !== false ? (string)($encounter_row['nro_registro'] ?? '') : ''); ?>">
                        </div>
                    </div>

                    <?php if (!$viewmode) {
                        $locationFields([], false);
                    } ?>
                </fieldset>

                <div class="form-group row mt-2 px-3">
                    <div class="col-sm-12">
                        <button type="submit" class="btn btn-primary" onclick="top.restoreSession()">
                            <i class="fa fa-check mr-1"></i><?php echo $viewmode ? xlt('Save') : xlt('Admit Patient'); ?>
                        </button>
                        <button type="button" class="btn btn-outline-secondary ml-2" onclick="return cancelClicked()">
                            <i class="fa fa-times mr-1"></i><?php echo $viewmode ? xlt('Back') : xlt('Cancel'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($viewmode) : ?>
    <div class="row">
        <div class="col-sm-12">
            <fieldset class="p-3 mt-3">
                <legend class="px-2"><?php echo xlt('Current location'); ?></legend>
                <?php if ($current !== null) : ?>
                <div class="mov-current mb-3">
                    <dl>
                        <dt><?php echo xlt('Department'); ?></dt><dd><?php echo text($label($departamentos, $current['departamento'])); ?></dd>
                        <dt><?php echo xlt('Service'); ?></dt><dd><?php echo text($label($servicios, $current['servicio'])); ?></dd>
                        <dt><?php echo xlt('Ward / Bed'); ?></dt><dd><?php echo text($label($cuartos, $current['cuarto']) . ' / ' . (string) ($current['cama'] ?? '')); ?></dd>
                        <dt><?php echo xlt('Primary team'); ?></dt><dd><?php echo text((string) ($current['equipo'] ?? '') !== '' ? (string) $current['equipo'] : xl('Not recorded')); ?></dd>
                        <dt><?php echo xlt('Since'); ?></dt><dd><?php echo text($fmt($current['start_date'])); ?></dd>
                    </dl>
                </div>
                <?php else : ?>
                <p class="text-muted"><?php echo xlt('No current location.'); ?></p>
                <?php endif; ?>

                <?php if (!$discharged) : ?>
                <div class="d-flex flex-wrap mb-3" style="gap:6px;">
                    <button type="button" class="btn btn-primary btn-mov" data-mode="transfer"><i class="fa fa-exchange-alt mr-1"></i><?php echo xlt('Transfer patient'); ?></button>
                    <button type="button" class="btn btn-outline-primary btn-mov" data-mode="reserve"><i class="fa fa-bed mr-1"></i><?php echo xlt('Reserve bed'); ?></button>
                    <button type="button" class="btn btn-outline-secondary btn-mov" data-mode="correct"><i class="fa fa-pencil-alt mr-1"></i><?php echo xlt('Correct current location'); ?></button>
                </div>

                <form method="post" action="<?php echo attr($saveUrl); ?>" id="form-mov" class="border rounded p-3 mb-3" style="display:none;">
                    <input type="hidden" name="csrf_token_form" value="<?php echo attr($csrf); ?>">
                    <input type="hidden" name="encounter_id" value="<?php echo attr((string) $encounter_id); ?>">
                    <input type="hidden" name="mode" id="mov-mode" value="transfer">
                    <h5 id="mov-title" class="mb-2"></h5>
                    <p id="mov-help" class="text-muted small mb-3"></p>
                    <?php $locationFields($current ?? [], true); ?>
                    <div class="form-group row mb-3 mov-when">
                        <label class="col-sm-3 col-form-label" id="mov-when-label"><?php echo xlt('Date and time'); ?></label>
                        <div class="col-sm-4">
                            <input type="datetime-local" name="when" class="form-control" value="<?php echo attr(date('Y-m-d\TH:i')); ?>">
                        </div>
                    </div>
                    <div class="form-group row mb-3 mov-note">
                        <label class="col-sm-3 col-form-label"><?php echo xlt('Note'); ?></label>
                        <div class="col-sm-6">
                            <input type="text" name="note" class="form-control" maxlength="255"
                                   placeholder="<?php echo xla('e.g. reason for the transfer'); ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" onclick="top.restoreSession()"><i class="fa fa-check mr-1"></i><?php echo xlt('Save'); ?></button>
                    <button type="button" class="btn btn-outline-secondary ml-2" id="mov-close"><?php echo xlt('Cancel'); ?></button>
                </form>
                <?php endif; ?>

                <?php if ($reservations !== []) : ?>
                <h5 class="mt-2"><?php echo xlt('Reserved beds'); ?></h5>
                <?php foreach ($reservations as $res) : ?>
                <div class="d-flex flex-wrap align-items-center border rounded p-2 mb-2" style="gap:8px;">
                    <span>
                        <strong><?php echo text(xl('Ward') . ' ' . $label($cuartos, $res['cuarto']) . ' / ' . xl('Bed') . ' ' . (string) ($res['cama'] ?? '')); ?></strong>
                        — <?php echo text($label($departamentos, $res['departamento']) . ', ' . $label($servicios, $res['servicio'])); ?>
                        <?php if ((string) ($res['equipo'] ?? '') !== '') : ?>— <?php echo text((string) $res['equipo']); ?><?php endif; ?>
                        <small class="d-block text-muted"><?php echo text(xl('Expected arrival') . ': ' . $fmt($res['start_date']) . ' · ' . xl('Reserved by') . ' ' . (string) ($res['user'] ?? '')); ?></small>
                    </span>
                    <form method="post" action="<?php echo attr($saveUrl); ?>" class="ml-auto d-flex flex-wrap" style="gap:6px;">
                        <input type="hidden" name="csrf_token_form" value="<?php echo attr($csrf); ?>">
                        <input type="hidden" name="encounter_id" value="<?php echo attr((string) $encounter_id); ?>">
                        <input type="hidden" name="movement_id" value="<?php echo attr((string) $res['id']); ?>">
                        <input type="datetime-local" name="when" class="form-control form-control-sm" style="width:auto;"
                               value="<?php echo attr(date('Y-m-d\TH:i')); ?>" title="<?php echo xla('Arrival'); ?>">
                        <button type="submit" name="mode" value="confirm" class="btn btn-success btn-sm" onclick="top.restoreSession()"><i class="fa fa-check mr-1"></i><?php echo xlt('Patient arrived'); ?></button>
                        <button type="submit" name="mode" value="cancel" class="btn btn-outline-danger btn-sm" onclick="top.restoreSession()"><i class="fa fa-times mr-1"></i><?php echo xlt('Cancel reservation'); ?></button>
                    </form>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </fieldset>

            <fieldset class="p-3 mt-2">
                <legend class="px-2"><?php echo xlt('Location history'); ?></legend>
                <?php if ($history === []) : ?>
                <p class="text-muted"><?php echo xlt('No movements recorded.'); ?></p>
                <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mov-history">
                        <thead>
                            <tr>
                                <th><?php echo xlt('Location start'); ?></th>
                                <th><?php echo xlt('Location end'); ?></th>
                                <th><?php echo xlt('Department'); ?></th>
                                <th><?php echo xlt('Service'); ?></th>
                                <th><?php echo xlt('Ward / Bed'); ?></th>
                                <th><?php echo xlt('Primary team'); ?></th>
                                <th><?php echo xlt('Status'); ?></th>
                                <th><?php echo xlt('Recorded by'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $m) : ?>
                                <?php $st = (string) $m['status']; ?>
                            <tr class="<?php echo $st === AdmissionMovements::CURRENT ? 'mov-current-row' : ($st === AdmissionMovements::CANCELLED ? 'mov-cancelled' : ''); ?>">
                                <td><?php echo text($fmt($m['start_date'])); ?></td>
                                <td><?php echo text($st === AdmissionMovements::CURRENT ? xl('Now') : $fmt($m['end_date'])); ?></td>
                                <td><?php echo text($label($departamentos, $m['departamento'])); ?></td>
                                <td><?php echo text($label($servicios, $m['servicio'])); ?></td>
                                <td><?php echo text($label($cuartos, $m['cuarto']) . ' / ' . (string) ($m['cama'] ?? '')); ?></td>
                                <td><?php echo text((string) ($m['equipo'] ?? '')); ?></td>
                                <td><?php echo text($statusLabels[$st] ?? $st); ?></td>
                                <td>
                                    <?php echo text((string) ($m['user'] ?? '') !== '' ? (string) $m['user'] : '—'); ?>
                                    <small><?php echo text($sourceLabels[(string) $m['source']] ?? (string) $m['source']); ?></small>
                                    <?php if ((string) ($m['arrived_by'] ?? '') !== '') : ?>
                                    <small><?php echo text(xl('Arrival recorded by') . ' ' . (string) $m['arrived_by']); ?></small>
                                    <?php endif; ?>
                                    <?php if ((string) ($m['corrected_by'] ?? '') !== '') : ?>
                                    <small><?php echo text(($st === AdmissionMovements::CANCELLED ? xl('Cancelled by') : xl('Corrected by')) . ' ' . (string) $m['corrected_by'] . ', ' . $fmt($m['corrected_at'])); ?></small>
                                    <?php endif; ?>
                                    <?php if ((string) ($m['note'] ?? '') !== '') : ?>
                                    <small><em><?php echo text((string) $m['note']); ?></em></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </fieldset>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php $oemr_ui->oeBelowContainerDiv(); ?>
<script>
    $('.datepicker').datetimepicker({
        <?php
        $datetimepicker_timepicker = false;
        $datetimepicker_showseconds = false;
        $datetimepicker_formatInput = true;
        require(OEGlobalsBag::getInstance()->getString('srcdir') . '/js/xl/jquery-datetimepicker-2-5-4.js.php');
        ?>
    });

    // One form for the three movements; the button sets what it records.
    var movTexts = <?php echo json_encode([
        'transfer' => [xl('Transfer patient'), xl('The current location ends and the new one starts at the date and time below. Both stay in the history.'), xl('Date and time of the transfer')],
        'reserve'  => [xl('Reserve bed'), xl('The bed is assigned but the patient has not arrived yet. The current location does not change until the arrival is recorded.'), xl('Expected arrival')],
        'correct'  => [xl('Correct current location'), xl('Only to fix a data entry error. It does not record a transfer; the history keeps who corrected it and when.'), ''],
    ]); ?>;
    $('.btn-mov').on('click', function () {
        var mode = $(this).data('mode');
        $('#mov-mode').val(mode);
        $('#mov-title').text(movTexts[mode][0]);
        $('#mov-help').text(movTexts[mode][1]);
        $('#mov-when-label').text(movTexts[mode][2]);
        $('.mov-when').toggle(mode !== 'correct');
        $('.mov-note').toggle(mode !== 'correct');
        $('#form-mov').show()[0].scrollIntoView({behavior: 'smooth', block: 'nearest'});
    });
    $('#mov-close').on('click', function () {
        $('#form-mov').hide();
    });
</script>
</body>
</html>
