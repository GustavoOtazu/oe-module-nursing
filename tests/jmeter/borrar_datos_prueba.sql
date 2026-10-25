-- Deletes everything the load test saved: the forms of the jmeter* users (the
-- form rows and their entries in forms). Run it on the OpenEMR database when the
-- tests are over; the jmeter* users themselves are deleted from Administration > Users.
DELETE x FROM form_balance_hidrico x WHERE x.user LIKE 'jmeter%';
DELETE x FROM form_alimentacion x WHERE x.user LIKE 'jmeter%';
DELETE x FROM form_registro_vm x WHERE x.user LIKE 'jmeter%';
DELETE x FROM form_curaciones x WHERE x.user LIKE 'jmeter%';
DELETE x FROM form_aplicaciones x WHERE x.user LIKE 'jmeter%';
DELETE x FROM form_cuidados x WHERE x.user LIKE 'jmeter%';
DELETE x FROM form_plan_cuidados x WHERE x.user LIKE 'jmeter%';
DELETE x FROM form_evaluaciones x WHERE x.user LIKE 'jmeter%';
DELETE x FROM form_escala_sofa x WHERE x.user LIKE 'jmeter%';
DELETE x FROM form_escala_apache x WHERE x.user LIKE 'jmeter%';
DELETE FROM forms WHERE user LIKE 'jmeter%' AND formdir IN ('balance_hidrico', 'alimentacion', 'registro_vm',
    'curaciones', 'aplicaciones', 'cuidados', 'plan_cuidados', 'evaluaciones', 'escala_sofa', 'escala_apache');
