<?php

/**
 * Vitals Link
 *
 * The module has no vital signs form of its own: vitals are recorded in OpenEMR's
 * standard vitals form, which is where the SOFA and APACHE II prefill reads them.
 * This builds the URL that opens that form for a given admission, through core's
 * encounter_top.php, which sets the patient and encounter in the session first so
 * the vitals are saved in the right encounter.
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

final class VitalsLink
{
    public static function url(int $pid, int $encounter): string
    {
        return OEGlobalsBag::getInstance()->getString('webroot')
            . '/interface/patient_file/encounter/encounter_top.php?'
            . http_build_query([
                'set_encounter' => $encounter,
                'pid' => $pid,
                'formname' => 'vitals',
                'formdesc' => xl('Vital Signs'),
            ]);
    }
}
