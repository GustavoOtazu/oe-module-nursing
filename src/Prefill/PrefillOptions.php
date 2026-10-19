<?php

/**
 * Prefill Options
 *
 * Renders, under a prefilled field of the SOFA and APACHE II forms, the list of
 * every value of the window that ClinicalPrefill considered, worst first, with
 * its points and the "Proposed" and "Latest" marks. Picking one puts it in the
 * field (which recalculates the score) and updates the note of where the value
 * came from. The worst value stays the default, as the scales define it.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing\Prefill;

final class PrefillOptions
{
    /**
     * @param array<string, array<string, mixed>> $prefill output of ClinicalPrefill::forSofa()/forApache()
     */
    public static function render(array $prefill, string $field): void
    {
        /** @var list<array{value: string, when: string, source: string, points: int, proposed: bool, latest: bool}> $options */
        $options = $prefill[$field]['options'] ?? [];
        if (count($options) < 2) {
            return;
        }
        ?>
        <div class="prefill-options" data-target="<?php echo attr($field); ?>">
            <a href="#" class="prefill-toggle small"><?php echo text(sprintf(xl('Show values (%d)'), count($options))); ?></a>
            <ul class="prefill-list list-unstyled small mb-0" hidden>
                <?php foreach ($options as $o) : ?>
                <li>
                    <button type="button" class="prefill-pick btn btn-link btn-sm p-0 text-left text-nowrap<?php echo $o['proposed'] ? ' font-weight-bold' : ''; ?>"
                            data-value="<?php echo attr($o['value']); ?>"
                            data-note="<?php echo attr(xl('Chosen by the user') . ': ' . $o['source'] . ', ' . $o['when']); ?>">
                        <?php echo text($o['when'] . ' · ' . $o['source'] . ' · ' . $o['value'] . ' · '
                            . $o['points'] . ' ' . ($o['points'] === 1 ? xl('point') : xl('points'))); ?>
                        <?php if ($o['proposed']) : ?><span class="badge badge-primary"><?php echo xlt('Proposed'); ?></span><?php endif; ?>
                        <?php if ($o['latest']) : ?><span class="badge badge-info"><?php echo xlt('Latest'); ?></span><?php endif; ?>
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    /** Behaviour of the lists; printed once per form. */
    public static function script(): void
    {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.prefill-options').forEach(function (box) {
                var input = document.getElementById(box.dataset.target);
                var list = box.querySelector('.prefill-list');
                box.querySelector('.prefill-toggle').addEventListener('click', function (e) {
                    e.preventDefault();
                    list.hidden = !list.hidden;
                });
                box.querySelectorAll('.prefill-pick').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        if (!input) {
                            return;
                        }
                        input.value = btn.dataset.value;
                        // The forms recalculate the score on these events.
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                        box.querySelectorAll('.prefill-pick').forEach(function (b) {
                            b.classList.toggle('font-weight-bold', b === btn);
                        });
                        // Update the "where it came from" note shown just above the list.
                        var hint = box.previousElementSibling;
                        if (hint && hint.classList.contains('prefill-hint')) {
                            var icon = hint.querySelector('i');
                            hint.textContent = '';
                            if (icon) {
                                hint.appendChild(icon);
                            }
                            hint.appendChild(document.createTextNode(btn.dataset.note + ' — ' + <?php echo js_escape(xl('Review before saving')); ?>));
                        }
                        list.hidden = true;
                    });
                });
            });
        });
        </script>
        <?php
    }
}
