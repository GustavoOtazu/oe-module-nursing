<?php

/**
 * Extra buttons for the nursing forms in the encounter's form list.
 *
 * OpenEMR's encounter form list (interface/patient_file/encounter/forms.php)
 * only adds a "Print" button to LBF forms and offers no event to add buttons
 * per form. It does render each form's report.php inside its row, so every
 * nursing report calls render(), which prints an invisible marker and a small
 * script that:
 *   - adds a "Print" button next to "Delete", which opens the form's PDF;
 *   - makes the "Locked" button of signed forms open the read-only view, which
 *     core leaves without a link.
 * Outside the encounter form list (for example, in the patient report) the
 * marker is not inside a form row, so the script does nothing.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing;

use OpenEMR\Core\OEGlobalsBag;

final class EncounterFormButtons
{
    private static bool $scriptPrinted = false;

    /**
     * @param string $formdir  module form directory, e.g. 'curaciones'
     * @param string $formName registry name, used to open the form tab
     * @param int    $id       row id of the form table (forms.form_id)
     */
    public static function render(string $formdir, string $formName, int $pid, int $encounter, int $id): void
    {
        if ($id <= 0 || $pid <= 0 || $encounter <= 0) {
            return;
        }
        $printUrl = OEGlobalsBag::getInstance()->getString('webroot')
            . '/interface/modules/custom_modules/oe-module-nursing/public/forms/' . rawurlencode($formdir)
            . '/print.php?pid=' . $pid . '&encounter=' . $encounter . '&id=' . $id;

        echo '<span class="nursing-form-marker d-none"'
            . ' data-print-url="' . attr($printUrl) . '"'
            . ' data-formdir="' . attr($formdir) . '"'
            . ' data-formname="' . attr($formName) . '"'
            . ' data-formid="' . attr((string) $id) . '"></span>';

        if (self::$scriptPrinted) {
            return;
        }
        self::$scriptPrinted = true;
        $printLabel = js_escape(xl('Print'));
        $printTitle = js_escape(xl('Print this form as PDF'));
        $viewTitle = js_escape(xl('View signed record'));
        echo <<<HTML
<script>
(function () {
    function nursingFormButtons() {
        document.querySelectorAll('.nursing-form-marker:not([data-done])').forEach(function (marker) {
            marker.setAttribute('data-done', '1');
            var detail = marker.closest('.form-detail');
            var header = detail ? detail.previousElementSibling : null;
            var controls = header ? header.querySelector('.form_header_controls') : null;
            if (!controls) {
                return;
            }
            var print = document.createElement('a');
            print.className = 'btn btn-text btn-sm nursing-print-button';
            print.href = marker.dataset.printUrl;
            print.target = '_blank';
            print.title = {$printTitle};
            print.innerHTML = '<i class="fa fa-print fa-fw"></i>&nbsp;';
            print.appendChild(document.createTextNode({$printLabel}));
            print.addEventListener('click', function () {
                if (top.restoreSession) { top.restoreSession(); }
            });
            var del = controls.querySelector('.btn-delete');
            controls.insertBefore(print, del);

            var locked = controls.querySelector('.form-edit-button-locked');
            if (locked && typeof openEncounterForm === 'function') {
                locked.title = {$viewTitle};
                locked.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (top.restoreSession) { top.restoreSession(); }
                    openEncounterForm(marker.dataset.formdir, marker.dataset.formname, marker.dataset.formid);
                });
            }
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', nursingFormButtons);
    } else {
        nursingFormButtons();
    }
})();
</script>
HTML;
    }
}
