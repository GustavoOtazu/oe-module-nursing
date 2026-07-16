<?php

/**
 * List Vitals for Inpatients
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    OpenEMR Contributors
 * @copyright Copyright (c) 2026 OpenEMR Contributors
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once(dirname(__DIR__, 5) . "/globals.php");

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Database\QueryUtils;

if (!AclMain::aclCheckCore('encounters', 'notes')) {
    die(xlt('Access denied'));
}

/*Extraer todos los internados actuales, tabla: form_encounter, con tipo Internación pc_catid = 16 (referencia tabla: openemr_postcalendar_categories   )*/
$_salas_raw = filter_input(INPUT_GET, 'salas');
$salas = is_string($_salas_raw) ? $_salas_raw : '';
$_camas_raw = filter_input(INPUT_GET, 'camas');
$camas = is_string($_camas_raw) ? $_camas_raw : '';

$query_where = 'WHERE f.pc_catid = 16 AND f.out_date IS NULL';
/** @var list<string> $params */
$params = [];

if ($salas !== '') {
    $salas_arr = explode(',', $salas);
    $placeholders = implode(',', array_fill(0, count($salas_arr), '?'));
    $query_where .= " AND f.cuarto IN ($placeholders)";
    $params = array_merge($params, $salas_arr);
}
if ($camas !== '') {
    $camas_arr = explode(',', $camas);
    $placeholders = implode(',', array_fill(0, count($camas_arr), '?'));
    $query_where .= " AND f.cama IN ($placeholders)";
    $params = array_merge($params, $camas_arr);
}

$internados_actuales_consult = "SELECT f.pid, CONCAT(p.fname, ' ', p.lname) AS paciente, f.cuarto AS sala, f.cama AS cama FROM form_encounter AS f JOIN patient_data AS p ON p.pid = f.pid $query_where ORDER BY sala, f.cama ASC";
/** @var list<array<string, string|int|null>> $rows_internados */
$rows_internados = QueryUtils::fetchRecords($internados_actuales_consult, $params);
$result = [];
foreach ($rows_internados as $row) {
    //encontrar el ultimo form_vitals insertado para este pid y mostrar
    $vital_sql = "SELECT * from form_vitals where  pid = ? order by DATE desc limit 1";
    /** @var array<string, string|int|null>|false $vitals */
    $vitals = QueryUtils::querySingleRow($vital_sql, [(string)($row['pid'] ?? '')]);
    if ($vitals !== false) {
        $ts_vital = strtotime((string)($vitals["date"] ?? ''));
        $result[] = [
            "paciente"        => (string)($row['paciente'] ?? ''),
            "sala"            => strtoupper((string)($row['sala'] ?? '')),
            "cama"            => (string)($row['cama'] ?? ''),
            "bps"             => $vitals["bps"],             //blood pressure systolic
            "bpd"             => $vitals["bpd"],             //blood pressure diastolic
            "temperatura"     => $vitals["temperature"],
            "respiracion"     => $vitals["respiration"],
            "pulse"           => $vitals["pulse"],
            "BMI"             => $vitals["BMI"],             //Índice de masa corporal
            "oxygen_saturation" => $vitals["oxygen_saturation"],
            "date"            => $ts_vital !== false ? date('d/m/Y H:i:s', $ts_vital) : '',
            "pid"             => $vitals["pid"],
            "hr"              => $vitals["hr"],
            "vpc"             => $vitals["vpc"],
            "lvp_s"           => $vitals["lvp_s"],
            "lvp_d"           => $vitals["lvp_d"],
            "pr_spo2"         => $vitals["pr_spo2"],
            "st1"             => $vitals["st1"],
            "st2"             => $vitals["st2"],
            "st3"             => $vitals["st3"],
            "nibps_sys"       => $vitals["nibps_sys"],
            "nibps_dys"       => $vitals["nibps_dys"],
        ];
    }
}
echo json_encode($result);
