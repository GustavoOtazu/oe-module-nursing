<?php

/**
 * Prefill values for the ICU forms from data already recorded for the patient,
 * so nurses validate values instead of typing them again.
 *
 * - APACHE II: worst value of each variable in the first 24 h of the admission.
 * - SOFA: worst value of each system in the last 24 h; urine output is the sum
 *   of the fluid balances of the last 24 h; respiratory support is checked when
 *   a mechanical ventilation record exists in that window.
 * - Fluid balance: oral, enteral and parenteral intake from the nutrition
 *   records entered since the previous fluid balance.
 *
 * Every value carries a note with its source and time, and the nurse can
 * change it before saving. Only records not deleted from the encounter are used.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing\Prefill;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Modules\Nursing\Scoring\ApacheIIScore;
use OpenEMR\Modules\Nursing\Scoring\Num;
use OpenEMR\Modules\Nursing\Scoring\SofaScore;
use OpenEMR\Modules\Nursing\Scoring\WorstValue;

final class ClinicalPrefill
{
    /** Vital signs found in the window of the last forApache()/forSofa() call. */
    private int $vitalsFound = 0;

    /** @var list<array{id: int, when: string, bps: ?float, bpd: ?float, map: ?float, temp_c: ?float, pulse: ?float, respiration: ?float, points: int, worst: bool, latest: bool}> */
    private array $takes = [];

    public function __construct(
        private readonly int $pid,
        private readonly int $encounter
    ) {
    }

    /**
     * Number of vital sign records the last forApache() or forSofa() call found
     * in its window, so the form can point out that there were none to use.
     */
    public function vitalsCount(): int
    {
        return $this->vitalsFound;
    }

    /**
     * Vital sign takes of the window of the last forApache() or forSofa() call,
     * most recent first, with their points in that scale. The worst one fills the
     * fields that come from vital signs; the form lets the nurse pick another one
     * and saves the id of the chosen take with the score.
     *
     * @return list<array{id: int, when: string, bps: ?float, bpd: ?float, map: ?float, temp_c: ?float, pulse: ?float, respiration: ?float, points: int, worst: bool, latest: bool}>
     */
    public function vitalsTakes(): array
    {
        return $this->takes;
    }

    /**
     * @return array<string, array{value: string, note: string, options?: list<array{value: string, when: string, source: string, points: int, proposed: bool, latest: bool}>}>
     */
    public function forApache(): array
    {
        [$from, $to] = $this->admissionWindow();
        $label = xl('Worst value of the first 24 h of the admission');
        $vitals = $this->vitals($from, $to);
        $this->vitalsFound = count($vitals);
        // Vital signs are chosen as a whole take (see vitalsTakes()); the other
        // sources are only used for a variable when no take has it.
        $this->takes = $this->takes($vitals, static function (array $v): ?int {
            $parts = array_filter([
                ApacheIIScore::temperature($v['temp_c']),
                ApacheIIScore::meanArterialPressure($v['map']),
                ApacheIIScore::heartRate($v['pulse']),
                ApacheIIScore::respiratoryRate($v['respiration']),
            ], static fn(?int $p): bool => $p !== null);
            return $parts === [] ? null : array_sum($parts);
        });
        $vitalsForChoice = $this->takes === [] ? $vitals : [];
        $apache = $this->moduleRows('form_escala_apache', 'escala_apache', $from, $to);
        $sofa = $this->moduleRows('form_escala_sofa', 'escala_sofa', $from, $to);
        $out = [];

        $this->choose($out, 'temperatura', $label,
            array_merge($this->series($vitalsForChoice, 'temp_c', xl('vital signs')), $this->series($apache, 'temperatura', xl('APACHE II'))),
            static fn(array $c): ?int => ApacheIIScore::temperature($c['value'])
        );
        $this->choose($out, 'pam', $label,
            array_merge($this->series($vitalsForChoice, 'map', xl('vital signs')), $this->series($apache, 'pam', xl('APACHE II')), $this->series($sofa, 'pam', xl('SOFA'))),
            static fn(array $c): ?int => ApacheIIScore::meanArterialPressure($c['value'])
        );
        $this->choose($out, 'frecuencia_cardiaca', $label,
            array_merge($this->series($vitalsForChoice, 'pulse', xl('vital signs')), $this->series($apache, 'frecuencia_cardiaca', xl('APACHE II'))),
            static fn(array $c): ?int => ApacheIIScore::heartRate($c['value'])
        );
        $this->choose($out, 'frecuencia_respiratoria', $label,
            array_merge(
                $this->series($vitalsForChoice, 'respiration', xl('vital signs')),
                $this->series($this->ventilationRows($from, $to), 'frecuencia_respiratoria', xl('ventilation record')),
                $this->series($apache, 'frecuencia_respiratoria', xl('APACHE II'))
            ),
            static fn(array $c): ?int => ApacheIIScore::respiratoryRate($c['value'])
        );

        // FiO2, PaO2 and PaCO2 come from the same blood gas, so they are chosen together.
        $gases = [];
        foreach ([[$apache, xl('APACHE II')], [$sofa, xl('SOFA')]] as [$rows, $source]) {
            foreach ($rows as $r) {
                $fio2 = SofaScore::normalizeFio2(Num::parse($r['fio2'] ?? null));
                $pao2 = Num::parse($r['pao2'] ?? null);
                if ($fio2 === null || $pao2 === null || $pao2 <= 0) {
                    continue;
                }
                $gases[] = ['fio2' => $fio2, 'pao2' => $pao2, 'paco2' => Num::parse($r['paco2'] ?? null), 'time' => (string) $r['date'], 'source' => $source];
            }
        }
        $gas = WorstValue::pick($gases, static fn(array $c): ?int => ApacheIIScore::oxygenation(
            $c['fio2'],
            $c['pao2'],
            ApacheIIScore::aaGradient($c['fio2'], $c['pao2'], $c['paco2'])
        ));
        if ($gas !== null) {
            $note = $this->note($label, $gas['source'], $gas['time']);
            $out['fio2'] = ['value' => $this->fmt($gas['fio2']), 'note' => $note];
            $out['pao2'] = ['value' => $this->fmt($gas['pao2']), 'note' => $note];
            if ($gas['paco2'] !== null) {
                $out['paco2'] = ['value' => $this->fmt($gas['paco2']), 'note' => $note];
            }
        }

        $ph = $this->choose($out, 'ph', $label, $this->series($apache, 'ph', xl('APACHE II')), static fn(array $c): ?int => ApacheIIScore::arterialPh($c['value']));
        if ($ph === null) {
            $this->choose($out, 'bicarbonato', $label,
                $this->series($apache, 'bicarbonato', xl('APACHE II')),
                static fn(array $c): ?int => ApacheIIScore::bicarbonate($c['value'])
            );
        }
        $this->choose($out, 'sodio', $label, $this->series($apache, 'sodio', xl('APACHE II')), static fn(array $c): ?int => ApacheIIScore::sodium($c['value']));
        $this->choose($out, 'potasio', $label, $this->series($apache, 'potasio', xl('APACHE II')), static fn(array $c): ?int => ApacheIIScore::potassium($c['value']));
        $this->choose($out, 'creatinina', $label,
            array_merge($this->series($apache, 'creatinina', xl('APACHE II')), $this->series($sofa, 'creatinina', xl('SOFA'))),
            static fn(array $c): ?int => ApacheIIScore::creatinine($c['value'], false)
        );
        $this->choose($out, 'hematocrito', $label, $this->series($apache, 'hematocrito', xl('APACHE II')), static fn(array $c): ?int => ApacheIIScore::hematocrit($c['value']));
        $this->choose($out, 'leucocitos', $label, $this->series($apache, 'leucocitos', xl('APACHE II')), static fn(array $c): ?int => ApacheIIScore::whiteBloodCells($c['value']));
        $this->choose($out, 'glasgow', $label,
            $this->glasgowSeries($from, $to, $apache, $sofa),
            static fn(array $c): ?int => ApacheIIScore::glasgow($c['value'])
        );

        $this->applyWorstTake($out, $label, ['temp_c' => 'temperatura', 'map' => 'pam', 'pulse' => 'frecuencia_cardiaca', 'respiration' => 'frecuencia_respiratoria']);

        return $out;
    }

    /**
     * @return array<string, array{value: string, note: string, options?: list<array{value: string, when: string, source: string, points: int, proposed: bool, latest: bool}>}>
     */
    public function forSofa(): array
    {
        [$from, $to] = $this->lastDayWindow();
        $label = xl('Worst value of the last 24 h');
        $vitals = $this->vitals($from, $to);
        $this->vitalsFound = count($vitals);
        // Vital signs are chosen as a whole take (see vitalsTakes()); SOFA only
        // uses their mean arterial pressure.
        $this->takes = $this->takes($vitals, static fn(array $v): ?int => SofaScore::cardiovascular($v['map'], null, false, null, null));
        $vitalsForChoice = $this->takes === [] ? $vitals : [];
        $apache = $this->moduleRows('form_escala_apache', 'escala_apache', $from, $to);
        $sofa = $this->moduleRows('form_escala_sofa', 'escala_sofa', $from, $to);
        $ventilation = $this->ventilationRows($from, $to);
        $out = [];

        // PaO2 and FiO2 are chosen together (lowest PaO2/FiO2 ratio).
        $gases = [];
        foreach ([[$sofa, xl('SOFA')], [$apache, xl('APACHE II')]] as [$rows, $source]) {
            foreach ($rows as $r) {
                $pafi = SofaScore::pafi(Num::parse($r['pao2'] ?? null), Num::parse($r['fio2'] ?? null));
                if ($pafi === null) {
                    continue;
                }
                $gases[] = [
                    'pao2' => (float) Num::parse($r['pao2']),
                    'fio2' => (float) SofaScore::normalizeFio2(Num::parse($r['fio2'])),
                    'pafi' => $pafi,
                    'time' => (string) $r['date'],
                    'source' => $source,
                ];
            }
        }
        // Ranked as if on respiratory support, so a lower ratio is always worse.
        $gas = WorstValue::pick($gases, static fn(array $c): ?int => SofaScore::respiratory($c['pafi'], true));
        if ($gas !== null) {
            $note = $this->note($label, $gas['source'], $gas['time']);
            $out['pao2'] = ['value' => $this->fmt($gas['pao2']), 'note' => $note];
            $out['fio2'] = ['value' => $this->fmt($gas['fio2']), 'note' => $note];
        }

        if ($ventilation !== []) {
            $latest = end($ventilation);
            $out['soporte_respiratorio'] = [
                'value' => '1',
                'note' => $this->note(xl('Mechanical ventilation recorded in the last 24 h'), xl('ventilation record'), (string) $latest['date']),
            ];
        }

        $this->choose($out, 'plaquetas', $label, $this->series($sofa, 'plaquetas', xl('SOFA')), static fn(array $c): ?int => SofaScore::coagulation($c['value']));
        $this->choose($out, 'bilirrubina', $label, $this->series($sofa, 'bilirrubina', xl('SOFA')), static fn(array $c): ?int => SofaScore::liver($c['value']));
        $this->choose($out, 'pam', $label,
            array_merge($this->series($vitalsForChoice, 'map', xl('vital signs')), $this->series($sofa, 'pam', xl('SOFA')), $this->series($apache, 'pam', xl('APACHE II'))),
            static fn(array $c): ?int => SofaScore::cardiovascular($c['value'], null, false, null, null)
        );
        $this->choose($out, 'glasgow', $label,
            $this->glasgowSeries($from, $to, $apache, $sofa),
            static fn(array $c): ?int => SofaScore::cns($c['value'])
        );
        $this->choose($out, 'creatinina', $label,
            array_merge($this->series($sofa, 'creatinina', xl('SOFA')), $this->series($apache, 'creatinina', xl('APACHE II'))),
            static fn(array $c): ?int => SofaScore::renal($c['value'], null)
        );

        /** @var array<string, string|int|null>|false $urine */
        $urine = QueryUtils::querySingleRow(
            "SELECT COUNT(*) AS n, SUM(b.eg_diuresis) AS total, MAX(b.date) AS last_date
               FROM form_balance_hidrico b
               JOIN forms f ON f.form_id = b.id AND f.formdir = 'balance_hidrico' AND f.deleted = 0
              WHERE b.pid = ? AND b.encounter = ? AND b.date >= ? AND b.date <= ? AND b.eg_diuresis IS NOT NULL",
            [$this->pid, $this->encounter, $from, $to]
        );
        $balances = $urine !== false ? (int) ($urine['n'] ?? 0) : 0;
        if ($urine !== false && $balances > 0) {
            $lastDate = $this->when((string) $urine['last_date']);
            $out['diuresis_24h'] = [
                'value' => $this->fmt((float) $urine['total']),
                'note' => $balances === 1
                    ? xl('Fluid balance of the last 24 h') . ', ' . $lastDate
                    : sprintf(xl('Sum of %d fluid balances of the last 24 h'), $balances) . ' (' . xl('last') . ': ' . $lastDate . ')',
            ];
        }

        $this->applyWorstTake($out, $label, ['map' => 'pam']);

        return $out;
    }

    /**
     * Oral, enteral and parenteral intake recorded in the nutrition form since the
     * previous fluid balance of the encounter (at most the last 24 h), so the same
     * volume is not counted in two balances.
     *
     * @return array<string, array{value: string, note: string}>
     */
    public function forFluidBalance(): array
    {
        [$from, $to] = $this->lastDayWindow();
        /** @var array<string, string|int|null>|false $previous */
        $previous = QueryUtils::querySingleRow(
            "SELECT MAX(b.date) AS last_date
               FROM form_balance_hidrico b
               JOIN forms f ON f.form_id = b.id AND f.formdir = 'balance_hidrico' AND f.deleted = 0
              WHERE b.pid = ? AND b.encounter = ? AND b.date >= ?",
            [$this->pid, $this->encounter, $from]
        );
        $since = ($previous !== false && !empty($previous['last_date'])) ? (string) $previous['last_date'] : $from;
        $sinceNote = ($since === $from) ? xl('in the last 24 h') : xl('since the previous fluid balance') . ' (' . $this->when($since) . ')';

        /** @var list<array<string, string|int|null>> $rows */
        $rows = QueryUtils::fetchRecords(
            "SELECT a.tipo_alimentacion, COUNT(*) AS n, SUM(a.volumen_ml) AS total
               FROM form_alimentacion a
               JOIN forms f ON f.form_id = a.id AND f.formdir = 'alimentacion' AND f.deleted = 0
              WHERE a.pid = ? AND a.encounter = ? AND a.date > ? AND a.date <= ? AND a.volumen_ml IS NOT NULL AND a.volumen_ml > 0
              GROUP BY a.tipo_alimentacion",
            [$this->pid, $this->encounter, $since, $to]
        );
        // Mixed feeding combines oral intake with a tube, so it is counted as enteral.
        $map = ['ORAL' => 'ing_via_oral', 'ENTERAL' => 'ing_enteral', 'MIXTA' => 'ing_enteral', 'PARENTERAL' => 'ing_parenteral'];
        $sum = [];
        $count = [];
        foreach ($rows as $r) {
            $field = $map[(string) ($r['tipo_alimentacion'] ?? '')] ?? null;
            if ($field === null) {
                continue;
            }
            $sum[$field] = ($sum[$field] ?? 0.0) + (float) $r['total'];
            $count[$field] = ($count[$field] ?? 0) + (int) $r['n'];
        }
        $out = [];
        foreach ($sum as $field => $total) {
            $out[$field] = [
                'value' => $this->fmt($total),
                'note' => ($count[$field] === 1
                    ? xl('From one nutrition record')
                    : sprintf(xl('Sum of %d nutrition records'), $count[$field])) . ' ' . $sinceNote,
            ];
        }
        return $out;
    }

    // ---------------------------------------------------------------------

    /** @return array{0: string, 1: string} */
    private function lastDayWindow(): array
    {
        /** @var array<string, string|int|null>|false $w */
        $w = QueryUtils::querySingleRow("SELECT NOW() - INTERVAL 24 HOUR AS f, NOW() AS t");
        return [(string) ($w['f'] ?? ''), (string) ($w['t'] ?? '')];
    }

    /**
     * First 24 h of the admission (the inpatient encounter date), capped at now.
     * Falls back to the last 24 h when the encounter has no date.
     *
     * @return array{0: string, 1: string}
     */
    private function admissionWindow(): array
    {
        /** @var array<string, string|int|null>|false $w */
        $w = QueryUtils::querySingleRow(
            "SELECT fe.date AS f, LEAST(fe.date + INTERVAL 24 HOUR, NOW()) AS t
               FROM form_encounter fe WHERE fe.pid = ? AND fe.encounter = ? LIMIT 1",
            [$this->pid, $this->encounter]
        );
        if ($w === false || empty($w['f'])) {
            return $this->lastDayWindow();
        }
        return [(string) $w['f'], (string) $w['t']];
    }

    /**
     * Vital signs of the patient in the window, from OpenEMR's standard vitals form
     * (temperature is stored in Fahrenheit, FiO2 as a percentage).
     *
     * @return list<array<string, mixed>>
     */
    private function vitals(string $from, string $to): array
    {
        /** @var list<array<string, string|int|null>> $rows */
        $rows = QueryUtils::fetchRecords(
            "SELECT v.id, v.date, v.bps, v.bpd, v.pulse, v.respiration, v.temperature
               FROM form_vitals v
               JOIN forms f ON f.form_id = v.id AND f.formdir = 'vitals' AND f.deleted = 0
              WHERE v.pid = ? AND v.date >= ? AND v.date <= ?
              ORDER BY v.date",
            [$this->pid, $from, $to]
        );
        $out = [];
        foreach ($rows as $r) {
            $pulse = Num::parse($r['pulse'] ?? null);
            $respiration = Num::parse($r['respiration'] ?? null);
            $bps = Num::parse($r['bps'] ?? null);
            $bpd = Num::parse($r['bpd'] ?? null);
            $out[] = [
                'id' => (int) $r['id'],
                'date' => (string) $r['date'],
                'bps' => ($bps !== null && $bps > 0) ? $bps : null,
                'bpd' => ($bpd !== null && $bpd > 0) ? $bpd : null,
                'temp_c' => WorstValue::fahrenheitToCelsius(Num::parse($r['temperature'] ?? null)),
                'map' => WorstValue::meanArterialPressure(Num::parse($r['bps'] ?? null), Num::parse($r['bpd'] ?? null)),
                'pulse' => ($pulse !== null && $pulse > 0) ? $pulse : null,
                'respiration' => ($respiration !== null && $respiration > 0) ? $respiration : null,
            ];
        }
        return $out;
    }

    /** @return list<array<string, string|int|null>> */
    private function moduleRows(string $table, string $formdir, string $from, string $to): array
    {
        // $table and $formdir are fixed identifiers from this class, never user input.
        /** @var list<array<string, string|int|null>> $rows */
        $rows = QueryUtils::fetchRecords(
            "SELECT t.* FROM `$table` t
               JOIN forms f ON f.form_id = t.id AND f.formdir = ? AND f.deleted = 0
              WHERE t.pid = ? AND t.encounter = ? AND t.date >= ? AND t.date <= ?
              ORDER BY t.date",
            [$formdir, $this->pid, $this->encounter, $from, $to]
        );
        return $rows;
    }

    /** @return list<array<string, string|int|null>> */
    private function ventilationRows(string $from, string $to): array
    {
        return $this->moduleRows('form_registro_vm', 'registro_vm', $from, $to);
    }

    /**
     * @param list<array<string, mixed>> $apache
     * @param list<array<string, mixed>> $sofa
     * @return list<array{value: float, time: string, source: string}>
     */
    private function glasgowSeries(string $from, string $to, array $apache, array $sofa): array
    {
        $evaluations = $this->moduleRows('form_evaluaciones', 'evaluaciones', $from, $to);
        $series = array_merge(
            $this->series($evaluations, 'glasgow_total', xl('nursing evaluation')),
            $this->series($apache, 'glasgow', xl('APACHE II')),
            $this->series($sofa, 'glasgow', xl('SOFA'))
        );
        // A partial evaluation (for example, only eye opening) is not a valid total.
        return array_values(array_filter($series, static fn(array $c): bool => $c['value'] >= 3 && $c['value'] <= 15));
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array{value: float, time: string, source: string}>
     */
    private function series(array $rows, string $field, string $source): array
    {
        $out = [];
        foreach ($rows as $r) {
            $value = Num::parse($r[$field] ?? null);
            // None of these variables can be zero or negative: a zero is an empty
            // field (OpenEMR also stores 0 by default in its vitals form).
            if ($value === null || $value <= 0) {
                continue;
            }
            $out[] = ['value' => $value, 'time' => (string) ($r['date'] ?? ''), 'source' => $source];
        }
        return $out;
    }

    /**
     * Takes that have something to score, most recent first, marking the worst
     * one (most points; ties: the most recent) and the most recent one.
     *
     * @param list<array<string, mixed>> $vitals
     * @param callable(array<string, mixed>): ?int $points
     * @return list<array{id: int, when: string, bps: ?float, bpd: ?float, map: ?float, temp_c: ?float, pulse: ?float, respiration: ?float, points: int, worst: bool, latest: bool}>
     */
    private function takes(array $vitals, callable $points): array
    {
        $scored = [];
        foreach ($vitals as $v) {
            $p = $points($v);
            if ($p !== null) {
                $scored[] = $v + ['points' => $p, 'time' => (string) $v['date']];
            }
        }
        if ($scored === []) {
            return [];
        }
        $worst = WorstValue::pick($scored, static fn(array $c): int => $c['points']);
        usort($scored, static fn(array $a, array $b): int => [$b['time'], $b['id']] <=> [$a['time'], $a['id']]);
        $out = [];
        foreach ($scored as $i => $v) {
            $out[] = [
                'id' => (int) $v['id'],
                'when' => $this->when((string) $v['date']),
                'bps' => $v['bps'],
                'bpd' => $v['bpd'],
                'map' => $v['map'],
                'temp_c' => $v['temp_c'],
                'pulse' => $v['pulse'],
                'respiration' => $v['respiration'],
                'points' => (int) $v['points'],
                'worst' => $worst !== null && (int) $v['id'] === (int) $worst['id'],
                'latest' => $i === 0,
            ];
        }
        return $out;
    }

    /**
     * Fills the fields that come from vital signs with the worst take.
     *
     * @param array<string, array<string, mixed>> $out
     * @param array<string, string> $fields take key => form field
     */
    private function applyWorstTake(array &$out, string $label, array $fields): void
    {
        foreach ($this->takes as $take) {
            if (!$take['worst']) {
                continue;
            }
            foreach ($fields as $key => $field) {
                if ($take[$key] !== null) {
                    $out[$field] = [
                        'value' => $this->fmt((float) $take[$key]),
                        'note' => $label . ': ' . xl('vital signs') . ', ' . $take['when'],
                    ];
                }
            }
        }
    }

    /**
     * Proposes the worst candidate of a field (see WorstValue::pick) and keeps
     * every candidate of the window, with its points, so the form can offer them
     * as alternatives: the nurse may pick another one, e.g. the latest, when the
     * worst value looks wrong.
     *
     * @param array<string, array<string, mixed>> $out
     * @param list<array{value: float, time: string, source: string}> $candidates
     * @param callable(array{value: float, time: string, source: string}): ?int $points
     * @return array{value: float, time: string, source: string, points: int}|null
     */
    private function choose(array &$out, string $field, string $label, array $candidates, callable $points): ?array
    {
        $pick = WorstValue::pick($candidates, $points);
        $this->put($out, $field, $label, $pick);
        if ($pick !== null) {
            $out[$field]['options'] = $this->options($candidates, $points, $pick);
        }
        return $pick;
    }

    /**
     * Candidates of a field, worst first (ties: most recent first), marking the
     * proposed one and the most recent one.
     *
     * @param list<array{value: float, time: string, source: string}> $candidates
     * @param callable(array{value: float, time: string, source: string}): ?int $points
     * @param array{value: float, time: string, source: string, points: int} $pick
     * @return list<array{value: string, when: string, source: string, points: int, proposed: bool, latest: bool}>
     */
    private function options(array $candidates, callable $points, array $pick): array
    {
        $scored = [];
        foreach ($candidates as $c) {
            $p = $points($c);
            if ($p !== null) {
                $scored[] = $c + ['points' => $p];
            }
        }
        $latestTime = max(array_column($scored, 'time'));
        usort($scored, static fn(array $a, array $b): int => [$b['points'], $b['time']] <=> [$a['points'], $a['time']]);

        $out = [];
        $proposedMarked = false;
        $latestMarked = false;
        foreach ($scored as $c) {
            $proposed = !$proposedMarked && $c['time'] === $pick['time'] && $c['source'] === $pick['source'] && $c['value'] === $pick['value'];
            $latest = !$latestMarked && $c['time'] === $latestTime;
            $proposedMarked = $proposedMarked || $proposed;
            $latestMarked = $latestMarked || $latest;
            $out[] = [
                'value' => $this->fmt($c['value']),
                'when' => $this->when($c['time']),
                'source' => $c['source'],
                'points' => $c['points'],
                'proposed' => $proposed,
                'latest' => $latest,
            ];
        }
        return $out;
    }

    /**
     * @param array<string, array<string, mixed>> $out
     * @param array{value: float, time: string, source: string, points: int}|null $pick
     */
    private function put(array &$out, string $field, string $label, ?array $pick): void
    {
        if ($pick === null) {
            return;
        }
        $out[$field] = ['value' => $this->fmt($pick['value']), 'note' => $this->note($label, $pick['source'], $pick['time'])];
    }

    private function note(string $label, string $source, string $time): string
    {
        return $label . ': ' . $source . ', ' . $this->when($time);
    }

    private function when(string $time): string
    {
        $ts = strtotime($time);
        return $ts !== false ? date('d/m H:i', $ts) : $time;
    }

    private function fmt(float $value): string
    {
        $s = number_format($value, 2, '.', '');
        return rtrim(rtrim($s, '0'), '.');
    }
}
