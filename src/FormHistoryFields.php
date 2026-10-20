<?php

/**
 * Form History Fields
 *
 * The rows of the history table of each nursing form (see FormHistory): the
 * column, its label (an xl() key), how its value is shown and its unit.
 *
 * Types: bool (Yes/No, with its observation column), num (number with unit),
 * enum and set (codes shown with their label), text, time (HH:MM), score
 * (a calculated total, in bold) and take (the vital signs take used).
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing;

final class FormHistoryFields
{
    private const SHIFTS = ['MANANA' => 'Morning', 'TARDE' => 'Afternoon', 'NOCHE' => 'Night'];

    /**
     * @return list<array{col: string, label: string, type: string, unit?: string, obs?: string, map?: array<string, string>}>|null
     */
    public static function for(string $formdir): ?array
    {
        $bool = static fn(string $col, string $label): array => ['col' => $col, 'label' => $label, 'type' => 'bool', 'obs' => 'obs_' . $col];
        $num = static fn(string $col, string $label, string $unit = ''): array => ['col' => $col, 'label' => $label, 'type' => 'num', 'unit' => $unit];

        return match ($formdir) {
            'curaciones' => [
                $bool('herida_operatoria', 'Surgical Wound'),
                $bool('traqueostomia', 'Tracheostomy'),
                $bool('ostomias', 'Ostomies'),
                $bool('escaras', 'Pressure Sores'),
                $bool('via_venosa_central', 'Central Venous Line'),
                $bool('via_venosa', 'Peripheral IV Line'),
                ['col' => 'hora_operacion', 'label' => 'Care Time', 'type' => 'time'],
            ],
            'aplicaciones' => [
                $bool('medicamentos', 'Medications'),
                $bool('sueros', 'Saline Solutions'),
                $bool('vacunas', 'Vaccines'),
                $bool('expansiones', 'Plasma Expanders'),
                $bool('sangre', 'Blood and Blood Products'),
                ['col' => 'hora_registro', 'label' => 'Record Time', 'type' => 'time'],
            ],
            'cuidados' => [
                ['col' => 'posicion_paciente', 'label' => 'Patient Position', 'type' => 'text', 'obs' => 'obs_posicion_paciente'],
                $bool('enjuague_bucal', 'Oral Rinse'),
                $bool('higiene_manos', 'Hand Hygiene Pre/Post Suctioning'),
                $bool('aspirado_secreciones', 'Secretion Suctioning with Gloves and Assistant'),
                $bool('suspension_sedacion', 'Daily Sedation Suspension and Extubation Evaluation'),
                $bool('medicion_cuff', 'Cuff Pressure Measurement'),
                ['col' => 'hora_cuidado', 'label' => 'Care Time', 'type' => 'time'],
            ],
            'evaluaciones' => [
                ['col' => 'conciencia', 'label' => 'Consciousness', 'type' => 'text', 'obs' => 'obs_conciencia'],
                ['col' => 'tono', 'label' => 'Muscle Tone', 'type' => 'text', 'obs' => 'obs_tono'],
                ['col' => 'pupilas', 'label' => 'Pupils', 'type' => 'text', 'obs' => 'obs_pupilas'],
                ['col' => 'mucosas', 'label' => 'Mucous Membranes', 'type' => 'text', 'obs' => 'obs_mucosas'],
                ['col' => 'glasgow_ojos', 'label' => 'Eye Opening', 'type' => 'text', 'obs' => 'obs_glasgow_ojos'],
                ['col' => 'glasgow_motora', 'label' => 'Motor Response', 'type' => 'text', 'obs' => 'obs_glasgow_motora'],
                ['col' => 'glasgow_verbal', 'label' => 'Verbal Response', 'type' => 'text', 'obs' => 'obs_glasgow_verbal'],
                ['col' => 'glasgow_total', 'label' => 'Glasgow Total Score', 'type' => 'score', 'unit' => '/15'],
                ['col' => 'hora_evaluacion', 'label' => 'Evaluation Time', 'type' => 'time'],
            ],
            'registro_vm' => [
                ['col' => 'modo_ventilacion', 'label' => 'Ventilation Mode', 'type' => 'enum', 'obs' => 'obs_modo',
                    'map' => ['ESPONTANEA' => 'Spontaneous', 'VENTILACION MECANICA' => 'Mechanical Ventilation']],
                $bool('presion', 'Pressure'),
                $bool('volumen', 'Volume'),
                $bool('simv', 'SIMV'),
                $bool('psv', 'PSV'),
                $bool('otros', 'Other'),
                $bool('frecuencia_respiratoria', 'Respiratory Rate'),
                $bool('p_inspiratorio', 'P.Inspiratory / T.Inspiratory'),
                $bool('p_media', 'P.Mean / PEEP'),
                $bool('p_max', 'P.Max / P.Plateau'),
                $bool('chst', 'CHST / CDIN'),
                $bool('disparo', 'Trigger F/P'),
                $bool('fvt', 'F / VT'),
                $bool('vol_tidal', 'Tidal Volume / Flow'),
                $bool('vm_programado', 'Programmed/Measured MV'),
                $bool('petco2', 'PETCO2'),
                $bool('vdvt', 'VD / VT'),
                $bool('ko2', 'KO2'),
                ['col' => 'hora_registro', 'label' => 'Record Time', 'type' => 'time'],
            ],
            'balance_hidrico' => [
                ['col' => 'turno', 'label' => 'Shift', 'type' => 'enum', 'map' => self::SHIFTS],
                ['col' => 'hora_registro', 'label' => 'Record Time', 'type' => 'time'],
                $num('ing_via_oral', 'Oral intake', 'mL'),
                $num('ing_enteral', 'Enteral nutrition', 'mL'),
                $num('ing_parenteral', 'Parenteral nutrition', 'mL'),
                $num('ing_sueros', 'IV fluids', 'mL'),
                $num('ing_medicacion', 'IV medication', 'mL'),
                $num('ing_hemoderivados', 'Blood products', 'mL'),
                $num('ing_otros', 'Other intake', 'mL'),
                ['col' => 'total_ingresos', 'label' => 'Total intake', 'type' => 'score', 'unit' => 'mL'],
                $num('eg_diuresis', 'Urine output', 'mL'),
                $num('eg_drenajes', 'Drains', 'mL'),
                $num('eg_sng', 'Nasogastric tube', 'mL'),
                $num('eg_vomitos', 'Vomiting', 'mL'),
                $num('eg_deposiciones', 'Stools', 'mL'),
                $num('eg_perdidas_insensibles', 'Insensible losses', 'mL'),
                $num('eg_otros', 'Other output', 'mL'),
                ['col' => 'total_egresos', 'label' => 'Total output', 'type' => 'score', 'unit' => 'mL'],
                ['col' => 'balance', 'label' => 'Fluid Balance', 'type' => 'score', 'unit' => 'mL'],
                ['col' => 'observaciones', 'label' => 'Observations', 'type' => 'text'],
            ],
            'alimentacion' => [
                ['col' => 'hora_registro', 'label' => 'Record Time', 'type' => 'time'],
                ['col' => 'tipo_alimentacion', 'label' => 'Feeding Type', 'type' => 'enum',
                    'map' => ['ORAL' => 'Oral', 'ENTERAL' => 'Enteral', 'PARENTERAL' => 'Parenteral', 'MIXTA' => 'Mixed feeding', 'AYUNO' => 'Fasting']],
                ['col' => 'via_enteral', 'label' => 'Enteral Route', 'type' => 'enum',
                    'map' => ['SNG' => 'Nasogastric tube', 'SNY' => 'Nasojejunal tube', 'GASTROSTOMIA' => 'Gastrostomy', 'YEYUNOSTOMIA' => 'Jejunostomy']],
                ['col' => 'modalidad', 'label' => 'Modality', 'type' => 'enum',
                    'map' => ['CONTINUA' => 'Continuous', 'INTERMITENTE' => 'Intermittent', 'BOLO' => 'Bolus']],
                ['col' => 'formula', 'label' => 'Formula / Diet', 'type' => 'text'],
                $num('volumen_ml', 'Volume', 'mL'),
                $num('velocidad_ml_h', 'Infusion Rate', 'mL/h'),
                $num('residuo_gastrico_ml', 'Gastric Residual', 'mL'),
                ['col' => 'tolerancia', 'label' => 'Tolerance', 'type' => 'enum',
                    'map' => ['BUENA' => 'Good', 'REGULAR' => 'Fair', 'MALA' => 'Poor']],
                ['col' => 'signos_intolerancia', 'label' => 'Signs of Intolerance', 'type' => 'set',
                    'map' => ['VOMITOS' => 'Vomiting', 'DISTENSION' => 'Abdominal distension', 'DIARREA' => 'Diarrhea', 'RESIDUO_ALTO' => 'High gastric residual']],
                ['col' => 'observaciones', 'label' => 'Observations', 'type' => 'text'],
            ],
            'escala_sofa' => [
                ['col' => 'hora_registro', 'label' => 'Record Time', 'type' => 'time'],
                ['col' => 'vitals_id', 'label' => 'Vital signs take used', 'type' => 'take'],
                $num('pao2', 'PaO2', 'mmHg'),
                $num('fio2', 'FiO2'),
                $num('pafi', 'PaO2/FiO2 ratio'),
                ['col' => 'soporte_respiratorio', 'label' => 'Mechanical ventilation / respiratory support', 'type' => 'bool'],
                $num('plaquetas', 'Platelets', 'x10³/µL'),
                $num('bilirrubina', 'Bilirubin', 'mg/dL'),
                $num('pam', 'Mean arterial pressure (MAP)', 'mmHg'),
                $num('dopamina', 'Dopamine', 'µg/kg/min'),
                ['col' => 'dobutamina', 'label' => 'Dobutamine (any dose)', 'type' => 'bool'],
                $num('epinefrina', 'Epinephrine', 'µg/kg/min'),
                $num('norepinefrina', 'Norepinephrine', 'µg/kg/min'),
                $num('glasgow', 'Glasgow Coma Scale'),
                $num('creatinina', 'Creatinine', 'mg/dL'),
                $num('diuresis_24h', 'Urine output', 'mL/24h'),
                ['col' => 'sofa_total', 'label' => 'SOFA', 'type' => 'score', 'unit' => '/24'],
                ['col' => 'observaciones', 'label' => 'Observations', 'type' => 'text'],
            ],
            'escala_apache' => [
                ['col' => 'hora_registro', 'label' => 'Record Time', 'type' => 'time'],
                ['col' => 'vitals_id', 'label' => 'Vital signs take used', 'type' => 'take'],
                $num('temperatura', 'Rectal temperature', '°C'),
                $num('pam', 'Mean arterial pressure', 'mmHg'),
                $num('frecuencia_cardiaca', 'Heart rate', 'bpm'),
                $num('frecuencia_respiratoria', 'Respiratory rate', 'rpm'),
                $num('fio2', 'FiO2'),
                $num('pao2', 'PaO2', 'mmHg'),
                $num('paco2', 'PaCO2', 'mmHg'),
                $num('ph', 'Arterial pH'),
                $num('bicarbonato', 'Serum HCO3', 'mmol/L'),
                $num('sodio', 'Sodium', 'mmol/L'),
                $num('potasio', 'Potassium', 'mmol/L'),
                $num('creatinina', 'Creatinine', 'mg/dL'),
                ['col' => 'insuficiencia_renal_aguda', 'label' => 'Acute renal failure', 'type' => 'bool'],
                $num('hematocrito', 'Hematocrit', '%'),
                $num('leucocitos', 'White blood cells', 'x10³/µL'),
                $num('glasgow', 'Glasgow Coma Scale'),
                $num('edad', 'Age', 'years'),
                ['col' => 'enfermedad_cronica', 'label' => 'History of severe organ insufficiency or immunocompromise', 'type' => 'bool'],
                ['col' => 'tipo_ingreso', 'label' => 'Admission type', 'type' => 'enum',
                    'map' => ['NO_QUIRURGICO' => 'Non-operative', 'URGENCIA' => 'Emergency postoperative', 'ELECTIVO' => 'Elective postoperative']],
                ['col' => 'apache_total', 'label' => 'APACHE II', 'type' => 'score'],
                ['col' => 'observaciones', 'label' => 'Observations', 'type' => 'text'],
            ],
            'plan_cuidados' => [
                ['col' => 'turno', 'label' => 'Shift', 'type' => 'enum', 'map' => self::SHIFTS],
                ['col' => 'hora_registro', 'label' => 'Record Time', 'type' => 'time'],
                ['col' => 'diagnostico_enfermeria', 'label' => 'Nursing diagnosis', 'type' => 'text'],
                ['col' => 'codigo_diagnostico', 'label' => 'Diagnosis code (optional)', 'type' => 'text'],
                ['col' => 'objetivo', 'label' => 'Goal / expected outcome', 'type' => 'text'],
                ['col' => 'intervenciones', 'label' => 'Nursing interventions', 'type' => 'text'],
                ['col' => 'evaluacion_resultado', 'label' => 'Outcome evaluation', 'type' => 'enum',
                    'map' => ['LOGRADO' => 'Achieved', 'PARCIAL' => 'Partially achieved', 'NO_LOGRADO' => 'Not achieved', 'EN_CURSO' => 'In progress']],
                ['col' => 'evolucion', 'label' => 'Shift progress note', 'type' => 'text'],
                ['col' => 'observaciones', 'label' => 'Observations', 'type' => 'text'],
            ],
            default => null,
        };
    }
}
