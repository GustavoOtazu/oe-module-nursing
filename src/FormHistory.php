<?php

/**
 * Form History
 *
 * Shows, below a nursing form, the previous records of the same form in the
 * same admission as a table like OpenEMR's vitals history: one row per field
 * (name and unit) and one column per record, most recent first, with its date,
 * time and user. The rows of each form are defined in FormHistoryFields.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Modules\Nursing\Prefill\VitalsChoice;

final class FormHistory
{
    /** How many previous records are shown; the rest are reached with "See all". */
    private const SHOWN = 5;

    /**
     * @param string $formdir   the form's directory (also its forms.formdir)
     * @param int    $excludeId record being edited, left out of its own history
     */
    public static function render(string $formdir, int $pid, int $encounter, int $excludeId = 0): void
    {
        $fields = FormHistoryFields::for($formdir);
        if ($fields === null || $pid <= 0 || $encounter <= 0) {
            return;
        }
        // $formdir is one of the known forms (FormHistoryFields), never user input.
        /** @var list<array<string, string|int|float|null>> $rows */
        $rows = QueryUtils::fetchRecords(
            "SELECT t.*, f.date AS fh_date, f.user AS fh_user
               FROM forms f
               JOIN `form_" . $formdir . "` t ON t.id = f.form_id
              WHERE f.formdir = ? AND f.pid = ? AND f.encounter = ? AND f.deleted = 0 AND f.form_id <> ?
              ORDER BY f.date DESC, f.id DESC",
            [$formdir, $pid, $encounter, $excludeId]
        );
        if ($rows === []) {
            return;
        }
        $total = count($rows);
        $shown = array_slice($rows, 0, self::SHOWN);
        $allUrl = OEGlobalsBag::getInstance()->getString('webroot')
            . '/interface/modules/custom_modules/oe-module-nursing/public/forms/' . $formdir
            . '/view.php?pid=' . $pid . '&encounter=' . $encounter . '&id=0';
        ?>
        <style>
            /* Like OpenEMR's vitals history; translucent overlays so it works in light and dark themes. */
            .form-history { border: 1px solid rgba(128, 128, 128, 0.35); border-radius: 6px; padding: 8px 12px; margin: 20px 0 16px; }
            .form-history > summary { cursor: pointer; font-weight: 600; font-size: 1.1em; }
            .form-history .fh-scroll { overflow-x: auto; margin-top: 10px; }
            .form-history table { border-collapse: collapse; font-size: 13px; min-width: 100%; }
            .form-history th, .form-history td { border: 1px solid rgba(128, 128, 128, 0.35); padding: 4px 8px; vertical-align: top; }
            .form-history thead th { background: rgba(128, 128, 128, 0.30); white-space: nowrap; }
            .form-history thead th small { display: block; font-weight: normal; opacity: 0.8; }
            .form-history td.fh-name { font-weight: 600; min-width: 200px; }
            .form-history td.fh-unit { opacity: 0.8; white-space: nowrap; }
            .form-history td.fh-val { background: rgba(128, 128, 128, 0.12); min-width: 130px; max-width: 260px; }
            .form-history td.fh-val small { display: block; opacity: 0.8; font-style: italic; }
            .form-history td.fh-score { font-weight: 700; }
        </style>
        <details class="form-history" open>
            <summary>
                <i class="fa fa-history mr-1"></i><?php echo xlt('History of this admission'); ?>
                (<?php echo text($total === 1 ? xl('1 previous record') : sprintf(xl('%d previous records'), $total)); ?>)
            </summary>
            <div class="fh-scroll">
                <table>
                    <thead>
                        <tr>
                            <th><?php echo xlt('Name'); ?></th>
                            <th><?php echo xlt('Unit'); ?></th>
                            <?php foreach ($shown as $r) : ?>
                            <th>
                                <?php $ts = strtotime((string) $r['fh_date']); ?>
                                <?php echo text($ts !== false ? date('d/m/Y H:i', $ts) : (string) $r['fh_date']); ?>
                                <small><?php echo text((string) $r['fh_user']); ?></small>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fields as $field) : ?>
                            <?php
                            $cells = array_map(static fn(array $r): array => self::cell($r, $field, $pid), $shown);
                            // Like the vitals history, skip a row with nothing in any record.
                            if (array_filter($cells, static fn(array $c): bool => $c[0] !== '' || $c[1] !== '') === []) {
                                continue;
                            }
                            ?>
                        <tr>
                            <td class="fh-name"><?php echo text(xl($field['label'])); ?></td>
                            <td class="fh-unit"><?php echo text(isset($field['unit']) ? xl($field['unit']) : ''); ?></td>
                            <?php foreach ($cells as [$value, $obs]) : ?>
                            <td class="fh-val<?php echo $field['type'] === 'score' ? ' fh-score' : ''; ?>">
                                <?php echo text($value); ?>
                                <?php if ($obs !== '') : ?><small><?php echo text($obs); ?></small><?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total > self::SHOWN) : ?>
            <small>
                <?php echo text(sprintf(xl('Showing the last %d.'), self::SHOWN)); ?>
                <a href="<?php echo attr($allUrl); ?>" target="_blank" rel="noopener"><?php echo xlt('See all'); ?></a>
            </small>
            <?php endif; ?>
        </details>
        <?php
    }

    /**
     * The value of one field in one record, and its observation.
     *
     * @param array<string, string|int|float|null> $row
     * @param array{col: string, label: string, type: string, unit?: string, obs?: string, map?: array<string, string>} $field
     * @return array{0: string, 1: string}
     */
    private static function cell(array $row, array $field, int $pid): array
    {
        $raw = $row[$field['col']] ?? null;
        $value = $raw === null ? '' : trim((string) $raw);
        $obs = isset($field['obs']) ? trim((string) ($row[$field['obs']] ?? '')) : '';
        $map = $field['map'] ?? [];

        $shown = match ($field['type']) {
            'bool' => $value === '1' ? xl('Yes') : ($value === '' ? '' : xl('No')),
            'num', 'score' => $value === '' || !is_numeric($value) ? $value : self::number((float) $value),
            'time' => substr($value, 0, 5),
            'enum' => $value === '' ? '' : (isset($map[$value]) ? xl($map[$value]) : $value),
            'set' => implode(', ', array_map(
                static fn(string $code): string => isset($map[$code]) ? xl($map[$code]) : $code,
                array_filter(array_map('trim', explode(',', $value)), static fn(string $c): bool => $c !== '')
            )),
            'take' => VitalsChoice::describe((int) $value, $pid) ?? '',
            default => $value,
        };
        return [$shown, $obs];
    }

    /** "250.00" -> "250", "12.50" -> "12.5" */
    private static function number(float $value): string
    {
        $s = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
        return $s === '-0' ? '0' : $s;
    }
}
