<?php

/**
 * Nursing Applications Form - new.php
 * Application record form for inpatients (medications, saline, vaccines, etc.)
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

$session = SessionWrapperFactory::getInstance()->getActiveSession();

// Get parameters
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

// A signed record can no longer be edited: show the read-only view instead.
if ($is_edit && \OpenEMR\Modules\Nursing\FormLock::isLocked('aplicaciones', $id, $encounter)) {
    require __DIR__ . '/view.php';
    return;
}

// Initialize field variables
$medicamentos     = 0;
$obs_medicamentos = '';
$sueros           = 0;
$obs_sueros       = '';
$vacunas          = 0;
$obs_vacunas      = '';
$expansiones      = 0;
$obs_expansiones  = '';
$sangre           = 0;
$obs_sangre       = '';
$hora_registro    = '';

// Load existing data in edit mode
if ($is_edit) {
    /** @var array<string, string|int|null>|false $row */
    $row = QueryUtils::querySingleRow("SELECT * FROM form_aplicaciones WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
    if ($row !== false) {
        $medicamentos     = (int)($row['medicamentos']  ?? 0);
        $obs_medicamentos = (string)($row['obs_medicamentos'] ?? '');
        $sueros           = (int)($row['sueros']        ?? 0);
        $obs_sueros       = (string)($row['obs_sueros'] ?? '');
        $vacunas          = (int)($row['vacunas']       ?? 0);
        $obs_vacunas      = (string)($row['obs_vacunas'] ?? '');
        $expansiones      = (int)($row['expansiones']   ?? 0);
        $obs_expansiones  = (string)($row['obs_expansiones'] ?? '');
        $sangre           = (int)($row['sangre']        ?? 0);
        $obs_sangre       = (string)($row['obs_sangre'] ?? '');
        $hora_registro    = (string)($row['hora_registro'] ?? '');
    } else {
        die(xlt("Error: Record not found or insufficient permissions."));
    }
}

$page_title = $is_edit ? xlt('Edit Application') : xlt('New Application');
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
        .nurs-section {
            border-left: 4px solid #3498db;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body class="body_top">
<div class="container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-12">
            <h4>
                <?php echo text($page_title); ?>
                <span class="badge badge-<?php echo $is_edit ? 'warning' : 'success'; ?> ml-2">
                    <?php echo $is_edit ? xlt('Edit Mode') : xlt('Create Mode'); ?>
                </span>
            </h4>
            <small class="text-muted"><?php echo xlt('Encounter'); ?>: <?php echo text((string)$encounter); ?></small>
        </div>
    </div>

    <form method="POST" action="<?php echo attr($save_url); ?>" id="formAplicaciones" onsubmit="top.restoreSession();">
        <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken(session: $session)); ?>">
        <input type="hidden" name="pid"       value="<?php echo attr((string)$pid); ?>">
        <input type="hidden" name="encounter" value="<?php echo attr((string)$encounter); ?>">
        <input type="hidden" name="from"      value="<?php echo attr($from); ?>">
        <?php if ($is_edit) : ?>
        <input type="hidden" name="id" value="<?php echo attr((string)$id); ?>">
        <?php endif; ?>

        <?php
        $fields = [
            'medicamentos' => ['label' => xlt('Medications'),            'val' => $medicamentos,  'obs' => $obs_medicamentos],
            'sueros'       => ['label' => xlt('Saline Solutions'),        'val' => $sueros,        'obs' => $obs_sueros],
            'vacunas'      => ['label' => xlt('Vaccines'),                'val' => $vacunas,       'obs' => $obs_vacunas],
            'expansiones'  => ['label' => xlt('Plasma Expanders'),        'val' => $expansiones,   'obs' => $obs_expansiones],
            'sangre'       => ['label' => xlt('Blood and Blood Products'), 'val' => $sangre,        'obs' => $obs_sangre],
        ];
        foreach ($fields as $name => $meta) : ?>
        <div class="nurs-section">
            <h6 class="font-weight-bold"><?php echo text($meta['label']); ?></h6>
            <div class="mb-2">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio"
                           name="<?php echo attr($name); ?>"
                           id="<?php echo attr($name); ?>_1" value="1"
                           <?php echo ($meta['val'] == 1) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="<?php echo attr($name); ?>_1">
                        <?php echo xlt('Yes'); ?>
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio"
                           name="<?php echo attr($name); ?>"
                           id="<?php echo attr($name); ?>_0" value="0"
                           <?php echo ($meta['val'] == 0) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="<?php echo attr($name); ?>_0">
                        <?php echo xlt('No'); ?>
                    </label>
                </div>
            </div>
            <textarea name="<?php echo attr('obs_' . $name); ?>" class="form-control" rows="2"
                      placeholder="<?php echo attr(xlt('Observations...')); ?>"><?php echo text((string)$meta['obs']); ?></textarea>
        </div>
        <?php endforeach; ?>

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
});
</script>
</body>
</html>
