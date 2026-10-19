<?php

/**
 * Form History
 *
 * Shows, below a nursing form, the previous records of the same form in the
 * same admission, the way OpenEMR's vitals form shows the previous takes next
 * to the new one. Each record is drawn by the form's own report function (the
 * summary OpenEMR shows in the encounter), most recent first, side by side.
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

final class FormHistory
{
    /** How many previous records are drawn; the rest are reached with "See all". */
    private const SHOWN = 5;

    /**
     * @param string $formdir   the form's directory (also its forms.formdir)
     * @param int    $excludeId record being edited, left out of its own history
     */
    public static function render(string $formdir, int $pid, int $encounter, int $excludeId = 0): void
    {
        if ($pid <= 0 || $encounter <= 0 || preg_match('/^[a-z_]+$/', $formdir) !== 1) {
            return;
        }
        $report = dirname(__DIR__) . '/public/forms/' . $formdir . '/report.php';
        $function = $formdir . '_report';
        if (!is_file($report)) {
            return;
        }
        require_once $report;
        if (!function_exists($function)) {
            return;
        }

        /** @var list<array<string, string|int|null>> $rows */
        $rows = QueryUtils::fetchRecords(
            "SELECT form_id, date, user FROM forms
              WHERE formdir = ? AND pid = ? AND encounter = ? AND deleted = 0 AND form_id <> ?
              ORDER BY date DESC, id DESC",
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
            /* No background/colour here: inherit the active theme (light or dark). */
            .form-history { border: 1px solid rgba(128, 128, 128, 0.35); border-radius: 6px; padding: 8px 12px; margin: 20px 0 16px; }
            .form-history > summary { cursor: pointer; font-weight: 600; }
            .form-history .fh-row { display: flex; gap: 12px; overflow-x: auto; padding: 10px 2px 4px; }
            .form-history .fh-card { flex: 0 0 380px; max-height: 420px; overflow: auto; border: 1px solid rgba(128, 128, 128, 0.35); border-radius: 6px; padding: 8px; font-size: 12px; }
            .form-history .fh-head { font-weight: 600; margin-bottom: 6px; padding-bottom: 4px; border-bottom: 1px solid rgba(128, 128, 128, 0.35); }
        </style>
        <details class="form-history" open>
            <summary>
                <i class="fa fa-history mr-1"></i><?php echo xlt('History of this admission'); ?>
                (<?php echo text($total === 1 ? xl('1 previous record') : sprintf(xl('%d previous records'), $total)); ?>)
            </summary>
            <div class="fh-row">
                <?php foreach ($shown as $r) : ?>
                <div class="fh-card">
                    <div class="fh-head">
                        <?php
                        $ts = strtotime((string) $r['date']);
                        echo text(($ts !== false ? date('d/m/Y H:i', $ts) : (string) $r['date']) . ' · ' . (string) $r['user']);
                        ?>
                    </div>
                    <?php $function($pid, $encounter, 2, (int) $r['form_id']); ?>
                </div>
                <?php endforeach; ?>
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
}
