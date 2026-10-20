# OpenEMR Nursing Module (ICU)

An [OpenEMR](https://www.open-emr.org) module for the nursing record of an intensive care unit: an inpatient list, ten nursing forms, ICU severity scores and a vital signs dashboard. It is installed as a custom module and **does not modify any OpenEMR file**, so OpenEMR can be updated and the module keeps working.

It was developed as an undergraduate thesis project at the Facultad Politécnica, Universidad Nacional de Asunción (Paraguay), together with the ICU of the Instituto de Previsión Social (IPS). The interface is translated into Spanish.

## Features

**Inpatient list** (top menu *Nursing*)
- Current admissions with record number, ward, bed and admission date; discharge and death registration.
- A form selector per patient, with direct access to OpenEMR's vitals form for that admission.
- A card in the patient summary showing whether the patient is admitted, where, with which primary team and since when.

**Location history** (button *Location and transfers*)
- Ward, bed, service and primary team are not overwritten: each transfer closes the current location and opens a new one, and the admission keeps the full history.
- A bed can be reserved before the patient arrives (e.g. still in the ED); the current location changes only when the arrival is recorded.
- Data entry errors are corrected without recording a transfer, keeping who corrected them and when.
- Location changes made outside the module (e.g. by the hospital information system) are detected and recorded.
- `form_encounter` keeps only the current location; the history is in the module's own table `nursing_admission_movement`.

**Nursing forms** (each one also available from the encounter)
- Wound care, applications, care bundle, nursing evaluation (with Glasgow), mechanical ventilation record.
- Fluid balance, nutrition, SOFA score, APACHE II score, nursing care plan and progress note.
- Every form shows, below it, the previous records of the same form in the admission.
- Each record can be printed as a PDF; signed records become read-only.

**Scores and prefill**
- Glasgow, fluid balance, SOFA (Vincent et al., 1996) and APACHE II (Knaus et al., 1985) are calculated on the server; the form shows a live preview.
- SOFA and APACHE II prefill the data already recorded (vital signs, nursing evaluations, ventilation records, fluid balances, previous scores), proposing the worst value of the scale's window: the last 24 h for SOFA, the first 24 h of the admission for APACHE II.
- Vital signs are chosen as one whole take of OpenEMR's vitals form: the worst take is preselected, the nurse can pick another one, and the take used is saved with the score.
- Systems or variables without data are shown as *No data* (they add 0 points), and APACHE II warns when the temperature was not taken rectally.

**Vital signs dashboard**
- One card per inpatient with the latest take, a warning when it is older than 6 hours, and Chart.js charts of the admission's evolution.

## Requirements

- OpenEMR 8.1 or later
- PHP 8.2 or later

## Installation

1. From the OpenEMR directory:
   ```bash
   composer require g0tazu/oe-module-nursing
   ```
   Composer places it in `interface/modules/custom_modules/oe-module-nursing`.
2. In OpenEMR, go to **Administration → Modules → Manage Modules**, find the module among the unregistered ones and click **Register**, **Install** and **Enable**. Installing creates the tables, registers the forms and loads the translations.
3. The **Nursing** menu appears at the top.

Vital signs are recorded in OpenEMR's own vitals form; the module reads them from there.

## Updating

```bash
composer update g0tazu/oe-module-nursing
```

Then, in **Manage Modules**, **Disable** and **Enable** the module. Enabling it runs its `sql/install.sql` again, which only adds what an older installation is missing (new tables, columns, forms and translations) and does not change existing data.

## Development

The scoring classes (`src/Scoring`), the vital-sign take scoring and the location history rules have unit tests in `tests/`. They need PHPUnit and OpenEMR's autoloader; `NursingCardTest` also needs an OpenEMR database.

## License

GNU General Public License v3.0. See [LICENSE](LICENSE).
