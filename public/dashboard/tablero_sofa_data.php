<?php

/**
 * SOFA Dashboard - data
 *
 * JSON for tablero_sofa.php. Scores come from the module's SOFA form,
 * only the ones recorded in each admission (inpatient encounter):
 *   action=latest                      latest score of every current admission
 *   action=series&encounter=N&hours=H  scores of one admission in the last H hours
 *                                      (H = 0: the whole admission)
 *
 * The time of a score is the record time entered in the form (hora_registro) on
 * the day it was saved (forms.date), or the time it was saved when the form has
 * no record time.
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

// Moment of the score: the record time entered in the form on the day it was
// saved; a time later than the save (entered before midnight, saved after) is
// from the day before.
$moment = static function (array $r): ?int {
    $saved = strtotime((string) ($r['saved_at'] ?? ''));
    if ($saved === false) {
        return null;
    }
    $time = (string) ($r['hora_registro'] ?? '');
    if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time) !== 1) {
        return $saved;
    }
    $ts = strtotime(date('Y-m-d', $saved) . ' ' . $time);
    if ($ts === false) {
        return $saved;
    }
    return $ts > $saved + 3600 ? $ts - 86400 : $ts;
};

// A form_escala_sofa row for the dashboard.
$score = static function (array $r) use ($moment): array {
    $ts = $moment($r);
    $int = static function (mixed $raw): ?int {
        $n = Num::parse($raw);
        return $n !== null ? (int) $n : null;
    };
    $positive = static function (mixed $raw): ?float {
        $n = Num::parse($raw);
        return ($n !== null && $n > 0) ? $n : null;
    };
    $pafi = $positive($r['pafi'] ?? null);
    return [
        'id' => (int) ($r['id'] ?? 0),
        'ts' => $ts,
        'date' => $ts !== null ? date('Y-m-d\TH:i:s', $ts) : '',
        'when' => $ts !== null ? date('d/m H:i', $ts) : '',
        'hours_ago' => $ts !== null ? round((time() - $ts) / 3600, 1) : null,
        'total' => $int($r['sofa_total'] ?? null),
        'resp' => $int($r['sofa_resp'] ?? null),
        'coag' => $int($r['sofa_coag'] ?? null),
        'liver' => $int($r['sofa_hepatico'] ?? null),
        'cardio' => $int($r['sofa_cardio'] ?? null),
        'cns' => $int($r['sofa_snc'] ?? null),
        'renal' => $int($r['sofa_renal'] ?? null),
        'evaluated' => $int($r['sofa_evaluados'] ?? null),
        'pafi' => $pafi !== null ? round($pafi) : null,
        'creatinine' => $positive($r['creatinina'] ?? null),
        'bilirubin' => $positive($r['bilirrubina'] ?? null),
    ];
};

$scoresSql = "SELECT a.id, a.hora_registro, a.sofa_total, a.sofa_resp, a.sofa_coag, a.sofa_hepatico, a.sofa_cardio,
                     a.sofa_snc, a.sofa_renal, a.sofa_evaluados, a.pafi, a.creatinina, a.bilirrubina, f.date AS saved_at
                FROM form_escala_sofa a
                JOIN forms f ON f.form_id = a.id AND f.formdir = 'escala_sofa' AND f.deleted = 0
               WHERE a.pid = ? AND f.encounter = ?";

// Scores of one admission, oldest first.
$scoresOf = static function (int $pid, int $encounter) use ($scoresSql, $score): array {
    /** @var list<array<string, string|int|null>> $rows */
    $rows = QueryUtils::fetchRecords($scoresSql . " ORDER BY f.date, a.id", [$pid, $encounter]);
    $out = array_values(array_filter(array_map($score, $rows), static fn(array $s): bool => $s['ts'] !== null));
    usort($out, static fn(array $x, array $y): int => [$x['ts'], $x['id']] <=> [$y['ts'], $y['id']]);
    return $out;
};

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
        $scores = $scoresOf((int) $a['pid'], (int) $a['encounter']);
        $n = count($scores);
        // change from the previous score (Sepsis-3: an increase of 2 or more)
        $delta = ($n > 1 && $scores[$n - 1]['total'] !== null && $scores[$n - 2]['total'] !== null)
            ? $scores[$n - 1]['total'] - $scores[$n - 2]['total'] : null;
        $out[] = [
            'pid' => (int) $a['pid'],
            'encounter' => (int) $a['encounter'],
            'patient' => (string) ($a['paciente'] ?? ''),
            'record' => (string) ($a['nro_registro'] ?? ''),
            'service' => (string) ($a['servicio'] ?? ''),
            'room' => strtoupper((string) ($a['cuarto'] ?? '')),
            'bed' => (string) ($a['cama'] ?? ''),
            'last' => $n > 0 ? $scores[$n - 1] : null,
            'delta' => $delta,
            'count' => $n,
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
    $scores = $scoresOf((int) $adm['pid'], $encounter);
    if ($hours > 0) {
        $from = time() - $hours * 3600;
        $scores = array_values(array_filter($scores, static fn(array $s): bool => $s['ts'] >= $from));
    }
    echo json_encode(['scores' => $scores]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => xl('Unknown action')]);
