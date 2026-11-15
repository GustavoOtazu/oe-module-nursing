<?php

/**
 * Points the OpenEMR session at the patient and encounter of the nursing record.
 *
 * The inpatient list opens the nursing forms with pid and encounter in the address,
 * but OpenEMR's audit log takes the patient from the session. If the user had another
 * patient open, the log named that patient instead of the owner of the record
 * (ISO 27789 asks for the right patient). Vital signs already go through
 * encounter_top.php, which sets both; the nursing forms now do the same.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing;

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Session\EncounterSessionUtil;
use OpenEMR\Common\Session\PatientSessionUtil;
use OpenEMR\Common\Session\SessionWrapperFactory;

final class PatientContext
{
    /**
     * Sets the session patient and encounter to those of the record, only for a user
     * who can read encounter notes and only when the encounter belongs to that
     * patient, so a crossed pair never changes the session.
     */
    public static function useRecord(int $pid, int $encounter): void
    {
        if ($pid <= 0 || $encounter <= 0 || !AclMain::aclCheckCore('encounters', 'notes')) {
            return;
        }
        $row = QueryUtils::querySingleRow(
            "SELECT 1 FROM form_encounter WHERE pid = ? AND encounter = ? LIMIT 1",
            [$pid, $encounter]
        );
        if ($row === false) {
            return;
        }
        $session = SessionWrapperFactory::getInstance()->getActiveSession();
        if ((int) $session->get('pid') !== $pid) {
            PatientSessionUtil::setPid($pid);
        }
        if ((int) $session->get('encounter') !== $encounter) {
            EncounterSessionUtil::setEncounter((string) $encounter);
        }
    }

    /**
     * Script for the page head that tells OpenEMR's main window which patient and
     * encounter are open, as the patient summary does, so the patient bar matches
     * the session. Empty when the encounter does not belong to the patient.
     */
    public static function uiScript(int $pid, int $encounter): string
    {
        if ($pid <= 0 || $encounter <= 0) {
            return '';
        }
        /** @var array<string, string|int|null>|false $row */
        $row = QueryUtils::querySingleRow(
            "SELECT p.fname, p.lname, p.pubpid, p.DOB, f.date
               FROM form_encounter f JOIN patient_data p ON p.pid = f.pid
              WHERE f.pid = ? AND f.encounter = ? LIMIT 1",
            [$pid, $encounter]
        );
        if ($row === false) {
            return '';
        }
        $dob = (string) ($row['DOB'] ?? '');
        $dobText = $dob !== '' ? ' ' . xl('DOB') . ': ' . oeFormatShortDate($dob) : '';
        if ($dob !== '' && function_exists('getPatientAgeDisplay')) {
            $dobText .= ' ' . xl('Age') . ': ' . getPatientAgeDisplay($dob);
        }
        $name = trim(($row['fname'] ?? '') . ' ' . ($row['lname'] ?? ''));
        $date = oeFormatShortDate(substr((string) ($row['date'] ?? ''), 0, 10));

        return '<script>(function () {'
            . 'var nav = window.top && window.top.left_nav;'
            . 'if (!nav || typeof nav.setPatient !== "function") { return; }'
            . 'nav.setPatient(' . js_escape($name) . ', ' . js_escape($pid) . ', ' . js_escape((string) ($row['pubpid'] ?? '')) . ", '', " . js_escape($dobText) . ');'
            . 'if (typeof nav.setEncounter === "function") { nav.setEncounter(' . js_escape($date) . ', ' . js_escape($encounter) . ", 'enc'); }"
            . '})();</script>';
    }
}
