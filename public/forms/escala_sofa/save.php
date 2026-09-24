<?php

/**
 * SOFA Score Form - save.php
 * Handles INSERT (create) and UPDATE (edit) for the escala_sofa form.
 * The subscores and the total are always calculated here with SofaScore.
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
use OpenEMR\Modules\Nursing\Scoring\SofaScore;
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

// Numeric inputs: null when empty or not numeric. The limits only guard the
// column sizes and obvious typos; clinical thresholds live in SofaScore.
$limits = [
    'pao2'          => [0.0, 999.9],
    'fio2'          => [0.0, 100.0],
    'plaquetas'     => [0.0, 9999.0],
    'bilirrubina'   => [0.0, 99.99],
    'pam'           => [0.0, 300.0],
    'dopamina'      => [0.0, 100.0],
    'epinefrina'    => [0.0, 99.999],
    'norepinefrina' => [0.0, 99.999],
    'glasgow'       => [3.0, 15.0],
    'creatinina'    => [0.0, 99.99],
    'diuresis_24h'  => [0.0, 99999.0],
];
$values = [];
foreach ($limits as $field => [$min, $max]) {
    $num = Num::parse(filter_input(INPUT_POST, $field));
    if ($num !== null && ($num < $min || $num > $max)) {
        die(xlt('Error: Value out of range') . ': ' . text($field));
    }
    $values[$field] = $num;
}

// FiO2 may be a fraction (0.21-1.0) or a percentage (21-100); anything else is a typo.
$fio2_raw = $values['fio2'];
if ($fio2_raw !== null && !(($fio2_raw >= 0.21 && $fio2_raw <= 1.0) || ($fio2_raw >= 21.0 && $fio2_raw <= 100.0))) {
    die(xlt('Error: Value out of range') . ': fio2');
}
if ($values['glasgow'] !== null && floor($values['glasgow']) !== $values['glasgow']) {
    die(xlt('Error: Value out of range') . ': glasgow');
}

$soporte_respiratorio = (filter_input(INPUT_POST, 'soporte_respiratorio') === '1') ? 1 : 0;
$dobutamina           = (filter_input(INPUT_POST, 'dobutamina') === '1') ? 1 : 0;
$observaciones        = (string) filter_input(INPUT_POST, 'observaciones');
$hora_raw             = (string) filter_input(INPUT_POST, 'hora_registro');
$hora_registro        = preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $hora_raw) === 1 ? $hora_raw : null;

$sofa = SofaScore::calculate($values + [
    'soporte_respiratorio' => $soporte_respiratorio === 1,
    'dobutamina'           => $dobutamina === 1,
]);

// FiO2 is stored as a fraction so every view shows it the same way.
$fio2    = SofaScore::normalizeFio2($values['fio2']);
$glasgow = $values['glasgow'] !== null ? (int) $values['glasgow'] : null;

$data = [
    $hora_registro,
    $values['pao2'], $fio2, $soporte_respiratorio,
    $values['plaquetas'], $values['bilirrubina'],
    $values['pam'], $values['dopamina'], $dobutamina, $values['epinefrina'], $values['norepinefrina'],
    $glasgow, $values['creatinina'], $values['diuresis_24h'],
    $sofa['pafi'],
    $sofa['resp'], $sofa['coag'], $sofa['hepatico'], $sofa['cardio'], $sofa['snc'], $sofa['renal'],
    $sofa['total'], $sofa['evaluados'],
    $observaciones,
];

$is_edit = ($id > 0);

if ($is_edit) {
    $check = QueryUtils::querySingleRow("SELECT id FROM form_escala_sofa WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
    if (!$check) {
        die(xlt("Error: Record not found or insufficient permissions."));
    }
    QueryUtils::sqlStatementThrowException(
        "UPDATE form_escala_sofa SET
            date = NOW(), user = ?, groupname = ?, authorized = ?,
            hora_registro = ?,
            pao2 = ?, fio2 = ?, soporte_respiratorio = ?,
            plaquetas = ?, bilirrubina = ?,
            pam = ?, dopamina = ?, dobutamina = ?, epinefrina = ?, norepinefrina = ?,
            glasgow = ?, creatinina = ?, diuresis_24h = ?,
            pafi = ?,
            sofa_resp = ?, sofa_coag = ?, sofa_hepatico = ?, sofa_cardio = ?, sofa_snc = ?, sofa_renal = ?,
            sofa_total = ?, sofa_evaluados = ?,
            observaciones = ?
         WHERE id = ? AND pid = ? AND encounter = ?",
        array_merge([$user, $groupname, $authorized], $data, [$id, $pid, $encounter])
    );
} else {
    $newid = QueryUtils::sqlInsert(
        "INSERT INTO form_escala_sofa (
            date, pid, encounter, user, groupname, authorized, activity,
            hora_registro,
            pao2, fio2, soporte_respiratorio,
            plaquetas, bilirrubina,
            pam, dopamina, dobutamina, epinefrina, norepinefrina,
            glasgow, creatinina, diuresis_24h,
            pafi,
            sofa_resp, sofa_coag, sofa_hepatico, sofa_cardio, sofa_snc, sofa_renal,
            sofa_total, sofa_evaluados,
            observaciones
         ) VALUES (
            NOW(), ?, ?, ?, ?, ?, 1,
            ?,
            ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?,
            ?
         )",
        array_merge([$pid, $encounter, $user, $groupname, $authorized], $data)
    );
    (new FormService())->addForm($encounter, 'SOFA Score', (int)$newid, 'escala_sofa', $pid, $authorized);
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
