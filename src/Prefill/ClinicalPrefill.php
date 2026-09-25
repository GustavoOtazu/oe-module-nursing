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
    public function __construct(
        private readonly int $pid,
        private readonly int $encounter
    ) {
    }

    /**
     * @return array<string, array{value: string, note: string}>
     */
    public function forApache(): array
    {
        [$from, $to] = $this->admissionWindow();
        $label = xl('Worst value of the first 24 h of the admission');
        $vitals = $this->vitals($from, $to);
        $apache = $this->moduleRows('form_escala_apache', 'escala_apache', $from, $to);
        $sofa = $this->moduleRows('form_escala_sofa', 'escala_sofa', $from, $to);
        $out = [];

        $this->put($out, 'temperatura', $label, WorstValue::pick(
            array_merge($this->series($vitals, 'temp_c', xl('vital signs')), $this->series($apache, 'temperatura', xl('APACHE II'))),
            static fn(array $c): ?int => ApacheIIScore::temperature($c['value'])
        ));
        $this->put($out, 'pam', $label, WorstValue::pick(
            array_merge($this->series($vitals, 'map', xl('vital signs')), $this->series($apache, 'pam', xl('APACHE II')), $this->series($sofa, 'pam', xl('SOFA'))),
            static fn(array $c): ?int => ApacheIIScore::meanArterialPressure($c['value'])
        ));
        $this->put($out, 'frecuencia_cardiaca', $label, WorstValue::pick(
            array_merge($this->series($vitals, 'pulse', xl('vital signs')), $this->series($apache, 'frecuencia_cardiaca', xl('APACHE II'))),
            static fn(array $c): ?int => ApacheIIScore::heartRate($c['value'])
        ));
        $this->put($out, 'frecuencia_respiratoria', $label, WorstValue::pick(
            array_merge(
                $this->series($vitals, 'respiration', xl('vital signs')),
                $this->series($this->ventilationRows($from, $to), 'frecuencia_respiratoria', xl('ventilation record')),
                $this->series($apache, 'frecuencia_respiratoria', xl('APACHE II'))
            ),
            static fn(array $c): ?int => ApacheIIScore::respiratoryRate($c['value'])
        ));

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

        $ph = WorstValue::pick($this->series($apache, 'ph', xl('APACHE II')), static fn(array $c): ?int => ApacheIIScore::arterialPh($c['value']));
        $this->put($out, 'ph', $label, $ph);
        if ($ph === null) {
            $this->put($out, 'bicarbonato', $label, WorstValue::pick(
                $this->series($apache, 'bicarbonato', xl('APACHE II')),
                static fn(array $c): ?int => ApacheIIScore::bicarbonate($c['value'])
            ));
        }
        $this->put($out, 'sodio', $label, WorstValue::pick($this->series($apache, 'sodio', xl('APACHE II')), static fn(array $c): ?int => ApacheIIScore::sodium($c['value'])));
        $this->put($out, 'potasio', $label, WorstValue::pick($this->series($apache, 'potasio', xl('APACHE II')), static fn(array $c): ?int => ApacheIIScore::potassium($c['value'])));
        $this->put($out, 'creatinina', $label, WorstValue::pick(
            array_merge($this->series($apache, 'creatinina', xl('APACHE II')), $this->series($sofa, 'creatinina', xl('SOFA'))),
            static fn(array $c): ?int => ApacheIIScore::creatinine($c['value'], false)
        ));
        $this->put($out, 'hematocrito', $label, WorstValue::pick($this->series($apache, 'hematocrito', xl('APACHE II')), static fn(array $c): ?int => ApacheIIScore::hematocrit($c['value'])));
        $this->put($out, 'leucocitos', $label, WorstValue::pick($this->series($apache, 'leucocitos', xl('APACHE II')), static fn(array $c): ?int => ApacheIIScore::whiteBloodCells($c['value'])));
        $this->put($out, 'glasgow', $label, WorstValue::pick(
            $this->glasgowSeries($from, $to, $apache, $sofa),
            static fn(array $c): ?int => ApacheIIScore::glasgow($c['value'])
        ));

        return $out;
    }

    /**
     * @return array<string, array{value: string, note: string}>
     */
    public function forSofa(): array
    {
        [$from, $to] = $this->lastDayWindow();
        $label = xl('Worst value of the last 24 h');
        $vitals = $this->vitals($from, $to);
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

        $this->put($out, 'plaquetas', $label, WorstValue::pick($this->series($sofa, 'plaquetas', xl('SOFA')), static fn(array $c): ?int => SofaScore::coagulation($c['value'])));
        $this->put($out, 'bilirrubina', $label, WorstValue::pick($this->series($sofa, 'bilirrubina', xl('SOFA')), static fn(array $c): ?int => SofaScore::liver($c['value'])));
        $this->put($out, 'pam', $label, WorstValue::pick(
            array_merge($this->series($vitals, 'map', xl('vital signs')), $this->series($sofa, 'pam', xl('SOFA')), $this->series($apache, 'pam', xl('APACHE II'))),
            static fn(array $c): ?int => SofaScore::cardiovascular($c['value'], null, false, null, null)
        ));
        $this->put($out, 'glasgow', $label, WorstValue::pick(
            $this->glasgowSeries($from, $to, $apache, $sofa),
            static fn(array $c): ?int => SofaScore::cns($c['value'])
        ));
        $this->put($out, 'creatinina', $label, WorstValue::pick(
            array_merge($this->series($sofa, 'creatinina', xl('SOFA')), $this->series($apache, 'creatinina', xl('APACHE II'))),
            static fn(array $c): ?int => SofaScore::renal($c['value'], null)
        ));

        /** @var array<string, string|int|null>|false $urine */
        $urine = QueryUtils::querySingleRow(
            "SELECT COUNT(*) AS n, SUM(b.eg_diuresis) AS total, MAX(b.date) AS last_date
               FROM form_balance_hidrico b
               JOIN forms f ON f.form_id = b.id AND f.formdir = 'balance_hidrico' AND f.deleted = 0
              WHERE b.pid = ? AND b.encounter = ? AND b.date >= ? AND b.date <= ? AND b.eg_diuresis IS NOT NULL",
            [$this->pid, $this->encounter, $from, $to]
        );
        if ($urine !== false && (int) ($urine['n'] ?? 0) > 0) {
            $out['diuresis_24h'] = [
                'value' => $this->fmt((float) $urine['total']),
                'note' => sprintf(xl('Sum of %d fluid balances of the last 24 h'), (int) $urine['n'])
                    . ' (' . xl('last') . ': ' . $this->when((string) $urine['last_date']) . ')',
            ];
        }

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
                'note' => sprintf(xl('Sum of %d nutrition records'), $count[$field]) . ' ' . $sinceNote,
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
            "SELECT v.date, v.bps, v.bpd, v.pulse, v.respiration, v.temperature
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
            $out[] = [
                'date' => (string) $r['date'],
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
     * @param array<string, array{value: string, note: string}> $out
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
