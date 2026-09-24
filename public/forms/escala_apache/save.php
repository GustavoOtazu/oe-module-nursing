<?php

/**
 * APACHE II Score Form - save.php
 * Handles INSERT (create) and UPDATE (edit) for the escala_apache form.
 * The score is always calculated here with ApacheIIScore::calculate().
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
use OpenEMR\Modules\Nursing\Scoring\ApacheIIScore;
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

if (!AclMain::aclCheckCore('encounters', 'notes', '', 'write')) {
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

// field => [decimals of the DECIMAL/INT column, min, max]. Values are rounded to the
// column scale before scoring, so the stored raw values reproduce the stored score.
$numeric_fields = [
    'temperatura'             => [1, 20, 45],
    'pam'                     => [1, 0, 300],
    'frecuencia_cardiaca'     => [0, 0, 300],
    'frecuencia_respiratoria' => [0, 0, 100],
    'fio2'                    => [2, 0.21, 100],
    'pao2'                    => [1, 0, 800],
    'paco2'                   => [1, 0, 250],
    'ph'                      => [2, 6.5, 8],
    'bicarbonato'             => [1, 0, 80],
    'sodio'                   => [1, 80, 220],
    'potasio'                 => [1, 0.5, 15],
    'creatinina'              => [2, 0, 40],
    'hematocrito'             => [1, 0, 90],
    'leucocitos'              => [1, 0, 999],
    'glasgow'                 => [0, 3, 15],
    'edad'                    => [0, 0, 130],
];

/** @var array<string, ?float> $values */
$values = [];
foreach ($numeric_fields as $field => [$decimals, $min, $max]) {
    $parsed = Num::parse(filter_input(INPUT_POST, $field));
    if ($parsed !== null) {
        if ($parsed < $min || $parsed > $max) {
            die(xlt('Error: Value out of range') . ': ' . text($field));
        }
        $parsed = round($parsed, $decimals);
    }
    $values[$field] = $parsed;
}

$insuficiencia_renal_aguda = (filter_input(INPUT_POST, 'insuficiencia_renal_aguda') === '1') ? 1 : 0;
$enfermedad_cronica        = (filter_input(INPUT_POST, 'enfermedad_cronica') === '1') ? 1 : 0;

$tipo_raw     = (string) filter_input(INPUT_POST, 'tipo_ingreso');
$tipo_ingreso = in_array($tipo_raw, [
    ApacheIIScore::ADMISSION_NON_OPERATIVE,
    ApacheIIScore::ADMISSION_EMERGENCY_POSTOP,
    ApacheIIScore::ADMISSION_ELECTIVE_POSTOP,
], true) ? $tipo_raw : null;

$hora_raw      = (string) filter_input(INPUT_POST, 'hora_registro');
$hora_registro = (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora_raw) === 1) ? $hora_raw : null;
$observaciones = (string) filter_input(INPUT_POST, 'observaciones');

// Calculate the score server-side
$calc = ApacheIIScore::calculate($values + [
    'insuficiencia_renal_aguda' => $insuficiencia_renal_aguda,
    'enfermedad_cronica'        => $enfermedad_cronica,
    'tipo_ingreso'              => $tipo_ingreso ?? '',
]);

// Column => value. Column names are fixed here, never taken from the request.
$data = [
    'hora_registro'             => $hora_registro,
    'temperatura'               => $values['temperatura'],
    'pam'                       => $values['pam'],
    'frecuencia_cardiaca'       => $values['frecuencia_cardiaca'] !== null ? (int) $values['frecuencia_cardiaca'] : null,
    'frecuencia_respiratoria'   => $values['frecuencia_respiratoria'] !== null ? (int) $values['frecuencia_respiratoria'] : null,
    'fio2'                      => $values['fio2'],
    'pao2'                      => $values['pao2'],
    'paco2'                     => $values['paco2'],
    'ph'                        => $values['ph'],
    'bicarbonato'               => $values['bicarbonato'],
    'sodio'                     => $values['sodio'],
    'potasio'                   => $values['potasio'],
    'creatinina'                => $values['creatinina'],
    'insuficiencia_renal_aguda' => $insuficiencia_renal_aguda,
    'hematocrito'               => $values['hematocrito'],
    'leucocitos'                => $values['leucocitos'],
    'glasgow'                   => $values['glasgow'] !== null ? (int) $values['glasgow'] : null,
    'edad'                      => $values['edad'] !== null ? (int) $values['edad'] : null,
    'enfermedad_cronica'        => $enfermedad_cronica,
    'tipo_ingreso'              => $tipo_ingreso,
    'a_ado2'                    => $calc['a_ado2'],
    'pts_fisiologicos'          => $calc['fisiologicos'],
    'pts_edad'                  => $calc['edad'],
    'pts_cronicos'              => $calc['cronicos'],
    'apache_total'              => $calc['total'],
    'apache_evaluados'          => $calc['evaluados'],
    'observaciones'             => $observaciones,
];
$columns = array_keys($data);

$is_edit = ($id > 0);

if ($is_edit) {
    $check = QueryUtils::querySingleRow("SELECT id FROM form_escala_apache WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
    if (!$check) {
        die(xlt("Error: Record not found or insufficient permissions."));
    }
    $set = implode(', ', array_map(static fn(string $c): string => "`" . $c . "` = ?", $columns));
    QueryUtils::sqlStatementThrowException(
        "UPDATE form_escala_apache SET
            date = NOW(), user = ?, groupname = ?, authorized = ?, " . $set . "
         WHERE id = ? AND pid = ? AND encounter = ?",
        array_merge(
            [$user, $groupname, $authorized],
            array_values($data),
            [$id, $pid, $encounter]
        )
    );
} else {
    $newid = QueryUtils::sqlInsert(
        "INSERT INTO form_escala_apache (
            date, pid, encounter, user, groupname, authorized, activity, `" . implode('`, `', $columns) . "`
         ) VALUES (
            NOW(), ?, ?, ?, ?, ?, 1, " . implode(', ', array_fill(0, count($columns), '?')) . "
         )",
        array_merge(
            [$pid, $encounter, $user, $groupname, $authorized],
            array_values($data)
        )
    );
    (new FormService())->addForm($encounter, 'APACHE II Score', (int)$newid, 'escala_apache', $pid, $authorized);
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
