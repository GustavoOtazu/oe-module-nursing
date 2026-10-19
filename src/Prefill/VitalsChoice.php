<?php

/**
 * Vitals Choice
 *
 * The SOFA and APACHE II forms take their vital-sign variables from one whole
 * take of OpenEMR's vitals form. When a record is created the form shows a
 * "Vital signs take" select with the takes of the scale's window, the worst one
 * preselected (see ClinicalPrefill::vitalsTakes()); the nurse may pick another
 * one. The id of the chosen take is saved with the score (column vitals_id),
 * so it is known which vital signs each score was calculated with.
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
use OpenEMR\Modules\Nursing\Scoring\Num;
use OpenEMR\Modules\Nursing\Scoring\WorstValue;

final class VitalsChoice
{
    /**
     * @param list<array{id: int, when: string, bps: ?float, bpd: ?float, map: ?float, temp_c: ?float, pulse: ?float, respiration: ?float, points: int, worst: bool, latest: bool}> $takes
     * @param array<string, string> $fields take key (map, temp_c, pulse, respiration) => form input id
     */
    public static function render(array $takes, array $fields): void
    {
        if ($takes === []) {
            return;
        }
        ?>
        <div class="form-group vitals-choice" data-fields="<?php echo attr((string) json_encode($fields)); ?>">
            <label for="vitals_id" class="font-weight-bold"><i class="fa fa-heartbeat mr-1"></i><?php echo xlt('Vital signs take'); ?></label>
            <select name="vitals_id" id="vitals_id" class="form-control">
                <?php foreach ($takes as $t) : ?>
                <option value="<?php echo attr((string) $t['id']); ?>"<?php echo $t['worst'] ? ' selected' : ''; ?>
                        data-when="<?php echo attr($t['when']); ?>"
                        <?php foreach (['map', 'temp_c', 'pulse', 'respiration'] as $k) : ?>
                        data-<?php echo attr(str_replace('_', '-', $k)); ?>="<?php echo attr($t[$k] !== null ? self::fmt($t[$k]) : ''); ?>"
                        <?php endforeach; ?>><?php echo text(self::label($t)); ?></option>
                <?php endforeach; ?>
            </select>
            <small class="form-text text-muted"><?php echo xlt('The worst take of the period is preselected. The values of the chosen take fill the fields that come from vital signs, and the take is saved with this score.'); ?></small>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var box = document.querySelector('.vitals-choice');
            var select = document.getElementById('vitals_id');
            var fields = JSON.parse(box.dataset.fields);
            select.addEventListener('change', function () {
                var opt = select.options[select.selectedIndex];
                Object.keys(fields).forEach(function (key) {
                    var value = opt.dataset[key.replace(/_([a-z])/g, function (m, c) { return c.toUpperCase(); })];
                    var input = document.getElementById(fields[key]);
                    if (!input || value === undefined || value === '') {
                        return;
                    }
                    input.value = value;
                    // The forms recalculate the score on these events.
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    var hint = input.closest('.form-group').querySelector('.prefill-hint');
                    if (hint) {
                        var icon = hint.querySelector('i');
                        hint.textContent = '';
                        if (icon) {
                            hint.appendChild(icon);
                        }
                        hint.appendChild(document.createTextNode(
                            <?php echo js_escape(xl('Chosen vital signs take')); ?> + ': ' + opt.dataset.when
                            + ' — ' + <?php echo js_escape(xl('Review before saving')); ?>
                        ));
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * The take id posted with a new score, when it is a vital signs record of
     * this patient; null otherwise.
     */
    public static function postedId(int $pid): ?int
    {
        $raw = filter_input(INPUT_POST, 'vitals_id', FILTER_SANITIZE_NUMBER_INT);
        $id = is_numeric($raw) ? (int) $raw : 0;
        if ($id <= 0) {
            return null;
        }
        /** @var array<string, string|int|null>|false $row */
        $row = QueryUtils::querySingleRow(
            "SELECT v.id FROM form_vitals v
               JOIN forms f ON f.form_id = v.id AND f.formdir = 'vitals' AND f.deleted = 0
              WHERE v.id = ? AND v.pid = ? LIMIT 1",
            [$id, $pid]
        );
        return $row !== false ? $id : null;
    }

    /** One-line description of a saved take, to show which vitals a score used. */
    public static function describe(int $vitalsId, int $pid): ?string
    {
        if ($vitalsId <= 0) {
            return null;
        }
        /** @var array<string, string|int|null>|false $r */
        $r = QueryUtils::querySingleRow(
            "SELECT v.date, v.bps, v.bpd, v.pulse, v.respiration, v.temperature
               FROM form_vitals v WHERE v.id = ? AND v.pid = ? LIMIT 1",
            [$vitalsId, $pid]
        );
        if ($r === false) {
            return null;
        }
        $bps = Num::parse($r['bps'] ?? null);
        $bpd = Num::parse($r['bpd'] ?? null);
        $pulse = Num::parse($r['pulse'] ?? null);
        $resp = Num::parse($r['respiration'] ?? null);
        return self::label([
            'when' => ($ts = strtotime((string) $r['date'])) !== false ? date('d/m H:i', $ts) : (string) $r['date'],
            'bps' => ($bps !== null && $bps > 0) ? $bps : null,
            'bpd' => ($bpd !== null && $bpd > 0) ? $bpd : null,
            'map' => WorstValue::meanArterialPressure($bps, $bpd),
            'temp_c' => WorstValue::fahrenheitToCelsius(Num::parse($r['temperature'] ?? null)),
            'pulse' => ($pulse !== null && $pulse > 0) ? $pulse : null,
            'respiration' => ($resp !== null && $resp > 0) ? $resp : null,
        ]);
    }

    /**
     * @param array{when: string, bps: ?float, bpd: ?float, map: ?float, temp_c: ?float, pulse: ?float, respiration: ?float, points?: int, worst?: bool, latest?: bool} $t
     */
    private static function label(array $t): string
    {
        $parts = [$t['when']];
        if ($t['bps'] !== null && $t['bpd'] !== null) {
            $parts[] = xl('BP') . ' ' . self::fmt($t['bps']) . '/' . self::fmt($t['bpd']);
        }
        if ($t['map'] !== null) {
            $parts[] = xl('MAP') . ' ' . self::fmt($t['map']);
        }
        if ($t['pulse'] !== null) {
            $parts[] = xl('HR') . ' ' . self::fmt($t['pulse']);
        }
        if ($t['respiration'] !== null) {
            $parts[] = xl('RR') . ' ' . self::fmt($t['respiration']);
        }
        if ($t['temp_c'] !== null) {
            $parts[] = self::fmt($t['temp_c']) . ' °C';
        }
        $label = implode(' · ', $parts);
        if (isset($t['points'])) {
            $label .= ' — ' . $t['points'] . ' ' . ($t['points'] === 1 ? xl('point') : xl('points'));
        }
        $marks = [];
        if (!empty($t['worst'])) {
            $marks[] = xl('worst');
        }
        if (!empty($t['latest'])) {
            $marks[] = xl('latest');
        }
        return $marks === [] ? $label : $label . ' (' . implode(', ', $marks) . ')';
    }

    private static function fmt(float $value): string
    {
        $s = number_format($value, 2, '.', '');
        return rtrim(rtrim($s, '0'), '.');
    }
}
