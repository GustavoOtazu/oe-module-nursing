--
--  Comment Meta Language Constructs:
--
--  #IfNotTable
--    argument: table_name
--    behavior: if the table_name does not exist, the block will be executed
--
--  #EndIf
--    all blocks are terminated with a #EndIf statement.
--

-- Nursing Module — Installation SQL
-- Package: g0tazu/oe-module-nursing
-- Adds inpatient nursing forms: Wound Care, Applications, Care Bundle,
-- Evaluations, Mechanical Ventilation, and Admission tracking.
-- This file is executed by ModuleManagerListener::install() via SQLUpgradeService.
-- -------------------------------------------------------------------------

#IfNotTable form_curaciones
CREATE TABLE `form_curaciones` (
  `id`                      bigint(20)   NOT NULL AUTO_INCREMENT,
  `date`                    datetime     DEFAULT NULL,
  `pid`                     bigint(20)   DEFAULT NULL,
  `encounter`               int(11)      NOT NULL,
  `user`                    varchar(255) DEFAULT NULL,
  `groupname`               varchar(255) DEFAULT NULL,
  `authorized`              tinyint(4)   DEFAULT NULL,
  `activity`                tinyint(4)   DEFAULT NULL,
  `herida_operatoria`       tinyint(1)   DEFAULT 0,
  `obs_herida_operatoria`   varchar(255) DEFAULT NULL,
  `traqueostomia`           tinyint(1)   DEFAULT 0,
  `obs_traqueostomia`       varchar(255) DEFAULT NULL,
  `ostomias`                tinyint(1)   DEFAULT 0,
  `obs_ostomias`            varchar(255) DEFAULT NULL,
  `escaras`                 tinyint(1)   DEFAULT 0,
  `obs_escaras`             varchar(255) DEFAULT NULL,
  `via_venosa_central`      tinyint(1)   DEFAULT 0,
  `obs_via_venosa_central`  varchar(255) DEFAULT NULL,
  `via_venosa`              tinyint(1)   DEFAULT 0,
  `obs_via_venosa`          varchar(255) DEFAULT NULL,
  `hora_operacion`          time         DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
#EndIf

#IfNotTable form_aplicaciones
CREATE TABLE `form_aplicaciones` (
  `id`               bigint(20)   NOT NULL AUTO_INCREMENT,
  `date`             datetime     DEFAULT NULL,
  `pid`              bigint(20)   NOT NULL DEFAULT 0,
  `encounter`        bigint(20)   NOT NULL DEFAULT 0,
  `user`             varchar(255) DEFAULT NULL,
  `groupname`        varchar(255) DEFAULT NULL,
  `authorized`       tinyint(4)   DEFAULT 0,
  `activity`         tinyint(4)   DEFAULT 1,
  `medicamentos`     tinyint(1)   DEFAULT 0,
  `obs_medicamentos` varchar(255) DEFAULT NULL,
  `sueros`           tinyint(1)   DEFAULT 0,
  `obs_sueros`       varchar(255) DEFAULT NULL,
  `vacunas`          tinyint(1)   DEFAULT 0,
  `obs_vacunas`      varchar(255) DEFAULT NULL,
  `expansiones`      tinyint(1)   DEFAULT 0,
  `obs_expansiones`  varchar(255) DEFAULT NULL,
  `sangre`           tinyint(1)   DEFAULT 0,
  `obs_sangre`       varchar(255) DEFAULT NULL,
  `hora_registro`    time         DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
#EndIf

#IfNotTable form_cuidados
CREATE TABLE `form_cuidados` (
  `id`                       bigint(20)   NOT NULL AUTO_INCREMENT,
  `date`                     datetime     DEFAULT NULL,
  `pid`                      bigint(20)   NOT NULL DEFAULT 0,
  `encounter`                bigint(20)   NOT NULL DEFAULT 0,
  `user`                     varchar(255) DEFAULT NULL,
  `groupname`                varchar(255) DEFAULT NULL,
  `authorized`               tinyint(4)   DEFAULT 0,
  `activity`                 tinyint(4)   DEFAULT 1,
  `posicion_paciente`        varchar(20)  DEFAULT NULL,
  `obs_posicion_paciente`    varchar(255) DEFAULT NULL,
  `enjuague_bucal`           tinyint(1)   DEFAULT 0,
  `obs_enjuague_bucal`       varchar(255) DEFAULT NULL,
  `higiene_manos`            tinyint(1)   DEFAULT 0,
  `obs_higiene_manos`        varchar(255) DEFAULT NULL,
  `aspirado_secreciones`     tinyint(1)   DEFAULT 0,
  `obs_aspirado_secreciones` varchar(255) DEFAULT NULL,
  `suspension_sedacion`      tinyint(1)   DEFAULT 0,
  `obs_suspension_sedacion`  varchar(255) DEFAULT NULL,
  `medicion_cuff`            tinyint(1)   DEFAULT 0,
  `obs_medicion_cuff`        varchar(255) DEFAULT NULL,
  `hora_cuidado`             time         DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
#EndIf

#IfNotTable form_evaluaciones
CREATE TABLE `form_evaluaciones` (
  `id`                  bigint(20)   NOT NULL AUTO_INCREMENT,
  `date`                datetime     DEFAULT NULL,
  `pid`                 bigint(20)   DEFAULT NULL,
  `encounter`           int(11)      NOT NULL,
  `user`                varchar(255) DEFAULT NULL,
  `groupname`           varchar(255) DEFAULT NULL,
  `authorized`          tinyint(4)   DEFAULT NULL,
  `activity`            tinyint(4)   DEFAULT NULL,
  `conciencia`          varchar(50)  DEFAULT NULL,
  `obs_conciencia`      varchar(255) DEFAULT NULL,
  `tono`                varchar(50)  DEFAULT NULL,
  `obs_tono`            varchar(255) DEFAULT NULL,
  `pupilas`             varchar(50)  DEFAULT NULL,
  `obs_pupilas`         varchar(255) DEFAULT NULL,
  `mucosas`             varchar(50)  DEFAULT NULL,
  `obs_mucosas`         varchar(255) DEFAULT NULL,
  `glasgow_ojos`        varchar(50)  DEFAULT NULL,
  `obs_glasgow_ojos`    varchar(255) DEFAULT NULL,
  `glasgow_motora`      varchar(50)  DEFAULT NULL,
  `obs_glasgow_motora`  varchar(255) DEFAULT NULL,
  `glasgow_verbal`      varchar(50)  DEFAULT NULL,
  `obs_glasgow_verbal`  varchar(255) DEFAULT NULL,
  `glasgow_total`       int(2)       DEFAULT NULL,
  `hora_evaluacion`     time         DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`, `encounter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
#EndIf

#IfNotTable form_registro_vm
CREATE TABLE `form_registro_vm` (
  `id`                          bigint(20)   NOT NULL AUTO_INCREMENT,
  `date`                        datetime     DEFAULT NULL,
  `pid`                         bigint(20)   NOT NULL DEFAULT 0,
  `encounter`                   bigint(20)   NOT NULL DEFAULT 0,
  `user`                        varchar(255) DEFAULT NULL,
  `groupname`                   varchar(255) DEFAULT NULL,
  `authorized`                  tinyint(4)   DEFAULT 0,
  `activity`                    tinyint(4)   DEFAULT 1,
  `modo_ventilacion`            varchar(30)  DEFAULT NULL,
  `obs_modo`                    varchar(255) DEFAULT NULL,
  `presion`                     tinyint(1)   DEFAULT 0,
  `obs_presion`                 varchar(255) DEFAULT NULL,
  `volumen`                     tinyint(1)   DEFAULT 0,
  `obs_volumen`                 varchar(255) DEFAULT NULL,
  `simv`                        tinyint(1)   DEFAULT 0,
  `obs_simv`                    varchar(255) DEFAULT NULL,
  `psv`                         tinyint(1)   DEFAULT 0,
  `obs_psv`                     varchar(255) DEFAULT NULL,
  `otros`                       tinyint(1)   DEFAULT 0,
  `obs_otros`                   varchar(255) DEFAULT NULL,
  `frecuencia_respiratoria`     tinyint(1)   DEFAULT 0,
  `obs_frecuencia_respiratoria` varchar(255) DEFAULT NULL,
  `p_inspiratorio`              tinyint(1)   DEFAULT 0,
  `obs_p_inspiratorio`          varchar(255) DEFAULT NULL,
  `p_media`                     tinyint(1)   DEFAULT 0,
  `obs_p_media`                 varchar(255) DEFAULT NULL,
  `p_max`                       tinyint(1)   DEFAULT 0,
  `obs_p_max`                   varchar(255) DEFAULT NULL,
  `chst`                        tinyint(1)   DEFAULT 0,
  `obs_chst`                    varchar(255) DEFAULT NULL,
  `disparo`                     tinyint(1)   DEFAULT 0,
  `obs_disparo`                 varchar(255) DEFAULT NULL,
  `fvt`                         tinyint(1)   DEFAULT 0,
  `obs_fvt`                     varchar(255) DEFAULT NULL,
  `vol_tidal`                   tinyint(1)   DEFAULT 0,
  `obs_vol_tidal`               varchar(255) DEFAULT NULL,
  `vm_programado`               tinyint(1)   DEFAULT 0,
  `obs_vm_programado`           varchar(255) DEFAULT NULL,
  `petco2`                      tinyint(1)   DEFAULT 0,
  `obs_petco2`                  varchar(255) DEFAULT NULL,
  `vdvt`                        tinyint(1)   DEFAULT 0,
  `obs_vdvt`                    varchar(255) DEFAULT NULL,
  `ko2`                         tinyint(1)   DEFAULT 0,
  `obs_ko2`                     varchar(255) DEFAULT NULL,
  `hora_registro`               time         DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
#EndIf

#IfNotRow openemr_postcalendar_categories pc_catname Inpatient
SET @catid = (SELECT MAX(pc_catid) FROM openemr_postcalendar_categories);
INSERT INTO `openemr_postcalendar_categories` (`pc_catid`, `pc_catname`, `pc_catcolor`, `pc_catdesc`, `pc_recurrtype`, `pc_enddate`, `pc_recurrspec`, `pc_recurrfreq`, `pc_duration`, `pc_end_date_flag`, `pc_end_date_type`, `pc_end_date_freq`, `pc_end_all_day`, `pc_dailylimit`, `pc_cattype`, `pc_active`, `pc_seq`) VALUES (@catid+1, 'Inpatient', '#FF6600', 'Inpatient / Hospitalization encounters', 0, NULL, 'a:5:{s:17:"event_repeat_freq";s:1:"0";s:22:"event_repeat_freq_type";s:1:"0";s:19:"event_repeat_on_num";s:1:"1";s:19:"event_repeat_on_day";s:1:"0";s:20:"event_repeat_on_freq";s:1:"0";}', 0, 0, 0, 0, 0, 0, 0, 0, 1, @catid+1);
#EndIf


-- Nursing Module: Spanish translations (Latin American + Spain)
#IfNotRow lang_constants constant_name Inpatient List
INSERT IGNORE INTO lang_constants (constant_name) VALUES
  ('Inpatient List'),('New Admission'),('Nursing Options'),('Nursing Applications'),
  ('Nursing Care Bundle'),('Nursing Evaluation'),('Ventilation Record'),('Wound Care'),
  ('Admission'),('Admission Date'),('Record No.'),('Reg. No.'),('Ward'),('Bed'),
  ('Coming Soon'),('Deceased'),('Discharge patient'),('Register patient death'),
  ('Register date of death'),('Are you sure you want to discharge the patient'),
  ('Patient discharged successfully'),('Patient updated successfully'),
  ('Error: Could not retrieve patient data.'),('ID (Patient Record)'),
  ('Select Patient'),('Admit Patient'),('Edit Admission'),('No active inpatient admission.');

INSERT INTO lang_definitions (cons_id, lang_id, definition)
SELECT c.cons_id, ll.lang_id, t.def
FROM lang_constants c
JOIN (SELECT 'Inpatient List' cn,'Lista de Internados' def UNION ALL
      SELECT 'New Admission','Nuevo Ingreso' UNION ALL
      SELECT 'Nursing Options','Opciones de Enfermería' UNION ALL
      SELECT 'Nursing Applications','Aplicaciones de Enfermería' UNION ALL
      SELECT 'Nursing Care Bundle','Paquete de Cuidados de Enfermería' UNION ALL
      SELECT 'Nursing Evaluation','Evaluación de Enfermería' UNION ALL
      SELECT 'Ventilation Record','Registro de Ventilación Mecánica' UNION ALL
      SELECT 'Wound Care','Curaciones' UNION ALL
      SELECT 'Admission','Admisión' UNION ALL
      SELECT 'Admission Date','Fecha de Admisión' UNION ALL
      SELECT 'Record No.','Nro. Registro' UNION ALL
      SELECT 'Reg. No.','Nro. Reg.' UNION ALL
      SELECT 'Ward','Sala' UNION ALL
      SELECT 'Bed','Cama' UNION ALL
      SELECT 'Coming Soon','Próximamente' UNION ALL
      SELECT 'Deceased','Fallecido' UNION ALL
      SELECT 'Discharge patient','Dar de alta al paciente' UNION ALL
      SELECT 'Register patient death','Registrar fallecimiento' UNION ALL
      SELECT 'Register date of death','Fecha de fallecimiento' UNION ALL
      SELECT 'Are you sure you want to discharge the patient','¿Dar de alta al paciente' UNION ALL
      SELECT 'Patient discharged successfully','Paciente dado de alta exitosamente' UNION ALL
      SELECT 'Patient updated successfully','Paciente actualizado exitosamente' UNION ALL
      SELECT 'Error: Could not retrieve patient data.','Error: No se pudieron obtener los datos del paciente.' UNION ALL
      SELECT 'ID (Patient Record)','ID (Registro del Paciente)' UNION ALL
      SELECT 'Select Patient','Seleccionar Paciente' UNION ALL
      SELECT 'Admit Patient','Internar Paciente' UNION ALL
      SELECT 'Edit Admission','Editar Internación' UNION ALL
      SELECT 'No active inpatient admission.','Sin internación activa.'
) t ON c.constant_name = t.cn
JOIN lang_languages ll ON ll.lang_description IN ('Spanish (Latin American)', 'Spanish (Spain)')
ON DUPLICATE KEY UPDATE definition = VALUES(definition);

UPDATE lang_definitions ld
JOIN lang_constants lc ON lc.cons_id = ld.cons_id
JOIN lang_languages ll ON ll.lang_id = ld.lang_id
SET ld.definition = 'Alta'
WHERE lc.constant_name = 'Discharge'
  AND ll.lang_description IN ('Spanish (Latin American)', 'Spanish (Spain)');

-- Nursing form labels translations
INSERT IGNORE INTO lang_constants (constant_name) VALUES
  ('Create Mode'),('Edit Mode'),('Observations...'),('Save Changes'),('Save'),
  ('New Wound Care'),('Edit Wound Care'),('Surgical Wound'),('Tracheostomy'),
  ('Ostomies'),('Pressure Sores'),('Central Venous Line'),('Peripheral IV Line'),('Care Time'),
  ('New Application'),('Edit Application'),('Saline Solutions'),('Vaccines'),
  ('Plasma Expanders'),('Blood and Blood Products'),('Record Time'),
  ('New Care Bundle'),('Edit Care Bundle'),('Patient Position'),('Oral Rinse'),
  ('Hand Hygiene Pre/Post Suctioning'),('Secretion Suctioning with Gloves and Assistant'),
  ('Daily Sedation Suspension and Extubation Evaluation'),('Cuff Pressure Measurement'),
  ('New Ventilation Record'),('Edit Ventilation Record'),('Ventilation Mode'),
  ('Spontaneous'),('Mechanical Ventilation'),('Pressure'),('Volume'),('Respiratory Rate'),
  ('P.Inspiratory / T.Inspiratory'),('P.Mean / PEEP'),('P.Max / P.Plateau'),
  ('Trigger F/P'),('Tidal Volume / Flow'),('Programmed/Measured MV'),
  ('New Nursing Evaluation'),('Edit Nursing Evaluation'),('Consciousness'),('Muscle Tone'),
  ('Mucous Membranes'),('Glasgow Coma Scale'),('Eye Opening'),('Motor Response'),
  ('Verbal Response'),('Glasgow Total Score'),('Evaluation Time'),
  ('Mild'),('Moderate'),('Severe'),
  ('Spontaneously'),('To auditory stimuli'),('To pain'),('No response'),
  ('Obeys commands'),('Localizes pain'),('Withdrawal'),('Abnormal flexion'),
  ('Abnormal extension'),('Oriented and conversing'),('Disoriented and conversing'),
  ('Inappropriate words'),('Incomprehensible sounds');

INSERT INTO lang_definitions (cons_id, lang_id, definition)
SELECT c.cons_id, ll.lang_id, t.def
FROM lang_constants c
JOIN (SELECT 'Create Mode' cn,'Modo Creación' def UNION ALL
      SELECT 'Edit Mode','Modo Edición' UNION ALL
      SELECT 'Observations...','Observaciones...' UNION ALL
      SELECT 'Save Changes','Guardar Cambios' UNION ALL
      SELECT 'Save','Guardar' UNION ALL
      SELECT 'New Wound Care','Nueva Curación' UNION ALL
      SELECT 'Edit Wound Care','Editar Curación' UNION ALL
      SELECT 'Surgical Wound','Herida Operatoria' UNION ALL
      SELECT 'Tracheostomy','Traqueostomía' UNION ALL
      SELECT 'Ostomies','Ostomías' UNION ALL
      SELECT 'Pressure Sores','Escaras' UNION ALL
      SELECT 'Central Venous Line','Vía Venosa Central' UNION ALL
      SELECT 'Peripheral IV Line','Vía Venosa Periférica' UNION ALL
      SELECT 'Care Time','Hora de Curación' UNION ALL
      SELECT 'New Application','Nueva Aplicación' UNION ALL
      SELECT 'Edit Application','Editar Aplicación' UNION ALL
      SELECT 'Saline Solutions','Sueros' UNION ALL
      SELECT 'Vaccines','Vacunas' UNION ALL
      SELECT 'Plasma Expanders','Expansores de Plasma' UNION ALL
      SELECT 'Blood and Blood Products','Sangre y Hemoderivados' UNION ALL
      SELECT 'Record Time','Hora de Registro' UNION ALL
      SELECT 'New Care Bundle','Nuevo Paquete de Cuidados' UNION ALL
      SELECT 'Edit Care Bundle','Editar Paquete de Cuidados' UNION ALL
      SELECT 'Patient Position','Posición del Paciente' UNION ALL
      SELECT 'Oral Rinse','Enjuague Bucal' UNION ALL
      SELECT 'Hand Hygiene Pre/Post Suctioning','Higiene de Manos Pre/Post Aspiración' UNION ALL
      SELECT 'Secretion Suctioning with Gloves and Assistant','Aspirado de Secreciones con Guantes y Asistente' UNION ALL
      SELECT 'Daily Sedation Suspension and Extubation Evaluation','Suspensión Diaria de Sedación y Evaluación de Extubación' UNION ALL
      SELECT 'Cuff Pressure Measurement','Medición de Presión del Cuff' UNION ALL
      SELECT 'New Ventilation Record','Nuevo Registro de VM' UNION ALL
      SELECT 'Edit Ventilation Record','Editar Registro de VM' UNION ALL
      SELECT 'Ventilation Mode','Modo de Ventilación' UNION ALL
      SELECT 'Spontaneous','Espontánea' UNION ALL
      SELECT 'Mechanical Ventilation','Ventilación Mecánica' UNION ALL
      SELECT 'Pressure','Presión' UNION ALL
      SELECT 'Volume','Volumen' UNION ALL
      SELECT 'Respiratory Rate','Frecuencia Respiratoria' UNION ALL
      SELECT 'P.Inspiratory / T.Inspiratory','P.Inspiratoria / T.Inspiratoria' UNION ALL
      SELECT 'P.Mean / PEEP','P.Media / PEEP' UNION ALL
      SELECT 'P.Max / P.Plateau','P.Máx / P.Meseta' UNION ALL
      SELECT 'Trigger F/P','Disparo F/P' UNION ALL
      SELECT 'Tidal Volume / Flow','Volumen Tidal / Flujo' UNION ALL
      SELECT 'Programmed/Measured MV','VM Programado/Medido' UNION ALL
      SELECT 'New Nursing Evaluation','Nueva Evaluación de Enfermería' UNION ALL
      SELECT 'Edit Nursing Evaluation','Editar Evaluación de Enfermería' UNION ALL
      SELECT 'Consciousness','Conciencia' UNION ALL
      SELECT 'Muscle Tone','Tono Muscular' UNION ALL
      SELECT 'Mucous Membranes','Mucosas' UNION ALL
      SELECT 'Glasgow Coma Scale','Escala de Glasgow' UNION ALL
      SELECT 'Eye Opening','Apertura Ocular' UNION ALL
      SELECT 'Motor Response','Respuesta Motora' UNION ALL
      SELECT 'Verbal Response','Respuesta Verbal' UNION ALL
      SELECT 'Glasgow Total Score','Puntaje Total Glasgow' UNION ALL
      SELECT 'Evaluation Time','Hora de Evaluación' UNION ALL
      SELECT 'Mild','Leve' UNION ALL
      SELECT 'Moderate','Moderado' UNION ALL
      SELECT 'Severe','Grave' UNION ALL
      SELECT 'Spontaneously','Espontáneamente' UNION ALL
      SELECT 'To auditory stimuli','A estímulos auditivos' UNION ALL
      SELECT 'To pain','Al dolor' UNION ALL
      SELECT 'No response','Sin respuesta' UNION ALL
      SELECT 'Obeys commands','Obedece órdenes' UNION ALL
      SELECT 'Localizes pain','Localiza el dolor' UNION ALL
      SELECT 'Withdrawal','Flexión de defensa' UNION ALL
      SELECT 'Abnormal flexion','Flexión anormal' UNION ALL
      SELECT 'Abnormal extension','Extensión anormal' UNION ALL
      SELECT 'Oriented and conversing','Orientado y conversa' UNION ALL
      SELECT 'Disoriented and conversing','Desorientado y conversa' UNION ALL
      SELECT 'Inappropriate words','Lenguaje inadecuado' UNION ALL
      SELECT 'Incomprehensible sounds','Sonidos incomprensibles'
) t ON c.constant_name = t.cn
JOIN lang_languages ll ON ll.lang_description IN ('Spanish (Latin American)', 'Spanish (Spain)')
ON DUPLICATE KEY UPDATE definition = VALUES(definition);
#EndIf

-- Nursing Module: German + Portuguese translations (and remaining Spanish strings)
#IfNotRow lang_constants constant_name VIGIL
INSERT IGNORE INTO lang_constants (constant_name) VALUES
  ('VIGIL'),('SOMNOLIENTO'),('ESTUPOROSO'),('COMATOSO'),
  ('FLACIDO'),('ESPASTICO'),('MIDRIASIS'),('MIOSIS'),
  ('SECA'),('HUMEDA'),('PALIDA'),('ICTERICA'),('CIANOSIS'),
  ('NORMAL'),('Nursing'),('Actions'),('Service'),('Ward'),('Bed'),('Department'),
  ('Room / Bed'),('Discharge'),('Deceased'),('Inpatient Admission'),
  ('Admission Details'),('Date of Admission'),('Reg. #'),('Yes'),('No'),
  ('Wound Care'),('Nursing Wound Care'),('Nursing Applications'),('Nursing Care Bundle'),
  ('Nursing Evaluation'),('Ventilation Record'),
  ('Access denied'),('Error: Could not retrieve patient data.'),
  ('Patient discharged successfully'),('Patient updated successfully');

INSERT INTO lang_definitions (cons_id, lang_id, definition)
SELECT c.cons_id, ll.lang_id,
  CASE ll.lang_id
    WHEN 3  THEN t.es WHEN 4  THEN t.es
    WHEN 5  THEN t.de
    WHEN 19 THEN t.pt_eu WHEN 21 THEN t.pt_eu
    WHEN 20 THEN t.pt_br
  END
FROM lang_constants c
JOIN (SELECT 'Nursing' cn,'Enfermería' es,'Pflege' de,'Enfermagem' pt_eu,'Enfermagem' pt_br UNION ALL
      SELECT 'VIGIL','Vigil','Wach','Vigil','Vigil' UNION ALL
      SELECT 'SOMNOLIENTO','Somnoliento','Schläfrig','Sonolento','Sonolento' UNION ALL
      SELECT 'ESTUPOROSO','Estuporoso','Stuporös','Estupuroso','Estupuroso' UNION ALL
      SELECT 'COMATOSO','Comatoso','Komatös','Comatoso','Comatoso' UNION ALL
      SELECT 'NORMAL','Normal','Normal','Normal','Normal' UNION ALL
      SELECT 'FLACIDO','Flácido','Schlaff','Flácido','Flácido' UNION ALL
      SELECT 'ESPASTICO','Espástico','Spastisch','Espástico','Espástico' UNION ALL
      SELECT 'MIDRIASIS','Midriasis','Mydriasis','Midríase','Midríase' UNION ALL
      SELECT 'MIOSIS','Miosis','Miosis','Miose','Miose' UNION ALL
      SELECT 'SECA','Seca','Trocken','Seca','Seca' UNION ALL
      SELECT 'HUMEDA','Húmeda','Feucht','Húmida','Úmida' UNION ALL
      SELECT 'PALIDA','Pálida','Blass','Pálida','Pálida' UNION ALL
      SELECT 'ICTERICA','Ictérica','Ikterisch','Ictérica','Ictérica' UNION ALL
      SELECT 'CIANOSIS','Cianosis','Zyanose','Cianose','Cianose' UNION ALL
      SELECT 'Actions','Acciones','Aktionen','Ações','Ações' UNION ALL
      SELECT 'Service','Servicio','Dienst','Serviço','Serviço' UNION ALL
      SELECT 'Ward','Sala','Station','Enfermaria','Enfermaria' UNION ALL
      SELECT 'Bed','Cama','Bett','Cama','Cama' UNION ALL
      SELECT 'Department','Departamento','Abteilung','Departamento','Departamento' UNION ALL
      SELECT 'Room / Bed','Cuarto / Cama','Zimmer / Bett','Quarto / Cama','Quarto / Cama' UNION ALL
      SELECT 'Yes','Sí','Ja','Sim','Sim' UNION ALL
      SELECT 'No','No','Nein','Não','Não' UNION ALL
      SELECT 'Discharge','Alta','Entlassung','Alta','Alta' UNION ALL
      SELECT 'Deceased','Fallecido','Verstorben','Falecido','Falecido' UNION ALL
      SELECT 'Inpatient Admission','Internación','Stationäre Aufnahme','Internamento','Internação' UNION ALL
      SELECT 'Admission Details','Detalles de Internación','Aufnahmedetails','Detalhes da Admissão','Detalhes da Admissão' UNION ALL
      SELECT 'Date of Admission','Fecha de Internación','Aufnahmedatum','Data de Internamento','Data de Internação' UNION ALL
      SELECT 'Reg. #','Reg. #','Reg.-Nr.','Reg. Nº','Reg. Nº' UNION ALL
      SELECT 'Wound Care','Curaciones','Wundversorgung','Cuidados de Feridas','Cuidados de Feridas' UNION ALL
      SELECT 'Nursing Wound Care','Registro de Curaciones de Enfermería','Pflegewundversorgung','Cuidados de Feridas de Enfermagem','Cuidados de Feridas de Enfermagem' UNION ALL
      SELECT 'Nursing Applications','Aplicaciones de Enfermería','Pflegeanwendungen','Aplicações de Enfermagem','Aplicações de Enfermagem' UNION ALL
      SELECT 'Nursing Care Bundle','Paquete de Cuidados de Enfermería','Pflegebündel','Bundle de Cuidados de Enfermagem','Bundle de Cuidados de Enfermagem' UNION ALL
      SELECT 'Nursing Evaluation','Evaluación de Enfermería','Pflegebewertung','Avaliação de Enfermagem','Avaliação de Enfermagem' UNION ALL
      SELECT 'Ventilation Record','Registro de Ventilación Mecánica','Beatmungsprotokoll','Registo de Ventilação','Registro de Ventilação' UNION ALL
      SELECT 'Access denied','Acceso denegado','Zugriff verweigert','Acesso negado','Acesso negado' UNION ALL
      SELECT 'Patient discharged successfully','Paciente dado de alta exitosamente','Patient erfolgreich entlassen','Paciente dado de alta com sucesso','Paciente dado de alta com sucesso' UNION ALL
      SELECT 'Patient updated successfully','Paciente actualizado exitosamente','Patient erfolgreich aktualisiert','Paciente atualizado com sucesso','Paciente atualizado com sucesso' UNION ALL
      SELECT 'Error: Could not retrieve patient data.','Error: No se pudieron obtener los datos del paciente.','Fehler: Patientendaten konnten nicht abgerufen werden.','Erro: Não foi possível obter os dados do paciente.','Erro: Não foi possível obter os dados do paciente.' UNION ALL
      -- Curaciones
      SELECT 'New Wound Care','Nueva Curación','Neue Wundversorgung','Novo Cuidado de Feridas','Novo Cuidado de Feridas' UNION ALL
      SELECT 'Edit Wound Care','Editar Curación','Wundversorgung bearbeiten','Editar Cuidado de Feridas','Editar Cuidado de Feridas' UNION ALL
      SELECT 'Surgical Wound','Herida Operatoria','Operationswunde','Ferida Operatória','Ferida Cirúrgica' UNION ALL
      SELECT 'Tracheostomy','Traqueostomía','Tracheostomie','Traqueostomia','Traqueostomia' UNION ALL
      SELECT 'Ostomies','Ostomías','Stomata','Ostomias','Ostomias' UNION ALL
      SELECT 'Pressure Sores','Escaras','Druckgeschwüre','Úlceras de Pressão','Úlceras de Pressão' UNION ALL
      SELECT 'Central Venous Line','Vía Venosa Central','Zentraler Venenkatheter','Cateter Venoso Central','Cateter Venoso Central' UNION ALL
      SELECT 'Peripheral IV Line','Vía Venosa Periférica','Peripherer Venenkatheter','Acesso Venoso Periférico','Acesso Venoso Periférico' UNION ALL
      SELECT 'Care Time','Hora de Curación','Pflegezeit','Hora dos Cuidados','Hora dos Cuidados' UNION ALL
      -- Aplicaciones
      SELECT 'New Application','Nueva Aplicación','Neue Anwendung','Nova Aplicação','Nova Aplicação' UNION ALL
      SELECT 'Edit Application','Editar Aplicación','Anwendung bearbeiten','Editar Aplicação','Editar Aplicação' UNION ALL
      SELECT 'Saline Solutions','Sueros','Kochsalzlösungen','Soluções Salinas','Soluções Salinas' UNION ALL
      SELECT 'Vaccines','Vacunas','Impfstoffe','Vacinas','Vacinas' UNION ALL
      SELECT 'Plasma Expanders','Expansores de Plasma','Plasmaexpander','Expansores de Plasma','Expansores de Plasma' UNION ALL
      SELECT 'Blood and Blood Products','Sangre y Hemoderivados','Blut und Blutprodukte','Sangue e Hemoderivados','Sangue e Hemoderivados' UNION ALL
      SELECT 'Record Time','Hora de Registro','Aufzeichnungszeit','Hora de Registo','Hora de Registro' UNION ALL
      -- Cuidados
      SELECT 'New Care Bundle','Nuevo Paquete de Cuidados','Neues Pflegebündel','Novo Bundle de Cuidados','Novo Bundle de Cuidados' UNION ALL
      SELECT 'Edit Care Bundle','Editar Paquete de Cuidados','Pflegebündel bearbeiten','Editar Bundle de Cuidados','Editar Bundle de Cuidados' UNION ALL
      SELECT 'Patient Position','Posición del Paciente','Patientenposition','Posição do Paciente','Posição do Paciente' UNION ALL
      SELECT 'Oral Rinse','Enjuague Bucal','Mundspülung','Higiene Oral','Higiene Oral' UNION ALL
      SELECT 'Hand Hygiene Pre/Post Suctioning','Higiene de Manos Pre/Post Aspiración','Händehygiene vor/nach Absaugung','Higiene das Mãos Pré/Pós Aspiração','Higiene das Mãos Pré/Pós Aspiração' UNION ALL
      SELECT 'Secretion Suctioning with Gloves and Assistant','Aspirado de Secreciones con Guantes y Asistente','Sekretabsaugung mit Handschuhen und Assistent','Aspiração de Secreções com Luvas e Assistente','Aspiração de Secreções com Luvas e Assistente' UNION ALL
      SELECT 'Daily Sedation Suspension and Extubation Evaluation','Suspensión Diaria de Sedación y Evaluación de Extubación','Tägliche Sedierungspause und Extubationsbewertung','Suspensão Diária de Sedação e Avaliação de Extubação','Suspensão Diária de Sedação e Avaliação de Extubação' UNION ALL
      SELECT 'Cuff Pressure Measurement','Medición de Presión del Cuff','Cuff-Druckmessung','Medição da Pressão do Cuff','Medição da Pressão do Cuff' UNION ALL
      -- Evaluaciones
      SELECT 'New Nursing Evaluation','Nueva Evaluación de Enfermería','Neue Pflegebewertung','Nova Avaliação de Enfermagem','Nova Avaliação de Enfermagem' UNION ALL
      SELECT 'Edit Nursing Evaluation','Editar Evaluación de Enfermería','Pflegebewertung bearbeiten','Editar Avaliação de Enfermagem','Editar Avaliação de Enfermagem' UNION ALL
      SELECT 'Consciousness','Conciencia','Bewusstsein','Consciência','Consciência' UNION ALL
      SELECT 'Muscle Tone','Tono Muscular','Muskeltonus','Tónus Muscular','Tônus Muscular' UNION ALL
      SELECT 'Mucous Membranes','Mucosas','Schleimhäute','Mucosas','Mucosas' UNION ALL
      SELECT 'Glasgow Coma Scale','Escala de Glasgow','Glasgow-Koma-Skala','Escala de Coma de Glasgow','Escala de Coma de Glasgow' UNION ALL
      SELECT 'Eye Opening','Apertura Ocular','Augenöffnung','Abertura Ocular','Abertura Ocular' UNION ALL
      SELECT 'Motor Response','Respuesta Motora','Motorische Reaktion','Resposta Motora','Resposta Motora' UNION ALL
      SELECT 'Verbal Response','Respuesta Verbal','Verbale Reaktion','Resposta Verbal','Resposta Verbal' UNION ALL
      SELECT 'Glasgow Total Score','Puntaje Total Glasgow','Glasgow Gesamtpunktzahl','Pontuação Total Glasgow','Pontuação Total Glasgow' UNION ALL
      SELECT 'Evaluation Time','Hora de Evaluación','Bewertungszeit','Hora da Avaliação','Hora da Avaliação' UNION ALL
      SELECT 'Mild','Leve','Leicht','Ligeiro','Leve' UNION ALL
      SELECT 'Moderate','Moderado','Mäßig','Moderado','Moderado' UNION ALL
      SELECT 'Severe','Grave','Schwer','Grave','Grave' UNION ALL
      SELECT 'Spontaneously','Espontáneamente','Spontan','Espontaneamente','Espontaneamente' UNION ALL
      SELECT 'To auditory stimuli','A estímulos auditivos','Auf akustische Reize','A estímulos auditivos','A estímulos auditivos' UNION ALL
      SELECT 'To pain','Al dolor','Auf Schmerzreize','À dor','À dor' UNION ALL
      SELECT 'No response','Sin respuesta','Keine Reaktion','Sem resposta','Sem resposta' UNION ALL
      SELECT 'Obeys commands','Obedece órdenes','Befolgt Befehle','Obedece ordens','Obedece ordens' UNION ALL
      SELECT 'Localizes pain','Localiza el dolor','Lokalisiert Schmerz','Localiza a dor','Localiza a dor' UNION ALL
      SELECT 'Withdrawal','Flexión de defensa','Rückzugsreaktion','Retirada','Retirada' UNION ALL
      SELECT 'Abnormal flexion','Flexión anormal','Abnormale Beugung','Flexão anormal','Flexão anormal' UNION ALL
      SELECT 'Abnormal extension','Extensión anormal','Abnormale Streckung','Extensão anormal','Extensão anormal' UNION ALL
      SELECT 'Oriented and conversing','Orientado y conversa','Orientiert und sprechend','Orientado e a conversar','Orientado e conversando' UNION ALL
      SELECT 'Disoriented and conversing','Desorientado y conversa','Desorientiert und sprechend','Desorientado e a conversar','Desorientado e conversando' UNION ALL
      SELECT 'Inappropriate words','Lenguaje inadecuado','Unangemessene Worte','Palavras inadequadas','Palavras inadequadas' UNION ALL
      SELECT 'Incomprehensible sounds','Sonidos incomprensibles','Unverständliche Laute','Sons incompreensíveis','Sons incompreensíveis' UNION ALL
      -- Registro VM
      SELECT 'New Ventilation Record','Nuevo Registro de VM','Neues Beatmungsprotokoll','Novo Registo de Ventilação','Novo Registro de Ventilação' UNION ALL
      SELECT 'Edit Ventilation Record','Editar Registro de VM','Beatmungsprotokoll bearbeiten','Editar Registo de Ventilação','Editar Registro de Ventilação' UNION ALL
      SELECT 'Ventilation Mode','Modo de Ventilación','Beatmungsmodus','Modo de Ventilação','Modo de Ventilação' UNION ALL
      SELECT 'Spontaneous','Espontánea','Spontanatmung','Espontânea','Espontânea' UNION ALL
      SELECT 'Mechanical Ventilation','Ventilación Mecánica','Mechanische Beatmung','Ventilação Mecânica','Ventilação Mecânica' UNION ALL
      SELECT 'Pressure','Presión','Druck','Pressão','Pressão' UNION ALL
      SELECT 'Volume','Volumen','Volumen','Volume','Volume' UNION ALL
      SELECT 'Other','Otro','Sonstige','Outro','Outro' UNION ALL
      SELECT 'Respiratory Rate','Frecuencia Respiratoria','Atemfrequenz','Frequência Respiratória','Frequência Respiratória' UNION ALL
      SELECT 'P.Inspiratory / T.Inspiratory','P.Inspiratoria / T.Inspiratoria','P.Inspiratorisch / T.Inspir.','P.Inspiratória / T.Inspiratória','P.Inspiratória / T.Inspiratória' UNION ALL
      SELECT 'P.Mean / PEEP','P.Media / PEEP','P.Mittel / PEEP','P.Média / PEEP','P.Média / PEEP' UNION ALL
      SELECT 'P.Max / P.Plateau','P.Máx / P.Meseta','P.Max / P.Plateau','P.Máx / P.Platô','P.Máx / P.Platô' UNION ALL
      SELECT 'Trigger F/P','Disparo F/P','Trigger F/P','Disparo F/P','Disparo F/P' UNION ALL
      SELECT 'Tidal Volume / Flow','Volumen Tidal / Flujo','Tidalvolumen / Fluss','Volume Tidal / Fluxo','Volume Tidal / Fluxo' UNION ALL
      SELECT 'Programmed/Measured MV','VM Programado/Medido','Progr./Gemess. Atemminutenvolumen','VM Programada/Medida','VM Programada/Medida' UNION ALL
      -- Common UI
      SELECT 'Create Mode','Modo Creación','Erstellungsmodus','Modo Criação','Modo Criação' UNION ALL
      SELECT 'Edit Mode','Modo Edición','Bearbeitungsmodus','Modo Edição','Modo Edição' UNION ALL
      SELECT 'Observations...','Observaciones...','Beobachtungen...','Observações...','Observações...' UNION ALL
      SELECT 'Save Changes','Guardar Cambios','Änderungen speichern','Guardar Alterações','Salvar Alterações' UNION ALL
      SELECT 'Save','Guardar','Speichern','Guardar','Salvar' UNION ALL
      SELECT 'No active inpatient admission.','Sin internación activa.','Keine aktive stationäre Aufnahme.','Sem internamento ativo.','Sem internação ativa.' UNION ALL
      -- Error / validation strings
      SELECT 'Encounter not found','Visita no encontrada','Behandlung nicht gefunden','Consulta não encontrada','Consulta não encontrada' UNION ALL
      SELECT 'Missing patient ID','ID de paciente faltante','Patienten-ID fehlt','ID do paciente em falta','ID do paciente ausente' UNION ALL
      SELECT 'Invalid patient or category','Paciente o categoría inválido','Ungültiger Patient oder Kategorie','Paciente ou categoria inválido','Paciente ou categoria inválido' UNION ALL
      SELECT 'Invalid encounter','Visita inválida','Ungültige Behandlung','Consulta inválida','Consulta inválida' UNION ALL
      SELECT 'Invalid mode','Modo inválido','Ungültiger Modus','Modo inválido','Modo inválido' UNION ALL
      SELECT 'Error: Missing required parameters (PID or Encounter)','Error: Faltan parámetros requeridos (PID o Visita)','Fehler: Erforderliche Parameter fehlen (PID oder Behandlung)','Erro: Parâmetros obrigatórios em falta (PID ou Consulta)','Erro: Parâmetros obrigatórios ausentes (PID ou Consulta)' UNION ALL
      SELECT 'Error: Record not found or insufficient permissions.','Error: Registro no encontrado o permisos insuficientes.','Fehler: Datensatz nicht gefunden oder unzureichende Berechtigungen.','Erro: Registo não encontrado ou permissões insuficientes.','Erro: Registro não encontrado ou permissões insuficientes.'
) t ON c.constant_name = t.cn
JOIN lang_languages ll ON ll.lang_id IN (3, 4, 5, 19, 20, 21)
ON DUPLICATE KEY UPDATE definition = VALUES(definition);

-- Fix partial translations for standard OpenEMR strings used in nursing views
SET @cid = (SELECT cons_id FROM lang_constants WHERE constant_name = 'Confirm' LIMIT 1);
INSERT IGNORE INTO lang_definitions (cons_id, lang_id, definition) VALUES (@cid, 3, 'Confirmar');
INSERT IGNORE INTO lang_definitions (cons_id, lang_id, definition) VALUES (@cid, 5, 'Bestätigen');

SET @cid = (SELECT cons_id FROM lang_constants WHERE constant_name = 'This encounter is locked. No new forms can be added.' LIMIT 1);
INSERT IGNORE INTO lang_definitions (cons_id, lang_id, definition) VALUES (@cid, 3, 'Esta visita está bloqueada. No se pueden agregar nuevos formularios.');
INSERT IGNORE INTO lang_definitions (cons_id, lang_id, definition) VALUES (@cid, 4, 'Esta visita está bloqueada. No se pueden agregar nuevos formularios.');
INSERT IGNORE INTO lang_definitions (cons_id, lang_id, definition) VALUES (@cid, 5, 'Diese Behandlung ist gesperrt. Es können keine neuen Formulare hinzugefügt werden.');

SET @cid = (SELECT cons_id FROM lang_constants WHERE constant_name = 'You must first select or add a patient or therapy group.' LIMIT 1);
INSERT IGNORE INTO lang_definitions (cons_id, lang_id, definition) VALUES (@cid, 3, 'Primero debe seleccionar o agregar un paciente o grupo de terapia.');
INSERT IGNORE INTO lang_definitions (cons_id, lang_id, definition) VALUES (@cid, 5, 'Sie müssen zuerst einen Patienten oder eine Therapiegruppe auswählen oder hinzufügen.');
#EndIf

-- Nursing Module: view/print translations + PT lang_ids 19/20/21 for all forms
-- (table.sql files are bypassed when sql_run=1 is set in the registry)
-- Note: standard OpenEMR strings (Date, Status, Mode, etc.) are NOT inserted
-- into lang_constants because they already exist; the JOIN below still finds them.
#IfNotRow lang_constants constant_name Injury level
INSERT IGNORE INTO lang_constants (constant_name) VALUES
  -- New strings not in any previous SQL
  ('Injury level'),('Reference scale'),('Nursing Wound Care'),
  -- Curaciones view/print strings (nursing-specific)
  ('Wound Care Detail'),('Wound Care List'),('NURSING WOUND CARE RECORD'),
  ('No wound care records found'),('Active procedures'),
  ('Responsible Signature'),('Clarification'),('Error: Missing required parameters.'),
  -- Aplicaciones view/print strings
  ('Application Detail'),('Application List'),('NURSING APPLICATIONS RECORD'),
  ('No application records found'),('Application Record'),('Active items'),
  -- Cuidados view/print strings
  ('Care Bundle Detail'),('Care Bundle List'),('NURSING CARE BUNDLE RECORD'),
  ('No care bundle records found'),('Care Bundle Record'),('Not specified'),
  -- Evaluaciones view/print strings
  ('Evaluation Detail'),('Nursing Evaluations'),('Nursing Evaluations List'),
  ('NURSING EVALUATIONS RECORD'),('No evaluations recorded'),
  ('No observations recorded'),('Basic Assessments'),
  ('Patient Information'),('Not available'),('years'),
  ('Glasgow Coma Scale — Total Score'),('Could not retrieve PID or Encounter.'),
  ('Component'),('Score'),('Glasgow'),
  -- Registro VM view/print strings
  ('Ventilation Record Detail'),('Ventilation Record List'),
  ('MECHANICAL VENTILATION RECORD'),('Mechanical Ventilation Record'),
  ('No ventilation records found'),('Ventilation Parameters'),
  ('Ventilation Record'),
  -- Shared print strings that may not exist in older installs
  ('Parameter');

INSERT INTO lang_definitions (cons_id, lang_id, definition)
SELECT c.cons_id, ll.lang_id,
  CASE ll.lang_id
    WHEN 3  THEN t.es WHEN 4  THEN t.es
    WHEN 5  THEN t.de
    WHEN 19 THEN t.pt_eu WHEN 21 THEN t.pt_eu
    WHEN 20 THEN t.pt_br
  END
FROM lang_constants c
JOIN (
  -- Curaciones
  SELECT 'Nursing Wound Care'          cn,'Registro de Curaciones de Enfermería'   es,'Pflegewundversorgung'                                de,'Cuidados de Feridas de Enfermagem'            pt_eu,'Cuidados de Feridas de Enfermagem'            pt_br UNION ALL
  -- Curaciones view/print
  SELECT 'Wound Care Detail'           cn,'Detalle de Curaciones'                  es,'Wundpflegedetail'                                  de,'Detalhe de Curativos'                         pt_eu,'Detalhe de Curativos'                         pt_br UNION ALL
  SELECT 'Wound Care List',               'Lista de Curaciones',                       'Wundpflegeliste',                                       'Lista de Curativos',                                'Lista de Curativos' UNION ALL
  SELECT 'NURSING WOUND CARE RECORD',     'REGISTRO DE CURACIONES DE ENFERMERÍA',      'PFLEGEWUNDVERSORGUNGSPROTOKOLL',                         'REGISTO DE CURATIVOS DE ENFERMAGEM',                'REGISTRO DE CURATIVOS DE ENFERMAGEM' UNION ALL
  SELECT 'No wound care records found',   'No hay curaciones registradas',             'Keine Wundpflegeaufzeichnungen gefunden',                'Nenhum registo de curativos encontrado',             'Nenhum registro de curativos encontrado' UNION ALL
  SELECT 'Active procedures',             'Procedimientos activos',                    'Aktive Prozeduren',                                     'Procedimentos ativos',                              'Procedimentos ativos' UNION ALL
  SELECT 'Responsible Signature',         'Firma del Responsable',                     'Unterschrift des Verantwortlichen',                     'Assinatura do Responsável',                         'Assinatura do Responsável' UNION ALL
  SELECT 'Clarification',                 'Aclaración',                                'Klärung',                                               'Esclarecimento',                                    'Esclarecimento' UNION ALL
  SELECT 'Error: Missing required parameters.','Error: Faltan parámetros requeridos.', 'Fehler: Erforderliche Parameter fehlen.',                'Erro: Parâmetros obrigatórios em falta.',            'Erro: Parâmetros obrigatórios ausentes.' UNION ALL
  -- Aplicaciones view/print
  SELECT 'Application Detail',            'Detalle de Aplicación',                     'Anwendungsdetail',                                      'Detalhe de Aplicação',                              'Detalhe de Aplicação' UNION ALL
  SELECT 'Application List',              'Lista de Aplicaciones',                      'Anwendungsliste',                                       'Lista de Aplicações',                               'Lista de Aplicações' UNION ALL
  SELECT 'NURSING APPLICATIONS RECORD',   'REGISTRO DE APLICACIONES DE ENFERMERÍA',    'PFLEGEANWENDUNGSPROTOKOLL',                              'REGISTO DE APLICAÇÕES DE ENFERMAGEM',               'REGISTRO DE APLICAÇÕES DE ENFERMAGEM' UNION ALL
  SELECT 'No application records found',  'No se encontraron registros de aplicaciones','Keine Anwendungsaufzeichnungen gefunden',               'Nenhum registo de aplicações encontrado',           'Nenhum registro de aplicações encontrado' UNION ALL
  -- Cuidados view/print
  SELECT 'Care Bundle Detail',            'Detalle del Paquete de Cuidados',           'Pflegepaket-Detail',                                    'Detalhe de Pacote de Cuidados',                     'Detalhe de Pacote de Cuidados' UNION ALL
  SELECT 'Care Bundle List',              'Lista de Paquetes de Cuidados',             'Pflegepaketliste',                                      'Lista de Pacotes de Cuidados',                      'Lista de Pacotes de Cuidados' UNION ALL
  SELECT 'NURSING CARE BUNDLE RECORD',    'REGISTRO DEL PAQUETE DE CUIDADOS',          'PFLEGEPAKET-PROTOKOLL',                                 'REGISTO DE PACOTE DE CUIDADOS',                     'REGISTRO DE PACOTE DE CUIDADOS' UNION ALL
  SELECT 'No care bundle records found',  'No se encontraron registros del paquete de cuidados','Keine Pflegepaket-Datensätze gefunden',                  'Nenhum registo de pacote de cuidados encontrado',   'Nenhum registro de pacote de cuidados encontrado' UNION ALL
  SELECT 'Not specified',                 'No especificado',                           'Nicht angegeben',                                       'Não especificado',                                  'Não especificado' UNION ALL
  -- Evaluaciones view/print
  SELECT 'Evaluation Detail',             'Detalle de Evaluación',                     'Bewertungsdetail',                                      'Detalhe da Avaliação',                              'Detalhe da Avaliação' UNION ALL
  SELECT 'Nursing Evaluations',           'Evaluaciones de Enfermería',                'Pflegebewertungen',                                     'Avaliações de Enfermagem',                          'Avaliações de Enfermagem' UNION ALL
  SELECT 'Nursing Evaluations List',      'Lista de Evaluaciones de Enfermería',       'Liste der Pflegebewertungen',                           'Lista de Avaliações de Enfermagem',                 'Lista de Avaliações de Enfermagem' UNION ALL
  SELECT 'NURSING EVALUATIONS RECORD',    'REGISTRO DE EVALUACIONES DE ENFERMERÍA',    'PFLEGEBEURTEILUNGSPROTOKOLL',                           'REGISTO DE AVALIAÇÕES DE ENFERMAGEM',               'REGISTRO DE AVALIAÇÕES DE ENFERMAGEM' UNION ALL
  SELECT 'No evaluations recorded',       'No se registraron evaluaciones',            'Keine Bewertungen erfasst',                             'Nenhuma avaliação registada',                       'Nenhuma avaliação registrada' UNION ALL
  SELECT 'No observations recorded',      'Sin observaciones registradas',             'Keine Beobachtungen aufgezeichnet',                     'Sem observações registadas',                        'Sem observações registradas' UNION ALL
  SELECT 'Basic Assessments',             'Valoraciones Básicas',                      'Grundbewertungen',                                      'Avaliações Básicas',                                'Avaliações Básicas' UNION ALL
  SELECT 'Patient Information',           'Información del Paciente',                  'Patienteninformationen',                                'Informações do Paciente',                           'Informações do Paciente' UNION ALL
  SELECT 'Not available',                 'No disponible',                             'Nicht verfügbar',                                       'Não disponível',                                    'Não disponível' UNION ALL
  SELECT 'years',                         'años',                                      'Jahre',                                                 'anos',                                              'anos' UNION ALL
  SELECT 'Glasgow Coma Scale — Total Score','Escala de Glasgow — Puntaje Total',       'Glasgow-Koma-Skala — Gesamtpunktzahl',                  'Escala de Coma de Glasgow — Pontuação Total',        'Escala de Coma de Glasgow — Pontuação Total' UNION ALL
  SELECT 'Could not retrieve PID or Encounter.','No se pudo obtener el PID o Encuentro.','PID oder Begegnung konnte nicht abgerufen werden.',   'Não foi possível obter o PID ou Consulta.',         'Não foi possível obter o PID ou Consulta.' UNION ALL
  SELECT 'Component',                     'Componente',                                'Komponente',                                            'Componente',                                        'Componente' UNION ALL
  SELECT 'Score',                         'Puntaje',                                   'Punktzahl',                                             'Pontuação',                                         'Pontuação' UNION ALL
  SELECT 'Observations',                  'Observaciones',                             'Beobachtungen',                                         'Observações',                                       'Observações' UNION ALL
  -- Injury level / Reference scale (print.php evaluaciones)
  SELECT 'Injury level',                  'Nivel de Lesión',                           'Verletzungsgrad',                                       'Nível de Lesão',                                    'Nível de Lesão' UNION ALL
  SELECT 'Reference scale',               'Escala de Referencia',                      'Referenzskala',                                         'Escala de Referência',                              'Escala de Referência' UNION ALL
  -- Registro VM view/print
  SELECT 'Ventilation Record Detail',     'Detalle de Registro de VM',                 'Beatmungsprotokolldetail',                               'Detalhe do Registo de Ventilação',                  'Detalhe do Registro de Ventilação' UNION ALL
  SELECT 'Ventilation Record List',       'Lista de Registros de VM',                  'Beatmungsprotokollliste',                                'Lista de Registos de Ventilação',                   'Lista de Registros de Ventilação' UNION ALL
  SELECT 'MECHANICAL VENTILATION RECORD', 'REGISTRO DE VENTILACIÓN MECÁNICA',          'BEATMUNGSPROTOKOLL',                                    'REGISTO DE VENTILAÇÃO MECÂNICA',                    'REGISTRO DE VENTILAÇÃO MECÂNICA' UNION ALL
  SELECT 'Mechanical Ventilation Record', 'Registro de Ventilación Mecánica',          'Beatmungsprotokoll',                                    'Registo de Ventilação Mecânica',                    'Registro de Ventilação Mecânica' UNION ALL
  SELECT 'No ventilation records found',  'No se encontraron registros de ventilación','Keine Beatmungsaufzeichnungen gefunden',                 'Nenhum registo de ventilação encontrado',           'Nenhum registro de ventilação encontrado' UNION ALL
  SELECT 'Ventilation Parameters',        'Parámetros de Ventilación',                 'Beatmungsparameter',                                    'Parâmetros de Ventilação',                          'Parâmetros de Ventilação' UNION ALL
  -- Common print strings (standard OpenEMR — upsert is safe)
  SELECT 'Parameter',                     'Parámetro',                                 'Parameter',                                             'Parâmetro',                                         'Parâmetro' UNION ALL
  SELECT 'Mode',                          'Modo',                                      'Modus',                                                 'Modo',                                              'Modo' UNION ALL
  SELECT 'Response',                      'Respuesta',                                 'Antwort',                                               'Resposta',                                          'Resposta' UNION ALL
  SELECT 'Position',                      'Posición',                                  'Position',                                              'Posição',                                           'Posição' UNION ALL
  SELECT 'Item',                          'Ítem',                                      'Element',                                               'Item',                                              'Item' UNION ALL
  SELECT 'Procedure',                     'Procedimiento',                             'Prozedur',                                              'Procedimento',                                      'Procedimento' UNION ALL
  SELECT 'Status',                        'Estado',                                    'Status',                                                'Estado',                                            'Estado' UNION ALL
  SELECT 'Date',                          'Fecha',                                     'Datum',                                                 'Data',                                              'Data' UNION ALL
  SELECT 'Glasgow',                       'Glasgow',                                   'Glasgow',                                               'Glasgow',                                           'Glasgow'
) t ON c.constant_name = t.cn
JOIN lang_languages ll ON ll.lang_id IN (3, 4, 5, 19, 20, 21)
ON DUPLICATE KEY UPDATE definition = VALUES(definition);
#EndIf

-- Register nursing forms in OpenEMR form registry
#IfNotRow registry directory curaciones
INSERT INTO registry (name, state, directory, sql_run, unpackaged, date, priority, category, patient_encounter, aco_spec)
VALUES ('Nursing Wound Care', 1, 'curaciones', 1, 1, NOW(), 0, 'Nursing', 1, 'encounters|notes');
#EndIf
#IfNotRow registry directory aplicaciones
INSERT INTO registry (name, state, directory, sql_run, unpackaged, date, priority, category, patient_encounter, aco_spec)
VALUES ('Nursing Applications', 1, 'aplicaciones', 1, 1, NOW(), 0, 'Nursing', 1, 'encounters|notes');
#EndIf
#IfNotRow registry directory cuidados
INSERT INTO registry (name, state, directory, sql_run, unpackaged, date, priority, category, patient_encounter, aco_spec)
VALUES ('Nursing Care Bundle', 1, 'cuidados', 1, 1, NOW(), 0, 'Nursing', 1, 'encounters|notes');
#EndIf
#IfNotRow registry directory evaluaciones
INSERT INTO registry (name, state, directory, sql_run, unpackaged, date, priority, category, patient_encounter, aco_spec)
VALUES ('Nursing Evaluations', 1, 'evaluaciones', 1, 1, NOW(), 0, 'Nursing', 1, 'encounters|notes');
#EndIf
#IfNotRow registry directory registro_vm
INSERT INTO registry (name, state, directory, sql_run, unpackaged, date, priority, category, patient_encounter, aco_spec)
VALUES ('Mechanical Ventilation Record', 1, 'registro_vm', 1, 1, NOW(), 0, 'Nursing', 1, 'encounters|notes');
#EndIf

-- Add carga_ws and death_date columns (WS patient load / inpatient tracking)
#IfMissingColumn patient_data carga_ws
ALTER TABLE `patient_data` ADD COLUMN `carga_ws` VARCHAR(3) DEFAULT NULL;
#EndIf

#IfMissingColumn form_encounter carga_ws
ALTER TABLE `form_encounter` ADD COLUMN `carga_ws` VARCHAR(3) DEFAULT NULL;
#EndIf

#IfMissingColumn form_encounter death_date
ALTER TABLE `form_encounter` ADD COLUMN `death_date` date DEFAULT NULL;
#EndIf

-- Add inpatient bed/location columns to form_encounter
#IfMissingColumn form_encounter departamento
ALTER TABLE `form_encounter` ADD COLUMN `departamento` VARCHAR(55) DEFAULT NULL;
#EndIf

#IfMissingColumn form_encounter servicio
ALTER TABLE `form_encounter` ADD COLUMN `servicio` VARCHAR(55) DEFAULT NULL;
#EndIf

#IfMissingColumn form_encounter cama
ALTER TABLE `form_encounter` ADD COLUMN `cama` VARCHAR(55) DEFAULT NULL;
#EndIf

#IfMissingColumn form_encounter out_date
ALTER TABLE `form_encounter` ADD COLUMN `out_date` date DEFAULT NULL;
#EndIf

#IfMissingColumn form_encounter cuarto
ALTER TABLE `form_encounter` ADD COLUMN `cuarto` VARCHAR(55) DEFAULT NULL;
#EndIf

#IfMissingColumn form_encounter nro_registro
ALTER TABLE `form_encounter` ADD COLUMN `nro_registro` VARCHAR(40) DEFAULT NULL;
#EndIf

-- The SIH web service finds each admission by its record number (src/Api/AdmissionApi.php).
#IfNotIndex form_encounter nro_registro
CREATE INDEX `nro_registro` ON `form_encounter` (`nro_registro`);
#EndIf

-- Location history of each admission (src/AdmissionMovements.php). form_encounter
-- keeps the current location; every change of it is kept here.
#IfNotTable nursing_admission_movement
CREATE TABLE `nursing_admission_movement` (
  `id`            bigint(20)   NOT NULL AUTO_INCREMENT,
  `pid`           bigint(20)   NOT NULL,
  `encounter`     bigint(20)   NOT NULL COMMENT 'form_encounter.encounter of the admission',
  `departamento`  varchar(55)  DEFAULT NULL,
  `servicio`      varchar(55)  DEFAULT NULL,
  `cuarto`        varchar(55)  DEFAULT NULL,
  `cama`          varchar(55)  DEFAULT NULL,
  `equipo`        varchar(100) DEFAULT NULL COMMENT 'primary team / specialty',
  `start_date`    datetime     NOT NULL COMMENT 'arrival; expected arrival for a reservation',
  `end_date`      datetime     DEFAULT NULL,
  `status`        varchar(16)  NOT NULL DEFAULT 'current' COMMENT 'current, reserved, ended, cancelled',
  `source`        varchar(16)  NOT NULL DEFAULT 'manual' COMMENT 'admission, manual, detected, sih',
  `user`          varchar(255) DEFAULT NULL COMMENT 'who recorded it',
  `arrived_by`    varchar(255) DEFAULT NULL COMMENT 'who confirmed the arrival at a reserved bed',
  `note`          varchar(255) DEFAULT NULL,
  `corrected_by`  varchar(255) DEFAULT NULL,
  `corrected_at`  datetime     DEFAULT NULL,
  `created_at`    datetime     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `encounter_status` (`encounter`, `status`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB;
#EndIf

-- -------------------------------------------------------------------------
-- UTI extension: fluid balance, nutrition, SOFA, APACHE II and care plan.
-- Scores and totals are computed server-side by src/Scoring/*.
-- -------------------------------------------------------------------------

#IfNotTable form_balance_hidrico
CREATE TABLE `form_balance_hidrico` (
  `id`                      bigint(20)    NOT NULL AUTO_INCREMENT,
  `date`                    datetime      DEFAULT NULL,
  `pid`                     bigint(20)    DEFAULT NULL,
  `encounter`               int(11)       NOT NULL,
  `user`                    varchar(255)  DEFAULT NULL,
  `groupname`               varchar(255)  DEFAULT NULL,
  `authorized`              tinyint(4)    DEFAULT NULL,
  `activity`                tinyint(4)    DEFAULT NULL,
  `turno`                   varchar(10)   DEFAULT NULL,
  `hora_registro`           time          DEFAULT NULL,
  `ing_via_oral`            decimal(8,2)  DEFAULT NULL,
  `ing_enteral`             decimal(8,2)  DEFAULT NULL,
  `ing_parenteral`          decimal(8,2)  DEFAULT NULL,
  `ing_sueros`              decimal(8,2)  DEFAULT NULL,
  `ing_medicacion`          decimal(8,2)  DEFAULT NULL,
  `ing_hemoderivados`       decimal(8,2)  DEFAULT NULL,
  `ing_otros`               decimal(8,2)  DEFAULT NULL,
  `eg_diuresis`             decimal(8,2)  DEFAULT NULL,
  `eg_drenajes`             decimal(8,2)  DEFAULT NULL,
  `eg_sng`                  decimal(8,2)  DEFAULT NULL,
  `eg_vomitos`              decimal(8,2)  DEFAULT NULL,
  `eg_deposiciones`         decimal(8,2)  DEFAULT NULL,
  `eg_perdidas_insensibles` decimal(8,2)  DEFAULT NULL,
  `eg_otros`                decimal(8,2)  DEFAULT NULL,
  `total_ingresos`          decimal(9,2)  DEFAULT NULL,
  `total_egresos`           decimal(9,2)  DEFAULT NULL,
  `balance`                 decimal(9,2)  DEFAULT NULL,
  `observaciones`           text,
  PRIMARY KEY (`id`),
  KEY `pid_encounter` (`pid`, `encounter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
#EndIf

#IfNotTable form_alimentacion
CREATE TABLE `form_alimentacion` (
  `id`                   bigint(20)    NOT NULL AUTO_INCREMENT,
  `date`                 datetime      DEFAULT NULL,
  `pid`                  bigint(20)    DEFAULT NULL,
  `encounter`            int(11)       NOT NULL,
  `user`                 varchar(255)  DEFAULT NULL,
  `groupname`            varchar(255)  DEFAULT NULL,
  `authorized`           tinyint(4)    DEFAULT NULL,
  `activity`             tinyint(4)    DEFAULT NULL,
  `hora_registro`        time          DEFAULT NULL,
  `tipo_alimentacion`    varchar(20)   DEFAULT NULL,
  `via_enteral`          varchar(20)   DEFAULT NULL,
  `modalidad`            varchar(20)   DEFAULT NULL,
  `formula`              varchar(255)  DEFAULT NULL,
  `volumen_ml`           decimal(8,2)  DEFAULT NULL,
  `velocidad_ml_h`       decimal(8,2)  DEFAULT NULL,
  `residuo_gastrico_ml`  decimal(8,2)  DEFAULT NULL,
  `tolerancia`           varchar(20)   DEFAULT NULL,
  `signos_intolerancia`  varchar(255)  DEFAULT NULL,
  `observaciones`        text,
  PRIMARY KEY (`id`),
  KEY `pid_encounter` (`pid`, `encounter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
#EndIf

#IfNotTable form_escala_sofa
CREATE TABLE `form_escala_sofa` (
  `id`                    bigint(20)    NOT NULL AUTO_INCREMENT,
  `date`                  datetime      DEFAULT NULL,
  `pid`                   bigint(20)    DEFAULT NULL,
  `encounter`             int(11)       NOT NULL,
  `user`                  varchar(255)  DEFAULT NULL,
  `groupname`             varchar(255)  DEFAULT NULL,
  `authorized`            tinyint(4)    DEFAULT NULL,
  `activity`              tinyint(4)    DEFAULT NULL,
  `hora_registro`         time          DEFAULT NULL,
  `pao2`                  decimal(6,1)  DEFAULT NULL,
  `fio2`                  decimal(5,2)  DEFAULT NULL,
  `soporte_respiratorio`  tinyint(1)    DEFAULT 0,
  `plaquetas`             decimal(7,1)  DEFAULT NULL,
  `bilirrubina`           decimal(5,2)  DEFAULT NULL,
  `pam`                   decimal(5,1)  DEFAULT NULL,
  `dopamina`              decimal(6,2)  DEFAULT NULL,
  `dobutamina`            tinyint(1)    DEFAULT 0,
  `epinefrina`            decimal(6,3)  DEFAULT NULL,
  `norepinefrina`         decimal(6,3)  DEFAULT NULL,
  `glasgow`               tinyint(4)    DEFAULT NULL,
  `creatinina`            decimal(5,2)  DEFAULT NULL,
  `diuresis_24h`          decimal(7,1)  DEFAULT NULL,
  `pafi`                  decimal(6,1)  DEFAULT NULL,
  `sofa_resp`             tinyint(4)    DEFAULT NULL,
  `sofa_coag`             tinyint(4)    DEFAULT NULL,
  `sofa_hepatico`         tinyint(4)    DEFAULT NULL,
  `sofa_cardio`           tinyint(4)    DEFAULT NULL,
  `sofa_snc`              tinyint(4)    DEFAULT NULL,
  `sofa_renal`            tinyint(4)    DEFAULT NULL,
  `sofa_total`            tinyint(4)    DEFAULT NULL,
  `sofa_evaluados`        tinyint(4)    DEFAULT NULL,
  `observaciones`         text,
  PRIMARY KEY (`id`),
  KEY `pid_encounter` (`pid`, `encounter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
#EndIf

#IfNotTable form_escala_apache
CREATE TABLE `form_escala_apache` (
  `id`                         bigint(20)    NOT NULL AUTO_INCREMENT,
  `date`                       datetime      DEFAULT NULL,
  `pid`                        bigint(20)    DEFAULT NULL,
  `encounter`                  int(11)       NOT NULL,
  `user`                       varchar(255)  DEFAULT NULL,
  `groupname`                  varchar(255)  DEFAULT NULL,
  `authorized`                 tinyint(4)    DEFAULT NULL,
  `activity`                   tinyint(4)    DEFAULT NULL,
  `hora_registro`              time          DEFAULT NULL,
  `temperatura`                decimal(4,1)  DEFAULT NULL,
  `pam`                        decimal(5,1)  DEFAULT NULL,
  `frecuencia_cardiaca`        smallint(6)   DEFAULT NULL,
  `frecuencia_respiratoria`    smallint(6)   DEFAULT NULL,
  `fio2`                       decimal(5,2)  DEFAULT NULL,
  `pao2`                       decimal(6,1)  DEFAULT NULL,
  `paco2`                      decimal(6,1)  DEFAULT NULL,
  `ph`                         decimal(4,2)  DEFAULT NULL,
  `bicarbonato`                decimal(5,1)  DEFAULT NULL,
  `sodio`                      decimal(5,1)  DEFAULT NULL,
  `potasio`                    decimal(4,1)  DEFAULT NULL,
  `creatinina`                 decimal(5,2)  DEFAULT NULL,
  `insuficiencia_renal_aguda`  tinyint(1)    DEFAULT 0,
  `hematocrito`                decimal(4,1)  DEFAULT NULL,
  `leucocitos`                 decimal(6,1)  DEFAULT NULL,
  `glasgow`                    tinyint(4)    DEFAULT NULL,
  `edad`                       smallint(6)   DEFAULT NULL,
  `enfermedad_cronica`         tinyint(1)    DEFAULT 0,
  `tipo_ingreso`               varchar(20)   DEFAULT NULL,
  `a_ado2`                     decimal(6,1)  DEFAULT NULL,
  `pts_fisiologicos`           tinyint(4)    DEFAULT NULL,
  `pts_edad`                   tinyint(4)    DEFAULT NULL,
  `pts_cronicos`               tinyint(4)    DEFAULT NULL,
  `apache_total`               tinyint(4)    DEFAULT NULL,
  `apache_evaluados`           tinyint(4)    DEFAULT NULL,
  `observaciones`              text,
  PRIMARY KEY (`id`),
  KEY `pid_encounter` (`pid`, `encounter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
#EndIf

#IfMissingColumn form_escala_sofa vitals_id
ALTER TABLE `form_escala_sofa` ADD COLUMN `vitals_id` bigint(20) DEFAULT NULL COMMENT 'form_vitals.id of the vital signs take used';
#EndIf

#IfMissingColumn form_escala_apache vitals_id
ALTER TABLE `form_escala_apache` ADD COLUMN `vitals_id` bigint(20) DEFAULT NULL COMMENT 'form_vitals.id of the vital signs take used';
#EndIf

#IfNotTable form_plan_cuidados
CREATE TABLE `form_plan_cuidados` (
  `id`                     bigint(20)    NOT NULL AUTO_INCREMENT,
  `date`                   datetime      DEFAULT NULL,
  `pid`                    bigint(20)    DEFAULT NULL,
  `encounter`              int(11)       NOT NULL,
  `user`                   varchar(255)  DEFAULT NULL,
  `groupname`              varchar(255)  DEFAULT NULL,
  `authorized`             tinyint(4)    DEFAULT NULL,
  `activity`               tinyint(4)    DEFAULT NULL,
  `turno`                  varchar(10)   DEFAULT NULL,
  `hora_registro`          time          DEFAULT NULL,
  `diagnostico_enfermeria` text,
  `codigo_diagnostico`     varchar(40)   DEFAULT NULL,
  `objetivo`               text,
  `intervenciones`         text,
  `evaluacion_resultado`   varchar(20)   DEFAULT NULL,
  `evolucion`              text,
  `observaciones`          text,
  PRIMARY KEY (`id`),
  KEY `pid_encounter` (`pid`, `encounter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
#EndIf

-- Register the new nursing forms
#IfNotRow registry directory balance_hidrico
INSERT INTO registry (name, state, directory, sql_run, unpackaged, date, priority, category, patient_encounter, aco_spec)
VALUES ('Nursing Fluid Balance', 1, 'balance_hidrico', 1, 1, NOW(), 0, 'Nursing', 1, 'encounters|notes');
#EndIf
#IfNotRow registry directory alimentacion
INSERT INTO registry (name, state, directory, sql_run, unpackaged, date, priority, category, patient_encounter, aco_spec)
VALUES ('Nursing Nutrition', 1, 'alimentacion', 1, 1, NOW(), 0, 'Nursing', 1, 'encounters|notes');
#EndIf
#IfNotRow registry directory escala_sofa
INSERT INTO registry (name, state, directory, sql_run, unpackaged, date, priority, category, patient_encounter, aco_spec)
VALUES ('SOFA Score', 1, 'escala_sofa', 1, 1, NOW(), 0, 'Nursing', 1, 'encounters|notes');
#EndIf
#IfNotRow registry directory escala_apache
INSERT INTO registry (name, state, directory, sql_run, unpackaged, date, priority, category, patient_encounter, aco_spec)
VALUES ('APACHE II Score', 1, 'escala_apache', 1, 1, NOW(), 0, 'Nursing', 1, 'encounters|notes');
#EndIf
#IfNotRow registry directory plan_cuidados
INSERT INTO registry (name, state, directory, sql_run, unpackaged, date, priority, category, patient_encounter, aco_spec)
VALUES ('Nursing Care Plan', 1, 'plan_cuidados', 1, 1, NOW(), 0, 'Nursing', 1, 'encounters|notes');
#EndIf

-- Spanish translations for the UTI extension forms.
-- lang_constants and lang_definitions have no unique keys, so INSERT IGNORE
-- would duplicate existing rows. Instead, a constant is added only when no
-- constant with that name exists, and a definition only when that text has no
-- definition yet for the language. Existing (core) translations are never
-- changed, and the block is safe to run on every upgrade.
DROP TEMPORARY TABLE IF EXISTS nursing_uti_lang;
CREATE TEMPORARY TABLE nursing_uti_lang (cn VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL, es TEXT CHARACTER SET utf8mb4 NOT NULL);
INSERT INTO nursing_uti_lang (cn, es) VALUES
  ('Oral', 'Oral'),
  ('Enteral', 'Enteral'),
  ('Parenteral', 'Parenteral'),
  ('Fasting', 'Ayuno'),
  ('Nasogastric tube', 'Sonda nasogástrica'),
  ('Nasojejunal tube', 'Sonda nasoyeyunal'),
  ('Gastrostomy', 'Gastrostomía'),
  ('Jejunostomy', 'Yeyunostomía'),
  ('Continuous', 'Continua'),
  ('Intermittent', 'Intermitente'),
  ('Bolus', 'Bolo'),
  ('Good', 'Buena'),
  ('Fair', 'Regular'),
  ('Poor', 'Mala'),
  ('Vomiting', 'Vómitos'),
  ('Abdominal distension', 'Distensión abdominal'),
  ('Diarrhea', 'Diarrea'),
  ('High gastric residual', 'Residuo gástrico alto'),
  ('New Nursing Nutrition', 'Nuevo registro de alimentación'),
  ('Edit Nursing Nutrition', 'Editar registro de alimentación'),
  ('Nursing Nutrition', 'Alimentación de enfermería'),
  ('Nutrition Detail', 'Detalle de alimentación'),
  ('No nutrition records', 'No hay registros de alimentación'),
  ('Feeding Type', 'Tipo de alimentación'),
  ('Enteral Route', 'Vía enteral'),
  ('Modality', 'Modalidad'),
  ('Formula / Diet', 'Fórmula / Dieta'),
  ('Volume', 'Volumen'),
  ('Infusion Rate', 'Velocidad de infusión'),
  ('Gastric Residual', 'Residuo gástrico'),
  ('Tolerance', 'Tolerancia'),
  ('Signs of Intolerance', 'Signos de intolerancia'),
  ('Record Time', 'Hora de registro'),
  ('None', 'Ninguno'),
  ('Value', 'Valor'),
  ('NURSING NUTRITION RECORD', 'REGISTRO DE ALIMENTACIÓN DE ENFERMERÍA'),
  ('Acid-base', 'Ácido-base'),
  ('Acute physiology', 'Fisiología aguda'),
  ('Acute physiology subtotal', 'Subtotal fisiología aguda'),
  ('Acute renal failure', 'Insuficiencia renal aguda'),
  ('Admission type', 'Tipo de ingreso'),
  ('Age and chronic health', 'Edad y salud crónica'),
  ('Age points', 'Puntos por edad'),
  ('APACHE II Detail', 'Detalle APACHE II'),
  ('APACHE II Score', 'Escala APACHE II'),
  ('APACHE II SCORE RECORD', 'REGISTRO DE ESCALA APACHE II'),
  ('Arterial pH', 'pH arterial'),
  ('Chronic health', 'Salud crónica'),
  ('Chronic health points', 'Puntos por enfermedad crónica'),
  ('Creatinine', 'Creatinina'),
  ('creatinine points doubled', 'puntos de creatinina duplicados'),
  ('Edit APACHE II Score', 'Editar escala APACHE II'),
  ('Elective postoperative', 'Postoperatorio electivo'),
  ('Emergency postoperative', 'Postoperatorio de urgencia'),
  ('Error: Value out of range', 'Error: Valor fuera de rango'),
  ('Fraction (0.21-1) or percentage (21-100)', 'Fracción (0,21-1) o porcentaje (21-100)'),
  ('Heart rate', 'Frecuencia cardíaca'),
  ('Hematocrit', 'Hematocrito'),
  ('Higher severity', 'Mayor gravedad'),
  ('History of severe organ insufficiency or immunocompromise', 'Antecedente de insuficiencia orgánica grave o inmunocompromiso'),
  ('Laboratory', 'Laboratorio'),
  ('Lower severity', 'Menor gravedad'),
  ('Mean arterial pressure', 'Presión arterial media'),
  ('Moderate severity', 'Gravedad moderada'),
  ('Neurological', 'Neurológico'),
  ('New APACHE II Score', 'Nueva escala APACHE II'),
  ('No', 'No'),
  ('No APACHE II scores recorded', 'No hay escalas APACHE II registradas'),
  ('Non-operative', 'No quirúrgico'),
  ('Not evaluated', 'No evaluado'),
  ('Only if no arterial blood gas', 'Solo si no hay gasometría arterial'),
  ('Oxygenation', 'Oxigenación'),
  ('Points', 'Puntos'),
  ('Potassium', 'Potasio'),
  ('Rectal temperature', 'Temperatura rectal'),
  ('Reference', 'Referencia'),
  ('Respiratory rate', 'Frecuencia respiratoria'),
  ('Serum HCO3', 'HCO3 sérico'),
  ('Sodium', 'Sodio'),
  ('Use the worst value of the first 24 hours', 'Utilice el peor valor de las primeras 24 horas'),
  ('Variable', 'Variable'),
  ('variables evaluated', 'variables evaluadas'),
  ('Variables left empty add 0 points and are reported as not evaluated', 'Las variables vacías suman 0 puntos y se informan como no evaluadas'),
  ('Vital signs', 'Signos vitales'),
  ('White blood cells', 'Leucocitos'),
  ('With FiO2 ≥ 0.5 the A-aDO2 gradient is used; otherwise PaO2', 'Con FiO2 ≥ 0,5 se usa el gradiente A-aDO2; de lo contrario, la PaO2'),
  ('Yes', 'Sí'),
  ('Oral intake', 'Vía oral'),
  ('Enteral nutrition', 'Nutrición enteral'),
  ('Parenteral nutrition', 'Nutrición parenteral'),
  ('IV fluids', 'Sueros'),
  ('IV medication', 'Medicación endovenosa'),
  ('Blood products', 'Hemoderivados'),
  ('Other intake', 'Otros ingresos'),
  ('Urine output', 'Diuresis'),
  ('Drains', 'Drenajes'),
  ('Stools', 'Deposiciones'),
  ('Insensible losses', 'Pérdidas insensibles'),
  ('Other output', 'Otros egresos'),
  ('Total intake', 'Total de ingresos'),
  ('Total output', 'Total de egresos'),
  ('Fluid balance', 'Balance hídrico'),
  ('Shift', 'Turno'),
  ('Morning', 'Mañana'),
  ('Afternoon', 'Tarde'),
  ('Night', 'Noche'),
  ('Intake', 'Ingresos'),
  ('Output', 'Egresos'),
  ('Edit Nursing Fluid Balance', 'Editar balance hídrico de enfermería'),
  ('New Nursing Fluid Balance', 'Nuevo balance hídrico de enfermería'),
  ('Nursing Fluid Balance', 'Balance hídrico de enfermería'),
  ('Fluid Balance Detail', 'Detalle del balance hídrico'),
  ('No fluid balance records', 'No hay registros de balance hídrico'),
  ('No values recorded', 'No se registraron valores'),
  ('Positive balance', 'Balance positivo'),
  ('Negative balance', 'Balance negativo'),
  ('Neutral balance', 'Balance neutro'),
  ('NURSING FLUID BALANCE RECORD', 'REGISTRO DE BALANCE HÍDRICO DE ENFERMERÍA'),
  ('Fluid Balance', 'Balance Hídrico'),
  ('Achieved', 'Logrado'),
  ('Care Plan Detail', 'Detalle del plan de cuidados'),
  ('Diagnosis code', 'Código de diagnóstico'),
  ('Diagnosis code (optional)', 'Código de diagnóstico (opcional)'),
  ('Edit Nursing Care Plan', 'Editar plan de cuidados de enfermería'),
  ('e.g. NANDA-I or ICNP code', 'p. ej. código NANDA-I o CIPE (ICNP)'),
  ('Enter at least a nursing diagnosis or a shift progress note.', 'Ingrese al menos un diagnóstico de enfermería o una evolución del turno.'),
  ('Goal / expected outcome', 'Objetivo / resultado esperado'),
  ('In progress', 'En curso'),
  ('New Nursing Care Plan', 'Nuevo plan de cuidados de enfermería'),
  ('No care plans recorded', 'No hay planes de cuidados registrados'),
  ('Not achieved', 'No logrado'),
  ('Nursing Care Plan', 'Plan de cuidados de enfermería'),
  ('NURSING CARE PLAN RECORD', 'REGISTRO DE PLAN DE CUIDADOS DE ENFERMERÍA'),
  ('Nursing diagnosis', 'Diagnóstico de enfermería'),
  ('Nursing interventions', 'Intervenciones de enfermería'),
  ('Outcome evaluation', 'Evaluación del resultado'),
  ('Partially achieved', 'Parcialmente logrado'),
  ('Relevant events of the shift', 'Eventos relevantes del turno'),
  ('Shift progress note', 'Evolución de enfermería del turno'),
  ('What care will be/was provided?', '¿Qué cuidados se brindarán/se brindaron?'),
  ('What do you expect to achieve and by when?', '¿Qué espera lograr y para cuándo?'),
  ('What problem or risk did you identify?', '¿Qué problema o riesgo identificó?'),
  ('SOFA Score', 'Escala SOFA'),
  ('New SOFA Score', 'Nueva escala SOFA'),
  ('Edit SOFA Score', 'Editar escala SOFA'),
  ('SOFA Score Detail', 'Detalle de escala SOFA'),
  ('SOFA SCORE RECORD', 'REGISTRO DE ESCALA SOFA'),
  ('No SOFA scores recorded', 'No hay escalas SOFA registradas'),
  ('Sequential Organ Failure Assessment', 'Evaluación Secuencial de Falla Orgánica'),
  ('Coagulation', 'Coagulación'),
  ('Liver', 'Hepático'),
  ('Cardiovascular', 'Cardiovascular'),
  ('Central nervous system', 'Sistema nervioso central'),
  ('Renal', 'Renal'),
  ('PaO2/FiO2 ratio', 'Relación PaO2/FiO2'),
  ('Fraction 0.21-1.0 or percentage 21-100', 'Fracción 0,21-1,0 o porcentaje 21-100'),
  ('Mechanical ventilation / respiratory support', 'Ventilación mecánica / soporte respiratorio'),
  ('Scores 3 and 4 require respiratory support', 'Los puntajes 3 y 4 requieren soporte respiratorio'),
  ('Platelets', 'Plaquetas'),
  ('Bilirubin', 'Bilirrubina'),
  ('Mean arterial pressure (MAP)', 'Presión arterial media (PAM)'),
  ('MAP', 'PAM'),
  ('Dopamine', 'Dopamina'),
  ('Dobutamine', 'Dobutamina'),
  ('Dobutamine (any dose)', 'Dobutamina (cualquier dosis)'),
  ('Epinephrine', 'Adrenalina'),
  ('Norepinephrine', 'Noradrenalina'),
  ('Vasopressor doses administered for at least 1 hour', 'Dosis de vasopresores administradas durante al menos 1 hora'),
  ('systems evaluated', 'sistemas evaluados'),
  ('Low', 'Bajo'),
  ('Intermediate', 'Intermedio'),
  ('High', 'Alto'),
  ('points', 'puntos'),
  ('System', 'Sistema'),
  ('Values', 'Valores'),
  ('Total Score', 'Puntaje total'),
  ('Respiratory system', 'Sistema respiratorio'),
  ('Mixed feeding', 'Mixta'),
  ('Worst value of the first 24 h of the admission', 'Peor valor de las primeras 24 h de internación'),
  ('Worst value of the last 24 h', 'Peor valor de las últimas 24 h'),
  ('vital signs', 'signos vitales'),
  ('ventilation record', 'registro de ventilación'),
  ('nursing evaluation', 'evaluación de enfermería'),
  ('Mechanical ventilation recorded in the last 24 h', 'Ventilación mecánica registrada en las últimas 24 h'),
  ('Sum of %d fluid balances of the last 24 h', 'Suma de %d balances hídricos de las últimas 24 h'),
  ('last', 'último'),
  ('in the last 24 h', 'en las últimas 24 h'),
  ('since the previous fluid balance', 'desde el balance hídrico anterior'),
  ('Sum of %d nutrition records', 'Suma de %d registros de alimentación'),
  ('From one nutrition record', 'De un registro de alimentación'),
  ('Fluid balance of the last 24 h', 'Balance hídrico de las últimas 24 h'),
  ('Vital Signs', 'Signos vitales'),
  ('Record vital signs', 'Cargar signos vitales'),
  ('leaves this form', 'sale de este formulario'),
  ('No vital signs recorded in the last 24 h', 'No hay signos vitales registrados en las últimas 24 h'),
  ('No vital signs recorded in the first 24 h of the admission', 'No hay signos vitales registrados en las primeras 24 h de la internación'),
  ('Show values (%d)', 'Ver valores (%d)'),
  ('No data', 'Sin datos'),
  ('Vital Signs Dashboard', 'Tablero de signos vitales'),
  ('%s h ago', 'hace %s h'),
  ('%s days ago', 'hace %s días'),
  ('No data in this period', 'Sin datos en este período'),
  ('History of this admission', 'Historial de esta internación'),
  ('1 previous record', '1 registro anterior'),
  ('%d previous records', '%d registros anteriores'),
  ('Showing the last %d.', 'Se muestran los últimos %d.'),
  ('See all', 'Ver todos'),
  ('7 days', '7 días'),
  ('Whole admission', 'Toda la internación'),
  ('Admission not found', 'Internación no encontrada'),
  ('Unknown action', 'Acción desconocida'),
  ('Evolution', 'Evolución'),
  ('Heart and respiratory rate', 'Frecuencia cardíaca y respiratoria'),
  ('Blood pressure', 'Presión arterial'),
  ('Systolic', 'Sistólica'),
  ('Diastolic', 'Diastólica'),
  ('Oxygen saturation', 'Saturación de oxígeno'),
  ('Temperature', 'Temperatura'),
  ('Last take', 'Última toma'),
  ('More than 6 h without vital signs', 'Más de 6 h sin signos vitales'),
  ('No vital signs in this period.', 'No hay signos vitales en este período.'),
  ('No vital signs recorded in this admission.', 'No hay signos vitales registrados en esta internación.'),
  ('There are no patients admitted.', 'No hay pacientes internados.'),
  ('The data could not be loaded.', 'No se pudieron cargar los datos.'),
  ('Refresh', 'Actualizar'),
  ('Updated', 'Actualizado'),
  ('Reg. No.', 'Nro. Reg.'),
  ('All', 'Todas'),
  ('No linked take', 'Sin toma vinculada'),
  ('The take saved with this score is selected. Choosing another one fills the fields that come from vital signs with its values.', 'Está seleccionada la toma guardada con esta escala. Si se elige otra, sus valores completan los campos que salen de los signos vitales.'),
  ('Temperature taken by: %s. APACHE II uses rectal temperature; review it before saving.', 'Temperatura tomada por vía: %s. APACHE II usa temperatura rectal; revisar antes de guardar.'),
  ('The take does not say how the temperature was taken. APACHE II uses rectal temperature; review it before saving.', 'La toma no indica cómo se tomó la temperatura. APACHE II usa temperatura rectal; revisar antes de guardar.'),
  ('These variables add 0 points, so the real score may be higher.', 'Esas variables suman 0 puntos, así que el puntaje real puede ser mayor.'),
  ('Temp.', 'Temp.'),
  ('No data for', 'Sin datos de'),
  ('These systems add 0 points, so the real score may be higher.', 'Esos sistemas suman 0 puntos, así que el puntaje real puede ser mayor.'),
  ('Respiratory (SOFA system)', 'Respiratorio'),
  ('CNS', 'SNC'),
  ('Enter PaO2 and FiO2 to evaluate this system', 'Cargar PaO2 y FiO2 para evaluar este sistema'),
  ('Enter the platelets to evaluate this system', 'Cargar las plaquetas para evaluar este sistema'),
  ('Enter the bilirubin to evaluate this system', 'Cargar la bilirrubina para evaluar este sistema'),
  ('Enter the MAP or the vasopressor doses to evaluate this system', 'Cargar la PAM o las dosis de vasopresores para evaluar este sistema'),
  ('Enter the Glasgow score to evaluate this system', 'Cargar el Glasgow para evaluar este sistema'),
  ('Enter the creatinine or the urine output to evaluate this system', 'Cargar la creatinina o la diuresis para evaluar este sistema'),
  ('Vital signs take', 'Toma de signos vitales'),
  ('Vital signs take used', 'Toma de signos vitales usada'),
  ('Chosen vital signs take', 'Toma de signos vitales elegida'),
  ('The worst take of the period is preselected. The values of the chosen take fill the fields that come from vital signs, and the take is saved with this score.', 'Se preselecciona la peor toma del período. Los valores de la toma elegida completan los campos que salen de los signos vitales, y la toma queda guardada con esta escala.'),
  ('BP', 'PA'),
  ('MAP', 'PAM'),
  ('HR', 'FC'),
  ('RR', 'FR'),
  ('worst', 'peor'),
  ('latest', 'último'),
  ('Chosen by the user', 'Elegido por el usuario'),
  ('Proposed', 'Propuesto'),
  ('Latest', 'Último'),
  ('point', 'punto'),
  ('points', 'puntos'),
  ('Review before saving', 'Revisar antes de guardar'),
  ('Print', 'Imprimir'),
  ('Print this form as PDF', 'Imprimir este formulario en PDF'),
  ('View signed record', 'Ver registro firmado'),
  ('This record is signed and can no longer be edited.', 'Este registro está firmado y ya no se puede modificar.');

INSERT INTO lang_constants (constant_name)
SELECT t.cn FROM nursing_uti_lang t
WHERE NOT EXISTS (SELECT 1 FROM lang_constants c WHERE c.constant_name = t.cn);

INSERT INTO lang_definitions (cons_id, lang_id, definition)
SELECT (SELECT MIN(c.cons_id) FROM lang_constants c WHERE c.constant_name = t.cn), ll.lang_id, t.es
FROM nursing_uti_lang t
JOIN lang_languages ll ON ll.lang_id IN (3, 4)
WHERE NOT EXISTS (
  SELECT 1 FROM lang_definitions d
  JOIN lang_constants c2 ON c2.cons_id = d.cons_id
  WHERE c2.constant_name = t.cn AND d.lang_id = ll.lang_id
);

DROP TEMPORARY TABLE IF EXISTS nursing_uti_lang;

-- ---------------------------------------------------------------------------
-- Nursing Module: Spanish for keys that had no row, or that core OpenEMR already
-- holds with an empty definition (then the English text was shown). Empty
-- definitions are filled; non-empty core translations are left as they are.
-- ---------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS nursing_fix_lang;
CREATE TEMPORARY TABLE nursing_fix_lang (cn VARCHAR(255) NOT NULL, es TEXT NOT NULL) DEFAULT CHARSET=utf8mb4;
INSERT INTO nursing_fix_lang (cn, es) VALUES
  ('Observations', 'Observaciones'),
  ('Encounter not found', 'Visita no encontrada'),
  ('Care Bundle Record', 'Registro del Paquete de Cuidados'),
  ('Wound Care Record', 'Registro de curaciones'),
  ('Application Record', 'Registro de aplicaciones'),
  ('Recorded', 'Registrado'),
  ('active', 'activos'),
  ('inactive', 'inactivos'),
  ('Active parameters', 'Parámetros activos'),
  ('Active items', 'Ítems activos'),
  ('Adult Therapy', 'Terapia de adultos'),
  ('Pediatric Therapy', 'Terapia pediátrica'),
  ('Intensive', 'Intensivo'),
  ('Minimal', 'Mínimo'),
  ('Medications', 'Medicamentos'),
  ('Pupils', 'Pupilas'),
  ('No data found', 'No se encontraron datos'),
  ('No data found for this record.', 'No se encontraron datos para este registro.'),
  ('Method not allowed', 'Método no permitido'),
  ('Redirecting...', 'Redirigiendo...'),
  ('Error: Could not save the record. Please try again.', 'Error: no se pudo guardar el registro. Intente de nuevo.'),
  ('Error: Missing required data (PID or Encounter)', 'Error: faltan datos obligatorios (PID o visita)'),
  ('See More Vital Records History', 'Ver historial completo de signos vitales'),
  ('Vitals History', 'Historial de signos vitales'),
  ('Back to top', 'Volver arriba'),
  ('Back to patient dashboard', 'Volver a la ficha del paciente'),
  ('Height/Length', 'Talla/Longitud'),
  ('Oxygen Flow Rate', 'Flujo de oxígeno'),
  ('Inhaled Oxygen Concentration', 'Concentración de oxígeno inspirado'),
  ('Pediatric Weight Height Percentile', 'Percentil de peso para la talla (pediátrico)'),
  ('Pediatric BMI Percentile', 'Percentil de IMC (pediátrico)'),
  ('Pediatric Head Circumference Percentile', 'Percentil de circunferencia cefálica (pediátrico)'),
  ('Decimal pounds or pounds and ounces separated by #(e.g. 5#4)', 'Libras con decimales, o libras y onzas separadas por # (p. ej. 5#4)'),
  ('Reason Code', 'Código de motivo'),
  ('Reason Status', 'Estado del motivo'),
  ('Reason Information', 'Información del motivo'),
  ('Select a reason code', 'Seleccione un código de motivo'),
  ('Select a status code', 'Seleccione un estado'),
  ('Pending', 'Pendiente'),
  ('Completed', 'Completado'),
  ('Negated', 'Negado'),
  ('Critical abnormal', 'Anormal crítico'),
  ('Critical high', 'Alto crítico'),
  ('Critical low', 'Bajo crítico'),
  ('Significantly high', 'Significativamente alto'),
  ('Current', 'Actual'),
  ('Reserved', 'Reservada'),
  ('Ended', 'Finalizada'),
  ('Cancelled', 'Cancelada'),
  ('Recorded in the module', 'Registrado en el módulo'),
  ('Detected (SIH or other system)', 'Detectado (SIH u otro sistema)'),
  ('Transfer recorded.', 'Traslado registrado.'),
  ('Bed reserved.', 'Cama reservada.'),
  ('Arrival at the reserved bed recorded.', 'Llegada a la cama reservada registrada.'),
  ('Reservation cancelled.', 'Reserva cancelada.'),
  ('Current location corrected.', 'Ubicación actual corregida.'),
  ('The movement could not be recorded. Check the date and time: it cannot be earlier than the start of the current location.', 'No se pudo registrar el movimiento. Revise la fecha y la hora: no puede ser anterior al inicio de la ubicación actual.'),
  ('Primary team', 'Equipo tratante'),
  ('e.g. Internal Medicine, General Surgery', 'p. ej. Clínica Médica, Cirugía General'),
  ('Current location', 'Ubicación actual'),
  ('Ward / Bed', 'Sala / Cama'),
  ('Not recorded', 'Sin registrar'),
  ('Since', 'Desde'),
  ('No current location.', 'Sin ubicación actual.'),
  ('Transfer patient', 'Trasladar paciente'),
  ('Location start', 'Desde'),
  ('Location end', 'Hasta'),
  ('Reserve bed', 'Reservar cama'),
  ('Correct current location', 'Corregir ubicación actual'),
  ('Date and time', 'Fecha y hora'),
  ('e.g. reason for the transfer', 'p. ej. motivo del traslado'),
  ('Reserved beds', 'Camas reservadas'),
  ('Reserved bed', 'Cama reservada'),
  ('Expected arrival', 'Llegada prevista'),
  ('Reserved by', 'Reservada por'),
  ('Arrival', 'Llegada'),
  ('Patient arrived', 'Llegó el paciente'),
  ('Cancel reservation', 'Cancelar reserva'),
  ('Location history', 'Historial de ubicaciones'),
  ('No movements recorded.', 'No hay movimientos registrados.'),
  ('Recorded by', 'Registrado por'),
  ('Now', 'Ahora'),
  ('Arrival recorded by', 'Llegada registrada por'),
  ('Cancelled by', 'Cancelada por'),
  ('Corrected by', 'Corregida por'),
  ('Date and time of the transfer', 'Fecha y hora del traslado'),
  ('The current location ends and the new one starts at the date and time below. Both stay in the history.', 'La ubicación actual termina y la nueva empieza en la fecha y hora indicadas. Las dos quedan en el historial.'),
  ('The bed is assigned but the patient has not arrived yet. The current location does not change until the arrival is recorded.', 'La cama queda asignada, pero el paciente todavía no llegó. La ubicación actual no cambia hasta que se registre la llegada.'),
  ('Only to fix a data entry error. It does not record a transfer; the history keeps who corrected it and when.', 'Solo para corregir un error de carga. No registra un traslado; en el historial queda quién lo corrigió y cuándo.'),
  ('In this bed since', 'En esta cama desde'),
  ('Location and transfers', 'Ubicación y traslados'),
  ('Patient transfers', 'Traslados'),
  ('The patient is already in that location with that team. Nothing was recorded.', 'El paciente ya está en esa ubicación con ese equipo. No se registró nada.'),
  ('Invalid date', 'Fecha inválida'),
  ('SIH web service', 'Servicio web del SIH'),
  ('Inpatient', 'Internación'),
  ('Finder', 'Buscador'),
  ('Recalls', 'Recordatorios'),
  ('Mode', 'Modo'),
  ('Invalid mode', 'Modo inválido'),
  ('Invalid patient or category', 'Paciente o categoría inválidos'),
  ('Missing patient ID', 'Falta el identificador del paciente'),
  ('Error: Missing required parameters (PID or Encounter)', 'Error: faltan datos obligatorios (paciente o encuentro)'),
  ('Error: Record not found or insufficient permissions.', 'Error: no se encontró el registro o no tiene permisos suficientes.'),
  ('Significantly low', 'Significativamente bajo');

INSERT INTO lang_constants (constant_name)
SELECT t.cn FROM nursing_fix_lang t
WHERE NOT EXISTS (SELECT 1 FROM lang_constants c WHERE BINARY c.constant_name = BINARY t.cn);

UPDATE lang_definitions d
JOIN lang_constants c ON c.cons_id = d.cons_id
JOIN nursing_fix_lang t ON BINARY c.constant_name = BINARY t.cn
SET d.definition = t.es
WHERE d.lang_id IN (3, 4) AND (d.definition IS NULL OR d.definition = '');

INSERT INTO lang_definitions (cons_id, lang_id, definition)
SELECT c.cons_id, ll.lang_id, t.es
FROM nursing_fix_lang t
JOIN lang_constants c ON BINARY c.constant_name = BINARY t.cn
JOIN lang_languages ll ON ll.lang_id IN (3, 4)
WHERE NOT EXISTS (SELECT 1 FROM lang_definitions d WHERE d.cons_id = c.cons_id AND d.lang_id = ll.lang_id);

DROP TEMPORARY TABLE IF EXISTS nursing_fix_lang;

-- -------------------------------------------------------------------------
-- Spanish for OpenEMR core texts the ICU staff sees (menu, top bar, patient
-- summary, encounters, history, vitals, e-signature, messages) that OpenEMR
-- ships without a translation. Only fills what is missing: existing
-- translations are not changed. constant_name is utf8mb4_bin, so a plain
-- comparison is exact and uses its index.
-- -------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS nursing_core_lang;
CREATE TEMPORARY TABLE nursing_core_lang (cn VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL, es TEXT CHARACTER SET utf8mb4 NOT NULL);
INSERT INTO nursing_core_lang (cn, es) VALUES
  ('0 = No risk, ≥1 = At risk', '0 = Sin riesgo, ≥1 = En riesgo'),
  ('2026 Real World Testing Report', 'Informe de pruebas en uso real 2026'),
  ('A valid UDI has not been processed yet', 'Todavía no se procesó un UDI válido'),
  ('A valid UDI has not been processed yet.', 'Todavía no se procesó un UDI válido.'),
  ('ACS 6-item set', 'Conjunto ACS de 6 ítems'),
  ('AMC Requires', 'Requisitos AMC'),
  ('API Clients', 'Clientes de la API'),
  ('Active Codes', 'Códigos activos'),
  ('Add Health Concerns', 'Agregar problemas de salud'),
  ('Add New Message', 'Agregar mensaje nuevo'),
  ('Add Related Health Concerns', 'Agregar problemas de salud relacionados'),
  ('Add Selected Concerns', 'Agregar los problemas seleccionados'),
  ('Add To Existing Message', 'Agregar al mensaje existente'),
  ('Add as a new message', 'Agregar como mensaje nuevo'),
  ('Add to list', 'Agregar a la lista'),
  ('Add/Edit Issue', 'Agregar/editar problema'),
  ('Additional Interventions (Manual)', 'Intervenciones adicionales (manuales)'),
  ('Address Verify', 'Verificar dirección'),
  ('Age at death', 'Edad al fallecer'),
  ('Already Linked', 'Ya vinculado'),
  ('Also Patient', 'También es paciente'),
  ('Amend Existing Message', 'Modificar el mensaje existente'),
  ('Amendment', 'Enmienda'),
  ('Amendment Add', 'Agregar enmienda'),
  ('Amendment Edit', 'Editar enmienda'),
  ('Amendment Print', 'Imprimir enmienda'),
  ('An error occurred signing the form', 'Ocurrió un error al firmar el formulario'),
  ('An error occurred while saving health concerns.', 'Ocurrió un error al guardar los problemas de salud.'),
  ('Any part of the document name', 'Cualquier parte del nombre del documento'),
  ('App', 'Aplicación'),
  ('Append this message', 'Agregar este mensaje'),
  ('Append to the existing message', 'Agregar al mensaje existente'),
  ('Are You Sure you want to delete this name?', '¿Está seguro de que desea eliminar este nombre?'),
  ('Are you sure you want to delete this message?', '¿Está seguro de que desea eliminar este mensaje?'),
  ('Are you sure you wish to inactivate this address?', '¿Está seguro de que desea desactivar esta dirección?'),
  ('Are you sure you wish to inactivate this contact information?', '¿Está seguro de que desea desactivar estos datos de contacto?'),
  ('Are you sure you wish to inactivate this relationship?', '¿Está seguro de que desea desactivar esta relación?'),
  ('Assessment Date', 'Fecha de la evaluación'),
  ('Assessment Information', 'Datos de la evaluación'),
  ('Assessment completed for', 'Evaluación completada para'),
  ('Assessor', 'Evaluador'),
  ('At risk', 'En riesgo'),
  ('Attaching Fax Id', 'Adjuntando el fax n.º'),
  ('Attachment', 'Adjunto'),
  ('Audit Log Tamper', 'Alteración del registro de auditoría'),
  ('Auto-calculated', 'Calculado automáticamente'),
  ('Back to Messages', 'Volver a los mensajes'),
  ('Back to Summary', 'Volver al resumen'),
  ('Based on the SDOH assessment, the following health concerns may be relevant for this patient. Select any that apply:', 'Según la evaluación de determinantes sociales (SDOH), estos problemas de salud pueden ser relevantes para el paciente. Seleccione los que correspondan:'),
  ('Begin Date and Time', 'Fecha y hora de inicio'),
  ('Build diagnoses list by clicking appropriate code button.', 'Arme la lista de diagnósticos haciendo clic en el botón del código correspondiente.'),
  ('CCD/CCR/CCDA Documents must be in a xml or pdf format in order to send via Direct', 'Los documentos CCD/CCR/CCDA deben estar en formato XML o PDF para enviarlos por Direct'),
  ('Care Experience Preferences', 'Preferencias sobre la atención'),
  ('Care Planning', 'Planificación de cuidados'),
  ('Caregiver Status', 'Situación del cuidador'),
  ('Check New Trusted Messages', 'Revisar mensajes seguros nuevos'),
  ('Check new messages failed. Check the server logs for more information.', 'No se pudieron revisar los mensajes nuevos. Revise los registros del servidor para más información.'),
  ('Check the server logs for more details', 'Revise los registros del servidor para más detalles'),
  ('Checking the lock checkbox will prevent any further edits on any forms in this encounter.', 'Si marca la opción de bloqueo, ya no se podrá editar ningún formulario de este encuentro.'),
  ('Choose a document to attach', 'Elija un documento para adjuntar'),
  ('Choose a patient', 'Elija un paciente'),
  ('Claim File Tracker', 'Seguimiento de archivos de reclamos'),
  ('Classification Type', 'Tipo de clasificación'),
  ('Click here to add new row', 'Haga clic aquí para agregar una fila'),
  ('Click here to see observation results', 'Haga clic aquí para ver los resultados de la observación'),
  ('Click here to view and graph all labdata.', 'Haga clic aquí para ver y graficar todos los datos de laboratorio.'),
  ('Click me to Open Patient Dashboard', 'Haga clic para abrir la ficha del paciente'),
  ('Click or change focus to dismiss', 'Haga clic o cambie de campo para cerrar'),
  ('Click to add patient', 'Haga clic para agregar un paciente'),
  ('Click to open SMS for patient', 'Haga clic para enviar un SMS al paciente'),
  ('Click to select or change coding', 'Haga clic para elegir o cambiar el código'),
  ('Click to show messages for all users', 'Haga clic para ver los mensajes de todos los usuarios'),
  ('Click to show messages for only the current user', 'Haga clic para ver solo los mensajes del usuario actual'),
  ('Click to view Help', 'Haga clic para ver la ayuda'),
  ('Close Patient Chart', 'Cerrar la ficha del paciente'),
  ('Co-Pay', 'Copago'),
  ('Code Description', 'Descripción del código'),
  ('Collections and Aging', 'Cobranzas y antigüedad de saldos'),
  ('Completion Status', 'Estado de finalización'),
  ('Compose Trusted Direct Message', 'Redactar mensaje seguro (Direct)'),
  ('Contact support if you continue to have issues', 'Si el problema continúa, comuníquese con soporte'),
  ('Create New Message', 'Crear mensaje nuevo'),
  ('Create follow-up encounter', 'Crear encuentro de seguimiento'),
  ('Creating...', 'Creando...'),
  ('Current Adherence to Medication', 'Adherencia actual a la medicación'),
  ('Current Working Procedure Diagnoses', 'Diagnósticos del procedimiento en curso'),
  ('Dashboard{{patient file}}', 'Resumen'),
  ('Date Asserted', 'Fecha de registro'),
  ('Date VIS Presented', 'Fecha de entrega de la hoja informativa de la vacuna'),
  ('Date VIS Published', 'Fecha de publicación de la hoja informativa de la vacuna'),
  ('Date of Onset', 'Fecha de inicio'),
  ('Deceased Date should not be greater than Today', 'La fecha de fallecimiento no puede ser posterior a hoy'),
  ('Delete Encounter Form', 'Eliminar formulario del encuentro'),
  ('Diagnostics', 'Diagnóstico del sistema'),
  ('Dicom Viewer', 'Visor DICOM'),
  ('Disability Status', 'Situación de discapacidad'),
  ('Dismiss', 'Descartar'),
  ('Displaying the following number of most recent messages', 'Cantidad de mensajes recientes que se muestran'),
  ('Document Finder', 'Buscador de documentos'),
  ('Domain', 'Dominio'),
  ('Due date', 'Fecha de vencimiento'),
  ('Duplicate codes are deleted from editor list otherwise, code will append to list.', 'Los códigos duplicados se eliminan de la lista del editor; los demás se agregan a la lista.'),
  ('EDI History', 'Historial EDI'),
  ('Edit Current Patient', 'Editar el paciente actual'),
  ('Edit Form', 'Editar formulario'),
  ('Edit History and Lifestyle', 'Editar antecedentes y estilo de vida'),
  ('Edit Issue', 'Editar problema'),
  ('Edit this form', 'Editar este formulario'),
  ('Edit/Record Disclosure', 'Editar/registrar divulgación'),
  ('Education Level', 'Nivel de instrucción'),
  ('Email Send Test', 'Prueba de envío de correo'),
  ('Emergency', 'Emergencia'),
  ('Employment Status', 'Situación laboral'),
  ('Encounter saved successfully.', 'Encuentro guardado correctamente.'),
  ('Encounter type', 'Tipo de encuentro'),
  ('End Date and Time', 'Fecha y hora de fin'),
  ('End date must be after start date', 'La fecha de fin debe ser posterior a la de inicio'),
  ('End of report', 'Fin del informe'),
  ('Enter ID', 'Ingrese el ID'),
  ('Enter an amendment...', 'Ingrese una enmienda...'),
  ('Enter any additional interventions, one per line', 'Ingrese las intervenciones adicionales, una por línea'),
  ('Enter name', 'Ingrese el nombre'),
  ('Enter your password to sign the form', 'Ingrese su contraseña para firmar el formulario'),
  ('Entered By', 'Registrado por'),
  ('Entered in Error', 'Cargado por error'),
  ('Error creating person', 'Error al crear la persona'),
  ('Error finding diagnosis element. Try again.', 'No se encontró el diagnóstico. Intente de nuevo.'),
  ('Error performing search. Please try again.', 'Error al realizar la búsqueda. Intente de nuevo.'),
  ('Error saving SDOH record.', 'Error al guardar la evaluación SDOH.'),
  ('Estimated Due Date', 'Fecha probable de parto'),
  ('Expand or collapse all items in section', 'Expandir o contraer todos los elementos de la sección'),
  ('Expand/Collapse this form', 'Expandir/contraer este formulario'),
  ('Expired', 'Vencido'),
  ('Failed to preview document', 'No se pudo mostrar la vista previa del documento'),
  ('Failed to retrieve validation results from server', 'No se pudieron obtener del servidor los resultados de la validación'),
  ('Find Code History', 'Buscar en el historial de códigos'),
  ('First name and last name are required', 'El nombre y el apellido son obligatorios'),
  ('Food Insecurity Status', 'Inseguridad alimentaria'),
  ('Forcing confirmation that recipient received a message could fail to send if the recipient''s system does not support or has disabled receipt confirmation.', 'Exigir la confirmación de recepción puede impedir el envío si el sistema del destinatario no la admite o la tiene desactivada.'),
  ('Form signed successfully', 'Formulario firmado correctamente'),
  ('Found', 'Encontrado'),
  ('Generated Goals', 'Objetivos generados'),
  ('Generated Interventions', 'Intervenciones generadas'),
  ('Go back', 'Volver'),
  ('Goals are automatically generated based on positive SDOH findings', 'Los objetivos se generan automáticamente a partir de los hallazgos positivos de la evaluación SDOH'),
  ('Google Sign-In is enabled but the service is unreachable.', 'El inicio de sesión con Google está activado, pero el servicio no responde.'),
  ('Group Details', 'Detalles del grupo'),
  ('Group name', 'Nombre del grupo'),
  ('Groups', 'Grupos'),
  ('HIPAA Notice', 'Aviso de privacidad (HIPAA)'),
  ('Hide More Fields', 'Ocultar campos adicionales'),
  ('History & Lifestyle', 'Antecedentes y estilo de vida'),
  ('History and Lifestyle', 'Antecedentes y estilo de vida'),
  ('Hospital date needed for successful billing of hospital encounters', 'Se necesita la fecha de hospitalización para facturar los encuentros hospitalarios'),
  ('Hunger Score', 'Puntaje de hambre'),
  ('Hunger VS', 'Signo vital de hambre'),
  ('Hunger Vital Signs', 'Signos vitales de hambre'),
  ('ID Card / Photos', 'Documento de identidad / fotos'),
  ('IP Tracker', 'Seguimiento de IP'),
  ('Icons', 'Íconos'),
  ('If set as high will restrict visibility of encounter to users belonging to certain groups (AROs). By default - Physicians and Administrators', 'Si se marca como alta, solo podrán ver el encuentro los usuarios de ciertos grupos (por defecto, médicos y administradores)'),
  ('If this page does not refresh, reload the Messages page manually', 'Si esta página no se actualiza, vuelva a cargar la página de mensajes'),
  ('Immunization Expiration Date', 'Fecha de vencimiento de la vacuna'),
  ('Immunization Ordering Provider', 'Profesional que indicó la vacuna'),
  ('Import Holidays', 'Importar feriados'),
  ('Inactivate Address', 'Desactivar dirección'),
  ('Inactivate Contact', 'Desactivar contacto'),
  ('Inactive Messages', 'Mensajes inactivos'),
  ('Include Inactive', 'Incluir inactivos'),
  ('Information Source', 'Fuente de la información'),
  ('Injury Log', 'Registro de lesiones'),
  ('Interventions / Referrals', 'Intervenciones / derivaciones'),
  ('Interventions are automatically generated based on positive SDOH findings', 'Las intervenciones se generan automáticamente a partir de los hallazgos positivos de la evaluación SDOH'),
  ('Invalid google log in', 'Inicio de sesión con Google inválido'),
  ('Is Primary Record (not reported by secondary source)?', '¿Es un registro primario (no informado por una fuente secundaria)?'),
  ('LOINC 88121-9 (Required)', 'LOINC 88121-9 (obligatorio)'),
  ('Last Modified', 'Última modificación'),
  ('Last Updated', 'Última actualización'),
  ('Last update', 'Última actualización'),
  ('Less', 'Menos'),
  ('Linked document', 'Documento vinculado'),
  ('Linked procedure order', 'Orden de procedimiento vinculada'),
  ('Load Compendium', 'Cargar catálogo'),
  ('Main Menu Logo', 'Logo del menú principal'),
  ('Manage Duplicates', 'Administrar duplicados'),
  ('Mark Message as Completed', 'Marcar el mensaje como completado'),
  ('Maximum displayable results reached. Narrow your search', 'Se alcanzó el máximo de resultados que se pueden mostrar. Acote la búsqueda'),
  ('MedEx Setup', 'Configuración de MedEx'),
  ('MedEx needs a valid mobile number to send SMS messages...', 'MedEx necesita un número de celular válido para enviar SMS...'),
  ('Medical Dashboard', 'Resumen médico'),
  ('Medical Issues', 'Problemas médicos'),
  ('Medical Record Dashboard', 'Resumen de la historia clínica'),
  ('Medication Adherence', 'Adherencia a la medicación'),
  ('Medication Dosage Instructions', 'Indicaciones de dosis'),
  ('Medication Request Intent', 'Tipo de indicación de medicación'),
  ('Medication Usage', 'Uso de la medicación'),
  ('Message Center', 'Centro de mensajes'),
  ('Message List', 'Lista de mensajes'),
  ('Message or an attached document', 'Mensaje o documento adjunto'),
  ('Messages, Reminders, Recalls', 'Mensajes, avisos y recordatorios'),
  ('Missing', 'Falta'),
  ('Missing patient id.', 'Falta el identificador del paciente.'),
  ('Missing required parameters.', 'Faltan datos obligatorios.'),
  ('More', 'Más'),
  ('More records found than could be displayed. Please narrow your search criteria.', 'Se encontraron más registros de los que se pueden mostrar. Acote los criterios de búsqueda.'),
  ('More than', 'Más de'),
  ('Most often this is due to a Username/Password mismatch', 'Lo más habitual es que el usuario o la contraseña no coincidan'),
  ('My Messages', 'Mis mensajes'),
  ('New Assessment', 'Nueva evaluación'),
  ('New Recall', 'Nuevo recordatorio'),
  ('New amendment request', 'Nueva solicitud de enmienda'),
  ('No SDOH assessments found.', 'No se encontraron evaluaciones SDOH.'),
  ('No amendment requests available', 'No hay solicitudes de enmienda'),
  ('No health concerns are currently configured in the system.', 'No hay problemas de salud configurados en el sistema.'),
  ('No health concerns selected. Continue without adding any?', 'No seleccionó ningún problema de salud. ¿Continuar sin agregar ninguno?'),
  ('No national provider number is setup for your account.', 'Su cuenta no tiene configurado un número de registro profesional.'),
  ('No parameters selected', 'No se seleccionaron parámetros'),
  ('No patient selected', 'No hay paciente seleccionado'),
  ('No patient selected.', 'No hay paciente seleccionado.'),
  ('No risk', 'Sin riesgo'),
  ('No tracks have been documented.', 'No hay registros documentados.'),
  ('Non-Provider', 'No profesional'),
  ('None{{Disclosure}}', 'Ninguna'),
  ('None{{Encounter}}', 'Ninguno'),
  ('None{{Issue}}', 'Ninguno'),
  ('None{{Note}}', 'Ninguna'),
  ('None{{Result}}', 'Ninguno'),
  ('None{{Sensitivity}}', 'Ninguna'),
  ('Not authorized to view this encounter', 'No tiene autorización para ver este encuentro'),
  ('Nothing to show.', 'No hay nada para mostrar.'),
  ('Observation Criteria', 'Criterio de observación'),
  ('Observation Criteria Value', 'Valor del criterio de observación'),
  ('Observation Results', 'Resultados de la observación'),
  ('Ok', 'Aceptar'),
  ('Old Medication', 'Medicación anterior'),
  ('On first page', 'En la primera página'),
  ('On opening, all dx code rows that match the new procedure from procedure order form will be marked and then will scroll to the first match.', 'Al abrir, se marcan los diagnósticos que coinciden con el procedimiento nuevo de la orden y la lista se desplaza hasta el primero.'),
  ('Once finished editing, click Save. The procedure forms current procedure diagnoses will fill exactly as built in this dialog.', 'Cuando termine de editar, haga clic en Guardar. Los diagnósticos del procedimiento se completarán tal como quedaron en esta ventana.'),
  ('One or more required fields are missing in order to send your message', 'Faltan uno o más datos obligatorios para enviar el mensaje'),
  ('Only Show Active Codes ', 'Mostrar solo códigos activos '),
  ('Open Address Book', 'Abrir la agenda de contactos'),
  ('Open Encounter', 'Abrir encuentro'),
  ('Open in New Browser Tab', 'Abrir en una pestaña nueva del navegador'),
  ('OpenEMR Website', 'Sitio web de OpenEMR'),
  ('Or create this new field ID', 'O cree este nuevo ID de campo'),
  ('Origin', 'Origen'),
  ('Origin Description', 'Descripción del origen'),
  ('Overall Disability Status', 'Situación general de discapacidad'),
  ('Past Therapy Group Encounters', 'Encuentros anteriores de grupos terapéuticos'),
  ('Patient Goals (SDOH)', 'Objetivos del paciente (SDOH)'),
  ('Patient Images', 'Imágenes del paciente'),
  ('Patient Message', 'Mensaje del paciente'),
  ('Patient Messages', 'Mensajes de pacientes'),
  ('Patient must be set manually', 'El paciente se debe indicar manualmente'),
  ('Patient name', 'Nombre del paciente'),
  ('Patient sex must be set to male or female to render a growth chart.', 'Para mostrar la curva de crecimiento, el sexo del paciente debe ser masculino o femenino.'),
  ('Payment Processing', 'Procesamiento de pagos'),
  ('Pediatric Height Weight Percentile', 'Percentil de talla y peso (pediátrico)'),
  ('Pending Faxes', 'Faxes pendientes'),
  ('Pending SMS', 'SMS pendientes'),
  ('Person created successfully', 'Persona creada correctamente'),
  ('Please Enter End Date greater than Begin Date!', '¡Ingrese una fecha de fin posterior a la de inicio!'),
  ('Please call our office to schedule', 'Llame a nuestra oficina para agendar'),
  ('Please enter a dollar amount using only numbers and a decimal point.', 'Ingrese un monto usando solo números y un punto decimal.'),
  ('Please enter a search term to search for a code', 'Ingrese un término para buscar un código'),
  ('Please enter a valid email address', 'Ingrese un correo electrónico válido'),
  ('Please fill in all required fields: ', 'Complete todos los campos obligatorios: '),
  ('Please select an item that has more data', 'Seleccione un elemento que tenga más datos'),
  ('Please select message(s) to delete', 'Seleccione los mensajes que desea eliminar'),
  ('Please try again or check your internet connection', 'Intente de nuevo o revise su conexión a internet'),
  ('Plot item', 'Graficar elemento'),
  ('Portal Audits', 'Auditorías del portal'),
  ('Portal Dashboard', 'Panel del portal'),
  ('Portal Mail', 'Correo del portal'),
  ('Portal Payments', 'Pagos del portal'),
  ('Posting Payments', 'Registro de pagos'),
  ('Postpartum End Date', 'Fecha de fin del puerperio'),
  ('Postpartum Status', 'Estado de puerperio'),
  ('Preferences updated successfully', 'Preferencias actualizadas correctamente'),
  ('Pregnancy / Postpartum Status', 'Embarazo / puerperio'),
  ('Pregnancy Status', 'Estado de embarazo'),
  ('Pregnancy intention in the next year', 'Intención de embarazo en el próximo año'),
  ('Print Amendments', 'Imprimir enmiendas'),
  ('Print Entire Listing', 'Imprimir la lista completa'),
  ('Print Record (HTML)', 'Imprimir el registro (HTML)'),
  ('Print Record (PDF)', 'Imprimir el registro (PDF)'),
  ('Print page', 'Imprimir página'),
  ('Print this form', 'Imprimir este formulario'),
  ('Problem loading.', 'Error al cargar.'),
  ('Problems', 'Problemas'),
  ('Proceeding to Preferences', 'Pasando a las preferencias'),
  ('Process UDI', 'Procesar UDI'),
  ('Re-Process UDI', 'Volver a procesar UDI'),
  ('Reactivate Address', 'Reactivar dirección'),
  ('Reactivate Contact', 'Reactivar contacto'),
  ('Reactivate this address?', '¿Reactivar esta dirección?'),
  ('Reactivate this contact information?', '¿Reactivar estos datos de contacto?'),
  ('Recall', 'Recordatorio'),
  ('Recall Board', 'Tablero de recordatorios'),
  ('Recipient required unless status is Done', 'Se requiere un destinatario, salvo que el estado sea Terminado'),
  ('Recommend not leaving or refreshing or you may lose data.', 'Se recomienda no salir ni recargar la página, porque podría perder datos.'),
  ('Report Results', 'Resultados de informes'),
  ('Report Unavailable', 'Informe no disponible'),
  ('Reported By (Address Book User)', 'Informado por (contacto de la agenda)'),
  ('Request Description', 'Descripción de la solicitud'),
  ('Request Status', 'Estado de la solicitud'),
  ('Requested By', 'Solicitado por'),
  ('Return Dashboard', 'Volver al resumen'),
  ('Return To Report Results', 'Volver a los resultados del informe'),
  ('Revenue', 'Ingresos'),
  ('Revenue Code', 'Código de ingreso'),
  ('Review Encounter', 'Revisar encuentro'),
  ('Run Setup again or contact support for assistance', 'Vuelva a ejecutar la configuración o comuníquese con soporte'),
  ('SDOH Assessment', 'Evaluación SDOH'),
  ('SDOH Assessment (USCDI v3)', 'Evaluación SDOH (USCDI v3)'),
  ('SDOH Assessment Saved Successfully', 'Evaluación SDOH guardada correctamente'),
  ('SDOH Assessments', 'Evaluaciones SDOH'),
  ('SMS Zone', 'Zona SMS'),
  ('SNOMED-CT Code', 'Código SNOMED-CT'),
  ('Save Assessment', 'Guardar evaluación'),
  ('Save as new message', 'Guardar como mensaje nuevo'),
  ('Screening Tool', 'Herramienta de tamizaje'),
  ('Search by', 'Buscar por'),
  ('Search by any demographics', 'Buscar por cualquier dato del paciente'),
  ('Search by name:', 'Buscar por nombre:'),
  ('Search failed', 'La búsqueda falló'),
  ('Search for patient by entering whole or part of any demographics field information', 'Busque un paciente escribiendo todo o parte de cualquier dato de su ficha'),
  ('Search for patient...', 'Buscar paciente...'),
  ('Search term is required for this code type.', 'Este tipo de código requiere un término de búsqueda.'),
  ('Search with exact method', 'Buscar con coincidencia exacta'),
  ('See observation results', 'Ver resultados de la observación'),
  ('Select Amendment Date', 'Seleccione la fecha de la enmienda'),
  ('Select Assessor', 'Seleccione el evaluador'),
  ('Select Codes', 'Seleccionar códigos'),
  ('Select Encounter', 'Seleccionar encuentro'),
  ('Select One', 'Seleccione uno'),
  ('Select a Code', 'Seleccione un código'),
  ('Select amendments to print', 'Seleccione las enmiendas que desea imprimir'),
  ('Select from list or type your own in Title', 'Elija de la lista o escriba el suyo en Título'),
  ('Select items', 'Seleccionar elementos'),
  ('Select users from the dropdown list', 'Seleccione usuarios de la lista'),
  ('Selected health concerns will be added as active conditions for this patient and linked to this SDOH assessment.', 'Los problemas de salud seleccionados se agregarán como problemas activos del paciente, vinculados a esta evaluación SDOH.'),
  ('Serv Rep', 'Repr. de servicio'),
  ('Show More Fields', 'Mostrar campos adicionales'),
  ('Showing documents for patient', 'Documentos del paciente'),
  ('Sign', 'Firmar'),
  ('Sign out', 'Cerrar sesión'),
  ('Sign-up successful for', 'Registro exitoso para'),
  ('Site', 'Sitio'),
  ('Skip - No Concerns', 'Omitir: sin problemas'),
  ('Social Context', 'Contexto social'),
  ('Social Determinants of Health (SDOH)', 'Determinantes sociales de la salud (SDOH)'),
  ('Social Determinants of Health Assessment', 'Evaluación de determinantes sociales de la salud'),
  ('Sorry you are not privileged enough. Enrollment is limited to Administrator accounts.', 'No tiene permisos suficientes. La inscripción está limitada a cuentas de administrador.'),
  ('Subscriber Address Line 1', 'Dirección del titular (línea 1)'),
  ('Subscriber Address Line 2', 'Dirección del titular (línea 2)'),
  ('Substance Refusal Reason', 'Motivo del rechazo de la sustancia'),
  ('Summary Of Care Provided?', '¿Se entregó el resumen de atención?'),
  ('Summary of Care Provided?', '¿Se entregó el resumen de atención?'),
  ('Supported document mime types are the following', 'Los tipos de documento admitidos son los siguientes'),
  ('The destination form was closed.', 'Se cerró el formulario de destino.'),
  ('The finder table is grouped by past procedures then diagnosis code. Although there may be duplicate dx codes, they will be grouped with the appropriate procedure making building diagnoses list easier.', 'La tabla del buscador se agrupa por procedimientos anteriores y luego por código de diagnóstico. Aunque haya diagnósticos repetidos, quedan agrupados con su procedimiento, lo que facilita armar la lista de diagnósticos.'),
  ('The following field has an invalid value', 'El siguiente campo tiene un valor inválido'),
  ('The following fields are required to send your message', 'Estos campos son obligatorios para enviar el mensaje'),
  ('The legacy code finder is still available for codes not found in this finder or code list editing.', 'El buscador de códigos anterior sigue disponible para los códigos que no aparecen aquí o para editar la lista.'),
  ('The password you entered is invalid', 'La contraseña ingresada no es válida'),
  ('The search field cannot be empty. Please enter a search term', 'El campo de búsqueda no puede quedar vacío. Ingrese un término de búsqueda'),
  ('The system received a document it does not know how to process through the Direct protocol', 'El sistema recibió por Direct un documento que no sabe procesar'),
  ('The system will not be able to provide legal attestation that you have reviewed this information without a valid national provider number.', 'Sin un número de registro profesional válido, el sistema no puede dejar constancia legal de que usted revisó esta información.'),
  ('There are no messages on file for this patient.', 'No hay mensajes registrados para este paciente.'),
  ('There is a lab order', 'Hay una orden de laboratorio'),
  ('There was an error in communicating with the network', 'Hubo un error de comunicación con la red'),
  ('There was an error in sending the message to your target recipient', 'Hubo un error al enviar el mensaje al destinatario'),
  ('There was an unknown system error', 'Ocurrió un error desconocido del sistema'),
  ('This action cannot be undone. Are you sure you wish to delete this form?', 'Esta acción no se puede deshacer. ¿Está seguro de que desea eliminar este formulario?'),
  ('This dialog is generated from patient problem diagnoses and the accumulated diagnoses of all past procedures.', 'Esta ventana se arma con los diagnósticos de los problemas del paciente y los de todos sus procedimientos anteriores.'),
  ('This is not a valid request', 'La solicitud no es válida'),
  ('This item does not have enough data to graph', 'Este elemento no tiene datos suficientes para graficar'),
  ('This patient does not allow SMS messaging!', '¡Este paciente no acepta mensajes SMS!'),
  ('This patient has already been added to the list', 'Este paciente ya está en la lista'),
  ('To (Trusted Email Address)', 'Para (correo seguro)'),
  ('To Dashboard', 'Ir al resumen'),
  ('To add messages, please click here', 'Para agregar mensajes, haga clic aquí'),
  ('To enable help - Go to  Administration > Globals > Features > Enable Help Modal', 'Para activar la ayuda, vaya a Administración > Configuración global > Funciones > Activar ventana de ayuda'),
  ('Toggle All', 'Marcar/desmarcar todo'),
  ('Tool', 'Herramienta'),
  ('Total Positives', 'Total de positivos'),
  ('Total SDOH score should have at least one positive. Please review before saving.', 'El puntaje SDOH total debería tener al menos un positivo. Revíselo antes de guardar.'),
  ('To{{Destination}}', 'Para'),
  ('Treatment Intervention Preferences', 'Preferencias de tratamiento'),
  ('Trusted Email Address', 'Correo seguro'),
  ('Trusted message was sent', 'Se envió el mensaje seguro'),
  ('Type to search and select revenue code', 'Escriba para buscar y seleccionar el código de ingreso'),
  ('UDI field is missing', 'Falta el campo UDI'),
  ('UDI search failed', 'La búsqueda del UDI falló'),
  ('UDI{{Unique Device Identifier}}', 'UDI'),
  ('Unknown error', 'Error desconocido'),
  ('Update by', 'Actualizado por'),
  ('Validating document for errors', 'Validando el documento'),
  ('Validation failed for form', 'La validación del formulario falló'),
  ('Validation report', 'Informe de validación'),
  ('Value cannot be negative', 'El valor no puede ser negativo'),
  ('Value is outside acceptable range', 'El valor está fuera del rango aceptable'),
  ('Value is outside typical clinical range', 'El valor está fuera del rango clínico habitual'),
  ('Verification', 'Verificación'),
  ('Verify Address', 'Verificar dirección'),
  ('Verify Message Received', 'Confirmar la recepción del mensaje'),
  ('Veteran Status', 'Condición de excombatiente'),
  ('View All', 'Ver todo'),
  ('View Processed Document', 'Ver el documento procesado'),
  ('We ran into some problems connecting your EHR to the MedEx servers', 'Hubo problemas al conectar la historia clínica con los servidores de MedEx'),
  ('Within the past 12 months, the food we bought just didn''t last and we didn''t have money to get more', 'En los últimos 12 meses, la comida que compramos no alcanzó y no tuvimos dinero para comprar más'),
  ('Within the past 12 months, we worried whether our food would run out before we got money to buy more', 'En los últimos 12 meses, nos preocupó que se terminara la comida antes de tener dinero para comprar más'),
  ('You have granted medical information permissions. Ensure proper HIPAA authorization forms are completed and on file.', 'Otorgó permisos sobre información médica. Asegúrese de que estén completos y archivados los formularios de autorización correspondientes.'),
  ('You must add a patient to the list before hitting ok', 'Debe agregar un paciente a la lista antes de aceptar'),
  ('You must provide a patient name or id to add to the list', 'Debe indicar el nombre o el ID del paciente para agregarlo a la lista'),
  ('Your message was successfully sent', 'El mensaje se envió correctamente'),
  ('Your password is your signature', 'Su contraseña es su firma'),
  ('day ago', 'día atrás'),
  ('does not have enough data to graph', 'no tiene datos suficientes para graficar'),
  ('eSign', 'Firma electrónica'),
  ('eSign Log', 'Registro de firmas electrónicas'),
  ('existing person', 'persona existente'),
  ('existing persons', 'personas existentes'),
  ('for this patient not yet assigned to any encounter.', 'de este paciente que todavía no están asignados a ningún encuentro.'),
  ('linked to procedure order', 'vinculado a la orden de procedimiento'),
  ('months ago', 'meses atrás'),
  ('to{{Destination}}', 'para'),
  ('years ago', 'años atrás'),
  ('yyyy-mm-dd HH:MM date of onset, surgery or start of medication', 'aaaa-mm-dd HH:MM fecha de inicio, de la cirugía o del comienzo de la medicación'),
  ('yyyy-mm-dd HH:MM date of recovery or end of medication', 'aaaa-mm-dd HH:MM fecha de recuperación o de fin de la medicación');

INSERT INTO lang_constants (constant_name)
SELECT t.cn FROM nursing_core_lang t
WHERE NOT EXISTS (SELECT 1 FROM lang_constants c WHERE c.constant_name = t.cn);

UPDATE lang_definitions d
JOIN lang_constants c ON c.cons_id = d.cons_id
JOIN nursing_core_lang t ON c.constant_name = t.cn
SET d.definition = t.es
WHERE d.lang_id IN (3, 4) AND (d.definition IS NULL OR d.definition = '');

INSERT INTO lang_definitions (cons_id, lang_id, definition)
SELECT c.cons_id, ll.lang_id, t.es
FROM nursing_core_lang t
JOIN lang_constants c ON c.constant_name = t.cn
JOIN lang_languages ll ON ll.lang_id IN (3, 4)
WHERE NOT EXISTS (SELECT 1 FROM lang_definitions d WHERE d.cons_id = c.cons_id AND d.lang_id = ll.lang_id);

DROP TEMPORARY TABLE IF EXISTS nursing_core_lang;

-- -------------------------------------------------------------------------
-- Spanish for more OpenEMR texts of the patient chart and the encounter form
-- (templates, demographics fields, short lists, form and category names) and
-- for the nationality list. Only fills missing translations, comparing on the
-- indexed, case-sensitive constant_name.
-- -------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS nursing_core_lang2;
CREATE TEMPORARY TABLE nursing_core_lang2 (cn VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL, es TEXT CHARACTER SET utf8mb4 NOT NULL);
INSERT INTO nursing_core_lang2 (cn, es) VALUES
  (' Online Support', ' Soporte en línea'),
  ('API Access', 'Acceso por API'),
  ('Active From', 'Activo desde'),
  ('Add / Edit', 'Agregar / editar'),
  ('Add Related Person', 'Agregar persona relacionada'),
  ('Additional Addresses', 'Direcciones adicionales'),
  ('Additional Telecom Contacts', 'Contactos telefónicos adicionales'),
  ('Additional gender category or other, please specify', 'Otra categoría de género, especifique'),
  ('Address Type', 'Tipo de dirección'),
  ('Address Use', 'Uso de la dirección'),
  ('Addresses', 'Direcciones'),
  ('Allow Patient Portal in Demographics > Choices', 'Habilite el portal del paciente en Datos demográficos > Opciones'),
  ('Amended', 'Enmendado'),
  ('Ask me', 'Preguntarme'),
  ('Asked But Declined', 'Se preguntó, pero no quiso responder'),
  ('Asked But Unknown', 'Se preguntó, pero no lo sabe'),
  ('Assign', 'Asignar'),
  ('Birth Date:', 'Fecha de nacimiento:'),
  ('Birth Name', 'Nombre de nacimiento'),
  ('Birth Sex', 'Sexo al nacer'),
  ('Can Make Medical Decisions', 'Puede tomar decisiones médicas'),
  ('Can Receive Medical Information', 'Puede recibir información médica'),
  ('Check to generate new registration code', 'Marque para generar un nuevo código de registro'),
  ('Choose not to disclose', 'Prefiere no decirlo'),
  ('Class', 'Clase'),
  ('Click to select group', 'Haga clic para elegir el grupo'),
  ('Clinical Instructions', 'Indicaciones clínicas'),
  ('Clinical Notes', 'Notas clínicas'),
  ('Coded Value (from answer list)', 'Valor codificado (de la lista de respuestas)'),
  ('Collection Balance', 'Saldo en cobranza'),
  ('Contact Permissions: ', 'Permisos de contacto: '),
  ('Contact Priority:', 'Prioridad de contacto:'),
  ('County/District', 'Distrito'),
  ('Create', 'Crear'),
  ('Create New Person and Add Relationship', 'Crear persona nueva y agregar la relación'),
  ('Credentials', 'Credenciales'),
  ('DSI Source Information', 'Información de origen del apoyo a decisiones'),
  ('Date Recorded', 'Fecha de registro'),
  ('Date of Birth:', 'Fecha de nacimiento:'),
  ('Date of onset or hospitalization', 'Fecha de inicio o de internación'),
  ('Dated', 'Fechado'),
  ('Decline to answer', 'Prefiere no responder'),
  ('Declined To Specify', 'Prefirió no especificar'),
  ('Delete Patient', 'Eliminar paciente'),
  ('Discharge Disposition', 'Destino al alta'),
  ('Dispensed Date', 'Fecha de dispensación'),
  ('Dispensed Medications', 'Medicamentos dispensados'),
  ('Don''t know', 'No sabe'),
  ('Donate', 'Donar'),
  ('Dosage Instructions', 'Indicaciones de dosis'),
  ('Effective Date End', 'Fecha de fin de vigencia'),
  ('Email is Enforced. Enter an Email in Demographics Contact.', 'El correo electrónico es obligatorio. Cárguelo en los datos de contacto.'),
  ('Employer Address Line 2', 'Dirección del empleador (línea 2)'),
  ('Employment End Date', 'Fecha de fin del empleo'),
  ('Employment Start Date', 'Fecha de inicio del empleo'),
  ('Encounter Provider', 'Profesional del encuentro'),
  ('Ensora Account Status', 'Estado de la cuenta Ensora'),
  ('Enter an Email in Demographics Contact for Sending Credentials.', 'Cargue un correo en los datos de contacto para enviar las credenciales.'),
  ('Enter first name', 'Ingrese el nombre'),
  ('Enter last name', 'Ingrese el apellido'),
  ('Extended family', 'Familia extendida'),
  ('Eye Exam', 'Examen oftalmológico'),
  ('Failed to load answers', 'No se pudieron cargar las respuestas'),
  ('Female-to-Male (FTM)/Transgender Male/Trans Man', 'Hombre trans (de femenino a masculino)'),
  ('Follow up for', 'Seguimiento de'),
  ('Foster Child', 'Hijo/a de crianza'),
  ('Free Text', 'Texto libre'),
  ('Friend', 'Amigo/a'),
  ('Functional and Cognitive Status', 'Estado funcional y cognitivo'),
  ('Future Appointments', 'Turnos futuros'),
  ('Gender Identity', 'Identidad de género'),
  ('Gender:', 'Género:'),
  ('Genderqueer, neither exclusively male nor female', 'Género no binario (ni exclusivamente masculino ni femenino)'),
  ('Generate new registration code', 'Generar un nuevo código de registro'),
  ('Grandchild', 'Nieto/a'),
  ('Grandparent', 'Abuelo/a'),
  ('Group Attendance Form', 'Formulario de asistencia grupal'),
  ('Group I', 'Grupo I'),
  ('Group II', 'Grupo II'),
  ('Group III', 'Grupo III'),
  ('Group IV', 'Grupo IV'),
  ('Group Therapy', 'Terapia grupal'),
  ('Group V', 'Grupo V'),
  ('Guardian', 'Tutor/a'),
  ('Handicapped dependent', 'Dependiente con discapacidad'),
  ('Health and Behavioral Assessment', 'Evaluación de salud y comportamiento'),
  ('Heavy tobacco smoker', 'Fumador intenso'),
  ('Holidays', 'Feriados'),
  ('Identifies as Female', 'Se identifica como mujer'),
  ('Identifies as Male', 'Se identifica como hombre'),
  ('Identifies as nonbinary gender (finding)', 'Se identifica como no binario'),
  ('Immunization Registry Status', 'Estado en el registro de vacunación'),
  ('Immunization Registry Status Effective Date', 'Fecha de vigencia del estado en el registro de vacunación'),
  ('In Collection', 'En cobranza'),
  ('Inactivate', 'Desactivar'),
  ('Inactive - Lost to follow - up', 'Inactivo: se perdió el seguimiento'),
  ('Inactive - Moved or gone elsewhere', 'Inactivo: se mudó o se atiende en otro lugar'),
  ('Inactive - Permanently inactive', 'Inactivo: de forma permanente'),
  ('Inactive - Unspecified', 'Inactivo: sin especificar'),
  ('Industry', 'Rubro'),
  ('Insurer', 'Aseguradora'),
  ('Interpreter Comments', 'Comentarios sobre el intérprete'),
  ('LOINC-coded list for FHIR compliance', 'Lista codificada en LOINC para cumplir con FHIR'),
  ('Launch', 'Abrir'),
  ('Lesbian, gay or homosexual', 'Lesbiana, gay u homosexual'),
  ('Link/Add Issues to This Visit', 'Vincular/agregar problemas a esta visita'),
  ('Loading…', 'Cargando…'),
  ('Male-to-Female (MTF)/Transgender Female/Trans Woman', 'Mujer trans (de masculino a femenino)'),
  ('Manager', 'Responsable'),
  ('Medical Decisions', 'Decisiones médicas'),
  ('Middle Name:', 'Segundo nombre:'),
  ('NO RELATED PERSONS', 'SIN PERSONAS RELACIONADAS'),
  ('NONE', 'NINGUNO'),
  ('Natural child', 'Hijo/a biológico/a'),
  ('New Group Encounter Form', 'Formulario de nuevo encuentro grupal'),
  ('New Questionnaire', 'Nuevo cuestionario'),
  ('No Known Allergies', 'Sin alergias conocidas'),
  ('No Past Appointments{{Appointment}}', 'No hay turnos anteriores'),
  ('No Recalls', 'Sin recordatorios'),
  ('No Recurring Appointments{{Appointments}}', 'No hay turnos periódicos'),
  ('No available providers', 'No hay profesionales disponibles'),
  ('No care experience preferences recorded.', 'No hay preferencias sobre la atención registradas.'),
  ('No codified answers available', 'No hay respuestas codificadas'),
  ('No medications dispensed during this encounter.', 'No se dispensaron medicamentos en este encuentro.'),
  ('No registered SMART apps in the system', 'No hay aplicaciones SMART registradas en el sistema'),
  ('No reminder/recall', 'Sin avisos ni recordatorios'),
  ('No treatment intervention preferences recorded.', 'No hay preferencias de tratamiento registradas.'),
  ('None{{Advanced_Directives}}', 'Ninguna'),
  ('None{{Amendment}}', 'Ninguna'),
  ('None{{Immunizations}}', 'Ninguna'),
  ('None{{Issues}}', 'Ninguno'),
  ('None{{Prescriptions}}', 'Ninguna'),
  ('Notes:', 'Notas:'),
  ('Notes: ', 'Notas: '),
  ('Observation', 'Observación'),
  ('Only recall to provider, no reminder', 'Solo recordatorio al profesional, sin aviso'),
  ('Only reminder to provider, no recall', 'Solo aviso al profesional, sin recordatorio'),
  ('Ophthalmological Services', 'Servicios oftalmológicos'),
  ('Optional notes about this person', 'Notas opcionales sobre esta persona'),
  ('Other adult', 'Otro adulto'),
  ('Owner', 'Titular'),
  ('Parent', 'Padre/madre'),
  ('Past Appointments', 'Turnos anteriores'),
  ('Patient Balance Due', 'Saldo adeudado por el paciente'),
  ('Patient Categories', 'Categorías del paciente'),
  ('Patient Portal Not Enabled. Admin Can Enable Patient Portal.', 'El portal del paciente no está habilitado. Un administrador lo puede habilitar.'),
  ('Patient Portal Site Address Not Set Properly. Admin Can Correct in Config.', 'La dirección del portal del paciente no está bien configurada. Un administrador la puede corregir.'),
  ('Patient''s Choice', 'Elección del paciente'),
  ('Patient''s Preference', 'Preferencia del paciente'),
  ('Patient''s Preference (Free Text)', 'Preferencia del paciente (texto libre)'),
  ('Phone:', 'Teléfono:'),
  ('Portal Access', 'Acceso al portal'),
  ('Portal Status', 'Estado del portal'),
  ('Preference', 'Preferencia'),
  ('Preference Category', 'Categoría de la preferencia'),
  ('Preference Type', 'Tipo de preferencia'),
  ('Preferred Name', 'Nombre preferido'),
  ('Press this icon to get more information.', 'Presione este ícono para ver más información.'),
  ('Prevent API Access', 'Impedir el acceso por API'),
  ('Prevent API Access can be disabled in Demographics Choices.', 'Impedir el acceso por API se puede desactivar en las opciones de datos demográficos.'),
  ('Preventive Care Services', 'Servicios de atención preventiva'),
  ('Previous Names', 'Nombres anteriores'),
  ('Primary Contact', 'Contacto principal'),
  ('Pronouns', 'Pronombres'),
  ('Protection Indicator', 'Indicador de protección'),
  ('Protection Indicator Effective Date', 'Fecha de vigencia del indicador de protección'),
  ('Provide Since Date', 'Indique desde cuándo'),
  ('Publicity Code', 'Código de publicidad'),
  ('Publicity Code Effective Date', 'Fecha de vigencia del código de publicidad'),
  ('Rank', 'Orden'),
  ('Reactivate', 'Reactivar'),
  ('Reason For Visit', 'Motivo de la visita'),
  ('Recall only - any method', 'Solo recordatorio, por cualquier medio'),
  ('Recall only - no calls', 'Solo recordatorio, sin llamadas'),
  ('Recall to provider', 'Recordatorio al profesional'),
  ('Receive Medical Info', 'Recibe información médica'),
  ('Recurring Appointments', 'Turnos periódicos'),
  ('Registered Email', 'Correo registrado'),
  ('Registration Code', 'Código de registro'),
  ('Registration code will be created when credentials are saved', 'El código de registro se crea al guardar las credenciales'),
  ('Related Persons', 'Personas relacionadas'),
  ('Relationship End Date:', 'Fecha de fin de la relación:'),
  ('Relationship Start Date:', 'Fecha de inicio de la relación:'),
  ('Relationship: ', 'Relación: '),
  ('Reminder only - any method', 'Solo aviso, por cualquier medio'),
  ('Reminder only - no calls', 'Solo aviso, sin llamadas'),
  ('Reminder to provider', 'Aviso al profesional'),
  ('Reminder/recall - any method', 'Aviso y recordatorio, por cualquier medio'),
  ('Reminder/recall - no calls', 'Aviso y recordatorio, sin llamadas'),
  ('Reminder/recall - to provider', 'Aviso y recordatorio al profesional'),
  ('Response Type', 'Tipo de respuesta'),
  ('Role: ', 'Rol: '),
  ('Save Preference', 'Guardar preferencia'),
  ('Select Facility', 'Seleccione el establecimiento'),
  ('Select Gender', 'Seleccione el género'),
  ('Select preference first...', 'Primero elija una preferencia...'),
  ('Select response…', 'Elija una respuesta…'),
  ('Select...', 'Seleccione...'),
  ('Sexual Orientation', 'Orientación sexual'),
  ('Sister', 'Hermana'),
  ('Something else, please describe', 'Otra, descríbala'),
  ('Straight or heterosexual', 'Heterosexual'),
  ('Supply Type', 'Tipo de suministro'),
  ('Support Phone Number', 'Teléfono de soporte'),
  ('TeleHealth Established Patient', 'Telemedicina, paciente conocido'),
  ('Telecom Contacts', 'Contactos telefónicos'),
  ('Telehealth New Patient', 'Telemedicina, paciente nuevo'),
  ('This application implements decision support interventions.', 'Esta aplicación incluye herramientas de apoyo a la toma de decisiones.'),
  ('Total Balance Due', 'Saldo total adeudado'),
  ('Trainer', 'Instructor/a'),
  ('Tribal Affiliations', 'Pertenencia a pueblos originarios'),
  ('Trusted Email', 'Correo seguro'),
  ('Unallocated Patient Pre-Payment', 'Pago anticipado del paciente sin asignar'),
  ('Unique Installation UUID', 'UUID único de la instalación'),
  ('Update Status', 'Actualizar estado'),
  ('Use', 'Uso'),
  ('Visit Details', 'Detalles de la visita'),
  ('Write a Review', 'Escribir una reseña'),
  ('characters', 'caracteres'),
  ('he/him/his/his/himself', 'él'),
  ('she/her/her/hers/herself', 'ella'),
  ('they/them/their/theirs/themselves', 'elle (neutro)'),
  ('unknown', 'desconocido'),
  ('Afghan', 'Afgana'),
  ('Algerian', 'Argelina'),
  ('American', 'Estadounidense'),
  ('American Islander', 'De las Islas Ultramarinas de EE. UU.'),
  ('American Mariana Islands', 'De las Islas Marianas del Norte'),
  ('American Samoan', 'De Samoa Americana'),
  ('Andorran', 'Andorrana'),
  ('Angolan', 'Angoleña'),
  ('Anguillian', 'Anguilense'),
  ('Antarctican', 'Antártica'),
  ('Antiguan, Barbudan', 'Antiguana, barbudense'),
  ('Argentine', 'Argentina'),
  ('Aruban', 'Arubeña'),
  ('Australian', 'Australiana'),
  ('Austrian', 'Austríaca'),
  ('Bahamian', 'Bahameña'),
  ('Bahraini', 'Bareiní'),
  ('Bangladeshi', 'Bangladesí'),
  ('Barbadian', 'Barbadense'),
  ('Belgian', 'Belga'),
  ('Belizean', 'Beliceña'),
  ('Beninese', 'Beninesa'),
  ('Bermudian', 'Bermudeña'),
  ('Bhutanese', 'Butanesa'),
  ('Bolivian', 'Boliviana'),
  ('Bosnian, Herzegovinian', 'Bosnia, herzegovina'),
  ('Brazilian', 'Brasileña'),
  ('British', 'Británica'),
  ('British Indian', 'Del Territorio Británico del Océano Índico'),
  ('Bruneian', 'Bruneana'),
  ('Burkinabe', 'Burkinesa'),
  ('Burundian', 'Burundesa'),
  ('Cameroonian', 'Camerunesa'),
  ('Canadian', 'Canadiense'),
  ('Cape Verdian', 'Caboverdiana'),
  ('Caymanian', 'Caimanesa'),
  ('Central African', 'Centroafricana'),
  ('Chadian', 'Chadiana'),
  ('Channel Islander Guernsey', 'De Guernsey'),
  ('Channel Islander Jersey', 'De Jersey'),
  ('Chilean', 'Chilena'),
  ('Christmas Islander', 'De la Isla de Navidad'),
  ('Cocos Islander', 'De las Islas Cocos'),
  ('Colombian', 'Colombiana'),
  ('Comoran', 'Comorense'),
  ('Congolese', 'Congoleña'),
  ('Cook Islander', 'De las Islas Cook'),
  ('Costa Rican', 'Costarricense'),
  ('Croatian', 'Croata'),
  ('Curaçaoan', 'Curazoleña'),
  ('Cypriot', 'Chipriota'),
  ('Djibouti', 'Yibutiana'),
  ('Dominican', 'Dominicana'),
  ('Dutch Caribbean ', 'Del Caribe Neerlandés '),
  ('East Timorese', 'Timorense'),
  ('Ecuadorean', 'Ecuatoriana'),
  ('Egyptian', 'Egipcia'),
  ('Emirati', 'Emiratí'),
  ('Equatorial Guinean', 'Ecuatoguineana'),
  ('Eritrean', 'Eritrea'),
  ('Estonian', 'Estonia'),
  ('Ethiopian', 'Etíope'),
  ('Falkland Islander', 'De las Islas Malvinas'),
  ('Faroese', 'Feroesa'),
  ('Fijian', 'Fiyiana'),
  ('Finnish', 'Finlandesa'),
  ('French Polynesian', 'De la Polinesia Francesa'),
  ('French Southern ', 'De las Tierras Australes Francesas '),
  ('Gabonese', 'Gabonesa'),
  ('Gambian', 'Gambiana'),
  ('Georgian', 'Georgiana'),
  ('Ghanaian', 'Ghanesa'),
  ('Gibraltar', 'Gibraltareña'),
  ('Greenlandic', 'Groenlandesa'),
  ('Grenadian', 'Granadina'),
  ('Guadeloupian', 'Guadalupeña'),
  ('Guatemalan', 'Guatemalteca'),
  ('Guianan', 'Guayanesa (francesa)'),
  ('Guinea-Bissauan', 'Guineana (Guinea-Bisáu)'),
  ('Guinean', 'Guineana'),
  ('Guyanese', 'Guyanesa'),
  ('Haitian', 'Haitiana'),
  ('Heard and McDonald Islander', 'De las Islas Heard y McDonald'),
  ('Honduran', 'Hondureña'),
  ('Hong Konger', 'Hongkonesa'),
  ('I-Kiribati', 'Kiribatiana'),
  ('Icelander', 'Islandesa'),
  ('Indonesian', 'Indonesia'),
  ('Iranian', 'Iraní'),
  ('Iraqi', 'Iraquí'),
  ('Irish', 'Irlandesa'),
  ('Israeli', 'Israelí'),
  ('Ivorian', 'Marfileña'),
  ('Jamaican', 'Jamaiquina'),
  ('Jordanian', 'Jordana'),
  ('Kazakhstani', 'Kazaja'),
  ('Kenyan', 'Keniana'),
  ('Kirghiz', 'Kirguisa'),
  ('Kittitian or Nevisian', 'Sancristobaleña o nevisiana'),
  ('Kuwaiti', 'Kuwaití'),
  ('Lebanese', 'Libanesa'),
  ('Liberian', 'Liberiana'),
  ('Libyan', 'Libia'),
  ('Liechtensteiner', 'Liechtensteiniana'),
  ('Lithuanian', 'Lituana'),
  ('Luxembourger', 'Luxemburguesa'),
  ('Macanese', 'Macaense'),
  ('Macedonian', 'Macedonia'),
  ('Mahoran', 'Mahorés'),
  ('Malagasy', 'Malgache'),
  ('Malawian', 'Malauí'),
  ('Malaysian', 'Malasia'),
  ('Maldivan', 'Maldiva'),
  ('Malian', 'Maliense'),
  ('Maltese', 'Maltesa'),
  ('Manx', 'De la Isla de Man'),
  ('Marshallese', 'Marshalesa'),
  ('Martinican', 'Martiniqueña'),
  ('Mauritanian', 'Mauritana'),
  ('Mauritian', 'Mauriciana'),
  ('Mexican', 'Mexicana'),
  ('Micronesian', 'Micronesia'),
  ('Moldovan', 'Moldava'),
  ('Monegasque', 'Monegasca'),
  ('Mongolian', 'Mongola'),
  ('Montenegrin', 'Montenegrina'),
  ('Montserratian', 'Montserratense'),
  ('Moroccan', 'Marroquí'),
  ('Mosotho', 'Lesotense'),
  ('Motswana', 'Botsuana'),
  ('Mozambican', 'Mozambiqueña'),
  ('Namibian', 'Namibia'),
  ('Nauruan', 'Nauruana'),
  ('Nepalese', 'Nepalí'),
  ('New Caledonian', 'Neocaledonia'),
  ('New Zealander', 'Neozelandesa'),
  ('Ni-Vanuatu', 'Vanuatuense'),
  ('Nicaraguan', 'Nicaragüense'),
  ('Nigerian', 'Nigeriana'),
  ('Nigerien', 'Nigerina'),
  ('Niuean', 'Niuana'),
  ('Norfolk Islander', 'De la Isla Norfolk'),
  ('North Korean', 'Norcoreana'),
  ('Norwegian Bouvet', 'De la Isla Bouvet'),
  ('Norwegian Svalbard and Jan Mayen', 'De Svalbard y Jan Mayen'),
  ('Omani', 'Omaní'),
  ('Pakistani', 'Pakistaní'),
  ('Palauan', 'Palauana'),
  ('Palestinian', 'Palestina'),
  ('Panamanian', 'Panameña'),
  ('Papua New Guinean', 'Papú'),
  ('Paraguayan', 'Paraguaya'),
  ('Peruvian', 'Peruana'),
  ('Pitcairn Islander', 'De las Islas Pitcairn'),
  ('Qatari', 'Catarí'),
  ('Rwandan', 'Ruandesa'),
  ('Réunionese', 'Reunionesa'),
  ('Sahrawi', 'Saharaui'),
  ('Saint Barthélemy Islander', 'De San Bartolomé'),
  ('Saint Helenian', 'De Santa Elena'),
  ('Saint Lucian', 'Santalucense'),
  ('Saint Martin Islander', 'De San Martín (parte francesa)'),
  ('Saint Vincentian', 'Sanvicentina'),
  ('Saint-Pierrais, Miquelonnais', 'De San Pedro y Miquelón'),
  ('Salvadoran', 'Salvadoreña'),
  ('Sammarinese', 'Sanmarinense'),
  ('Sao Tomean', 'Santotomense'),
  ('Saudi Arabian', 'Saudí'),
  ('Scottish', 'Escocesa'),
  ('Senegalese', 'Senegalesa'),
  ('Serbian', 'Serbia'),
  ('Seychellois', 'Seychellense'),
  ('Sierra Leonean', 'Sierraleonesa'),
  ('Singaporean', 'Singapurense'),
  ('Slovene', 'Eslovena'),
  ('Solomon Islander', 'Salomonense'),
  ('Somali', 'Somalí'),
  ('South African', 'Sudafricana'),
  ('South Georgian South Sandwich Islander', 'De las Islas Georgias y Sandwich del Sur'),
  ('South Korean', 'Surcoreana'),
  ('South Sudanese', 'Sursudanesa'),
  ('Sri Lankan', 'Ceilanesa'),
  ('St. Maartener', 'De San Martín (parte neerlandesa)'),
  ('Sudanese', 'Sudanesa'),
  ('Surinamer', 'Surinamesa'),
  ('Swazi', 'Suazi'),
  ('Swiss', 'Suiza'),
  ('Syrian', 'Siria'),
  ('Tadzhik', 'Tayika'),
  ('Taiwanese', 'Taiwanesa'),
  ('Tanzanian', 'Tanzana'),
  ('Togolese', 'Togolesa'),
  ('Tokelauan', 'Tokelauana'),
  ('Tongan', 'Tongana'),
  ('Trinidadian', 'Trinitense'),
  ('Tunisian', 'Tunecina'),
  ('Turkmen', 'Turcomana'),
  ('Turks and Caicos Islander', 'De las Islas Turcas y Caicos'),
  ('Tuvaluan', 'Tuvaluana'),
  ('Ugandan', 'Ugandesa'),
  ('Uruguayan', 'Uruguaya'),
  ('Uzbekistani', 'Uzbeka'),
  ('Vatican', 'Vaticana'),
  ('Venezuelan', 'Venezolana'),
  ('Virgin Islander British', 'De las Islas Vírgenes Británicas'),
  ('Virgin Islander US', 'De las Islas Vírgenes de EE. UU.'),
  ('Wallis and Futuna Islander', 'De Wallis y Futuna'),
  ('Yemeni', 'Yemení'),
  ('Zambian', 'Zambiana'),
  ('Zimbabwean', 'Zimbabuense'),
  ('Ålandish', 'De las Islas Åland');

INSERT INTO lang_constants (constant_name)
SELECT t.cn FROM nursing_core_lang2 t
WHERE NOT EXISTS (SELECT 1 FROM lang_constants c WHERE c.constant_name = t.cn);

UPDATE lang_definitions d
JOIN lang_constants c ON c.cons_id = d.cons_id
JOIN nursing_core_lang2 t ON c.constant_name = t.cn
SET d.definition = t.es
WHERE d.lang_id IN (3, 4) AND (d.definition IS NULL OR d.definition = '');

INSERT INTO lang_definitions (cons_id, lang_id, definition)
SELECT c.cons_id, ll.lang_id, t.es
FROM nursing_core_lang2 t
JOIN lang_constants c ON c.constant_name = t.cn
JOIN lang_languages ll ON ll.lang_id IN (3, 4)
WHERE NOT EXISTS (SELECT 1 FROM lang_definitions d WHERE d.cons_id = c.cons_id AND d.lang_id = ll.lang_id);

DROP TEMPORARY TABLE IF EXISTS nursing_core_lang2;

-- -------------------------------------------------------------------------
-- Spanish for a few more OpenEMR texts of the ICU screens (top bar, vitals,
-- encounters) and for the relationship list used in "Add Related Person".
-- Only fills missing translations, on the indexed constant_name.
-- -------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS nursing_core_lang3;
CREATE TEMPORARY TABLE nursing_core_lang3 (cn VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL, es TEXT CHARACTER SET utf8mb4 NOT NULL);
INSERT INTO nursing_core_lang3 (cn, es) VALUES
  ('Current user', 'Usuario actual'),
  ('Group Encounter', 'Encuentro grupal'),
  ('MFA Management', 'Autenticación en dos pasos'),
  ('None{{Therapy Group}}', 'Ninguno'),
  ('Patient has no other vital history to display', 'El paciente no tiene otros signos vitales registrados'),
  ('Toggle the Patient Panel', 'Mostrar/ocultar el panel del paciente'),
  ('Validation errors', 'Errores de validación'),
  ('When recording a reason for the value (or absence of a value) of an observation both the reason code and status of the reason are required', 'Al registrar el motivo de un valor (o de su ausencia) en una observación, se requieren el código y el estado del motivo'),
  ('Aunt Maternal', 'Tía materna'),
  ('Aunt Paternal', 'Tía paterna'),
  ('Brother Identical Twin', 'Hermano gemelo idéntico'),
  ('Brother Natural', 'Hermano biológico'),
  ('Brother Twin', 'Hermano gemelo'),
  ('Brother Twin Fraternal', 'Hermano mellizo'),
  ('Brother-In-Law', 'Cuñado'),
  ('Child Adopted', 'Hijo/a adoptivo/a'),
  ('Child Foster', 'Hijo/a de crianza'),
  ('Child Natural', 'Hijo/a biológico/a'),
  ('Cousin Maternal', 'Primo/a materno/a'),
  ('Cousin Paternal', 'Primo/a paterno/a'),
  ('Daughter', 'Hija'),
  ('Daughter Adopted', 'Hija adoptiva'),
  ('Daughter Foster', 'Hija de crianza'),
  ('Daughter Natural', 'Hija biológica'),
  ('Daughter-In-Law', 'Nuera'),
  ('Family Member', 'Familiar'),
  ('Family Member Extended', 'Familiar (familia extendida)'),
  ('Father Adoptive', 'Padre adoptivo'),
  ('Father Foster', 'Padre de crianza'),
  ('Father Natural', 'Padre biológico'),
  ('Father-In-Law', 'Suegro'),
  ('Former Spouse', 'Excónyuge'),
  ('Friend Unrelated', 'Amigo/a (sin parentesco)'),
  ('Granddaughter', 'Nieta'),
  ('Grandfather', 'Abuelo'),
  ('Grandfather Maternal', 'Abuelo materno'),
  ('Grandfather Paternal', 'Abuelo paterno'),
  ('Grandmother', 'Abuela'),
  ('Grandmother Maternal', 'Abuela materna'),
  ('Grandmother Paternal', 'Abuela paterna'),
  ('Grandparent Maternal', 'Abuelo/a materno/a'),
  ('Grandparent Paternal', 'Abuelo/a paterno/a'),
  ('Grandson', 'Nieto'),
  ('Great-Grandfather', 'Bisabuelo'),
  ('Great-Grandfather Maternal', 'Bisabuelo materno'),
  ('Great-Grandfather Paternal', 'Bisabuelo paterno'),
  ('Great-Grandmother', 'Bisabuela'),
  ('Great-Grandmother Maternal', 'Bisabuela materna'),
  ('Great-Grandmother Paternal', 'Bisabuela paterna'),
  ('Great-Grandparent', 'Bisabuelo/a'),
  ('Great-Grandparent Maternal', 'Bisabuelo/a materno/a'),
  ('Great-Grandparent Paternal', 'Bisabuelo/a paterno/a'),
  ('Half-Brother', 'Medio hermano'),
  ('Half-Sibling', 'Medio/a hermano/a'),
  ('Half-Sister', 'Media hermana'),
  ('Husband', 'Esposo'),
  ('Inlaw', 'Familiar político'),
  ('Mother Adoptive', 'Madre adoptiva'),
  ('Mother Foster', 'Madre de crianza'),
  ('Mother Gestational', 'Madre gestante'),
  ('Mother Natural', 'Madre biológica'),
  ('Mother-In-Law', 'Suegra'),
  ('Neighbor', 'Vecino/a'),
  ('Nephew', 'Sobrino'),
  ('Niece', 'Sobrina'),
  ('Parent Adoptive', 'Padre/madre adoptivo/a'),
  ('Parent Foster', 'Padre/madre de crianza'),
  ('Parent Natural', 'Padre/madre biológico/a'),
  ('Parent-In-Law', 'Suegro/a'),
  ('Roommate', 'Compañero/a de vivienda'),
  ('Sibling Natural', 'Hermano/a biológico/a'),
  ('Sibling-In-Law', 'Cuñado/a'),
  ('Sister Identical Twin', 'Hermana gemela idéntica'),
  ('Sister Natural', 'Hermana biológica'),
  ('Sister Twin', 'Hermana gemela'),
  ('Sister Twin Fraternal', 'Hermana melliza'),
  ('Sister-In-Law', 'Cuñada'),
  ('Son', 'Hijo'),
  ('Son Adopted', 'Hijo adoptivo'),
  ('Son Foster', 'Hijo de crianza'),
  ('Son Natural', 'Hijo biológico'),
  ('Son-In-Law', 'Yerno'),
  ('Step Sibling', 'Hermanastro/a'),
  ('Stepbrother', 'Hermanastro'),
  ('Stepdaughter', 'Hijastra'),
  ('Stepfather', 'Padrastro'),
  ('Stepmother', 'Madrastra'),
  ('Stepsister', 'Hermanastra'),
  ('Stepson', 'Hijastro'),
  ('Twin', 'Gemelo/a'),
  ('Twin Fraternal', 'Mellizo/a'),
  ('Twin Identical', 'Gemelo/a idéntico/a'),
  ('Uncle', 'Tío'),
  ('Uncle Maternal', 'Tío materno'),
  ('Uncle Paternal', 'Tío paterno'),
  ('Wife', 'Esposa');

INSERT INTO lang_constants (constant_name)
SELECT t.cn FROM nursing_core_lang3 t
WHERE NOT EXISTS (SELECT 1 FROM lang_constants c WHERE c.constant_name = t.cn);

UPDATE lang_definitions d
JOIN lang_constants c ON c.cons_id = d.cons_id
JOIN nursing_core_lang3 t ON c.constant_name = t.cn
SET d.definition = t.es
WHERE d.lang_id IN (3, 4) AND (d.definition IS NULL OR d.definition = '');

INSERT INTO lang_definitions (cons_id, lang_id, definition)
SELECT c.cons_id, ll.lang_id, t.es
FROM nursing_core_lang3 t
JOIN lang_constants c ON c.constant_name = t.cn
JOIN lang_languages ll ON ll.lang_id IN (3, 4)
WHERE NOT EXISTS (SELECT 1 FROM lang_definitions d WHERE d.cons_id = c.cons_id AND d.lang_id = ll.lang_id);

DROP TEMPORARY TABLE IF EXISTS nursing_core_lang3;

-- -------------------------------------------------------------------------
-- One Spanish term for the care bundle form: "Paquete de Cuidados" (as in the
-- thesis). Replaces the earlier mixed translations ("Bundle"/"Paquete") and
-- removes a duplicate definition of "Nursing Care Bundle".
-- -------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS nursing_bundle_lang;
CREATE TEMPORARY TABLE nursing_bundle_lang (cn VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL, es TEXT CHARACTER SET utf8mb4 NOT NULL);
INSERT INTO nursing_bundle_lang (cn, es) VALUES
  ('Nursing Care Bundle', 'Paquete de Cuidados de Enfermería'),
  ('New Care Bundle', 'Nuevo Paquete de Cuidados'),
  ('Edit Care Bundle', 'Editar Paquete de Cuidados'),
  ('Care Bundle Record', 'Registro del Paquete de Cuidados'),
  ('Care Bundle Detail', 'Detalle del Paquete de Cuidados'),
  ('Care Bundle List', 'Lista de Paquetes de Cuidados'),
  ('NURSING CARE BUNDLE RECORD', 'REGISTRO DEL PAQUETE DE CUIDADOS'),
  ('No care bundle records found', 'No se encontraron registros del paquete de cuidados');

DELETE d1 FROM lang_definitions d1
JOIN lang_definitions d2 ON d2.cons_id = d1.cons_id AND d2.lang_id = d1.lang_id AND d2.def_id < d1.def_id
JOIN lang_constants c ON c.cons_id = d1.cons_id
JOIN nursing_bundle_lang t ON c.constant_name = t.cn
WHERE d1.lang_id IN (3, 4);

UPDATE lang_definitions d
JOIN lang_constants c ON c.cons_id = d.cons_id
JOIN nursing_bundle_lang t ON c.constant_name = t.cn
SET d.definition = t.es
WHERE d.lang_id IN (3, 4);

DROP TEMPORARY TABLE IF EXISTS nursing_bundle_lang;

-- -------------------------------------------------------------------------
-- One Spanish translation for texts that had two different ones (Patient
-- Information, Severe, Item, and "Notes:" with and without a trailing space)
-- and Spanish for two that were left in English (Config, Hits). The extra
-- BINARY check keeps "Notes:" and "Notes: " apart (the collation pads spaces).
-- -------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS nursing_fix_lang4;
CREATE TEMPORARY TABLE nursing_fix_lang4 (cn VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL, es TEXT CHARACTER SET utf8mb4 NOT NULL);
INSERT INTO nursing_fix_lang4 (cn, es) VALUES
  ('Patient Information', 'Información del Paciente'),
  ('Severe', 'Grave'),
  ('Item', 'Ítem'),
  ('Config', 'Configuración'),
  ('Hits', 'Resultados'),
  ('Notes:', 'Notas:'),
  ('Notes: ', 'Notas: ');

DELETE d1 FROM lang_definitions d1
JOIN lang_definitions d2 ON d2.cons_id = d1.cons_id AND d2.lang_id = d1.lang_id AND d2.def_id < d1.def_id
JOIN lang_constants c ON c.cons_id = d1.cons_id
JOIN nursing_fix_lang4 t ON c.constant_name = t.cn AND BINARY c.constant_name = BINARY t.cn
WHERE d1.lang_id IN (3, 4);

UPDATE lang_definitions d
JOIN lang_constants c ON c.cons_id = d.cons_id
JOIN nursing_fix_lang4 t ON c.constant_name = t.cn AND BINARY c.constant_name = BINARY t.cn
SET d.definition = t.es
WHERE d.lang_id IN (3, 4);

DROP TEMPORARY TABLE IF EXISTS nursing_fix_lang4;

-- -------------------------------------------------------------------------
-- Spanish found missing in a walkthrough of the ICU screens: patient summary
-- (care team, assessments), encounter form menus, left panel and messages,
-- demographics fields, and the table texts of the inpatient list. Only fills
-- missing translations, on the indexed constant_name.
-- -------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS nursing_fix_lang5;
CREATE TEMPORARY TABLE nursing_fix_lang5 (cn VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL, es TEXT CHARACTER SET utf8mb4 NOT NULL);
INSERT INTO nursing_fix_lang5 (cn, es) VALUES
  ('Show _MENU_ entries', 'Mostrar _MENU_ registros'),
  ('Showing _START_ to _END_ of _TOTAL_ entries', 'Mostrando _START_ a _END_ de _TOTAL_ registros'),
  ('Showing 0 to 0 of 0 entries', 'Mostrando 0 a 0 de 0 registros'),
  ('(filtered from _MAX_ total entries)', '(filtrado de _MAX_ registros en total)'),
  ('No data available in table', 'No hay datos en la tabla'),
  ('Assessments', 'Evaluaciones'),
  ('Care Team Name', 'Nombre del equipo de cuidado'),
  ('Care Team Status', 'Estado del equipo de cuidado'),
  ('Member', 'Integrante'),
  ('Role', 'Rol'),
  ('Administrative', 'Administrativo'),
  ('Orders', 'Órdenes'),
  ('Questionnaires', 'Cuestionarios'),
  ('Recent Patients', 'Pacientes recientes'),
  ('Click to show search', 'Haga clic para mostrar la búsqueda'),
  ('Dated Reminders', 'Recordatorios con fecha'),
  ('Create A Dated Reminder', 'Crear un recordatorio con fecha'),
  ('Send Message', 'Enviar mensaje'),
  ('Patient Religion', 'Religión del paciente'),
  ('Guardian Email Address', 'Correo electrónico del tutor'),
  ('Trusted Direct Email Address', 'Correo seguro (Direct)'),
  ('Patient Level Billing Note (Collections)', 'Nota de facturación del paciente (cobranzas)'),
  ('Administrator', 'Administrador'),
  ('Issues Help', 'Ayuda de problemas'),
  ('History and Lifestyle Help', 'Ayuda de antecedentes y estilo de vida'),
  ('Click to contract page to center view', 'Haga clic para centrar la página'),
  ('MedEx Communication Service', 'Servicio de comunicación MedEx');

INSERT INTO lang_constants (constant_name)
SELECT t.cn FROM nursing_fix_lang5 t
WHERE NOT EXISTS (SELECT 1 FROM lang_constants c WHERE c.constant_name = t.cn);

UPDATE lang_definitions d
JOIN lang_constants c ON c.cons_id = d.cons_id
JOIN nursing_fix_lang5 t ON c.constant_name = t.cn
SET d.definition = t.es
WHERE d.lang_id IN (3, 4) AND (d.definition IS NULL OR d.definition = '');

INSERT INTO lang_definitions (cons_id, lang_id, definition)
SELECT c.cons_id, ll.lang_id, t.es
FROM nursing_fix_lang5 t
JOIN lang_constants c ON c.constant_name = t.cn
JOIN lang_languages ll ON ll.lang_id IN (3, 4)
WHERE NOT EXISTS (SELECT 1 FROM lang_definitions d WHERE d.cons_id = c.cons_id AND d.lang_id = ll.lang_id);

DROP TEMPORARY TABLE IF EXISTS nursing_fix_lang5;

-- -------------------------------------------------------------------------
-- Spanish found missing in a full walkthrough of OpenEMR: the encounter form
-- options, the clinical forms (care plan, observation, clinical notes,
-- functional status, questionnaires), documents and transactions, and the
-- help pages of the patient summary, issues, history, patient report,
-- transactions and message center. Only fills missing translations.
-- -------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS nursing_fix_lang6;
CREATE TEMPORARY TABLE nursing_fix_lang6 (cn TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL, es TEXT CHARACTER SET utf8mb4 NOT NULL);
INSERT INTO nursing_fix_lang6 (cn, es) VALUES
  ('Patient Reports Help', 'Ayuda de informes del paciente'),
  ('Reports consisting of various portions of the patient''s medical record can be created here', 'Aquí se pueden crear informes con distintas partes de la historia clínica del paciente'),
  ('There are three main types of reports that can be created, two pertain to continuity of ongoing care - Continuity of Care Record (CCR) and Continuity of Care Document (CCD) and the third - Patient Report that creates a document containing various sections of the patient''s medical record including demographics, medical issues, procedures and encounters. It also has the ability to include all or any of the scanned documents in the patient''s chart', 'Se pueden crear tres tipos principales de informes. Dos se refieren a la continuidad de la atención, el Registro de Continuidad de la Atención (CCR) y el Documento de Continuidad de la Atención (CCD), y el tercero, el Informe del paciente, crea un documento con distintas secciones de la historia clínica: datos demográficos, problemas médicos, procedimientos y encuentros. También puede incluir todos o algunos de los documentos escaneados de la ficha del paciente'),
  ('This help file is divided into four sections', 'Este archivo de ayuda se divide en cuatro secciones'),
  ('Enabling EMR Direct phiMail', 'Cómo habilitar EMR Direct phiMail'),
  ('To help explore the various components of the Report page especially if you want to use it as an instruction manual it is suggested that you reduce the size of the browser to cover half the viewport, resize the help pop-up by clicking and dragging the bottom right corner of the pop-up. Open another instance of the browser and resize it to cover the other half of the viewport, login to openEMR ', 'Para recorrer las partes de la página de informes, sobre todo si la quiere usar como manual, conviene achicar el navegador a la mitad de la pantalla y ajustar el tamaño de esta ventana de ayuda arrastrando su esquina inferior derecha. Abra otro navegador en la otra mitad de la pantalla e inicie sesión en OpenEMR '),
  ('The Continuity of Care Record (CCR) is the clinical record of the patient’s current and historical health care status', 'El Registro de Continuidad de la Atención (CCR) es el registro clínico del estado de salud actual y pasado del paciente'),
  ('Basic patient information is included, such as patient and provider information, insurance, patient health status, recent care provided, care plan information, and reason for referral or transfer', 'Incluye información básica, como los datos del paciente y del profesional, el seguro, el estado de salud, la atención reciente, el plan de cuidados y el motivo de la derivación o el traslado'),
  ('It is intended to include only the information that is critical to effectively continue care', 'Su objetivo es incluir solo la información indispensable para continuar la atención'),
  ('For those interested in details of what constitutes a Continuity of Care Record (CCR) click on the eye icon', 'Para ver en detalle qué compone un Registro de Continuidad de la Atención (CCR), haga clic en el ícono del ojo'),
  ('Click to Show', 'Haga clic para mostrar'),
  ('The Continuity of Care Record (CCR) has been developed jointly by ASTM International - an organization that is involved in the development and delivery of voluntary consensus, the Massachusetts Medical Society (MMS), the Health Information Management and Systems Society (HIMSS), the American Academy of Family Physicians (AAFP), and the American Academy of Pediatrics', 'El Registro de Continuidad de la Atención (CCR) fue desarrollado en conjunto por ASTM International, una organización dedicada a elaborar normas por consenso voluntario, la Massachusetts Medical Society (MMS), la Health Information Management and Systems Society (HIMSS), la American Academy of Family Physicians (AAFP) y la American Academy of Pediatrics'),
  ('The basis for CCR is a Patient Care Referral Form developed by the Massachusetts Department of Public Health', 'El CCR se basa en un formulario de derivación de pacientes elaborado por el Departamento de Salud Pública de Massachusetts'),
  ('In total, there are seventeen sections that can be included within a Continuity of Care Record (CCR)', 'En total, un Registro de Continuidad de la Atención (CCR) puede incluir diecisiete secciones'),
  ('All sections are not required, rather, they are available based on the information a practitioner believes is critical data for the patient at any given moment in time', 'No todas son obligatorias: están disponibles según la información que el profesional considere indispensable para el paciente en cada momento'),
  ('Patient Demographics', 'Datos demográficos del paciente'),
  ('Problems & Diagnoses', 'Problemas y diagnósticos'),
  ('Insurance Information', 'Información del seguro'),
  ('Health Care Providers', 'Profesionales de la salud'),
  ('Encounter Information', 'Información del encuentro'),
  ('Allergies/Alerting Data', 'Alergias y alertas'),
  ('Appropriate Results', 'Resultados relevantes'),
  ('Results', 'Resultados'),
  ('Necessary Medical Equipment', 'Equipamiento médico necesario'),
  ('Social History', 'Antecedentes sociales'),
  ('The core dataset consists of', 'El conjunto básico de datos incluye'),
  ('Document identifying information', 'Datos de identificación del documento'),
  ('Patient identifying information', 'Datos de identificación del paciente'),
  ('Patient insurance/financial information', 'Datos del seguro y financieros del paciente'),
  ('Patient’s health status', 'Estado de salud del paciente'),
  ('Care documentation', 'Documentación de la atención'),
  ('Care plan recommendations', 'Recomendaciones del plan de cuidados'),
  ('List of health care practitioners', 'Lista de profesionales de la salud'),
  ('The items that have asterisks after them are mandated', 'Los elementos marcados con asterisco son obligatorios'),
  ('In openEMR the Continuity of Care Record (CCR) reports to be generated can be limited to a specific date range by checking the Use Date Range check-box and entering the desired date range', 'En OpenEMR, los informes CCR se pueden limitar a un rango de fechas marcando la opción Usar rango de fechas e ingresando las fechas deseadas'),
  ('There are three options available when you create a Continuity of Care Record (CCR)', 'Al crear un Registro de Continuidad de la Atención (CCR) hay tres opciones'),
  ('Generate Report - that opens the CCR in a separate tab on the browser', 'Generar informe: abre el CCR en otra pestaña del navegador'),
  ('Download - the created CCR is downloaded as a pdf file to the downloads from the browser', 'Descargar: el CCR se descarga como archivo PDF en la carpeta de descargas del navegador'),
  ('Transmit - securely transmit the CCR using phiMail Direct Messaging', 'Transmitir: envía el CCR de forma segura con la mensajería Direct de phiMail'),
  ('Clicking the Transmit button will open up a text box where the Direct address of the recipient needs to be entered', 'Al hacer clic en Transmitir se abre un cuadro donde se ingresa la dirección Direct del destinatario'),
  ('Note: this is not an email address', 'Atención: no es una dirección de correo electrónico'),
  ('More information about what phiMail is and how to set it up is available here', 'Aquí hay más información sobre qué es phiMail y cómo configurarlo'),
  ('Continuity of Care Document (CCD) is an electronic document containing patient specific information that aids in continuity of care', 'El Documento de Continuidad de la Atención (CCD) es un documento electrónico con información específica del paciente que facilita la continuidad de la atención'),
  ('It contains structured data that is included in the Continuity of Care Record (CCR) set to the the Clinical Document Architecture standard', 'Contiene los datos estructurados del Registro de Continuidad de la Atención (CCR), adaptados a la norma Clinical Document Architecture'),
  ('Click to learn more about the Clinical Document Architecture (CDA) and Consolidated CDA (C-CDA)', 'Haga clic para saber más sobre Clinical Document Architecture (CDA) y Consolidated CDA (C-CDA)'),
  ('CDA or Clinical Document Architecture is a document standard developed by the HL7 organization', 'CDA (Clinical Document Architecture) es una norma de documentos desarrollada por la organización HL7'),
  ('CDA defines building blocks which can be used to contain healthcare data elements that can be captured, stored, accessed, displayed and transmitted electronically for use and reuse in many formats', 'CDA define bloques que pueden contener datos de salud para registrarlos, guardarlos, consultarlos, mostrarlos y transmitirlos electrónicamente, y reutilizarlos en muchos formatos'),
  ('Clinical documents are produced by arranging or limiting CDA elements in defined ways using templates and Implementation Guides (IG)', 'Los documentos clínicos se arman ordenando o limitando los elementos de CDA de formas definidas, con plantillas y guías de implementación'),
  ('The CDA is a plain text document that is coded using the Extensible Markup Language (XML) and contains a Header and Body', 'El CDA es un documento de texto codificado en XML (Extensible Markup Language) que tiene un encabezado y un cuerpo'),
  ('The Header defines the context for the clinical document as a whole', 'El encabezado define el contexto del documento clínico en su conjunto'),
  ('The Body contains the clinical data that may be structured and organized as one or more sections or may be an unstructured blob of data', 'El cuerpo contiene los datos clínicos, que pueden estar estructurados en una o más secciones o ser un bloque de datos sin estructura'),
  ('Each Section has one Narrative Block and zero to many coded Entries', 'Cada sección tiene un bloque narrativo y cero o más entradas codificadas'),
  ('The Narrative Block contains information that is rendered as readable text', 'El bloque narrativo contiene información que se muestra como texto legible'),
  ('The Entries contain data used for further computer processing', 'Las entradas contienen datos para que los procese una computadora'),
  ('Examples of Sections are - Medications, Allergies, Vital Signs', 'Ejemplos de secciones: medicamentos, alergias, signos vitales'),
  ('The initial efforts led to the creation of duplicative and conflicting Implementation Guides (IGs) published by different standards organizations', 'Los primeros esfuerzos produjeron guías de implementación repetidas y contradictorias, publicadas por distintas organizaciones de normas'),
  ('The end result was a confusing collection of documents containing ambiguous and/or conflicting information', 'El resultado fue un conjunto confuso de documentos con información ambigua o contradictoria'),
  ('To help simplify implementations, commonly used templates were harmonized from existing CDA implementation guides and consolidated into a single implementation guide – the C-CDA Implementation Guide (IG) (07/2012)', 'Para simplificar la implementación, se unificaron las plantillas más usadas de las guías CDA existentes en una sola guía: la guía de implementación C-CDA (07/2012)'),
  ('There are nine C-CDA templates that are currently defined', 'Actualmente hay nueve plantillas C-CDA definidas'),
  ('Continuity of Care Document', 'Documento de Continuidad de la Atención'),
  ('Consultation Notes - 2008', 'Notas de consulta - 2008'),
  ('Discharge Summary - 2009', 'Resumen de alta - 2009'),
  ('Imaging Integration, and DICOM Diagnostic Imaging Reports - 2009', 'Integración de imágenes e informes de diagnóstico por imágenes DICOM - 2009'),
  ('History and Physical 2008', 'Historia clínica y examen físico 2008'),
  ('Operative Note - 2009', 'Nota operatoria - 2009'),
  ('Progress Note - 2010', 'Nota de evolución - 2010'),
  ('Procedure Note - 2010', 'Nota de procedimiento - 2010'),
  ('Unstructured Documents - 2010', 'Documentos sin estructura - 2010'),
  ('The Continuity of Care Document (CCD) and Continuity of Care Record (CCR) were both selected as acceptable formats to extract information for clinical care summaries as a part of Meaningful Use Stage 1', 'El Documento de Continuidad de la Atención (CCD) y el Registro de Continuidad de la Atención (CCR) fueron aceptados como formatos para extraer resúmenes de atención clínica en la etapa 1 de Meaningful Use'),
  ('In the second stage of Meaningful Use, the CCD, but not the CCR, was included as part of the standard for clinical document exchange', 'En la segunda etapa de Meaningful Use, el CCD, pero no el CCR, quedó incluido en la norma de intercambio de documentos clínicos'),
  ('Continuity of Care Document (CCD) is meant to be created at the conclusion of an encounter prior to transfer of care to enable the next provider to easily access such information', 'El Documento de Continuidad de la Atención (CCD) se crea al terminar un encuentro, antes de traspasar la atención, para que el siguiente profesional acceda fácilmente a esa información'),
  ('It serves as a necessary bridge to a different environment, often with new clinicians who know nothing about the patient, enabling next provider to easily', 'Sirve de puente necesario hacia otro entorno, muchas veces con profesionales nuevos que no conocen al paciente, y permite al siguiente profesional'),
  ('Access core data set of patient information at the beginning of an encounter', 'Acceder al conjunto básico de datos del paciente al comenzar un encuentro'),
  ('Update information when the patient goes to another provider, to support safety, quality, and continuity of patient care', 'Actualizar la información cuando el paciente pasa a otro profesional, para favorecer la seguridad, la calidad y la continuidad de la atención'),
  ('There are three options available when you create a Continuity of Care Document (CCD)', 'Al crear un Documento de Continuidad de la Atención (CCD) hay tres opciones'),
  ('Generate Report - that opens the CCD in a separate tab on the browser', 'Generar informe: abre el CCD en otra pestaña del navegador'),
  ('Download - the created CCD is downloaded as a pdf file to the downloads from the browser', 'Descargar: el CCD se descarga como archivo PDF en la carpeta de descargas del navegador'),
  ('Transmit - securely transmit the CCD using phiMail Direct Messaging', 'Transmitir: envía el CCD de forma segura con la mensajería Direct de phiMail'),
  ('Creates a report that contains various sections of the patient''s medical record', 'Crea un informe con distintas secciones de la historia clínica del paciente'),
  ('The created report can contain as much or as little information based on the need', 'El informe puede tener tanta o tan poca información como haga falta'),
  ('By default only Demographics and Billing information is selected', 'Por defecto solo se seleccionan los datos demográficos y la información de facturación'),
  ('There are two buttons that enable you to Check or Clear all available check-boxes', 'Hay dos botones para marcar o desmarcar todas las opciones'),
  ('Alternatively you could clear all selections and select only the items that you want to be a part of the report', 'También puede desmarcar todo y elegir solo los elementos que quiere incluir en el informe'),
  ('To include the scanned documents that are a part of the patient''s record select the desired records by check the relevant check-boxes', 'Para incluir documentos escaneados de la ficha del paciente, marque los que desee'),
  ('There are two options for the records that will be created', 'Hay dos opciones para el informe que se crea'),
  ('Generate Report that creates a report and displays it in a separate tab on the browser ', 'Generar informe: crea el informe y lo muestra en otra pestaña del navegador '),
  ('Download report as a pdf file into the browser''s download folder', 'Descargar el informe como PDF en la carpeta de descargas del navegador'),
  ('When the generated report is displayed in a separate tab there is an option that lets you view a Printable Version that can be printed', 'Cuando el informe se muestra en otra pestaña, hay una opción para ver una versión para imprimir'),
  ('If the report is sent electronically all steps needed for safe and secure transmission of Protected Health Information (PHI) must be followed', 'Si el informe se envía por medios electrónicos, hay que seguir todos los pasos para transmitir de forma segura la información de salud protegida'),
  ('EMR Direct phiMail is a secure, scalable, standards-based way for participants to send authenticated, encrypted health information directly to known, trusted recipients over the Internet', 'EMR Direct phiMail es una forma segura, escalable y basada en normas de enviar información de salud autenticada y cifrada directamente a destinatarios conocidos y de confianza por internet'),
  ('You will need Administrator privileges to setup phiMail Direct Messaging', 'Para configurar la mensajería Direct de phiMail se necesitan permisos de administrador'),
  ('The first step is signing up for a production Direct messaging account with EMR Direct by registering on their website', 'El primer paso es registrarse en el sitio de EMR Direct para obtener una cuenta de mensajería Direct de producción'),
  ('Subscribers will receive the username, password, and server address information with which to configure OpenEMR', 'Los suscriptores reciben el usuario, la contraseña y la dirección del servidor para configurar OpenEMR'),
  ('Go to Administration > Globals > Connectors', 'Vaya a Administración > Configuración global > Conectores'),
  ('Check the Enable phiMail Direct Messaging Service check-box', 'Marque la opción Habilitar el servicio de mensajería Direct de phiMail'),
  ('Enter the Server Address, Username, and Password provided to you', 'Ingrese la dirección del servidor, el usuario y la contraseña que le dieron'),
  ('The server address will be of the form ', 'La dirección del servidor tendrá la forma '),
  ('Replace the hostname and port with the values provided to you by EMR Direct', 'Reemplace el nombre del servidor y el puerto por los que le dio EMR Direct'),
  ('The Username is your Direct Address', 'El usuario es su dirección Direct'),
  ('Do not enter the server URL into your browser address bar, as this will not work', 'No escriba la dirección del servidor en la barra del navegador: no va a funcionar'),
  ('Specify the OpenEMR user who will receive notification of new incoming Direct messages', 'Indique qué usuario de OpenEMR recibirá el aviso de los mensajes Direct nuevos'),
  ('Enter their OpenEMR username in the notification user field', 'Ingrese su usuario de OpenEMR en el campo de usuario para avisos'),
  ('Specify the interval for automatic message checking, 5 or 10 minutes as a starting point', 'Indique cada cuánto se revisan los mensajes automáticamente; 5 o 10 minutos es un buen punto de partida'),
  ('Installations processing a large number of Direct messages may want a shorter interval', 'Las instalaciones que procesan muchos mensajes Direct pueden usar un intervalo más corto'),
  ('To disable automatic message checking go to Administration > Globals > Connectors  and set phiMail Message Check Interval to 0 (zero) ', 'Para desactivar la revisión automática, vaya a Administración > Configuración global > Conectores y ponga en 0 (cero) el intervalo de revisión de mensajes de phiMail '),
  ('Disabling automatic checking would be appropriate if message checking is managed through another mechanism, such as a system cron job', 'Desactivar la revisión automática conviene si los mensajes se revisan por otro medio, como una tarea programada del sistema'),
  ('In order to send Continuity of Care Record (CCR) and /or Continuity of Care Document (CCD) optionally check phiMail Allow CCD Send and/or phiMail Allow CCR Send to enable the Transmit feature for these data types', 'Para enviar CCR y/o CCD, marque opcionalmente las opciones Permitir envío de CCD y/o Permitir envío de CCR de phiMail, que habilitan la transmisión de esos documentos'),
  ('If you do not select at least one of these, OpenEMR will operate in a receive-only mode', 'Si no marca al menos una, OpenEMR funcionará solo para recibir'),
  ('Click the Save button', 'Haga clic en el botón Guardar'),
  ('To receive error notifications from the Direct Messaging service a valid Notification Email Address needs to be entered for the user named in Administration > Globals > Connectors > phiMail notification user', 'Para recibir avisos de error del servicio de mensajería Direct, el usuario indicado en Administración > Configuración global > Conectores > usuario de avisos de phiMail debe tener un correo de avisos válido'),
  ('Install the EMR Direct trust anchor certificate', 'Instale el certificado de confianza de EMR Direct'),
  ('SENDING A MESSAGE', 'CÓMO ENVIAR UN MENSAJE'),
  ('At present only Continuity of Care Record (CCR) Continuity of Care Document (CCD) can be sent using phiMail', 'Por ahora con phiMail solo se pueden enviar el Registro de Continuidad de la Atención (CCR) y el Documento de Continuidad de la Atención (CCD)'),
  ('Click the Transmit button to reveal the Direct address box', 'Haga clic en Transmitir para que aparezca el cuadro de la dirección Direct'),
  ('Enter the Direct address and click Send CCR/Send CCD as the case may be', 'Ingrese la dirección Direct y haga clic en Enviar CCR o Enviar CCD, según corresponda'),
  ('Send CCR', 'Enviar CCR'),
  ('Send CCD', 'Enviar CCD'),
  ('RECEIVING A MESSAGE', 'CÓMO RECIBIR UN MENSAJE'),
  ('Received messages are processed and a new Patient Note is delivered to a specified user and appears in that user''s Message Center', 'Los mensajes recibidos se procesan y se entrega una nota de paciente nueva al usuario indicado, que aparece en su centro de mensajes'),
  ('These Patient notes are sent without an assigned patient', 'Estas notas de paciente llegan sin un paciente asignado'),
  ('Open and review the message content and any attachments', 'Abra y revise el contenido del mensaje y sus adjuntos'),
  ('Assign the message to the correct patient by clicking Patient: Click to select, assign a Type and forward the message on to the correct clinician or staff member', 'Asigne el mensaje al paciente correcto haciendo clic en Paciente: Haga clic para seleccionar, elija un tipo y reenvíe el mensaje al profesional o al personal que corresponda'),
  ('More information on the use of phiMail messages is available here', 'Aquí hay más información sobre el uso de los mensajes de phiMail'),
  ('Detailed information on how to setup and troubleshoot phiMail messages is available here', 'Aquí hay información detallada para configurar los mensajes de phiMail y resolver problemas'),
  ('Click to Hide', 'Haga clic para ocultar'),
  ('Creating and Managing Encounters', 'Crear y administrar encuentros'),
  ('All patient interactions in OpenEMR are linked to encounters', 'En OpenEMR, toda interacción con el paciente está vinculada a un encuentro'),
  ('Clicking on the + button on the patient header panel opens the New Encounter Form', 'El botón + del encabezado del paciente abre el formulario de nuevo encuentro'),
  ('This form allows you to enter details of a visit or patient interaction', 'Este formulario permite registrar los datos de una visita o interacción con el paciente'),
  ('The information entered here is used to display a summary of the encounter. It is also used for billing purposes', 'Lo que se ingresa aquí se usa para mostrar un resumen del encuentro y también para facturar'),
  ('It consists of three sections ', 'Tiene tres secciones '),
  ('Reason for visit', 'Motivo de la visita'),
  ('Link/Add Issues (Injuries/Medical/Allergy) to Current Visit', 'Vincular/agregar problemas (lesiones, problemas médicos, alergias) a la visita actual'),
  ('The minimum information required to create a new encounter is to select a visit category', 'Lo mínimo para crear un encuentro nuevo es elegir una categoría de visita'),
  ('Date of Service is today''s date by default. If needed it can be changed to any valid date', 'La fecha de atención es la de hoy por defecto. Si hace falta, se puede cambiar por cualquier fecha válida'),
  ('Facility reflects the facility in which the visit occurred', 'El establecimiento es aquel donde se hizo la visita'),
  ('Billing Facility lets you choose the facility used for billing. To create and edit the billing facility go to Administration  > Facilities', 'El establecimiento de facturación es el que se usa para facturar. Para crearlo o editarlo, vaya a Administración > Establecimientos'),
  ('Sensitivity - lets you limit the information in this encounter to those with appropriate privileges', 'Confidencialidad: permite limitar la información de este encuentro a quienes tengan los permisos adecuados'),
  ('The default is Normal, i.e. it is visible to everyone. Selecting High will restrict it to users belonging to the Physicians and Administrators groups by default', 'Por defecto es Normal, es decir, visible para todos. Si se elige Alta, por defecto solo la ven los usuarios de los grupos Médicos y Administradores'),
  ('You need administrator privileges to give access to other groups', 'Para dar acceso a otros grupos se necesitan permisos de administrador'),
  ('Access can be given to other groups by editing User Memberships or Groups and Access Controls in Administration > ACL', 'Se puede dar acceso a otros grupos editando las membresías de usuarios o los grupos y controles de acceso en Administración > Permisos'),
  ('Onset/hosp. date - is used for billing hospital encounters', 'Fecha de inicio/internación: se usa para facturar los encuentros hospitalarios'),
  ('You need administrator privileges to perform the changes to the values in the drop-down boxes.', 'Para cambiar los valores de las listas desplegables se necesitan permisos de administrador.'),
  ('It will be displayed in the visit summary', 'Se muestra en el resumen de la visita'),
  ('Also provides a short description of the visit in Patient Reports', 'También da una breve descripción de la visita en los informes del paciente'),
  ('An issue is a medical problem, allergy, medication, surgery or dental issue', 'Un problema puede ser un problema médico, una alergia, un medicamento, una cirugía o un problema dental'),
  ('It is used to link problems with encounters and is also used to see which encounters are associated to a particular issue', 'Sirve para vincular problemas con encuentros y para ver qué encuentros están asociados a un problema determinado'),
  ('Clicking on the Add Issue button brings up the add issue pop-up box', 'El botón Agregar problema abre la ventana para agregar un problema'),
  ('The default issue type is set to Problem', 'El tipo de problema por defecto es Problema'),
  ('For each issue type there is a list of commonly selected titles which are customizable for your clinic''s specific needs', 'Para cada tipo de problema hay una lista de títulos frecuentes que se puede adaptar a las necesidades de su institución'),
  ('If the desired title is not included, it can be typed into the text box below the list', 'Si el título deseado no está, se puede escribir en el cuadro de texto debajo de la lista'),
  ('Select an appropriate ICD/CPT code. This helps search for education material to give to the patient for that particular problem', 'Elija un código ICD/CPT adecuado. Ayuda a buscar material educativo para darle al paciente sobre ese problema'),
  ('To link this encounter/consult to an existing issue, click the desired issue above to highlight it and then click Save', 'Para vincular este encuentro o consulta con un problema existente, haga clic en el problema de arriba para resaltarlo y después en Guardar'),
  ('Hold down Ctrl button to select multiple issues', 'Mantenga presionada la tecla Ctrl para seleccionar varios problemas'),
  ('For more information on Issues see the ', 'Para más información sobre problemas, vea la '),
  ('Issues and Immunizations wiki page', 'página del wiki sobre problemas y vacunas'),
  ('Message Center Help', 'Ayuda del centro de mensajes'),
  ('The messaging center conveniently consolidates communications options in one place', 'El centro de mensajes reúne en un solo lugar las opciones de comunicación'),
  ('It lets you send messages and dated reminders to staff members, add patient to the recall list and send SMS text messages to patients', 'Permite enviar mensajes y recordatorios con fecha al personal, agregar pacientes a la lista de recordatorios y enviar SMS a los pacientes'),
  ('The default messaging center is divided into three sections - Messages, Reminders and Recalls', 'Por defecto, el centro de mensajes se divide en tres secciones: Mensajes, Avisos y Recordatorios'),
  ('Lets you send messages to staff members about patient related matters', 'Permite enviar mensajes al personal sobre temas relacionados con pacientes'),
  ('It is a part of the medical record', 'Forma parte de la historia clínica'),
  ('Upon logging in to openEMR a small envelope icon is visible on the top right. It shows the number of pending messages and dated reminders', 'Al iniciar sesión en OpenEMR, arriba a la derecha aparece un pequeño ícono de sobre con la cantidad de mensajes y recordatorios con fecha pendientes'),
  ('Alternatively it can be accessed by clicking in the Messages menu item in the top navigation bar', 'También se puede abrir con la opción Mensajes del menú superior'),
  ('The messaging center will open with the Messages tab activated and will display the logged in user''s messages as indicated by the caption My Messages', 'El centro de mensajes se abre en la pestaña Mensajes y muestra los mensajes del usuario conectado, como indica el título Mis mensajes'),
  ('For those with administrative privileges an icon is displayed next to the My Messages caption', 'Quienes tienen permisos de administrador ven un ícono junto al título Mis mensajes'),
  ('Clicking on the icon next to the caption will display everyone''s messages and the caption will change to All Messages', 'Al hacer clic en ese ícono se muestran los mensajes de todos y el título cambia a Todos los mensajes'),
  ('By default Active Messages are displayed as indicated in the section below the Messages caption', 'Por defecto se muestran los mensajes activos, como se indica debajo del título Mensajes'),
  ('Clicking on the Show All or Show Inactive buttons will show the appropriate messages', 'Los botones Mostrar todos o Mostrar inactivos muestran los mensajes correspondientes'),
  ('CREATING A NEW MESSAGE', 'CÓMO CREAR UN MENSAJE NUEVO'),
  ('To create a new message click on the Add New button at the bottom', 'Para crear un mensaje nuevo, haga clic en el botón Agregar nuevo, abajo'),
  ('It will open the Create New Message section', 'Se abre la sección Crear mensaje nuevo'),
  ('Select the message type to more accurately reflect the type of message you are sending. You may choose to leave it as unassigned', 'Elija el tipo de mensaje que mejor refleje lo que envía. También puede dejarlo sin asignar'),
  ('The message status will be New as it a new message that is being created', 'El estado del mensaje será Nuevo, porque se está creando'),
  ('Select a patient by clicking on the patient input box', 'Seleccione un paciente haciendo clic en el campo de paciente'),
  ('It will bring up the Multi patient finder  popup', 'Se abre la ventana para buscar varios pacientes'),
  ('New to openEMR 5.0.2', 'Nuevo en OpenEMR 5.0.2'),
  ('Clicking on either the Enter Name or Enter ID tab will bring up the search box which you can use to select a patient', 'Las pestañas Ingresar nombre o Ingresar ID abren el cuadro de búsqueda para seleccionar un paciente'),
  ('Click on Add to List button to add to the bottom section', 'Haga clic en Agregar a la lista para sumarlo a la sección de abajo'),
  ('Click OK to import these patients into the patient input box', 'Haga clic en Aceptar para pasar estos pacientes al campo de paciente'),
  ('Click the Clear button to clear the imported patient names and start afresh', 'El botón Limpiar borra los pacientes cargados para empezar de nuevo'),
  ('Use the Select User dropdown box to select the user(s) to whom the message will be sent', 'Use la lista Seleccionar usuario para elegir a quién se envía el mensaje'),
  ('Click the Clear button to start afresh', 'El botón Limpiar permite empezar de nuevo'),
  ('Type the message and click Send Message ', 'Escriba el mensaje y haga clic en Enviar mensaje '),
  ('A new message can thus be sent to a single or multiple users and/or can be about a single or multiple patients', 'Así, un mensaje nuevo se puede enviar a uno o varios usuarios y referirse a uno o varios pacientes'),
  ('ADD TO EXISTING MESSAGE', 'AGREGAR A UN MENSAJE EXISTENTE'),
  ('All pending messages are displayed on the MY MESSAGES pane', 'Todos los mensajes pendientes se muestran en el panel MIS MENSAJES'),
  ('You can sort the pending messages in ascending or descending order by clicking on the arrowhead in each cell of the table header', 'Los mensajes pendientes se pueden ordenar de forma ascendente o descendente con la flecha de cada columna del encabezado'),
  ('Clicking on the patient''s name will reveal the message', 'Al hacer clic en el nombre del paciente se muestra el mensaje'),
  ('You can only add to the existing message and cannot edit previously entered data', 'Solo se puede agregar texto al mensaje existente; lo ya escrito no se puede editar'),
  ('You can change the type or leave it as it is', 'Puede cambiar el tipo o dejarlo como está'),
  ('There are four message statuses - New, Forwarded, Read and Done', 'Hay cuatro estados de mensaje: Nuevo, Reenviado, Leído y Terminado'),
  ('Changing the status to Done will remove the message from the active message list', 'Si el estado pasa a Terminado, el mensaje sale de la lista de mensajes activos'),
  ('Changing the status to Read would leave it as a read message in the active messages list of the current user', 'Si el estado pasa a Leído, queda como mensaje leído en la lista de mensajes activos del usuario actual'),
  ('If the message is forwarded it will show up in the active messages list of the user to whom the message was forwarded and disappear from the current user''s active messages list', 'Si el mensaje se reenvía, aparece en la lista de mensajes activos del usuario al que se reenvió y desaparece de la del usuario actual'),
  ('Changing the status to New will achieve the same result', 'Cambiar el estado a Nuevo produce el mismo resultado'),
  ('If desired the current message can be printed', 'Si se desea, el mensaje actual se puede imprimir'),
  ('Clicking on the Delete button will delete the message', 'El botón Eliminar borra el mensaje'),
  ('Otherwise known as Dated Reminders are short messages of up to 160 characters', 'También llamados recordatorios con fecha, son mensajes cortos de hasta 160 caracteres'),
  ('Unlike a message sent in Messages the dated reminder is not a part of the medical record', 'A diferencia de los mensajes de la sección Mensajes, el recordatorio con fecha no forma parte de la historia clínica'),
  ('It can be either linked to a patient or just be a message addressed to another user(s)', 'Puede estar vinculado a un paciente o ser solo un mensaje para otros usuarios'),
  ('There are three actions that are possible - Create a New Dated Reminder, Forward and Set as Completed', 'Hay tres acciones posibles: crear un recordatorio con fecha nuevo, reenviarlo y marcarlo como completado'),
  ('CREATING A NEW DATED REMINDER', 'CÓMO CREAR UN RECORDATORIO CON FECHA'),
  ('Clicking on the Create A Dated Reminder button will bring up the Send a Reminder popup', 'El botón Crear un recordatorio con fecha abre la ventana Enviar un recordatorio'),
  ('The first section lets you either link this message to a patient if so desired.', 'La primera sección permite vincular el mensaje a un paciente, si se desea.'),
  ('The second section lets you choose to whom you wish to send the message', 'La segunda sección permite elegir a quién se envía el mensaje'),
  ('If the checkbox is checked and the message is sent to multiple users then each user has to sign off on the message by clicking the Set As Completed Button for that message', 'Si la casilla está marcada y el mensaje se envía a varios usuarios, cada uno tiene que darlo por cumplido con el botón Marcar como completado de ese mensaje'),
  ('A due date can be specified or a specific time period can be chosen for the dated reminder to to show up', 'Se puede indicar una fecha de vencimiento o un período para que aparezca el recordatorio'),
  ('Set the priority level', 'Indique la prioridad'),
  ('Type a brief message and hit Send Message ', 'Escriba un mensaje breve y presione Enviar mensaje '),
  ('The messages that were sent by the user on that day will be displayed in the table below', 'Los mensajes que el usuario envió ese día se muestran en la tabla de abajo'),
  ('The dated reminder will start to appear 5 days before the reminder date under Dated Reminders ', 'El recordatorio empieza a aparecer en Recordatorios con fecha 5 días antes de su fecha '),
  ('The upcoming reminders have a green exclamation icon', 'Los recordatorios próximos tienen un ícono de exclamación verde'),
  ('The reminder for the current day will have an orange exclamation icon', 'El recordatorio del día tiene un ícono de exclamación naranja'),
  ('The reminders that are past the due date will have a red exclamation triangle icon', 'Los recordatorios vencidos tienen un ícono de triángulo de exclamación rojo'),
  ('By default only five dated reminders are shown at a time', 'Por defecto se muestran solo cinco recordatorios con fecha a la vez'),
  ('Increase the limit to a larger number say 100 so that you can decide on which ones to act upon', 'Aumente el límite a un número mayor, por ejemplo 100, para decidir sobre cuáles actuar'),
  ('You need administrator privileges to increase the number of reminders to show', 'Para aumentar la cantidad de recordatorios que se muestran se necesitan permisos de administrador'),
  ('Go to Administration > Globals > CDR > Dated reminders maximum alerts to show and change the value to a higher number', 'Vaya a Administración > Configuración global > CDR > Máximo de recordatorios con fecha a mostrar y ponga un número mayor'),
  ('A dated reminder message can be either forwarded by clicking the Forward button or removed from the dated reminders list by clicking on the Set As Completed button', 'Un recordatorio con fecha se puede reenviar con el botón Reenviar o quitar de la lista con el botón Marcar como completado'),
  ('FORWARD A DATED REMINDER', 'CÓMO REENVIAR UN RECORDATORIO CON FECHA'),
  ('The process for forwarding the message is quite similar to that of creating a new message', 'Reenviar el mensaje es muy parecido a crear uno nuevo'),
  ('While forwarding a dated reminder the old message is displayed, it can be overwritten', 'Al reenviar un recordatorio se muestra el mensaje anterior, que se puede reemplazar'),
  ('The previous message can however be viewed by clicking on the View Log button', 'De todos modos, el mensaje anterior se puede ver con el botón Ver registro'),
  ('Clicking on the View Log button will bring up the Dated Message Log popup', 'El botón Ver registro abre la ventana Registro de mensajes con fecha'),
  ('Lets you filter the results as per the chosen criteria', 'Permite filtrar los resultados según los criterios elegidos'),
  ('If you hit the refresh button without setting any filters then all dated reminders for the user will appear below', 'Si presiona actualizar sin ningún filtro, aparecen abajo todos los recordatorios con fecha del usuario'),
  ('The log table will show a list of both messages and dated reminders if any', 'La tabla del registro muestra los mensajes y los recordatorios con fecha, si los hay'),
  ('The display window can be resized by clicking and dragging the bottom right corner of the window', 'La ventana se puede agrandar arrastrando su esquina inferior derecha'),
  ('Clicking on the slashed eye icon will hide the filters', 'El ícono del ojo tachado oculta los filtros'),
  ('To reveal the filters click on the eye icon', 'Para volver a ver los filtros, haga clic en el ícono del ojo'),
  ('SET AS COMPLETED', 'MARCAR COMO COMPLETADO'),
  ('Clicking on Set As Completed will remove the reminder from the active display', 'Marcar como completado quita el recordatorio de la lista activa'),
  ('This feature is used to add patients to a recall list, i.e those that need an appointment at a future date but the appointment has not yet been scheduled', 'Esta función sirve para agregar pacientes a una lista de recordatorios, es decir, los que necesitan un turno en el futuro que todavía no se agendó'),
  ('Once an appointment is scheduled the name automatically drops off the recall board', 'Cuando se agenda el turno, el nombre sale automáticamente del tablero de recordatorios'),
  ('The Recalls tab has two buttons New Recall and Recall Board', 'La pestaña Recordatorios tiene dos botones: Nuevo recordatorio y Tablero de recordatorios'),
  ('ADD A NEW RECALL', 'CÓMO AGREGAR UN RECORDATORIO NUEVO'),
  ('Click on the New Recall button to open a new recall scheduling page', 'El botón Nuevo recordatorio abre una página para programarlo'),
  ('It contains a left and right section', 'Tiene una sección izquierda y una derecha'),
  ('Click on the Name text box on the left section to bring up the patient finder pop-up', 'Haga clic en el campo Nombre de la izquierda para abrir el buscador de pacientes'),
  ('Search and select a patient and click OK', 'Busque y seleccione un paciente y haga clic en Aceptar'),
  ('If the patient has any demographic data entered in openEMR it will automatically populate the relevant fields on the right sections', 'Si el paciente tiene datos demográficos cargados en OpenEMR, se completan automáticamente en la sección derecha'),
  ('Fill in any missing details or edit existing information. This will be saved in the patient''s demographics page in openEMR', 'Complete los datos que falten o corrija los existentes. Se guardan en los datos demográficos del paciente en OpenEMR'),
  ('The last visit date will automatically be filled', 'La fecha de la última visita se completa sola'),
  ('If the patient is being added to the recall list on the day of the visit it would reflect the current date', 'Si el paciente se agrega a la lista el mismo día de la visita, figura la fecha actual'),
  ('If the patient is calling back to be added to the list the last visit may be in the remote past. Be aware that the 1,2,3 plus years are calculated and displayed on the Date box at the bottom of this section', 'Si el paciente llama más adelante para que lo agreguen, la última visita puede ser lejana. Tenga en cuenta que los 1, 2, 3 o más años se calculan y muestran en el campo Fecha, al pie de esta sección'),
  ('You could manually edit this date should you choose to or an entirely different date', 'Si quiere, puede editar esa fecha a mano o poner otra distinta'),
  ('Fill in the reason and select a provider and clinic and click the Add Recall button to complete the process', 'Complete el motivo, elija un profesional y un establecimiento y haga clic en Agregar recordatorio para terminar'),
  ('RECALL BOARD - Default - without MedEx Communication Service', 'TABLERO DE RECORDATORIOS - Por defecto - sin el servicio de comunicación MedEx'),
  ('This fully functional Recall Board included in the default install can be used to add patients to the recall list', 'Este tablero de recordatorios, incluido en la instalación por defecto, sirve para agregar pacientes a la lista de recordatorios'),
  ('To access it click on the Recall Board menu item in the top navigation bar or click on the Recall Board button in the Message Center', 'Para abrirlo, use la opción Tablero de recordatorios del menú superior o el botón Tablero de recordatorios del centro de mensajes'),
  ('The top portion of the Recall Board allows the setting of filters to display specified data', 'La parte superior del tablero permite poner filtros para mostrar ciertos datos'),
  ('Enter or select the options in the various boxes to filter the results according to need and press Filter', 'Complete o elija las opciones de los distintos campos para filtrar los resultados y presione Filtrar'),
  ('The filtered results will show up in the section below it', 'Los resultados filtrados aparecen en la sección de abajo'),
  ('The filter section can be hidden or revealed by clicking the arrowhead  on the bottom right ', 'La sección de filtros se oculta o se muestra con la flecha de abajo a la derecha '),
  ('The Name cell contains the name, date of birth, patient ID and date of last visit', 'La columna Nombre tiene el nombre, la fecha de nacimiento, el ID del paciente y la fecha de la última visita'),
  ('You can click on the patient name to quickly access the patient''s chart', 'Haciendo clic en el nombre del paciente se abre rápidamente su ficha'),
  ('The Recall cell has the date of recall and the reason for recall', 'La columna Recordatorio tiene la fecha y el motivo del recordatorio'),
  ('The Contacts cell has various bits of contact information like phone numbers, email etc', 'La columna Contactos tiene datos de contacto como teléfonos, correo, etc.'),
  ('The next three cells are the action cells that let you perform some recall activity', 'Las tres columnas siguientes son de acciones y permiten hacer alguna gestión del recordatorio'),
  ('If you check the check box in any of these cells it will generate an entry in the last cell called Progress', 'Si marca la casilla en cualquiera de ellas, se genera una entrada en la última columna, llamada Seguimiento'),
  ('Checking the Postcards check box will let you print a postcard that can be mailed to a patient', 'La casilla Postales permite imprimir una postal para enviar al paciente'),
  ('Labels can be printed either for one patient or for all the selected patients', 'Se pueden imprimir etiquetas para un paciente o para todos los seleccionados'),
  ('The Office: Phone cell lets you indicate a phone call was made', 'La columna Oficina: Teléfono permite registrar que se hizo una llamada'),
  ('The default installation will not make the phone call automatically', 'La instalación por defecto no hace la llamada automáticamente'),
  ('An appointment can be scheduled by clicking on the calendar icon in that cell', 'Se puede agendar un turno con el ícono del calendario de esa columna'),
  ('Once an appointment is scheduled the recall will drop off the Recall Board', 'Cuando se agenda el turno, el recordatorio sale del tablero'),
  ('The Notes cell lets you add a note to an action that you take in the 3 action cells or just a note without any association to the action', 'La columna Notas permite agregar una nota sobre una acción de las 3 columnas de acciones, o una nota independiente'),
  ('The Progress cell lists all the actions along with any associated notes if any', 'La columna Seguimiento lista todas las acciones con sus notas, si las hay'),
  ('You can delete the recall by clicking on the X button on the top right corner of the cell', 'El recordatorio se elimina con el botón X de la esquina superior derecha de la columna'),
  ('If so desired the default board can be disabled by going to Administration > Calendar > Recall Board: Disable and checking the checkbox and click Save', 'Si se desea, el tablero por defecto se puede desactivar en Administración > Calendario > Tablero de recordatorios: Desactivar, marcando la casilla y haciendo clic en Guardar'),
  ('You need administrator privileges to disable the default Recall Board', 'Para desactivar el tablero de recordatorios por defecto se necesitan permisos de administrador'),
  ('It is a commercial messaging module available to openEMR practices utilizing version 5.0.1 and higher', 'Es un módulo comercial de mensajería para instalaciones de OpenEMR de la versión 5.0.1 en adelante'),
  ('MedEx automates Appointment Reminders and Recalls using phone calls, text messages and e-mails', 'MedEx automatiza los avisos de turnos y los recordatorios con llamadas, SMS y correos'),
  ('Replies from patients are displayed directly in your EHR', 'Las respuestas de los pacientes se muestran directamente en la historia clínica electrónica'),
  ('You need administrator privileges to enable the MedEx Communication Service', 'Para habilitar el servicio de comunicación MedEx se necesitan permisos de administrador'),
  ('Go to Administration > Globals > Connectors and check the Enable MedEx Communication Service check box and click Save', 'Vaya a Administración > Configuración global > Conectores, marque Habilitar el servicio de comunicación MedEx y haga clic en Guardar'),
  ('Refresh the Message Center or just reopen it', 'Actualice el centro de mensajes o vuelva a abrirlo'),
  ('Click on the File menu item on the top left of the Message center page and select Setup MedEx', 'Haga clic en el menú Archivo, arriba a la izquierda del centro de mensajes, y elija Configurar MedEx'),
  ('It will take you to the MedEx sign-up page', 'Lo lleva a la página de registro de MedEx'),
  ('More information available at the openEMR MedEx wiki page', 'Hay más información en la página del wiki de OpenEMR sobre MedEx'),
  ('The features of a MedEx enabled installation are explained in this video', 'Las funciones de una instalación con MedEx se explican en este video'),
  ('SMS zone - This section lets use send and receive SMS texts', 'Zona SMS: esta sección permite enviar y recibir mensajes SMS'),
  ('It is also a subscription based service', 'También es un servicio por suscripción'),
  ('Medical Dashboard Help', 'Ayuda del resumen médico'),
  ('The dashboard is the central location for convenient access the patient''s medical record', 'El resumen es el lugar central para acceder cómodamente a la historia clínica del paciente'),
  ('To help familiarize you with the various components of the Dashboard page it is suggested that you reduce the size of the browser to cover half the viewport, resize the help pop-up by clicking and dragging the bottom right corner of the pop-up. Open another instance of the browser and resize it to cover the other half of the viewport, login to openEMR', 'Para familiarizarse con las partes del resumen, conviene achicar el navegador a la mitad de la pantalla y ajustar el tamaño de esta ventana de ayuda arrastrando su esquina inferior derecha. Abra otro navegador en la otra mitad de la pantalla e inicie sesión en OpenEMR'),
  ('The Dashboard page is divided into three sections', 'La página de resumen se divide en tres secciones'),
  ('Header', 'Encabezado'),
  ('Nav Bar', 'Barra de navegación'),
  ('Data Section', 'Sección de datos'),
  ('The header section will reveal patient specific information across most pages related to the patient''s medical record', 'El encabezado muestra los datos del paciente en la mayoría de las páginas de su historia clínica'),
  ('E-PRESCRIBING', 'RECETA ELECTRÓNICA'),
  ('If Ensora eRx - the electronic prescription module, is enabled the Ensora MedEntry and Ensora Account Status buttons will be appear here', 'Si está habilitado Ensora eRx, el módulo de receta electrónica, aquí aparecen los botones Ensora MedEntry y Estado de la cuenta Ensora'),
  ('Ensora MedEntry', 'Ensora MedEntry'),
  ('You will need Administrator privileges to setup the Ensora service and has to be setup in conjunction with technical support from the Ensora eRx service', 'Para configurar el servicio Ensora se necesitan permisos de administrador, y se hace junto con el soporte técnico del servicio Ensora eRx'),
  ('This module is subscription based and needs to be enabled from Administration > Globals > Connectors > Enable Ensora eRx Service', 'Este módulo es por suscripción y se habilita en Administración > Configuración global > Conectores > Habilitar el servicio Ensora eRx'),
  ('The Ensora eRx Partner Name, Ensora eRx Name and Ensora eRx Password will be provided by the vendor', 'El proveedor entrega el nombre de socio, el nombre y la contraseña de Ensora eRx'),
  ('The rest of the boxes related to the Ensora eRx service can be left at default values', 'Los demás campos del servicio Ensora eRx pueden quedar con los valores por defecto'),
  ('This module is well integrated with openEMR, there are however two non-subscription based alternatives, Weno and Allscripts that can be used instead', 'Este módulo está bien integrado con OpenEMR; de todos modos hay dos alternativas sin suscripción, Weno y Allscripts, que se pueden usar en su lugar'),
  ('The Weno Exchange is well integrated with openEMR and is not subscription based', 'Weno Exchange está bien integrado con OpenEMR y no es por suscripción'),
  ('The Allscripts solution integrates the Allscripts ePrescribe web site with openEMR', 'La solución de Allscripts integra el sitio web Allscripts ePrescribe con OpenEMR'),
  ('Further information regarding using the e-prescribing modules can be found by clicking this link', 'Hay más información sobre los módulos de receta electrónica en este enlace'),
  ('At present e-prescribing from openEMR is possible only in the United States', 'Por ahora, la receta electrónica desde OpenEMR solo es posible en Estados Unidos'),
  ('PATIENT PORTAL', 'PORTAL DEL PACIENTE'),
  ('Information regarding the Patient Portal is also shown in the header section', 'El encabezado también muestra información sobre el portal del paciente'),
  ('There are multiple options regarding patient portals and information on how to setup the patient portal is available here', 'Hay varias opciones de portal del paciente; aquí hay información sobre cómo configurarlo'),
  ('To enable Patient Portal go to Administration > Portal > Enable Version 2 Onsite Patient Portal, Enable Offsite Patient Portal or Enable CMS Portal', 'Para habilitar el portal del paciente, vaya a Administración > Portal > Habilitar el portal del paciente en sitio versión 2, Habilitar el portal externo o Habilitar el portal CMS'),
  ('Enable only one type of portal by checking the relevant check-box', 'Habilite un solo tipo de portal marcando la casilla correspondiente'),
  ('You will need Administrator privileges to enable the patient portal', 'Para habilitar el portal del paciente se necesitan permisos de administrador'),
  ('If the patient has not authorized patient portal then a Patient has not authorized the Patient Portal message will be shown here', 'Si el paciente no autorizó el portal, aquí aparece el mensaje El paciente no autorizó el portal del paciente'),
  ('To authorize the patient portal for the patient go to Dashboard > Demographics > Edit > Choices and select Yes in Allow Patient Portal drop-down box and Save', 'Para autorizar el portal para el paciente, vaya a Resumen > Datos demográficos > Editar > Opciones, elija Sí en Permitir portal del paciente y guarde'),
  ('If the Online Patient portal is enabled there will be either a button that says Create Online Portal Credentials provided the patient has given permission to access the online patient portal or a message that says Patient has not authorized the Patient Portal', 'Si el portal en línea está habilitado, aparece el botón Crear credenciales del portal en línea, si el paciente dio permiso para usarlo, o el mensaje El paciente no autorizó el portal del paciente'),
  ('Create Online Portal Credentials', 'Crear credenciales del portal en línea'),
  ('If the Offsite Patient portal is enabled there will be either a button that says Create Offsite Portal Credentials provided the patient has given permission to access the online patient portal or a message that says Patient has not authorized the Patient Portal', 'Si el portal externo está habilitado, aparece el botón Crear credenciales del portal externo, si el paciente dio permiso para usarlo, o el mensaje El paciente no autorizó el portal del paciente'),
  ('Clicking on the Create Online/Offsite Portal Credentials button will generate a username and password for the patient that has to be given to the patient', 'El botón Crear credenciales del portal genera un usuario y una contraseña que hay que entregarle al paciente'),
  ('These credentials will be used by the patient to login to the patient portal for the first time', 'El paciente usa esas credenciales para entrar al portal por primera vez'),
  ('The patient will have to change their credentials at the first login', 'En el primer ingreso, el paciente tiene que cambiar sus credenciales'),
  ('If the Online/Offsite Portal Credentials has already been set the button will change to', 'Si las credenciales del portal ya se crearon, el botón cambia a'),
  ('Reset Online Portal Credentials', 'Restablecer credenciales del portal en línea'),
  ('DECEASED NOTIFICATION', 'AVISO DE FALLECIMIENTO'),
  ('If the patient is deceased then the deceased notification will appear in red in this section', 'Si el paciente falleció, el aviso de fallecimiento aparece en rojo en esta sección'),
  ('For the deceased notification to appear the date of death must be noted under Medical Dashboard > Edit Demographics > Misc', 'Para que aparezca el aviso, la fecha de fallecimiento tiene que estar registrada en Resumen médico > Editar datos demográficos > Varios'),
  ('The help icon will let you access context sensitive help for each of the pages accessed', 'El ícono de ayuda permite acceder a la ayuda de cada página'),
  ('The Nav Bar allows one to quickly navigate to various parts of the patient''s medical record', 'La barra de navegación permite ir rápidamente a distintas partes de la historia clínica del paciente'),
  ('The default installation has the following items', 'La instalación por defecto tiene estos elementos'),
  ('Dashboard - summarizes all patient related information', 'Resumen: reúne toda la información relacionada con el paciente'),
  ('History - patient''s past medical history, family history, personal history', 'Antecedentes: antecedentes médicos, familiares y personales del paciente'),
  ('Report - Generates and downloads the patient''s Continuity of Care Record (CCR), Continuity of Care Document (CCD) and Patient Report', 'Informe: genera y descarga el Registro de Continuidad de la Atención (CCR), el Documento de Continuidad de la Atención (CCD) y el Informe del paciente'),
  ('Documents - a repository of the patient''s scanned/faxed paper documents. It also the place to download patient specific templates', 'Documentos: guarda los documentos en papel escaneados o enviados por fax del paciente. También es donde se descargan las plantillas del paciente'),
  ('Transactions - lists various notes about happenings in a patient''s chart with respect to billing, legal, patient request, physician request and also generates a patient referral or counter-referral', 'Transacciones: lista notas sobre lo ocurrido en la ficha del paciente en relación con facturación, temas legales, pedidos del paciente o del médico, y genera derivaciones y contrarreferencias'),
  ('Issues - summarizes the patient''s medical problems, allergies, medications, surgeries and dental issues', 'Problemas: reúne los problemas médicos, alergias, medicamentos, cirugías y problemas dentales del paciente'),
  ('Ledger - Summarizes and tabulates all the charges, payments, adjustments and balances for all encounters pertaining to the patient', 'Libro mayor: resume y tabula los cargos, pagos, ajustes y saldos de todos los encuentros del paciente'),
  ('External Data - any external data linked to either encounters or procedures', 'Datos externos: datos externos vinculados a encuentros o procedimientos'),
  ('The data section of the dashboard page lists all pertinent items related to a patient', 'La sección de datos del resumen lista todos los elementos relevantes del paciente'),
  ('These items can be edited if the user has sufficient privilege', 'Estos elementos se pueden editar si el usuario tiene permisos suficientes'),
  ('Some of these data sections can be turned off if not being utilized by the clinic. Go to Admin, Config, Appearance to hide the cards to streamline the dashboard', 'Algunas de estas secciones se pueden ocultar si la institución no las usa. Vaya a Administración > Configuración > Apariencia para ocultar las tarjetas y simplificar el resumen'),
  ('Billing - provides a summary of the balances - Patient Balance Due, Insurance Balance Due, Total Balance Due and lists the name of the Primary Insurance along with its effective date', 'Facturación: resume los saldos (saldo del paciente, saldo del seguro y saldo total) y muestra el seguro principal con su fecha de vigencia'),
  ('Demographics - patient demographics and insurance information', 'Datos demográficos: datos demográficos y del seguro del paciente'),
  ('Patient Reminders - a list reminders for preventive or follow-up care according to patient preferences based on demographic data, specific conditions, and/or medication list as well as the status of the notification', 'Avisos al paciente: lista de avisos de atención preventiva o de seguimiento según las preferencias del paciente, basados en sus datos demográficos, en condiciones específicas o en su medicación, junto con el estado del aviso'),
  ('Disclosures - Record disclosures made for treatment, payment, and health care operations with date, time, patient identification (name or number), user identification (name or number), and a description of the disclosure', 'Divulgaciones: registra las divulgaciones hechas para tratamiento, pago y operaciones de atención de salud, con fecha, hora, identificación del paciente (nombre o número), identificación del usuario (nombre o número) y una descripción'),
  ('Amendments - Enable a user to electronically select the record affected by a patient’s request for amendment and either append the amendment to the affected record or include a link that indicates the amendment’s location', 'Enmiendas: permite seleccionar el registro afectado por una solicitud de enmienda del paciente y agregarle la enmienda o un enlace que indique dónde está'),
  ('ID Card/Photos - will display any ID Card or patient photo that has been uploaded to Documents > Patient Information > Patient ID Card and Patient Photograph folders', 'Documento de identidad/fotos: muestra el documento de identidad o la foto del paciente cargados en Documentos > Información del paciente > carpetas Documento de identidad y Foto del paciente'),
  ('Please ensure that there is only one image file - jpeg, png or bmp in the Patient Photograph folder', 'Asegúrese de que haya un solo archivo de imagen (jpeg, png o bmp) en la carpeta Foto del paciente'),
  ('Clinical Reminders - is a widget that displays the Passive Alerts for a Clinical Decision Rule', 'Avisos clínicos: es un recuadro que muestra las alertas pasivas de una regla de decisión clínica'),
  ('A detailed guide on how to enable and setup a Clinical Decision rule is found here', 'Aquí hay una guía detallada para habilitar y configurar una regla de decisión clínica'),
  ('Once a rule is setup it can be enabled for a particular patient', 'Una vez configurada, la regla se puede habilitar para un paciente en particular'),
  ('Upon reaching a predetermined point, either a date or value, the rule will trigger one or more events', 'Al llegar a un punto predeterminado, una fecha o un valor, la regla dispara uno o más eventos'),
  ('Active Alert - that presents as a pop-up notification when a patient''s chart is entered', 'Alerta activa: aparece como ventana emergente al entrar a la ficha del paciente'),
  ('Passive Alert - that will be displayed in the Clinical Reminders widget section', 'Alerta pasiva: se muestra en el recuadro de Avisos clínicos'),
  ('Patient Reminder - that is used to communicate relevant information pertaining to that particular Clinical Decision Rule and is shown in the Patient Reminders widget as Well as under Administration > Patient Reminders', 'Aviso al paciente: sirve para comunicar información relevante de esa regla de decisión clínica y se muestra en el recuadro Avisos al paciente y en Administración > Avisos al paciente'),
  ('More information about Clinical Decision Rule can be found here', 'Aquí hay más información sobre las reglas de decisión clínica'),
  ('Appointments - shows all future appointments as well as Recalls', 'Turnos: muestra todos los turnos futuros y los recordatorios'),
  ('Recurrent Appointments - shows all recurring appointments', 'Turnos periódicos: muestra todos los turnos que se repiten'),
  ('Past Appointments - will show all past appointments', 'Turnos anteriores: muestra todos los turnos pasados'),
  ('Medical Problems - will show the patient''s medical issues, Issues > Medical Problems', 'Problemas médicos: muestra los problemas médicos del paciente, en Problemas > Problemas médicos'),
  ('Allergies - will show the allergies listed under Issues > Allergies. If eRx is enabled the allergy list has to be entered on the eRx page', 'Alergias: muestra las alergias registradas en Problemas > Alergias. Si eRx está habilitado, las alergias se cargan en la página de eRx'),
  ('Medications - lists the medications under Issues > Medications. If eRx is enabled the medication list has to be entered on the eRx page', 'Medicamentos: lista los medicamentos de Problemas > Medicamentos. Si eRx está habilitado, los medicamentos se cargan en la página de eRx'),
  ('Immunizations - lists immunization history and allows for adding new entries or editing existing ones', 'Vacunas: lista el historial de vacunación y permite agregar o editar registros'),
  ('Prescription - lists the prescriptions of the current patient', 'Recetas: lista las recetas del paciente actual'),
  ('Tracks - if the Track Anything feature is enabled it will display a list of values that can be tracked and graphed', 'Seguimientos: si la función Track Anything está habilitada, muestra una lista de valores que se pueden seguir y graficar'),
  ('An Issue concerns matters relating to the patient''s health', 'Un problema se refiere a cuestiones relacionadas con la salud del paciente'),
  ('The default installation lists 5 types of issues that can be documented', 'La instalación por defecto permite registrar 5 tipos de problemas'),
  ('Issues can also be linked to zero or more encounters', 'Los problemas también se pueden vincular a ninguno, uno o varios encuentros'),
  ('The ability to link individual issues to patient encounters will let the user quickly determine the outcome of the issue over time', 'Vincular cada problema con los encuentros del paciente permite ver rápidamente cómo evoluciona con el tiempo'),
  ('The Issues can be entered in one of two ways', 'Los problemas se pueden cargar de dos maneras'),
  ('From the Patient dashboard via the individual Issues widgets on the right hand column', 'Desde el resumen del paciente, con los recuadros de cada tipo de problema de la columna derecha'),
  ('From the Issues Menu item on the Nav Bar', 'Desde la opción Problemas de la barra de navegación'),
  ('Either way data entered by any method will feature in both locations', 'Lo que se cargue por cualquiera de las dos vías aparece en ambos lugares'),
  ('Click on the Issues menu item to bring up the Issues page', 'Haga clic en la opción Problemas para abrir la página de problemas'),
  ('You can enter data into all 5 categories from this page', 'Desde esta página puede cargar datos en las 5 categorías'),
  ('To use the help page as an instruction manual it is suggested that you reduce the size of the browser to cover half the viewport, resize the help pop-up by clicking and dragging the bottom right corner of the pop-up. Open another instance of the browser and resize it to cover the other half of the viewport, login to openEMR ', 'Para usar la ayuda como manual, conviene achicar el navegador a la mitad de la pantalla y ajustar el tamaño de esta ventana de ayuda arrastrando su esquina inferior derecha. Abra otro navegador en la otra mitad de la pantalla e inicie sesión en OpenEMR '),
  ('Click on Add to bring up the Add/Edit Issue pop-up page', 'Haga clic en Agregar para abrir la ventana Agregar/editar problema'),
  ('The Type would be Problem indicating a medical problem', 'El tipo será Problema, que indica un problema médico'),
  ('Underneath it will be some common problems', 'Debajo aparecen algunos problemas frecuentes'),
  ('Choose one if appropriate', 'Elija uno si corresponde'),
  ('This will fill in the Title text-box as well as ICD10 code in the Coding text-box', 'Se completan el título y el código ICD10 en el campo de código'),
  ('If the medical problem is not listed in the drop-down box click on Coding text-box to bring up the Select Codes pop-up page', 'Si el problema no está en la lista, haga clic en el campo de código para abrir la ventana Seleccionar códigos'),
  ('Select only one code per problem', 'Elija un solo código por problema'),
  ('If the displayed table says No matching record found it means that the ICD10 code set is not installed', 'Si la tabla dice No hay registros coincidentes, significa que el catálogo ICD10 no está instalado'),
  ('Install it by going to Administration > Other > External Data Loads > ICD10 > Staged Releases and click Install', 'Para instalarlo, vaya a Administración > Otros > Cargas de datos externos > ICD10 > Versiones preparadas y haga clic en Instalar'),
  ('You will need Administrator Privileges to install the ICD10 code set', 'Para instalar el catálogo ICD10 se necesitan permisos de administrador'),
  ('Now when you bring up the Select Codes pop-up you will see all the ICD10 codes listed', 'Ahora, al abrir la ventana Seleccionar códigos, aparecen todos los códigos ICD10'),
  ('You can search for and select the codes by clicking once on each line containing the code', 'Puede buscar y elegir los códigos haciendo un clic en la línea de cada código'),
  ('Click Close to exit the Select Codes pop-up page', 'Haga clic en Cerrar para salir de la ventana Seleccionar códigos'),
  ('The selected code will be added to the coding text-box and the Description on the code will be added to the Title text-box', 'El código elegido se agrega al campo de código y su descripción, al título'),
  ('You can edit the title if you so choose', 'Si quiere, puede editar el título'),
  ('If a wrong code was selected click on the Coding text-box to to bring up the Select Codes pop-up page', 'Si eligió un código equivocado, haga clic en el campo de código para abrir de nuevo la ventana Seleccionar códigos'),
  ('Listing an ICD10 code will let you search for educational material for that particular code ', 'Registrar un código ICD10 permite buscar material educativo para ese código '),
  ('It is also necessary to list an ICD10 code in order for the problem to be listed in Continuity of Care Report (CCR) and Continuity of Care Document (CCD)', 'También hace falta un código ICD10 para que el problema figure en el Registro de Continuidad de la Atención (CCR) y en el Documento de Continuidad de la Atención (CCD)'),
  ('Enter a Begin Date', 'Ingrese una fecha de inicio'),
  ('Enter an End Date if the problem has been resolved', 'Ingrese una fecha de fin si el problema se resolvió'),
  ('Leave it blank if the problem is still active', 'Déjela vacía si el problema sigue activo'),
  ('Select the type of Occurrence of the problem from the drop-down box, the choices are Unknown or N/A, First, Early Recurrence, Late Recurrence, Delayed Recurrence, Chronic/Recurrent and Acute on Chronic', 'Elija en la lista cómo se presenta el problema: Desconocido o N/A, Primera vez, Recurrencia temprana, Recurrencia tardía, Recurrencia diferida, Crónico/recurrente o Agudización de un crónico'),
  ('You may type in a name of the person referring or leave blank', 'Puede escribir el nombre de quien lo derivó o dejarlo vacío'),
  ('An Outcome for the problem - Resolved, Improved, Status Quo, Worse, Pending Followup or leave as Unassigned', 'Un resultado para el problema: Resuelto, Mejorado, Sin cambios, Peor, Pendiente de seguimiento, o dejarlo Sin asignar'),
  ('Leave Destination blank', 'Deje vacío el destino'),
  ('Click Save to save this problem', 'Haga clic en Guardar para guardar el problema'),
  ('The saved Medical Problem will now be listed as a single line in the main Issues page under Medical Problems', 'El problema médico guardado aparece como una línea en la página principal de problemas, en Problemas médicos'),
  ('If it it still active i.e. End Date is not entered it will be displayed in red, if the problem has an end Date then it will be displayed in black', 'Si sigue activo, es decir, sin fecha de fin, se muestra en rojo; si tiene fecha de fin, en negro'),
  ('Clicking on the first cell under Title will bring up the Add/Edit Issue pop-up page that will let you edit this entry', 'Al hacer clic en la primera celda de Título se abre la ventana Agregar/editar problema para editar el registro'),
  ('Clicking on the Coding cell will bring up the Educational materials pop-up that will let you search by ICD10 code', 'Al hacer clic en la celda de código se abre la ventana de materiales educativos, para buscar por código ICD10'),
  ('There are two sources that can be searched - MedlinePlus Connect or Local Content', 'Se puede buscar en dos fuentes: MedlinePlus Connect o contenido local'),
  ('You can upload content and label them by ICD10 code enable you to search and download the selected material', 'Puede cargar contenidos y etiquetarlos por código ICD10 para buscarlos y descargarlos después'),
  ('The last cell on the line in Encounter', 'La última celda de la línea es Encuentro'),
  ('If the issue has not been linked to an encounter it will display 0', 'Si el problema no está vinculado a ningún encuentro, muestra 0'),
  ('To be able to link this issue to an encounter click on the button to bring up the Issues and Encounters pop-up page', 'Para vincularlo a un encuentro, haga clic en el botón que abre la ventana Problemas y encuentros'),
  ('It will have two sections - the Issues Section and the Encounters Section', 'Tiene dos secciones: Problemas y Encuentros'),
  ('The Issues section will display all the issues entered for the patient and will display the type of issue, its title and a description', 'La sección Problemas muestra todos los problemas del paciente con su tipo, título y descripción'),
  ('The Encounters Section will display the date of encounter as well as the presenting complaint', 'La sección Encuentros muestra la fecha de cada encuentro y el motivo de consulta'),
  ('To link the Issues to an encounter first select the issue, it will be highlighted yellow, then click on one or more encounters to link them, the selected encounters will be highlighted in purple', 'Para vincular un problema a un encuentro, primero elija el problema, que se resalta en amarillo, y después haga clic en uno o más encuentros, que se resaltan en violeta'),
  ('To delink just click on the item to delink and it will get delinked and will no longer be highlighted', 'Para desvincular, haga clic en el elemento: se desvincula y deja de estar resaltado'),
  ('Click Save', 'Haga clic en Guardar'),
  ('You can also add an Issue from this page by clicking on the Add Issue button to bring up the Add issue pop-up page', 'Desde esta página también puede agregar un problema con el botón Agregar problema'),
  ('You can then proceed to link the newly added issue to encounters', 'Después puede vincular el problema nuevo con los encuentros'),
  ('Depending on whether or not Ensora eRx module is enabled the method of entry will vary', 'La forma de carga varía según esté habilitado o no el módulo Ensora eRx'),
  ('In the default installation click on Add to bring up the Add/Edit Issue pop-up page', 'En la instalación por defecto, haga clic en Agregar para abrir la ventana Agregar/editar problema'),
  ('The Type would be Allergy', 'El tipo será Alergia'),
  ('Underneath it will be some common drugs', 'Debajo aparecen algunos medicamentos frecuentes'),
  ('If not listed you can add the drug to the Title text-box', 'Si no está, puede escribir el medicamento en el título'),
  ('Leave Coding Blank', 'Deje vacío el código'),
  ('Begin date if known', 'Fecha de inicio, si se conoce'),
  ('End Date leave blank if ongoing', 'Deje vacía la fecha de fin si sigue vigente'),
  ('Enter Occurrence if appropriate', 'Indique cómo se presenta, si corresponde'),
  ('Enter the Severity of the allergy', 'Indique la gravedad de la alergia'),
  ('Reaction - hives, nausea, shortness of breath or unassigned', 'Reacción: urticaria, náuseas, falta de aire o sin asignar'),
  ('Referred By, Outcome and Destination can be left blank', 'Derivado por, Resultado y Destino pueden quedar vacíos'),
  ('If you have Ensora eRx module enabled then the allergies have to be entered on the Ensora MedEntry page', 'Si el módulo Ensora eRx está habilitado, las alergias se cargan en la página Ensora MedEntry'),
  ('Click on the Allergy/Intolerance button', 'Haga clic en el botón Alergia/intolerancia'),
  ('Search for the drug by typing its name in the Search for Allergy text-box', 'Busque el medicamento escribiendo su nombre en el campo Buscar alergia'),
  ('Click the Search for Allergy button', 'Haga clic en el botón Buscar alergia'),
  ('Select from the displayed list', 'Elija de la lista que aparece'),
  ('Assign Severity and Save', 'Indique la gravedad y guarde'),
  ('It will then appear in the Allergies widget on the Patient Dashboard as well as under allergies in the Issues page', 'Aparece en el recuadro Alergias del resumen del paciente y en alergias, en la página de problemas'),
  ('You can link the allergy to one or more encounters if appropriate', 'Si corresponde, puede vincular la alergia a uno o más encuentros'),
  ('The Type would be Medication', 'El tipo será Medicamento'),
  ('Fill in the details in a fashion similar to that used for allergies', 'Complete los datos de forma parecida a las alergias'),
  ('Type the name of the drug in the Drug Search text-box', 'Escriba el nombre del medicamento en el campo Buscar medicamento'),
  ('Assign the dosage values etc. and click Save', 'Indique la dosis y demás datos y haga clic en Guardar'),
  ('It will then appear in the Medications widget on the Patient Dashboard as well as under Medications in the Issues page', 'Aparece en el recuadro Medicamentos del resumen del paciente y en medicamentos, en la página de problemas'),
  ('You can link the medication to one or more encounters if appropriate', 'Si corresponde, puede vincular el medicamento a uno o más encuentros'),
  ('The Type would be Surgery', 'El tipo será Cirugía'),
  ('Select a surgery if it is listed in the drop-down box or enter a new surgery name under Title', 'Elija la cirugía si está en la lista o escriba una nueva en el título'),
  ('Leave coding blank as currently you can only enter ICD10 codes', 'Deje vacío el código, porque por ahora solo se pueden cargar códigos ICD10'),
  ('Begin and End date if information is available', 'Fecha de inicio y de fin, si se conocen'),
  ('The rest may be left blank if so preferred', 'Lo demás puede quedar vacío, si se prefiere'),
  ('The Type would be Dental Issues', 'El tipo será Problema dental'),
  ('Fill in the necessary details in a fashion similar to that for Medical Problems', 'Complete los datos necesarios de forma parecida a los problemas médicos'),
  ('More information about Issues can be found here', 'Aquí hay más información sobre los problemas'),
  ('Patient Transactions Help', 'Ayuda de transacciones del paciente'),
  ('Transactions are for events or information not necessarily tied to one specific visit or encounter', 'Las transacciones son para hechos o información que no están necesariamente ligados a una visita o encuentro en particular'),
  ('Most activities in relation to a patient are based on an encounter', 'La mayoría de las actividades con un paciente se basan en un encuentro'),
  ('Transactions provides a mechanism to link an activity to patient that is not encounter based', 'Las transacciones permiten vincular al paciente una actividad que no se basa en un encuentro'),
  ('Create New Transaction', 'Crear transacción nueva'),
  ('View/Print Blank Referral Form - that can be filled by hand', 'Ver/imprimir formulario de derivación en blanco, para completar a mano'),
  ('View/Print Blank Referral Form', 'Ver/imprimir formulario de derivación en blanco'),
  ('Below this is a table that contains the existing transactions, if any, pertaining to the current patient', 'Debajo hay una tabla con las transacciones del paciente actual, si las hay'),
  ('There are three sets of actions that can be performed on this page, in addition the form can be customized', 'En esta página se pueden hacer tres tipos de acciones; además, el formulario se puede adaptar'),
  ('Interact with created transactions', 'Trabajar con las transacciones creadas'),
  ('Customize the Transaction Form', 'Adaptar el formulario de transacciones'),
  ('Click on Create New Transaction to open the Add/Edit Patient Transaction page', 'Haga clic en Crear transacción nueva para abrir la página Agregar/editar transacción del paciente'),
  ('This is where the referrals and various other simple transactions are created', 'Ahí se crean las derivaciones y otras transacciones simples'),
  ('Use the help file on that page for further help', 'Para más ayuda, use la ayuda de esa página'),
  ('All Transactions for the patient will appear on the Transactions page in descending order of its date of creation', 'Todas las transacciones del paciente aparecen en la página de transacciones, de la más reciente a la más antigua'),
  ('Each Transaction is listed on a separate line', 'Cada transacción aparece en una línea'),
  ('Depending on the level of access you can View/Edit the Transaction', 'Según su nivel de acceso, puede ver o editar la transacción'),
  ('Those with adequate privilege would be able to able to Delete the transaction', 'Quienes tienen permisos suficientes pueden eliminarla'),
  ('These two actions are available for all transactions', 'Estas dos acciones están disponibles para todas las transacciones'),
  ('A Referral has an additional action - to print the referral or save it as a pdf file', 'Una derivación tiene una acción más: imprimirla o guardarla como PDF'),
  ('In addition to creating a Referral the system also allows you to print a blank referral form that can be manually filled to generate a Referral', 'Además de crear una derivación, el sistema permite imprimir un formulario de derivación en blanco para completar a mano'),
  ('This method will however result in the data becoming non-structured and one would loose the ability to document the reply in an electronic format', 'Pero de esa forma los datos quedan sin estructura y se pierde la posibilidad de registrar la respuesta en formato electrónico'),
  ('The default form can be customized by editing it in Administration > Layouts', 'El formulario por defecto se puede adaptar editándolo en Administración > Diseños'),
  ('You will need Administrator privileges to edit this form', 'Para editar este formulario se necesitan permisos de administrador'),
  ('There are 3 forms in the Core category - Demographics, Facility Specific User Information and History and all 5 forms in Transactions that can be edited ', 'Se pueden editar 3 formularios de la categoría Básicos (Datos demográficos, Información del usuario por establecimiento y Antecedentes) y los 5 formularios de Transacciones '),
  ('More information on how to edit this form and other such forms can be found here', 'Aquí hay más información sobre cómo editar este y otros formularios'),
  ('Add Edit Transactions Help', 'Ayuda para agregar o editar transacciones'),
  ('Currently there are 5 types of transactions in OpenEMR', 'Actualmente hay 5 tipos de transacciones en OpenEMR'),
  ('The first step is to select a Transaction type', 'El primer paso es elegir el tipo de transacción'),
  ('CREATE A REFERRAL', 'CÓMO CREAR UNA DERIVACIÓN'),
  ('A Referral is the most common type of transaction', 'La derivación es el tipo de transacción más común'),
  ('Select Referral from the Transaction type drop-down box', 'Elija Derivación en la lista de tipos de transacción'),
  ('The first tab is the referral tab that constitutes the referral being made', 'La primera pestaña es la de derivación, que corresponde a la derivación que se hace'),
  ('The second tab, is somewhat ambiguously named as Counter-Referral, is the place to document the received reply to the referral being made', 'La segunda pestaña, llamada Contrarreferencia, sirve para registrar la respuesta recibida a la derivación'),
  ('There are various selections to be filled, the minimum data needed to successfully save the referral is Referral Date, Refer To and Reason', 'Hay varios campos para completar; lo mínimo para guardar la derivación es la fecha, a quién se deriva y el motivo'),
  ('Filling in the other fields will help provide additional details pertinent to the referral', 'Completar los otros campos agrega detalles útiles sobre la derivación'),
  ('Meaningful Use Requirements', 'Requisitos de Meaningful Use'),
  ('To fulfill Meaningful use requirements and help track its use you can check on the Sent Summary of Care check-box and the Sent Summary of Care Electronically check-box if appropriate', 'Para cumplir con los requisitos de Meaningful Use y registrar su uso, puede marcar las casillas Se envió el resumen de atención y Se envió el resumen de atención por vía electrónica, si corresponde'),
  ('Note this refers to the Continuity of Care Record (CCR) or Continuity of Care Document (CCD) as the case may be', 'Esto se refiere al Registro de Continuidad de la Atención (CCR) o al Documento de Continuidad de la Atención (CCD), según el caso'),
  ('It does not refer to the actual referral that has been created', 'No se refiere a la derivación que se creó'),
  ('If you use the Carecoordination module to generate this document for this patient on the same day after creating this referral these checkboxes will be checked automatically', 'Si usa el módulo de coordinación de la atención para generar este documento el mismo día, después de crear la derivación, estas casillas se marcan solas'),
  ('A MIPS eligible clinician must verify that the fields for current problem list, current medication list, and current medication allergy list are not blank and include the most recent information known by the MIPS eligible clinician as of the time of generating the summary of care document or include a notation of no current problem, medication and/or medication allergies', 'Un profesional habilitado para MIPS debe verificar que la lista de problemas, de medicamentos y de alergias a medicamentos actuales no estén vacías y tengan la información más reciente al momento de generar el resumen de atención, o que se indique que no hay problemas, medicamentos o alergias actuales'),
  ('A MIPS eligible clinician must have reasonable certainty of receipt of the summary of care document by the receiving clinician', 'Un profesional habilitado para MIPS debe tener una certeza razonable de que el profesional que recibe el resumen de atención efectivamente lo recibió'),
  ('If a CEHRT software other than EMR Direct was used to transfer the summary of care you can check the Confirmed Recipient Received Summary of Care check-box upon confirmation of receipt if appropriate', 'Si para enviar el resumen de atención se usó un software certificado distinto de EMR Direct, puede marcar la casilla El destinatario confirmó la recepción del resumen de atención cuando se confirme, si corresponde'),
  ('CREATE A COUNTER-REFERRAL', 'CÓMO CREAR UNA CONTRARREFERENCIA'),
  ('The somewhat ambiguously named Counter-Referral is the space where the reply to the referral being made can be documented', 'La contrarreferencia es el lugar donde se registra la respuesta a la derivación'),
  ('This lets you manually enter the reply to the referral as structured data', 'Permite cargar a mano la respuesta a la derivación como datos estructurados'),
  ('Fill in the details as required and click Save', 'Complete los datos necesarios y haga clic en Guardar'),
  ('It therefore stands to reason that you cannot create a Counter-Referral by itself', 'Por eso, una contrarreferencia no se puede crear sola'),
  ('CREATE A SIMPLE TRANSACTION TYPE', 'CÓMO CREAR UNA TRANSACCIÓN SIMPLE'),
  ('The other 4 transaction types are simple transaction types - Patient Request, Physician Request, Legal and Billing', 'Los otros 4 tipos son transacciones simples: Pedido del paciente, Pedido del médico, Legal y Facturación'),
  ('Select as appropriate and fill in the text box and click Save', 'Elija el que corresponda, complete el cuadro de texto y haga clic en Guardar'),
  ('Once a Transaction is saved it will appear on the Transaction page', 'Una vez guardada, la transacción aparece en la página de transacciones'),
  ('These Transactions are generally used to document events that have already occurred', 'Estas transacciones se usan en general para registrar hechos que ya ocurrieron'),
  ('A place to document and display the patient''s past medical history, family history, personal history', 'El lugar para registrar y mostrar los antecedentes médicos, familiares y personales del paciente'),
  ('It has four tabs', 'Tiene cuatro pestañas'),
  ('General Tab - lists the risk factors / past medical conditions on the left and the Results of various clinical exams, procedures and tests on the right', 'General: lista a la izquierda los factores de riesgo y antecedentes médicos, y a la derecha los resultados de exámenes, procedimientos y estudios'),
  ('Family history - Documents the patient''s family history, an ICD10 diagnosis can be linked to the medical conditions', 'Antecedentes familiares: registra los antecedentes familiares del paciente; se puede vincular un diagnóstico ICD10 a cada enfermedad'),
  ('Relatives - lists the patient relatives having various medical conditions like Cancer, Diabetes, Hypertension etc', 'Familiares: lista los familiares del paciente con enfermedades como cáncer, diabetes, hipertensión, etc.'),
  ('Lifestyle - lists the patient''s use of Tobacco, Coffee, Alcohol, Recreational Drugs etc', 'Estilo de vida: lista el consumo de tabaco, café, alcohol, drogas recreativas, etc.'),
  ('Other - lists items not covered in the above sections', 'Otros: lista lo que no entra en las secciones anteriores'),
  ('Users with appropriate privileges can edit these items by clicking on the Edit button', 'Los usuarios con los permisos adecuados pueden editar estos datos con el botón Editar'),
  ('Documents List', 'Lista de documentos'),
  ('Onsite Portal', 'Portal del paciente'),
  ('Document Uploader/Viewer', 'Carga y visor de documentos'),
  ('Recurrent Appointments', 'Turnos periódicos'),
  ('Outpatient', 'Ambulatorio'),
  ('Inpatient Encounter', 'Encuentro de internación'),
  ('Observation Encounter', 'Encuentro de observación'),
  ('Virtual Encounter', 'Encuentro virtual'),
  ('Visit out of hours', 'Visita fuera de horario'),
  ('Out of Hours visit (Not Night)', 'Visita fuera de horario (no nocturna)'),
  ('Office visit for pediatric care and assessment', 'Consulta de atención y evaluación pediátrica'),
  ('New Patient - 10 Minutes', 'Paciente nuevo - 10 minutos'),
  ('New Patient - 15-29 Minutes', 'Paciente nuevo - 15 a 29 minutos'),
  ('New Patient - 30-44 Minutes', 'Paciente nuevo - 30 a 44 minutos'),
  ('New Patient - 45-59 Minutes', 'Paciente nuevo - 45 a 59 minutos'),
  ('New Patient - 60-74 Minutes', 'Paciente nuevo - 60 a 74 minutos'),
  ('Established Patient - 10-19 Minutes', 'Paciente conocido - 10 a 19 minutos'),
  ('Established Patient - 20-29 Minutes', 'Paciente conocido - 20 a 29 minutos'),
  ('Established Patient - 30-39 Minutes', 'Paciente conocido - 30 a 39 minutos'),
  ('Established Patient - 40-54 Minutes', 'Paciente conocido - 40 a 54 minutos'),
  ('Discharge to home for hospice care', 'Alta a domicilio con cuidados paliativos'),
  ('Hospice', 'Cuidados paliativos'),
  ('Patient self-discharge against medical advice', 'Alta voluntaria contra indicación médica'),
  ('Discharge to community hospital', 'Alta con derivación a hospital de la comunidad'),
  ('Enter Details', 'Ingrese los detalles'),
  ('Plan of Care', 'Plan de cuidados'),
  ('Test/Order', 'Estudio/orden'),
  ('Add Reason', 'Agregar motivo'),
  ('yyyy-mm-dd HH:MM Date of service', 'aaaa-mm-dd HH:MM fecha de atención'),
  ('yyyy-mm-dd HH:MM Target or Achieve-by date', 'aaaa-mm-dd HH:MM fecha objetivo'),
  ('yyyy-mm-dd HH:MM planned end date', 'aaaa-mm-dd HH:MM fecha de fin prevista'),
  ('Click here to provide an explanation for the observation value (or lack of value)', 'Haga clic aquí para explicar el valor de la observación (o su ausencia)'),
  ('Clinical Notes Form', 'Formulario de notas clínicas'),
  ('Note Details', 'Detalles de la nota'),
  ('Select Note Type', 'Seleccione el tipo de nota'),
  ('History & Physical', 'Historia clínica y examen físico'),
  ('Laboratory Report Narrative', 'Informe narrativo de laboratorio'),
  ('Select Note Category', 'Seleccione la categoría de la nota'),
  ('No documents linked', 'No hay documentos vinculados'),
  ('Add Documents', 'Agregar documentos'),
  ('No results linked', 'No hay resultados vinculados'),
  ('Add Results', 'Agregar resultados'),
  ('Click to expand page to full width', 'Haga clic para ampliar la página a todo el ancho'),
  ('Check if this is a cognitive status. Unchecked is a functional status', 'Marque si es un estado cognitivo. Sin marcar, es un estado funcional'),
  ('Basic Information', 'Información básica'),
  ('Select Type', 'Seleccione el tipo'),
  ('Cognitive Status', 'Estado cognitivo'),
  ('Functional Status', 'Estado funcional'),
  ('Imaging', 'Imágenes'),
  ('Therapy', 'Terapia'),
  ('Linked Questionnaire', 'Cuestionario vinculado'),
  ('No Questionnaire Linked', 'No hay cuestionario vinculado'),
  ('Link a questionnaire response to provide additional context for this observation', 'Vincule la respuesta de un cuestionario para dar más contexto a esta observación'),
  ('Link Questionnaire', 'Vincular cuestionario'),
  ('Sub-observations', 'Subobservaciones'),
  ('Add Sub-observation', 'Agregar subobservación'),
  ('Save Observation', 'Guardar observación'),
  ('Enter observation code', 'Ingrese el código de la observación'),
  ('Enter observation description', 'Ingrese la descripción de la observación'),
  ('Enter value', 'Ingrese el valor'),
  ('Additional comments or notes about this observation', 'Comentarios o notas adicionales sobre esta observación'),
  ('Create Encounter Questionnaires', 'Crear cuestionarios del encuentro'),
  ('Type to search.', 'Escriba para buscar.'),
  ('You may edit name to shorten to be more understandable.', 'Puede editar el nombre para acortarlo y que se entienda mejor.'),
  ('AVM Confirmed', 'Confirmado por mensaje de voz'),
  ('SMS Confirmed', 'Confirmado por SMS'),
  ('Show Day of Week', 'Mostrar el día de la semana'),
  ('With Canceled Appointments', 'Con turnos cancelados'),
  ('Show Patient Address', 'Mostrar la dirección del paciente'),
  ('Date of this letter', 'Fecha de esta carta');

INSERT INTO lang_constants (constant_name)
SELECT t.cn FROM nursing_fix_lang6 t
WHERE NOT EXISTS (SELECT 1 FROM lang_constants c WHERE c.constant_name = t.cn);

UPDATE lang_definitions d
JOIN lang_constants c ON c.cons_id = d.cons_id
JOIN nursing_fix_lang6 t ON c.constant_name = t.cn
SET d.definition = t.es
WHERE d.lang_id IN (3, 4) AND (d.definition IS NULL OR d.definition = '');

INSERT INTO lang_definitions (cons_id, lang_id, definition)
SELECT c.cons_id, ll.lang_id, t.es
FROM nursing_fix_lang6 t
JOIN lang_constants c ON c.constant_name = t.cn
JOIN lang_languages ll ON ll.lang_id IN (3, 4)
WHERE NOT EXISTS (SELECT 1 FROM lang_definitions d WHERE d.cons_id = c.cons_id AND d.lang_id = ll.lang_id);

DROP TEMPORARY TABLE IF EXISTS nursing_fix_lang6;

-- Units on the axes of the vital signs dashboard charts.
DROP TEMPORARY TABLE IF EXISTS nursing_fix_lang7;
CREATE TEMPORARY TABLE nursing_fix_lang7 (cn TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL, es TEXT CHARACTER SET utf8mb4 NOT NULL);
INSERT INTO nursing_fix_lang7 (cn, es) VALUES
  ('beats/min', 'lat/min'),
  ('breaths/min', 'resp/min');

INSERT INTO lang_constants (constant_name)
SELECT t.cn FROM nursing_fix_lang7 t
WHERE NOT EXISTS (SELECT 1 FROM lang_constants c WHERE c.constant_name = t.cn);

UPDATE lang_definitions d
JOIN lang_constants c ON c.cons_id = d.cons_id
JOIN nursing_fix_lang7 t ON c.constant_name = t.cn
SET d.definition = t.es
WHERE d.lang_id IN (3, 4) AND (d.definition IS NULL OR d.definition = '');

INSERT INTO lang_definitions (cons_id, lang_id, definition)
SELECT c.cons_id, ll.lang_id, t.es
FROM nursing_fix_lang7 t
JOIN lang_constants c ON c.constant_name = t.cn
JOIN lang_languages ll ON ll.lang_id IN (3, 4)
WHERE NOT EXISTS (SELECT 1 FROM lang_definitions d WHERE d.cons_id = c.cons_id AND d.lang_id = ll.lang_id);

DROP TEMPORARY TABLE IF EXISTS nursing_fix_lang7;

-- APACHE II dashboard.
DROP TEMPORARY TABLE IF EXISTS nursing_fix_lang8;
CREATE TEMPORARY TABLE nursing_fix_lang8 (cn TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL, es TEXT CHARACTER SET utf8mb4 NOT NULL);
INSERT INTO nursing_fix_lang8 (cn, es) VALUES
  ('APACHE II Dashboard', 'Tablero APACHE II'),
  ('30 days', '30 días'),
  ('Last score', 'Última evaluación'),
  ('More than 24 h without APACHE II', 'Más de 24 h sin APACHE II'),
  ('No APACHE II score recorded in this admission.', 'No hay evaluaciones APACHE II registradas en esta internación.'),
  ('No APACHE II scores in this period.', 'No hay evaluaciones APACHE II en este período.'),
  ('Parts of the score', 'Componentes del puntaje'),
  ('Record APACHE II', 'Cargar APACHE II'),
  ('Variables', 'Variables');

INSERT INTO lang_constants (constant_name)
SELECT t.cn FROM nursing_fix_lang8 t
WHERE NOT EXISTS (SELECT 1 FROM lang_constants c WHERE c.constant_name = t.cn);

UPDATE lang_definitions d
JOIN lang_constants c ON c.cons_id = d.cons_id
JOIN nursing_fix_lang8 t ON c.constant_name = t.cn
SET d.definition = t.es
WHERE d.lang_id IN (3, 4) AND (d.definition IS NULL OR d.definition = '');

INSERT INTO lang_definitions (cons_id, lang_id, definition)
SELECT c.cons_id, ll.lang_id, t.es
FROM nursing_fix_lang8 t
JOIN lang_constants c ON c.constant_name = t.cn
JOIN lang_languages ll ON ll.lang_id IN (3, 4)
WHERE NOT EXISTS (SELECT 1 FROM lang_definitions d WHERE d.cons_id = c.cons_id AND d.lang_id = ll.lang_id);

DROP TEMPORARY TABLE IF EXISTS nursing_fix_lang8;
