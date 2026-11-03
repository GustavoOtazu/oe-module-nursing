<?php

/**
 * Admission Teams
 *
 * The teams involved in an ICU stay besides the primary team. The module is for
 * the ICU, so the primary team is always the ICU team, which decides admission
 * and discharge. This class keeps the other two kinds, which are not the same in
 * hospital practice:
 *
 *  - shared care: another team that also follows the patient and is
 *    responsible for part of the care (e.g. General Surgery after an
 *    operation). It does not block the ICU discharge, because that decision
 *    belongs to the ICU team, but the discharge warns that the team is still
 *    following the patient. Its participation in the stay ends with the
 *    discharge or before, when the team stops following the patient.
 *  - consultation: another specialty is asked for an opinion. It assesses the
 *    patient and recommends a plan without taking over the care; it has no
 *    effect on the discharge and stays recorded with its recommendation.
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

final class AdmissionTeams
{
    public const TABLE = 'nursing_admission_team';

    public const ROLE_SHARED = 'shared';
    public const ROLE_CONSULT = 'consult';

    /** Shared care. */
    public const ACTIVE = 'active';
    public const ENDED = 'ended';

    /** Consultation. */
    public const REQUESTED = 'requested';
    public const ANSWERED = 'answered';
    public const CANCELLED = 'cancelled';

    public const MAX_TEAM = 100;
    public const MAX_PROFESSIONAL = 150;

    /**
     * Names of the shared care teams still following the patient.
     *
     * @param list<array<string, mixed>> $rows rows of the admission
     * @return list<string>
     */
    public static function activeSharedTeams(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            if (($r['role'] ?? '') === self::ROLE_SHARED && ($r['status'] ?? '') === self::ACTIVE) {
                $out[] = (string) $r['team'];
            }
        }
        return $out;
    }

    /**
     * Names of the specialties asked for an opinion that has not been answered.
     *
     * @param list<array<string, mixed>> $rows
     * @return list<string>
     */
    public static function pendingConsults(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            if (($r['role'] ?? '') === self::ROLE_CONSULT && ($r['status'] ?? '') === self::REQUESTED) {
                $out[] = (string) $r['team'];
            }
        }
        return $out;
    }

    /**
     * Why a team cannot be added to shared care, or null if it can: it is the
     * primary team, or it already follows the patient. Names are compared
     * ignoring case and surrounding spaces.
     *
     * @param list<array<string, mixed>> $rows
     */
    public static function sharedConflict(array $rows, string $team, ?string $primary): ?string
    {
        $name = self::norm($team);
        if ($name === '') {
            return 'empty';
        }
        if ($primary !== null && self::norm($primary) === $name) {
            return 'primary';
        }
        foreach (self::activeSharedTeams($rows) as $active) {
            if (self::norm($active) === $name) {
                return 'duplicate';
            }
        }
        return null;
    }

    /**
     * When something recorded at $when may start: not in the future (a few
     * minutes of clock skew are allowed) and not before the ICU stay started.
     */
    public static function clampWhen(string $when, string $stayStart, ?int $now = null): string
    {
        $now ??= time();
        $ts = strtotime($when);
        if ($ts === false || $ts > $now + 300) {
            $ts = $now;
        }
        $out = date('Y-m-d H:i:s', $ts);
        return $stayStart !== '' && $out < $stayStart ? $stayStart : $out;
    }

    /**
     * A team starts following the patient.
     *
     * @throws \DomainException if it is the primary team or already follows the patient
     */
    public static function addShared(int $pid, int $encounter, string $team, string $when, string $user, ?string $note, ?string $professional = null): void
    {
        // The primary team is always the ICU team.
        $primary = function_exists('xl') ? xl('Intensive Care') : 'Intensive Care';
        $conflict = self::sharedConflict(self::forAdmission($encounter), $team, $primary);
        if ($conflict !== null) {
            throw new \DomainException($conflict);
        }
        QueryUtils::sqlInsert(
            "INSERT INTO " . self::TABLE . "
                (pid, encounter, role, team, professional, status, start_date, reason, user, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [$pid, $encounter, self::ROLE_SHARED, self::cut($team), self::blank($professional, self::MAX_PROFESSIONAL), self::ACTIVE, $when, self::blank($note), $user]
        );
    }

    /** The team stops following the patient during the stay. */
    public static function endShared(int $id, int $encounter, string $when, string $user): void
    {
        $row = self::find($id, $encounter);
        if ($row === null || $row['role'] !== self::ROLE_SHARED || $row['status'] !== self::ACTIVE) {
            throw new \InvalidArgumentException('Not an active shared care team of this admission.');
        }
        $end = $when < (string) $row['start_date'] ? (string) $row['start_date'] : $when;
        QueryUtils::sqlStatementThrowException(
            "UPDATE " . self::TABLE . " SET status = ?, end_date = ?, ended_by = ? WHERE id = ? AND status = ?",
            [self::ENDED, $end, $user, $id, self::ACTIVE]
        );
    }

    /** Another specialty is asked for an opinion. */
    public static function requestConsult(int $pid, int $encounter, string $team, string $when, string $user, ?string $reason): void
    {
        if (self::norm($team) === '') {
            throw new \DomainException('empty');
        }
        QueryUtils::sqlInsert(
            "INSERT INTO " . self::TABLE . "
                (pid, encounter, role, team, status, start_date, reason, user, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [$pid, $encounter, self::ROLE_CONSULT, self::cut($team), self::REQUESTED, $when, self::blank($reason), $user]
        );
    }

    /** The specialty answered: its recommendation and, if given, who answered stay recorded. */
    public static function answerConsult(int $id, int $encounter, string $when, string $user, string $recommendation, ?string $professional = null): void
    {
        $row = self::find($id, $encounter);
        if ($row === null || $row['role'] !== self::ROLE_CONSULT || $row['status'] !== self::REQUESTED) {
            throw new \InvalidArgumentException('Not a pending consultation of this admission.');
        }
        if (trim($recommendation) === '') {
            throw new \DomainException('empty');
        }
        $end = $when < (string) $row['start_date'] ? (string) $row['start_date'] : $when;
        QueryUtils::sqlStatementThrowException(
            "UPDATE " . self::TABLE . " SET status = ?, end_date = ?, recommendation = ?, professional = ?, answered_by = ?
              WHERE id = ? AND status = ?",
            [self::ANSWERED, $end, trim($recommendation), self::blank($professional, self::MAX_PROFESSIONAL), $user, $id, self::REQUESTED]
        );
    }

    /** A consultation requested by mistake or no longer needed. */
    public static function cancelConsult(int $id, int $encounter, string $user): void
    {
        $row = self::find($id, $encounter);
        if ($row === null || $row['role'] !== self::ROLE_CONSULT || $row['status'] !== self::REQUESTED) {
            throw new \InvalidArgumentException('Not a pending consultation of this admission.');
        }
        QueryUtils::sqlStatementThrowException(
            "UPDATE " . self::TABLE . " SET status = ?, ended_by = ?, end_date = NOW() WHERE id = ? AND status = ?",
            [self::CANCELLED, $user, $id, self::REQUESTED]
        );
    }

    /**
     * The patient leaves the ICU: the participation of the shared care teams in
     * this stay ends with it. Pending consultations stay pending, since the
     * specialty can still answer them.
     */
    public static function closeForDischarge(int $encounter, string $end): void
    {
        QueryUtils::sqlStatementThrowException(
            "UPDATE " . self::TABLE . " SET status = ?, end_date = GREATEST(start_date, ?)
              WHERE encounter = ? AND role = ? AND status = ?",
            [self::ENDED, $end, $encounter, self::ROLE_SHARED, self::ACTIVE]
        );
    }

    /**
     * Every team of the admission: shared care first, then consultations, the
     * most recent first within each.
     *
     * @return list<array<string, string|int|null>>
     */
    public static function forAdmission(int $encounter): array
    {
        /** @var list<array<string, string|int|null>> */
        return QueryUtils::fetchRecords(
            "SELECT * FROM " . self::TABLE . " WHERE encounter = ?
              ORDER BY role DESC, start_date DESC, id DESC",
            [$encounter]
        );
    }

    /** @return array<string, string|int|null>|null */
    private static function find(int $id, int $encounter): ?array
    {
        $row = QueryUtils::querySingleRow(
            "SELECT * FROM " . self::TABLE . " WHERE id = ? AND encounter = ?",
            [$id, $encounter]
        );
        return is_array($row) ? $row : null;
    }

    private static function cut(string $team): string
    {
        return mb_substr(trim($team), 0, self::MAX_TEAM);
    }

    private static function blank(?string $value, int $max = 255): ?string
    {
        $value = $value === null ? '' : trim($value);
        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    private static function norm(mixed $value): string
    {
        return mb_strtolower(trim(is_scalar($value) ? (string) $value : ''));
    }
}
