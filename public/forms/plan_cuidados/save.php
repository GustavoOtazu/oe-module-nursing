<?php

/**
 * Nursing Care Plan Form - save.php
 * Handles INSERT (create) and UPDATE (edit) for the plan_cuidados form.
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
require_once("$srcdir/forms.inc.php");

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Session\SessionWrapperFactory;
use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Services\FormService;

$session = SessionWrapperFactory::getInstance()->getActiveSession();

if (filter_input(INPUT_SERVER, 'REQUEST_METHOD') !== 'POST') {
    die(xlt("Method not allowed"));
}

$csrf_token = (string) filter_input(INPUT_POST, 'csrf_token_form');
if (!CsrfUtils::verifyCsrfToken($csrf_token, session: $session)) {
    CsrfUtils::csrfNotVerified();
}

if (!AclMain::aclCheckCore('encounters', 'notes')) {
    die(xlt('Access denied'));
}

$pid       = (int) filter_input(INPUT_POST, 'pid', FILTER_SANITIZE_NUMBER_INT);
$encounter = (int) filter_input(INPUT_POST, 'encounter', FILTER_SANITIZE_NUMBER_INT);
$id        = (int) filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$pid || !$encounter) {
    die(xlt("Error: Missing required data (PID or Encounter)"));
}

$user       = is_string($v = $session->get('authUser')) ? $v : die(xlt('Access denied'));
$groupname  = is_string($v = $session->get('authProvider')) ? $v : die(xlt('Access denied'));
$authorized = is_numeric($v = $session->get('userauthorized')) ? (int) $v : 0;

// Whitelisted enumerations: anything else is stored as ''.
$allowed_turno      = ['MANANA', 'TARDE', 'NOCHE'];
$allowed_evaluacion = ['LOGRADO', 'PARCIAL', 'NO_LOGRADO', 'EN_CURSO'];

$turno = (string) filter_input(INPUT_POST, 'turno');
if (!in_array($turno, $allowed_turno, true)) {
    $turno = '';
}
$evaluacion_resultado = (string) filter_input(INPUT_POST, 'evaluacion_resultado');
if (!in_array($evaluacion_resultado, $allowed_evaluacion, true)) {
    $evaluacion_resultado = '';
}

$hora_raw      = trim((string) filter_input(INPUT_POST, 'hora_registro'));
$hora_registro = (preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $hora_raw) === 1) ? $hora_raw : null;

$diagnostico_enfermeria = trim((string) filter_input(INPUT_POST, 'diagnostico_enfermeria'));
$codigo_diagnostico     = mb_substr(trim((string) filter_input(INPUT_POST, 'codigo_diagnostico')), 0, 40);
$objetivo               = trim((string) filter_input(INPUT_POST, 'objetivo'));
$intervenciones         = trim((string) filter_input(INPUT_POST, 'intervenciones'));
$evolucion              = trim((string) filter_input(INPUT_POST, 'evolucion'));
$observaciones          = trim((string) filter_input(INPUT_POST, 'observaciones'));

// Same rule as the client-side check in new.php.
if ($diagnostico_enfermeria === '' && $evolucion === '') {
    die(xlt('Enter at least a nursing diagnosis or a shift progress note.'));
}

$is_edit = ($id > 0);

if ($is_edit) {
    $check = QueryUtils::querySingleRow("SELECT id FROM form_plan_cuidados WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
    if (!$check) {
        die(xlt("Error: Record not found or insufficient permissions."));
    }
    QueryUtils::sqlStatementThrowException(
        "UPDATE form_plan_cuidados SET
            date = NOW(), user = ?, groupname = ?, authorized = ?,
            turno = ?, hora_registro = ?,
            diagnostico_enfermeria = ?, codigo_diagnostico = ?,
            objetivo = ?, intervenciones = ?,
            evaluacion_resultado = ?, evolucion = ?,
            observaciones = ?
         WHERE id = ? AND pid = ? AND encounter = ?",
        [
            $user, $groupname, $authorized,
            $turno, $hora_registro,
            $diagnostico_enfermeria, $codigo_diagnostico,
            $objetivo, $intervenciones,
            $evaluacion_resultado, $evolucion,
            $observaciones,
            $id, $pid, $encounter,
        ]
    );
} else {
    $newid = QueryUtils::sqlInsert(
        "INSERT INTO form_plan_cuidados (
            date, pid, encounter, user, groupname, authorized, activity,
            turno, hora_registro,
            diagnostico_enfermeria, codigo_diagnostico,
            objetivo, intervenciones,
            evaluacion_resultado, evolucion,
            observaciones
         ) VALUES (
            NOW(), ?, ?, ?, ?, ?, 1,
            ?, ?, ?, ?, ?, ?, ?, ?, ?
         )",
        [
            $pid, $encounter, $user, $groupname, $authorized,
            $turno, $hora_registro,
            $diagnostico_enfermeria, $codigo_diagnostico,
            $objetivo, $intervenciones,
            $evaluacion_resultado, $evolucion,
            $observaciones,
        ]
    );
    (new FormService())->addForm($encounter, 'Nursing Care Plan', (int)$newid, 'plan_cuidados', $pid, $authorized);
}

$from = (filter_input(INPUT_POST, 'from') === 'list') ? 'list' : 'encounter';
formHeader(xlt("Redirecting..."));
if ($from === 'list') {
    formJump(OEGlobalsBag::getInstance()->getString('webroot')
        . "/interface/modules/custom_modules/oe-module-nursing/public/dashboard/lista_internados.php");
} else {
    // Encounter forms open in a frame tab: closing it returns to the
    // encounter's form list, the same thing core encounter forms do.
    formJump();
}
formFooter();
