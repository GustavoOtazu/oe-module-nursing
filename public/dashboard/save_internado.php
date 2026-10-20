<?php

/**
 * Save handler for inpatient admissions: new, edit, and the location movements
 * (transfer, bed reservation, arrival, cancellation and correction).
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    g0tazu
 * @copyright Copyright (c) 2026 g0tazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once(dirname(__DIR__, 5) . "/globals.php");
/** @var string $srcdir */
require_once("$srcdir/forms.inc.php");

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Services\FormService;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Session\SessionWrapperFactory;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\Nursing\AdmissionMovements;

$session = SessionWrapperFactory::getInstance()->getActiveSession();

if (!AclMain::aclCheckCore('patients', 'med')) {
    die(xlt('Access denied'));
}

$csrf_token = (string) filter_input(INPUT_POST, 'csrf_token_form');
if (!CsrfUtils::verifyCsrfToken($csrf_token, session: $session)) {
    CsrfUtils::csrfNotVerified();
}

$mode         = (string) filter_input(INPUT_POST, 'mode');
$pid          = (int)    filter_input(INPUT_POST, 'pid', FILTER_SANITIZE_NUMBER_INT);
$encounter_id = (int)    filter_input(INPUT_POST, 'encounter_id', FILTER_SANITIZE_NUMBER_INT);
$nro_registro = (string) filter_input(INPUT_POST, 'nro_registro');
$departamento = (string) filter_input(INPUT_POST, 'departamento');
$servicio     = (string) filter_input(INPUT_POST, 'servicio');
$cuarto       = (string) filter_input(INPUT_POST, 'cuarto');
$cama         = (string) filter_input(INPUT_POST, 'cama');
$date_raw     = (string) filter_input(INPUT_POST, 'form_date');
$date         = $date_raw !== '' ? $date_raw : date('Y-m-d');

// Sanitize date
$ts_date = strtotime($date);
$date = $ts_date !== false ? date('Y-m-d', $ts_date) : date('Y-m-d');

// Resolve Inpatient category id
/** @var array<string, string|int|null>|false $catRow */
$catRow = QueryUtils::querySingleRow(
    "SELECT pc_catid FROM openemr_postcalendar_categories WHERE pc_catname = 'Inpatient' LIMIT 1"
);
$inpatient_catid = $catRow !== false ? (int)($catRow['pc_catid'] ?? 0) : 0;

if ($mode === 'new') {
    if ($pid <= 0 || $inpatient_catid === 0) {
        die(xlt('Invalid patient or category'));
    }

    // Get facility and provider from session/user
    /** @var array<string, string|int|null>|false $userRow */
    $userRow = QueryUtils::querySingleRow("SELECT facility_id FROM users WHERE username = ?", [is_string($v = $session->get('authUser')) ? $v : '']);
    $facility_id = $userRow !== false ? (int) ($userRow['facility_id'] ?? 0) : 0;
    /** @var array<string, string|int|null>|false $facilityRow */
    $facilityRow = $facility_id ? QueryUtils::querySingleRow("SELECT name FROM facility WHERE id = ?", [$facility_id]) : false;
    $facility = $facilityRow !== false ? (string) ($facilityRow['name'] ?? '') : '';

    $encounter   = QueryUtils::generateId();
    $uuid        = (new UuidRegistry(['table_name' => 'form_encounter']))->createUuid();
    $provider_id = is_numeric($v = $session->get('authUserID')) ? (int) $v : 0;
    $user        = is_string($v = $session->get('authUser')) ? $v : '';
    $group       = is_string($v = $session->get('authProvider')) ? $v : '';

    $fe_id = QueryUtils::sqlInsert(
        "INSERT INTO form_encounter
            (pid, encounter, uuid, date, facility, facility_id, pc_catid, provider_id, billing_facility,
             nro_registro, departamento, servicio, cuarto, cama)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [$pid, $encounter, $uuid, $date . ' 00:00:00', $facility, $facility_id,
         $inpatient_catid, $provider_id, $facility_id,
         $nro_registro, $departamento, $servicio, $cuarto, $cama]
    );

    (new FormService())->addForm($encounter, "New Patient Encounter", (int)$fe_id, "newpatient", $pid, $provider_id);
    AdmissionMovements::sync($inpatient_catid, (int) $fe_id);

    header("Location: lista_internados.php?update=1");
    exit;
}

if ($mode === 'edit') {
    // Only the admission data; the location changes through the movements below.
    $feRow = $encounter_id > 0 ? QueryUtils::querySingleRow("SELECT id FROM form_encounter WHERE id = ?", [$encounter_id]) : false;
    if (!$feRow) {
        die(xlt('Encounter not found'));
    }
    QueryUtils::sqlStatementThrowException(
        "UPDATE form_encounter SET nro_registro = ? WHERE id = ?",
        [$nro_registro, $encounter_id]
    );
    header("Location: lista_internados.php?update=1");
    exit;
}

$movementModes = ['transfer', 'reserve', 'confirm', 'cancel', 'correct'];
if (in_array($mode, $movementModes, true)) {
    /** @var array<string, string|int|null>|false $feRow */
    $feRow = $encounter_id > 0
        ? QueryUtils::querySingleRow("SELECT pid, encounter, date_end FROM form_encounter WHERE id = ?", [$encounter_id])
        : false;
    if (!$feRow || ($feRow['date_end'] ?? null) !== null) {
        die(xlt('Encounter not found'));
    }
    $fePid       = (int) $feRow['pid'];
    $feEncounter = (int) $feRow['encounter'];
    $user        = is_string($v = $session->get('authUser')) ? $v : '';
    $location    = [
        'departamento' => $departamento,
        'servicio'     => $servicio,
        'cuarto'       => $cuarto,
        'cama'         => $cama,
        'equipo'       => (string) filter_input(INPUT_POST, 'equipo'),
    ];
    $note = (string) filter_input(INPUT_POST, 'note');

    // Date and time of the movement (datetime-local input); now if missing.
    $whenRaw = (string) filter_input(INPUT_POST, 'when');
    $whenTs  = $whenRaw !== '' ? strtotime($whenRaw) : time();
    if ($whenTs === false) {
        die(xlt('Invalid date'));
    }
    // Only a reservation can be in the future (a few minutes of clock skew are allowed).
    if ($mode !== 'reserve' && $whenTs > time() + 300) {
        $whenTs = time();
    }
    $when = date('Y-m-d H:i:s', $whenTs);
    $movementId = (int) filter_input(INPUT_POST, 'movement_id', FILTER_SANITIZE_NUMBER_INT);

    AdmissionMovements::sync($inpatient_catid, $encounter_id);
    try {
        match ($mode) {
            'transfer' => AdmissionMovements::transfer($fePid, $feEncounter, $location, $when, $user, $note),
            'reserve'  => AdmissionMovements::reserve($fePid, $feEncounter, $location, $when, $user, $note),
            'confirm'  => AdmissionMovements::confirm($movementId, $feEncounter, $when, $user),
            'cancel'   => AdmissionMovements::cancel($movementId, $feEncounter, $user),
            'correct'  => AdmissionMovements::correct($feEncounter, $location, $user),
        };
        $result = $mode;
    } catch (\InvalidArgumentException) {
        $result = 'error';
    }
    header("Location: editar_internado.php?id=" . urlencode((string) $encounter_id) . "&done=" . urlencode($result));
    exit;
}

die(xlt('Invalid mode'));
