<?php

/**
 * Nursing Nutrition Form - save.php
 * Handles INSERT (create) and UPDATE (edit) for the alimentacion form.
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
use OpenEMR\Modules\Nursing\Scoring\Num;
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

// Whitelists: anything not listed here is stored as ''.
$tipos       = ['ORAL', 'ENTERAL', 'PARENTERAL', 'MIXTA', 'AYUNO'];
$vias        = ['SNG', 'SNY', 'GASTROSTOMIA', 'YEYUNOSTOMIA'];
$modalidades = ['CONTINUA', 'INTERMITENTE', 'BOLO'];
$tolerancias = ['BUENA', 'REGULAR', 'MALA'];
$signos      = ['VOMITOS', 'DISTENSION', 'DIARREA', 'RESIDUO_ALTO'];

$pick = static function (string $field, array $allowed): string {
    $value = (string) filter_input(INPUT_POST, $field);
    return in_array($value, $allowed, true) ? $value : '';
};

// Empty, non-numeric or negative quantities are stored as NULL.
$num = static function (string $field): ?float {
    $value = Num::parse(filter_input(INPUT_POST, $field));
    return ($value !== null && $value >= 0) ? $value : null;
};

$tipo_alimentacion   = $pick('tipo_alimentacion', $tipos);
$via_enteral         = $pick('via_enteral', $vias);
$modalidad           = $pick('modalidad', $modalidades);
$tolerancia          = $pick('tolerancia', $tolerancias);
$formula             = mb_substr(trim((string) filter_input(INPUT_POST, 'formula')), 0, 255);
$volumen_ml          = $num('volumen_ml');
$velocidad_ml_h      = $num('velocidad_ml_h');
$residuo_gastrico_ml = $num('residuo_gastrico_ml');
$observaciones       = (string) filter_input(INPUT_POST, 'observaciones');

$signos_post = filter_input(INPUT_POST, 'signos_intolerancia', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
$signos_post = is_array($signos_post) ? array_map('strval', $signos_post) : [];
// Keep whitelist order so the stored value is stable regardless of POST order.
$signos_intolerancia = implode(',', array_values(array_filter(
    $signos,
    static fn(string $s): bool => in_array($s, $signos_post, true)
)));

// Drop fields that do not apply to the selected feeding type; the form hides
// them, but a value typed before switching type would otherwise be posted.
if ($tipo_alimentacion !== 'ENTERAL' && $tipo_alimentacion !== 'MIXTA') {
    $via_enteral = '';
}
if ($tipo_alimentacion === 'ORAL' || $tipo_alimentacion === 'AYUNO') {
    $modalidad      = '';
    $velocidad_ml_h = null;
}
if ($tipo_alimentacion === 'AYUNO') {
    $volumen_ml = null;
}

$hora_raw      = (string) filter_input(INPUT_POST, 'hora_registro');
$hora_registro = preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $hora_raw) ? $hora_raw : null;

$is_edit = ($id > 0);

if ($is_edit) {
    $check = QueryUtils::querySingleRow("SELECT id FROM form_alimentacion WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
    if (!$check) {
        die(xlt("Error: Record not found or insufficient permissions."));
    }
    QueryUtils::sqlStatementThrowException(
        "UPDATE form_alimentacion SET
            date = NOW(), user = ?, groupname = ?, authorized = ?,
            hora_registro = ?, tipo_alimentacion = ?, via_enteral = ?, modalidad = ?,
            formula = ?, volumen_ml = ?, velocidad_ml_h = ?, residuo_gastrico_ml = ?,
            tolerancia = ?, signos_intolerancia = ?, observaciones = ?
         WHERE id = ? AND pid = ? AND encounter = ?",
        [
            $user, $groupname, $authorized,
            $hora_registro, $tipo_alimentacion, $via_enteral, $modalidad,
            $formula, $volumen_ml, $velocidad_ml_h, $residuo_gastrico_ml,
            $tolerancia, $signos_intolerancia, $observaciones,
            $id, $pid, $encounter,
        ]
    );
} else {
    $newid = QueryUtils::sqlInsert(
        "INSERT INTO form_alimentacion (
            date, pid, encounter, user, groupname, authorized, activity,
            hora_registro, tipo_alimentacion, via_enteral, modalidad,
            formula, volumen_ml, velocidad_ml_h, residuo_gastrico_ml,
            tolerancia, signos_intolerancia, observaciones
         ) VALUES (
            NOW(), ?, ?, ?, ?, ?, 1,
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?
         )",
        [
            $pid, $encounter, $user, $groupname, $authorized,
            $hora_registro, $tipo_alimentacion, $via_enteral, $modalidad,
            $formula, $volumen_ml, $velocidad_ml_h, $residuo_gastrico_ml,
            $tolerancia, $signos_intolerancia, $observaciones,
        ]
    );
    (new FormService())->addForm($encounter, 'Nursing Nutrition', (int)$newid, 'alimentacion', $pid, $authorized);
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
