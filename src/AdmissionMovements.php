<?php

/**
 * Admission Movements
 *
 * Keeps the location history of an inpatient admission: one row per stay in a
 * department / service / ward / bed, with its primary team, start and end. The
 * admission's form_encounter row keeps only the current location (the inpatient
 * list, the vital signs dashboard and the SIH web service read and write it
 * there), and every change of it is kept here instead of being overwritten.
 *
 * Changes made outside the module (e.g. the SIH web service updating the bed)
 * are detected by sync(): when the location in form_encounter differs from the
 * current movement, the movement is closed and a new one is opened.
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

final class AdmissionMovements
{
    public const TABLE = 'nursing_admission_movement';

    /** Location columns shared with form_encounter. */
    public const LOCATION = ['departamento', 'servicio', 'cuarto', 'cama'];

    public const CURRENT = 'current';
    public const RESERVED = 'reserved';
    public const ENDED = 'ended';
    public const CANCELLED = 'cancelled';

    /** admission: first location; manual: recorded in the module; detected: changed outside it. */
    public const SOURCE_ADMISSION = 'admission';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_DETECTED = 'detected';

    /**
     * Whether two locations differ. Empty and missing values are the same, and
     * the comparison ignores case and surrounding spaces ("a" and "A " are the
     * same ward).
     *
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     */
    public static function locationChanged(array $a, array $b): bool
    {
        foreach (self::LOCATION as $col) {
            if (self::norm($a[$col] ?? null) !== self::norm($b[$col] ?? null)) {
                return true;
            }
        }
        return false;
    }

    /**
     * When a movement ends for a discharge or death recorded with a date only:
     * now if it is today, the end of that day if it is in the past, and never
     * before the movement started.
     */
    public static function endFor(string $date, string $start, ?int $now = null): string
    {
        $now ??= time();
        $day = strtotime($date);
        if ($day === false || date('Y-m-d', $day) >= date('Y-m-d', $now)) {
            $end = date('Y-m-d H:i:s', $now);
        } else {
            $end = date('Y-m-d', $day) . ' 23:59:59';
        }
        return $start !== '' && $end < $start ? $start : $end;
    }

    /**
     * Brings the history of open admissions up to date with form_encounter:
     * opens the first movement of an admission that has none, and records a
     * movement when the location was changed outside the module.
     *
     * @param int $encounterId form_encounter.id; 0 = every open admission of the category
     */
    public static function sync(int $inpatientCatId, int $encounterId = 0): void
    {
        if ($inpatientCatId <= 0 && $encounterId <= 0) {
            return;
        }
        $where = $encounterId > 0 ? 'f.id = ?' : 'f.pc_catid = ?';
        /** @var list<array<string, string|int|null>> $rows */
        $rows = QueryUtils::fetchRecords(
            "SELECT f.pid, f.encounter, f.date, f.departamento, f.servicio, f.cuarto, f.cama,
                    m.id AS m_id, m.departamento AS m_departamento, m.servicio AS m_servicio,
                    m.cuarto AS m_cuarto, m.cama AS m_cama, m.equipo AS m_equipo
               FROM form_encounter f
          LEFT JOIN " . self::TABLE . " m ON m.encounter = f.encounter AND m.status = ?
              WHERE $where AND f.date_end IS NULL",
            [self::CURRENT, $encounterId > 0 ? $encounterId : $inpatientCatId]
        );
        foreach ($rows as $r) {
            $location = self::pick($r);
            if ($r['m_id'] === null) {
                self::open((int) $r['pid'], (int) $r['encounter'], $location + ['equipo' => null], (string) $r['date'], self::SOURCE_ADMISSION, null, null);
                continue;
            }
            $current = [];
            foreach (self::LOCATION as $col) {
                $current[$col] = $r['m_' . $col];
            }
            if (self::locationChanged($location, $current)) {
                // The team usually stays with the patient; it is changed with a transfer.
                self::move((int) $r['pid'], (int) $r['encounter'], $location + ['equipo' => $r['m_equipo']], date('Y-m-d H:i:s'), self::SOURCE_DETECTED, null, null, (int) $r['m_id']);
            }
        }
    }

    /**
     * Records a transfer: closes the current movement at $when and opens the new
     * location, which also becomes the admission's location in form_encounter.
     * A pending reservation for the same bed is taken as fulfilled.
     *
     * @param array<string, string|null> $location departamento, servicio, cuarto, cama, equipo
     */
    public static function transfer(int $pid, int $encounter, array $location, string $when, string $user, ?string $note): void
    {
        $current = self::current($encounter);
        if ($current !== null && $when < (string) $current['start_date']) {
            throw new \InvalidArgumentException('The transfer cannot be earlier than the start of the current location.');
        }
        // Same bed and same team is not a movement; same bed with another team is (a change of team).
        if ($current !== null && !self::locationChanged($current, $location)
            && self::norm($current['equipo'] ?? null) === self::norm($location['equipo'] ?? null)) {
            throw new \DomainException('The patient is already in that location.');
        }
        $reservation = null;
        foreach (self::reservations($encounter) as $res) {
            if (!self::locationChanged($res, $location)) {
                $reservation = $res;
                break;
            }
        }
        if ($reservation === null) {
            QueryUtils::inTransaction(function () use ($pid, $encounter, $location, $when, $user, $note, $current): void {
                self::move($pid, $encounter, $location, $when, self::SOURCE_MANUAL, $user, $note, $current !== null ? (int) $current['id'] : 0);
            });
            return;
        }
        // The reserved bed becomes the current location from the moment the patient arrives.
        QueryUtils::inTransaction(function () use ($encounter, $location, $when, $user, $note, $current, $reservation): void {
            if ($current !== null) {
                QueryUtils::sqlStatementThrowException(
                    "UPDATE " . self::TABLE . " SET status = ?, end_date = ? WHERE id = ? AND status = ?",
                    [self::ENDED, $when, (int) $current['id'], self::CURRENT]
                );
            }
            QueryUtils::sqlStatementThrowException(
                "UPDATE " . self::TABLE . " SET status = ?, start_date = ?, equipo = COALESCE(?, equipo),
                        note = COALESCE(?, note), arrived_by = ? WHERE id = ?",
                [self::CURRENT, $when, self::blank($location['equipo'] ?? null), self::blank($note), $user, (int) $reservation['id']]
            );
            self::writeEncounter($encounter, $location);
        });
    }

    /**
     * Reserves a bed the patient has not reached yet (e.g. still in the ED). It
     * does not change the current location.
     *
     * @param array<string, string|null> $location
     */
    public static function reserve(int $pid, int $encounter, array $location, string $when, string $user, ?string $note): void
    {
        QueryUtils::sqlInsert(
            "INSERT INTO " . self::TABLE . "
                (pid, encounter, departamento, servicio, cuarto, cama, equipo, start_date, status, source, user, note, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [$pid, $encounter, ...self::values($location), $when, self::RESERVED, self::SOURCE_MANUAL, $user, self::blank($note)]
        );
    }

    /** The patient arrived at a reserved bed. */
    public static function confirm(int $movementId, int $encounter, string $when, string $user): void
    {
        $res = self::find($movementId, $encounter);
        if ($res === null || $res['status'] !== self::RESERVED) {
            throw new \InvalidArgumentException('Not a pending reservation of this admission.');
        }
        self::transfer((int) $res['pid'], $encounter, $res, $when, $user, null);
    }

    public static function cancel(int $movementId, int $encounter, string $user): void
    {
        $res = self::find($movementId, $encounter);
        if ($res === null || $res['status'] !== self::RESERVED) {
            throw new \InvalidArgumentException('Not a pending reservation of this admission.');
        }
        QueryUtils::sqlStatementThrowException(
            "UPDATE " . self::TABLE . " SET status = ?, corrected_by = ?, corrected_at = NOW() WHERE id = ?",
            [self::CANCELLED, $user, $movementId]
        );
    }

    /**
     * Fixes a data entry error in the current location without recording a
     * transfer. The row keeps who corrected it and when.
     *
     * @param array<string, string|null> $location
     */
    public static function correct(int $encounter, array $location, string $user): void
    {
        $current = self::current($encounter);
        if ($current === null) {
            throw new \InvalidArgumentException('The admission has no current location.');
        }
        QueryUtils::inTransaction(function () use ($encounter, $location, $user, $current): void {
            QueryUtils::sqlStatementThrowException(
                "UPDATE " . self::TABLE . " SET departamento = ?, servicio = ?, cuarto = ?, cama = ?, equipo = ?,
                        corrected_by = ?, corrected_at = NOW() WHERE id = ?",
                [...self::values($location), $user, (int) $current['id']]
            );
            self::writeEncounter($encounter, $location);
        });
    }

    /** Closes the history when the patient leaves (discharge or death). */
    public static function close(int $encounterId, string $date): void
    {
        $fe = QueryUtils::querySingleRow("SELECT encounter FROM form_encounter WHERE id = ?", [$encounterId]);
        if (!is_array($fe)) {
            return;
        }
        $encounter = (int) $fe['encounter'];
        $current = self::current($encounter);
        if ($current !== null) {
            QueryUtils::sqlStatementThrowException(
                "UPDATE " . self::TABLE . " SET status = ?, end_date = ? WHERE id = ?",
                [self::ENDED, self::endFor($date, (string) $current['start_date']), (int) $current['id']]
            );
        }
        QueryUtils::sqlStatementThrowException(
            "UPDATE " . self::TABLE . " SET status = ? WHERE encounter = ? AND status = ?",
            [self::CANCELLED, $encounter, self::RESERVED]
        );
    }

    /** @return array<string, string|int|null>|null */
    public static function current(int $encounter): ?array
    {
        $row = QueryUtils::querySingleRow(
            "SELECT * FROM " . self::TABLE . " WHERE encounter = ? AND status = ? ORDER BY start_date DESC, id DESC LIMIT 1",
            [$encounter, self::CURRENT]
        );
        return is_array($row) ? $row : null;
    }

    /** @return list<array<string, string|int|null>> */
    public static function reservations(int $encounter): array
    {
        /** @var list<array<string, string|int|null>> */
        return QueryUtils::fetchRecords(
            "SELECT * FROM " . self::TABLE . " WHERE encounter = ? AND status = ? ORDER BY start_date, id",
            [$encounter, self::RESERVED]
        );
    }

    /**
     * Every movement of the admission, most recent first.
     *
     * @return list<array<string, string|int|null>>
     */
    public static function history(int $encounter): array
    {
        /** @var list<array<string, string|int|null>> */
        return QueryUtils::fetchRecords(
            "SELECT * FROM " . self::TABLE . " WHERE encounter = ? ORDER BY start_date DESC, id DESC",
            [$encounter]
        );
    }

    /**
     * Closes $currentId (if any) at $when and opens $location as current. The
     * close only counts if the row was still current, so two requests detecting
     * the same change do not open it twice.
     *
     * @param array<string, string|int|null> $location
     */
    private static function move(int $pid, int $encounter, array $location, string $when, string $source, ?string $user, ?string $note, int $currentId): void
    {
        if ($currentId > 0) {
            QueryUtils::sqlStatementThrowException(
                "UPDATE " . self::TABLE . " SET status = ?, end_date = ? WHERE id = ? AND status = ?",
                [self::ENDED, $when, $currentId, self::CURRENT]
            );
            if (QueryUtils::affectedRows() < 1) {
                return;
            }
        }
        self::open($pid, $encounter, $location, $when, $source, $user, $note);
        self::writeEncounter($encounter, $location);
    }

    /** @param array<string, string|int|null> $location */
    private static function open(int $pid, int $encounter, array $location, string $when, string $source, ?string $user, ?string $note): void
    {
        QueryUtils::sqlInsert(
            "INSERT INTO " . self::TABLE . "
                (pid, encounter, departamento, servicio, cuarto, cama, equipo, start_date, status, source, user, note, created_at)
             SELECT ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW() FROM DUAL
              WHERE NOT EXISTS (SELECT 1 FROM " . self::TABLE . " WHERE encounter = ? AND status = ?)",
            [$pid, $encounter, ...self::values($location), $when, self::CURRENT, $source, $user, self::blank($note), $encounter, self::CURRENT]
        );
    }

    /** @param array<string, string|int|null> $location */
    private static function writeEncounter(int $encounter, array $location): void
    {
        QueryUtils::sqlStatementThrowException(
            "UPDATE form_encounter SET departamento = ?, servicio = ?, cuarto = ?, cama = ? WHERE encounter = ?",
            [...array_slice(self::values($location), 0, 4), $encounter]
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

    /**
     * @param array<string, string|int|null> $row
     * @return array<string, string|null>
     */
    private static function pick(array $row): array
    {
        $out = [];
        foreach (self::LOCATION as $col) {
            $out[$col] = self::blank(isset($row[$col]) ? (string) $row[$col] : null);
        }
        return $out;
    }

    /**
     * departamento, servicio, cuarto, cama, equipo, in that order.
     *
     * @param array<string, string|int|null> $location
     * @return list<string|null>
     */
    private static function values(array $location): array
    {
        $out = [];
        foreach ([...self::LOCATION, 'equipo'] as $col) {
            $out[] = self::blank(isset($location[$col]) ? (string) $location[$col] : null);
        }
        return $out;
    }

    private static function blank(?string $value): ?string
    {
        $value = $value === null ? '' : trim($value);
        return $value === '' ? null : $value;
    }

    private static function norm(mixed $value): string
    {
        return strtolower(trim(is_scalar($value) ? (string) $value : ''));
    }
}
