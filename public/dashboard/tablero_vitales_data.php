<?php

/**
 * Vital Signs Dashboard - data
 *
 * JSON for tablero_vitales.php. Vital signs come from OpenEMR's own vitals form,
 * only the takes recorded in each admission (inpatient encounter):
 *   action=latest                      latest take of every current admission
 *   action=series&encounter=N&hours=H  takes of one admission in the last H hours
 *                                      (H = 0: the whole admission)
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once(dirname(__DIR__, 5) . "/globals.php");

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Modules\Nursing\Scoring\Num;
use OpenEMR\Modules\Nursing\Scoring\WorstValue;

header('Content-Type: application/json; charset=utf-8');

if (!AclMain::aclCheckCore('encounters', 'notes')) {
    http_response_code(403);
    echo json_encode(['error' => xl('Access denied')]);
    exit;
}

/** @var array<string, string|int|null>|false $catRow */
$catRow = QueryUtils::querySingleRow(
    "SELECT pc_catid FROM openemr_postcalendar_categories WHERE pc_catname = ? LIMIT 1",
    ['Inpatient']
);
$inpatientCatid = $catRow !== false ? (int) ($catRow['pc_catid'] ?? 0) : 0;

// A form_vitals row in the units shown on the dashboard (zeros are empty fields).
$vital = static function (array $r): array {
    $positive = static function (mixed $raw): ?float {
        $n = Num::parse($raw);
        return ($n !== null && $n > 0) ? $n : null;
    };
    $ts = strtotime((string) ($r['date'] ?? ''));
    $map = WorstValue::meanArterialPressure(Num::parse($r['bps'] ?? null), Num::parse($r['bpd'] ?? null));
    $method = trim((string) ($r['temp_method'] ?? ''));
    return [
        'id' => (int) ($r['id'] ?? 0),
        'date' => $ts !== false ? date('Y-m-d\TH:i:s', $ts) : '',
        'when' => $ts !== false ? date('d/m H:i', $ts) : '',
        'hours_ago' => $ts !== false ? round((time() - $ts) / 3600, 1) : null,
        'bps' => $positive($r['bps'] ?? null),
        'bpd' => $positive($r['bpd'] ?? null),
        'map' => $map !== null ? round($map, 1) : null,
        'pulse' => $positive($r['pulse'] ?? null),
        'respiration' => $positive($r['respiration'] ?? null),
        'temp_c' => WorstValue::fahrenheitToCelsius(Num::parse($r['temperature'] ?? null)),
        'temp_method' => $method !== '' ? xl($method) : null,
        'spo2' => $positive($r['oxygen_saturation'] ?? null),
    ];
};

$vitalsSql = "SELECT v.id, v.date, v.bps, v.bpd, v.pulse, v.respiration, v.temperature, v.temp_method, v.oxygen_saturation
                FROM form_vitals v
                JOIN forms f ON f.form_id = v.id AND f.formdir = 'vitals' AND f.deleted = 0
               WHERE v.pid = ? AND f.encounter = ?";

$action = (string) filter_input(INPUT_GET, 'action');

if ($action === 'latest') {
    /** @var list<array<string, string|int|null>> $admissions */
    $admissions = QueryUtils::fetchRecords(
        "SELECT f.pid, f.encounter, f.nro_registro, f.servicio, f.cuarto, f.cama, f.date,
                CONCAT(p.fname, ' ', p.lname) AS paciente
           FROM form_encounter AS f
           JOIN patient_data AS p ON p.pid = f.pid
          WHERE f.pc_catid = ? AND f.date_end IS NULL
          ORDER BY f.cuarto, CAST(f.cama AS UNSIGNED), f.cama",
        [$inpatientCatid]
    );
    $out = [];
    foreach ($admissions as $a) {
        /** @var array<string, string|int|null>|false $last */
        $last = QueryUtils::querySingleRow(
            $vitalsSql . " ORDER BY v.date DESC, v.id DESC LIMIT 1",
            [(int) $a['pid'], (int) $a['encounter']]
        );
        $out[] = [
            'pid' => (int) $a['pid'],
            'encounter' => (int) $a['encounter'],
            'patient' => (string) ($a['paciente'] ?? ''),
            'record' => (string) ($a['nro_registro'] ?? ''),
            'service' => (string) ($a['servicio'] ?? ''),
            'room' => strtoupper((string) ($a['cuarto'] ?? '')),
            'bed' => (string) ($a['cama'] ?? ''),
            'last' => $last !== false ? $vital($last) : null,
        ];
    }
    echo json_encode(['admissions' => $out, 'now' => date('H:i')]);
    exit;
}

if ($action === 'series') {
    $encounter = (int) filter_input(INPUT_GET, 'encounter', FILTER_SANITIZE_NUMBER_INT);
    $hours = (int) filter_input(INPUT_GET, 'hours', FILTER_SANITIZE_NUMBER_INT);
    /** @var array<string, string|int|null>|false $adm */
    $adm = QueryUtils::querySingleRow(
        "SELECT pid FROM form_encounter WHERE encounter = ? AND pc_catid = ? LIMIT 1",
        [$encounter, $inpatientCatid]
    );
    if ($adm === false) {
        http_response_code(404);
        echo json_encode(['error' => xl('Admission not found')]);
        exit;
    }
    $sql = $vitalsSql;
    $params = [(int) $adm['pid'], $encounter];
    if ($hours > 0) {
        $sql .= " AND v.date >= ?";
        $params[] = date('Y-m-d H:i:s', time() - $hours * 3600);
    }
    /** @var list<array<string, string|int|null>> $rows */
    $rows = QueryUtils::fetchRecords($sql . " ORDER BY v.date, v.id", $params);
    echo json_encode(['takes' => array_map($vital, $rows)]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => xl('Unknown action')]);
