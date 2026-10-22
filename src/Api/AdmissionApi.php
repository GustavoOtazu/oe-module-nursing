<?php

/**
 * Admission API
 *
 * Lets the SIH web service (the IPS-Demon of ips_ws) keep the inpatient
 * admissions up to date through the OpenEMR REST API, instead of writing to
 * the database directly. The standard API creates patients and encounters but
 * cannot set the admission columns this module adds to form_encounter (record
 * number, location, discharge), so this resource does it, with the same rules
 * as the inpatient screens:
 *
 *  - an admission is an Inpatient encounter with its "New Patient Encounter"
 *    form row and the first movement of its location history;
 *  - a readmission has another record number and is another encounter;
 *  - a bed change is a transfer in the location history;
 *  - a discharge fills date_end (what closes the admission in the module)
 *    and out_date, and closes the history.
 *
 * Each write runs in one transaction, and repeating a request does not
 * duplicate anything (the record number identifies the admission).
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing\Api;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\Nursing\AdmissionMovements;
use OpenEMR\Services\FormService;

final class AdmissionApi
{
    /** Resource name in the route (/api/nursing_admission) and in the scopes. */
    public const RESOURCE = 'nursing_admission';

    /** form_encounter.carga_ws of the admissions created by the web service. */
    public const FROM_SIH = 'si';

    private const RECORD_NUMBER = '/^[A-Za-z0-9.\-]{1,40}$/';
    private const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
    private const LOCATION_MAX = 55;

    public function __construct(private readonly int $inpatientCatId)
    {
    }

    public static function fromDatabase(): self
    {
        $row = QueryUtils::querySingleRow(
            "SELECT pc_catid FROM openemr_postcalendar_categories WHERE pc_catname = ? LIMIT 1",
            ['Inpatient']
        );
        return new self(is_array($row) ? (int) ($row['pc_catid'] ?? 0) : 0);
    }

    // ------------------------------------------------------------------
    // Validation (no database, unit tested)
    // ------------------------------------------------------------------

    public static function validRecordNumber(mixed $value): bool
    {
        return is_string($value) && preg_match(self::RECORD_NUMBER, $value) === 1;
    }

    /**
     * 'Y-m-d H:i:s' from 'Y-m-d H:i:s', 'Y-m-d\TH:i:s' or an ISO 8601 value
     * with offset (converted to the server time zone); null if invalid.
     */
    public static function normalizeDateTime(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        $value = trim($value);
        foreach (['Y-m-d H:i:s', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i:sP', 'Y-m-d\TH:i:s.uP'] as $format) {
            $dt = \DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($dt !== false && \DateTimeImmutable::getLastErrors() === false) {
                return $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()))->format('Y-m-d H:i:s');
            }
        }
        return null;
    }

    /**
     * @param array<string, mixed> $location
     * @return array{departamento: ?string, servicio: ?string, cuarto: ?string, cama: ?string}
     */
    public static function cleanLocation(array $location): array
    {
        $out = [];
        foreach (AdmissionMovements::LOCATION as $col) {
            $v = isset($location[$col]) && is_scalar($location[$col]) ? trim((string) $location[$col]) : '';
            $out[$col] = $v === '' ? null : mb_substr($v, 0, self::LOCATION_MAX);
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $data
     * @return list<string> errors; empty when valid
     */
    public static function validateCreate(array $data): array
    {
        $errors = [];
        if (!is_string($data['patient_uuid'] ?? null) || preg_match(self::UUID, $data['patient_uuid']) !== 1) {
            $errors[] = 'patient_uuid is required and must be a UUID';
        }
        if (!self::validRecordNumber($data['nro_registro'] ?? null)) {
            $errors[] = 'nro_registro is required (letters, digits, "." or "-", up to 40)';
        }
        if (self::normalizeDateTime($data['fecha_ingreso'] ?? null) === null) {
            $errors[] = 'fecha_ingreso is required (Y-m-d H:i:s or ISO 8601)';
        }
        $location = self::cleanLocation(is_array($data['ubicacion'] ?? null) ? $data['ubicacion'] : []);
        if ($location['cama'] === null) {
            $errors[] = 'ubicacion.cama is required';
        }
        return $errors;
    }

    /**
     * @param array<string, mixed> $data
     * @return list<string>
     */
    public static function validateUpdate(array $data): array
    {
        $errors = [];
        $hasLocation = array_key_exists('ubicacion', $data);
        $hasDischarge = array_key_exists('alta', $data);
        if (!$hasLocation && !$hasDischarge) {
            $errors[] = 'nothing to update: send ubicacion and/or alta';
        }
        if ($hasLocation) {
            if (!is_array($data['ubicacion'])) {
                $errors[] = 'ubicacion must be an object';
            } else {
                if (self::cleanLocation($data['ubicacion'])['cama'] === null) {
                    $errors[] = 'ubicacion.cama is required';
                }
                if (isset($data['ubicacion']['desde']) && self::normalizeDateTime($data['ubicacion']['desde']) === null) {
                    $errors[] = 'ubicacion.desde must be a date and time';
                }
            }
        }
        if ($hasDischarge && self::normalizeDateTime($data['alta']) === null) {
            $errors[] = 'alta must be a date and time';
        }
        return $errors;
    }

    // ------------------------------------------------------------------
    // Operations
    // ------------------------------------------------------------------

    /**
     * Search by record number (nro_registro=...) or the open admissions loaded
     * by the web service (origen=sih&estado=abierta).
     *
     * @param array<string, mixed> $query
     * @return array{status: int, body: array<string, mixed>}
     */
    public function search(array $query): array
    {
        $where = ['f.pc_catid = ?'];
        $binds = [$this->inpatientCatId];
        if (isset($query['nro_registro'])) {
            if (!self::validRecordNumber($query['nro_registro'])) {
                return self::error(400, ['invalid nro_registro']);
            }
            $where[] = 'f.nro_registro = ?';
            $binds[] = $query['nro_registro'];
        }
        if (($query['origen'] ?? null) === 'sih') {
            $where[] = 'f.carga_ws = ?';
            $binds[] = self::FROM_SIH;
        }
        if (($query['estado'] ?? null) === 'abierta') {
            $where[] = 'f.date_end IS NULL';
        }
        if (count($where) === 1) {
            return self::error(400, ['send nro_registro, origen=sih or estado=abierta']);
        }
        /** @var list<array<string, mixed>> $rows */
        $rows = QueryUtils::fetchRecords(self::SELECT . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY f.date', $binds);
        return ['status' => 200, 'body' => ['data' => array_map(self::present(...), $rows)]];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{status: int, body: array<string, mixed>}
     */
    public function create(array $data, string $user, string $group): array
    {
        $errors = self::validateCreate($data);
        if ($errors !== []) {
            return self::error(400, $errors);
        }
        if ($this->inpatientCatId <= 0) {
            return self::error(500, ['the Inpatient encounter category does not exist']);
        }
        $nro = (string) $data['nro_registro'];
        $date = (string) self::normalizeDateTime($data['fecha_ingreso']);
        $location = self::cleanLocation((array) $data['ubicacion']);

        $patient = QueryUtils::querySingleRow(
            "SELECT pid FROM patient_data WHERE uuid = ?",
            [UuidRegistry::uuidToBytes((string) $data['patient_uuid'])]
        );
        if (!is_array($patient)) {
            return self::error(404, ['patient not found']);
        }
        $pid = (int) $patient['pid'];

        $existing = $this->findByRecordNumber($nro);
        if ($existing !== null) {
            if ((int) $existing['pid'] !== $pid) {
                return self::error(409, ['nro_registro belongs to an admission of another patient']);
            }
            // Same request again (e.g. after a timeout): nothing to do.
            return ['status' => 200, 'body' => ['data' => self::present($existing), 'existente' => true]];
        }
        $open = QueryUtils::querySingleRow(
            "SELECT nro_registro FROM form_encounter WHERE pid = ? AND pc_catid = ? AND date_end IS NULL LIMIT 1",
            [$pid, $this->inpatientCatId]
        );
        if (is_array($open)) {
            // e.g. an admission recorded by hand while the web service was down: a person decides.
            return self::error(409, ['the patient already has an open admission']);
        }

        $userRow = QueryUtils::querySingleRow("SELECT id, facility_id FROM users WHERE username = ?", [$user]);
        $providerId = is_array($userRow) ? (int) ($userRow['id'] ?? 0) : 0;
        $facilityId = is_array($userRow) ? (int) ($userRow['facility_id'] ?? 0) : 0;
        $facilityRow = $facilityId > 0 ? QueryUtils::querySingleRow("SELECT name FROM facility WHERE id = ?", [$facilityId]) : false;
        $facility = is_array($facilityRow) ? (string) ($facilityRow['name'] ?? '') : '';

        $feId = QueryUtils::inTransaction(function () use ($pid, $nro, $date, $location, $facility, $facilityId, $providerId, $user, $group): int {
            $encounter = (int) QueryUtils::generateId();
            $uuid = (new UuidRegistry(['table_name' => 'form_encounter']))->createUuid();
            $feId = (int) QueryUtils::sqlInsert(
                "INSERT INTO form_encounter
                    (pid, encounter, uuid, date, onset_date, facility, facility_id, pc_catid, provider_id, billing_facility,
                     nro_registro, departamento, servicio, cuarto, cama, carga_ws)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$pid, $encounter, $uuid, $date, $date, $facility, $facilityId, $this->inpatientCatId, $providerId, $facilityId,
                 $nro, $location['departamento'], $location['servicio'], $location['cuarto'], $location['cama'], self::FROM_SIH]
            );
            (new FormService())->addForm($encounter, "New Patient Encounter", $feId, "newpatient", $pid, "0", $date, $user, $group);
            AdmissionMovements::sync($this->inpatientCatId, $feId);
            return $feId;
        });

        $created = $this->findById((int) $feId);
        return ['status' => 201, 'body' => ['data' => $created !== null ? self::present($created) : null]];
    }

    /**
     * Location change and/or discharge of the admission with that record number.
     *
     * @param array<string, mixed> $data
     * @return array{status: int, body: array<string, mixed>}
     */
    public function update(string $nro, array $data, string $user): array
    {
        if (!self::validRecordNumber($nro)) {
            return self::error(400, ['invalid nro_registro']);
        }
        $errors = self::validateUpdate($data);
        if ($errors !== []) {
            return self::error(400, $errors);
        }
        $adm = $this->findByRecordNumber($nro);
        if ($adm === null) {
            return self::error(404, ['admission not found']);
        }
        $feId = (int) $adm['id'];
        $discharged = $adm['date_end'] !== null;

        if (is_array($data['ubicacion'] ?? null)) {
            if ($discharged) {
                return self::error(409, ['the admission is already discharged']);
            }
            $location = self::cleanLocation($data['ubicacion']);
            $when = self::normalizeDateTime($data['ubicacion']['desde'] ?? null) ?? date('Y-m-d H:i:s');
            if ($when > date('Y-m-d H:i:s')) {
                $when = date('Y-m-d H:i:s');
            }
            AdmissionMovements::sync($this->inpatientCatId, $feId);
            $encounter = (int) $adm['encounter'];
            $current = AdmissionMovements::current($encounter);
            if ($current === null || AdmissionMovements::locationChanged($current, $location)) {
                // The team stays the same; only the place changes.
                $location['equipo'] = $current['equipo'] ?? null;
                if ($current !== null && $when < (string) $current['start_date']) {
                    $when = (string) $current['start_date'];
                }
                try {
                    AdmissionMovements::transfer((int) $adm['pid'], $encounter, $location, $when, $user, null, AdmissionMovements::SOURCE_SIH);
                } catch (\DomainException) {
                    // already there
                }
            }
        }

        if (array_key_exists('alta', $data) && !$discharged) {
            $end = (string) self::normalizeDateTime($data['alta']);
            if ($end < (string) $adm['date']) {
                return self::error(409, ['alta is earlier than the admission']);
            }
            QueryUtils::inTransaction(function () use ($feId, $end): void {
                QueryUtils::sqlStatementThrowException(
                    "UPDATE form_encounter SET date_end = ?, out_date = ? WHERE id = ? AND date_end IS NULL",
                    [$end, substr($end, 0, 10), $feId]
                );
                AdmissionMovements::close($feId, $end);
            });
        }

        $updated = $this->findById($feId);
        return ['status' => 200, 'body' => ['data' => $updated !== null ? self::present($updated) : null]];
    }

    // ------------------------------------------------------------------

    private const SELECT = "SELECT f.id, f.encounter, f.uuid, f.pid, p.uuid AS patient_uuid, f.nro_registro, f.date,
            f.date_end, f.departamento, f.servicio, f.cuarto, f.cama, f.carga_ws
       FROM form_encounter f
       JOIN patient_data p ON p.pid = f.pid";

    /** @return array<string, mixed>|null */
    private function findByRecordNumber(string $nro): ?array
    {
        $row = QueryUtils::querySingleRow(
            self::SELECT . " WHERE f.nro_registro = ? AND f.pc_catid = ? ORDER BY f.id DESC LIMIT 1",
            [$nro, $this->inpatientCatId]
        );
        return is_array($row) ? $row : null;
    }

    /** @return array<string, mixed>|null */
    private function findById(int $id): ?array
    {
        $row = QueryUtils::querySingleRow(self::SELECT . " WHERE f.id = ?", [$id]);
        return is_array($row) ? $row : null;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function present(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'encounter' => (int) $row['encounter'],
            'uuid' => empty($row['uuid']) ? null : UuidRegistry::uuidToString($row['uuid']),
            'patient_uuid' => empty($row['patient_uuid']) ? null : UuidRegistry::uuidToString($row['patient_uuid']),
            'nro_registro' => $row['nro_registro'],
            'fecha_ingreso' => $row['date'],
            'fecha_alta' => $row['date_end'],
            'ubicacion' => [
                'departamento' => $row['departamento'],
                'servicio' => $row['servicio'],
                'cuarto' => $row['cuarto'],
                'cama' => $row['cama'],
            ],
            'origen' => ($row['carga_ws'] ?? null) === self::FROM_SIH ? 'sih' : 'manual',
        ];
    }

    /**
     * @param list<string> $errors
     * @return array{status: int, body: array<string, mixed>}
     */
    private static function error(int $status, array $errors): array
    {
        return ['status' => $status, 'body' => ['errors' => $errors]];
    }
}
