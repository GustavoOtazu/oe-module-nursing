<?php

/**
 * Electronic signature lock for the nursing forms.
 *
 * Once a form is signed with OpenEMR's eSign (or its encounter is signed while
 * "lock encounters" is enabled) the record must not change. OpenEMR only hides
 * its own "Edit" button, so the module checks the lock itself in new.php (opens
 * the read-only view instead), save.php (rejects the update on the server) and
 * view.php (hides "Edit").
 *
 * A record deleted from the encounter is locked too, signed or not: core's
 * "Delete" only flags forms.deleted, so the row and its signature stay in the
 * database and must not be changed afterwards.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing;

use ESign\Api as ESignApi;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Core\OEGlobalsBag;

final class FormLock
{
    /** @var array<string, bool> */
    private static array $cache = [];

    /**
     * @param string $formdir   module form directory, e.g. 'curaciones'
     * @param int    $formId    row id of the form table (forms.form_id)
     */
    public static function isLocked(string $formdir, int $formId, int $encounter): bool
    {
        if ($formId <= 0 || $encounter <= 0) {
            return false;
        }
        $key = $formdir . ':' . $formId . ':' . $encounter;
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        // ESign is not autoloaded; core does the same require in forms.php.
        require_once OEGlobalsBag::getInstance()->getString('srcdir') . '/ESign/Api.php';
        $api = new ESignApi();

        $locked = false;
        /** @var array<string, string|int|null>|false $row */
        $row = QueryUtils::querySingleRow(
            "SELECT id, deleted FROM forms WHERE form_id = ? AND formdir = ? AND encounter = ? ORDER BY deleted LIMIT 1",
            [$formId, $formdir, $encounter]
        );
        if ($row !== false && (int) $row['deleted'] !== 0) {
            // Deleted from the encounter: the record is kept as it was.
            $locked = true;
        } elseif ($row !== false && $api->createFormESign((int) $row['id'], $formdir, $encounter)->isLocked()) {
            $locked = true;
        } elseif ($api->lockEncounters() && $api->createEncounterESign($encounter)->isLocked()) {
            // A signed encounter locks all of its forms when "lock encounters" is enabled.
            $locked = true;
        }

        return self::$cache[$key] = $locked;
    }
}
