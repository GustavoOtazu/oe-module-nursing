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
