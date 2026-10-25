<?php

/**
 * Permission checks remembered for the rest of the request.
 *
 * OpenEMR's AclMain::aclCheckCore() asks phpGACL every time (about 12 queries per
 * check: OpenEMR creates phpGACL with its cache off). The encounter view renders the
 * report of every form of the admission, and each report checks the same permission,
 * so an admission with 50 nursing forms ran the same check 50 times. Within one
 * request the user and their permissions do not change, so the answer is kept.
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

final class NursingAcl
{
    /** @var array<string, bool> */
    private static array $answers = [];

    /** Same as AclMain::aclCheckCore() for the current user, asked once per request. */
    public static function check(string $section, string $value, string $level = ''): bool
    {
        return self::$answers[$section . "\0" . $value . "\0" . $level]
            ??= AclMain::aclCheckCore($section, $value, '', $level);
    }
}
