<?php

/**
 * APACHE II Dashboard - data
 *
 * JSON for tablero_apache.php. Scores come from the module's APACHE II form,
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
use OpenEMR\Modules\Nursing\Scoring\SofaScore;

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

// A form_escala_apache row for the dashboard.
$score = static function (array $r) use ($moment): array {
    $ts = $moment($r);
    $int = static function (mixed $raw): ?int {
        $n = Num::parse($raw);
        return $n !== null ? (int) $n : null;
    };
    $pafi = SofaScore::pafi(Num::parse($r['pao2'] ?? null), Num::parse($r['fio2'] ?? null));
    return [
        'id' => (int) ($r['id'] ?? 0),
        'ts' => $ts,
        'date' => $ts !== null ? date('Y-m-d\TH:i:s', $ts) : '',
        'when' => $ts !== null ? date('d/m H:i', $ts) : '',
        'hours_ago' => $ts !== null ? round((time() - $ts) / 3600, 1) : null,
        'total' => $int($r['apache_total'] ?? null),
        'physiology' => $int($r['pts_fisiologicos'] ?? null),
        'age' => $int($r['pts_edad'] ?? null),
        'chronic' => $int($r['pts_cronicos'] ?? null),
        'evaluated' => $int($r['apache_evaluados'] ?? null),
        'glasgow' => $int($r['glasgow'] ?? null),
        'pafi' => $pafi !== null ? round($pafi) : null,
    ];
};

$scoresSql = "SELECT a.id, a.hora_registro, a.apache_total, a.pts_fisiologicos, a.pts_edad, a.pts_cronicos,
                     a.apache_evaluados, a.glasgow, a.pao2, a.fio2, f.date AS saved_at
                FROM form_escala_apache a
                JOIN forms f ON f.form_id = a.id AND f.formdir = 'escala_apache' AND f.deleted = 0
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
        $out[] = [
            'pid' => (int) $a['pid'],
            'encounter' => (int) $a['encounter'],
            'patient' => (string) ($a['paciente'] ?? ''),
            'record' => (string) ($a['nro_registro'] ?? ''),
            'service' => (string) ($a['servicio'] ?? ''),
            'room' => strtoupper((string) ($a['cuarto'] ?? '')),
            'bed' => (string) ($a['cama'] ?? ''),
            'last' => $scores !== [] ? $scores[count($scores) - 1] : null,
            'count' => count($scores),
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
