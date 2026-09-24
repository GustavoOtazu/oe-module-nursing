<?php

/**
 * Nursing Fluid Balance Form - save.php
 * Handles INSERT (create) and UPDATE (edit) for the balance_hidrico form.
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
use OpenEMR\Modules\Nursing\Scoring\FluidBalance;
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

$turno_raw = (string) filter_input(INPUT_POST, 'turno');
$turno     = in_array($turno_raw, ['MANANA', 'TARDE', 'NOCHE'], true) ? $turno_raw : null;

$hora_raw      = trim((string) filter_input(INPUT_POST, 'hora_registro'));
$hora_registro = preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $hora_raw) === 1 ? $hora_raw : null;

$observaciones = (string) filter_input(INPUT_POST, 'observaciones');

// Volumes in mL: empty/non-numeric -> NULL. Negative values and values that do
// not fit decimal(8,2) are also stored as NULL so the saved fields always match
// the totals FluidBalance computes from them.
$fields = array_merge(FluidBalance::INTAKE_FIELDS, FluidBalance::OUTPUT_FIELDS);
/** @var array<string, float|null> $volumes */
$volumes = [];
foreach ($fields as $field) {
    $parsed = Num::parse(filter_input(INPUT_POST, $field));
    $volumes[$field] = ($parsed !== null && $parsed >= 0 && $parsed < 1000000) ? round($parsed, 2) : null;
}

// Totals are always computed server-side; client-side values are display only.
$totals = FluidBalance::calculate($volumes);

$volume_params = array_values($volumes);
$total_params  = [$totals['total_ingresos'], $totals['total_egresos'], $totals['balance']];

$is_edit = ($id > 0);

if ($is_edit) {
    $check = QueryUtils::querySingleRow("SELECT id FROM form_balance_hidrico WHERE id = ? AND pid = ? AND encounter = ? LIMIT 1", [$id, $pid, $encounter]);
    if (!$check) {
        die(xlt("Error: Record not found or insufficient permissions."));
    }
    QueryUtils::sqlStatementThrowException(
        "UPDATE form_balance_hidrico SET
            date = NOW(), user = ?, groupname = ?, authorized = ?,
            turno = ?, hora_registro = ?,
            ing_via_oral = ?, ing_enteral = ?, ing_parenteral = ?, ing_sueros = ?,
            ing_medicacion = ?, ing_hemoderivados = ?, ing_otros = ?,
            eg_diuresis = ?, eg_drenajes = ?, eg_sng = ?, eg_vomitos = ?,
            eg_deposiciones = ?, eg_perdidas_insensibles = ?, eg_otros = ?,
            total_ingresos = ?, total_egresos = ?, balance = ?,
            observaciones = ?
         WHERE id = ? AND pid = ? AND encounter = ?",
        array_merge(
            [$user, $groupname, $authorized, $turno, $hora_registro],
            $volume_params,
            $total_params,
            [$observaciones, $id, $pid, $encounter]
        )
    );
} else {
    $newid = QueryUtils::sqlInsert(
        "INSERT INTO form_balance_hidrico (
            date, pid, encounter, user, groupname, authorized, activity,
            turno, hora_registro,
            ing_via_oral, ing_enteral, ing_parenteral, ing_sueros,
            ing_medicacion, ing_hemoderivados, ing_otros,
            eg_diuresis, eg_drenajes, eg_sng, eg_vomitos,
            eg_deposiciones, eg_perdidas_insensibles, eg_otros,
            total_ingresos, total_egresos, balance,
            observaciones
         ) VALUES (
            NOW(), ?, ?, ?, ?, ?, 1,
            ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?
         )",
        array_merge(
            [$pid, $encounter, $user, $groupname, $authorized, $turno, $hora_registro],
            $volume_params,
            $total_params,
            [$observaciones]
        )
    );
    (new FormService())->addForm($encounter, 'Nursing Fluid Balance', (int)$newid, 'balance_hidrico', $pid, $authorized);
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
