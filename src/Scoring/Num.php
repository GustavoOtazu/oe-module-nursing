<?php

/**
 * Numeric input helper for the nursing forms.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing\Scoring;

final class Num
{
    /**
     * Parse a form value into a float, or null when it is empty or not numeric.
     * Accepts a comma as decimal separator ("12,5"), which is the local convention.
     */
    public static function parse(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        if (!is_string($value)) {
            return null;
        }
        $clean = str_replace(',', '.', trim($value));
        if ($clean === '' || !is_numeric($clean)) {
            return null;
        }
        return (float) $clean;
    }
}
