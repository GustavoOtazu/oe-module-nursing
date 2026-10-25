#!/usr/bin/env python3
"""Generates plan_carga.jmx, the Apache JMeter plan for the nursing module.

Each virtual user is a nurse with a session of its own: logs in once and then,
in a loop (one "round"), takes one of the test patients of pacientes.csv and
goes through every screen of the module:

  01 Inpatient list
  02 Location and transfers of the admission
  03-05 Vital signs, APACHE II and SOFA dashboards (page, latest values of every
        admission and the evolution of the patient)
  06 Encounter: the forms recorded in the admission (each form's report)
  07-16 The ten nursing forms: open a blank one, save it, open the last one for
        editing and print it

with a pause between screens (reading the screen or filling the form). Forms are
saved only for the test patients, with the note PRUEBA DE CARGA JMETER.

Run: python3 generar_plan.py  (writes plan_carga.jmx next to this file)
"""

from pathlib import Path
from xml.sax.saxutils import escape

MARCA = 'PRUEBA DE CARGA JMETER'
HORA = '${__time(HH:mm)}'
RUTA_FORMS = '/interface/modules/custom_modules/oe-module-nursing/public/forms'

# Form directory, name shown in the report, fields saved (besides pid, encounter,
# id, csrf token and from). Values are valid for each form's save.php.
FORMULARIOS = [
    ('balance_hidrico', 'Balance hídrico', {
        'turno': 'TARDE', 'hora_registro': HORA,
        'ing_via_oral': '${__Random(100,400)}', 'ing_sueros': '${__Random(200,800)}',
        'eg_diuresis': '${__Random(150,600)}', 'observaciones': MARCA}),
    ('alimentacion', 'Alimentación', {
        'tipo_alimentacion': 'ENTERAL', 'via_enteral': 'SNG', 'modalidad': 'CONTINUA',
        'tolerancia': 'BUENA', 'formula': 'Fórmula estándar', 'hora_registro': HORA,
        'observaciones': MARCA}),
    ('registro_vm', 'Registro de ventilación', {
        'hora_registro': HORA, 'frecuencia_respiratoria': '${__Random(12,22)}',
        'vol_tidal': '${__Random(380,520)}', 'obs_otros': MARCA}),
    ('curaciones', 'Curaciones', {
        'hora_operacion': HORA, 'obs_via_venosa': MARCA}),
    ('aplicaciones', 'Aplicaciones', {
        'hora_registro': HORA, 'obs_medicamentos': MARCA}),
    ('cuidados', 'Paquete de cuidados', {
        'hora_cuidado': HORA, 'obs_higiene_manos': MARCA}),
    ('plan_cuidados', 'Plan de cuidados', {
        'turno': 'TARDE', 'hora_registro': HORA,
        'diagnostico_enfermeria': 'Riesgo de infección', 'intervenciones': 'Higiene de manos y control de catéteres',
        'evolucion': 'Paciente estable durante el turno', 'observaciones': MARCA}),
    ('evaluaciones', 'Evaluaciones', {
        'hora_evaluacion': HORA, 'glasgow_ojos': 'ESPONTANEAMENTE', 'glasgow_verbal': 'ORIENTADO Y CONVERSA',
        'glasgow_motora': 'OBEDECE ORDENES', 'obs_conciencia': MARCA}),
    ('escala_sofa', 'Escala SOFA', {
        'hora_registro': HORA, 'pao2': '${__Random(70,100)}', 'fio2': '0.40', 'plaquetas': '${__Random(90,250)}',
        'bilirrubina': '1.1', 'pam': '${__Random(62,85)}', 'glasgow': '14', 'creatinina': '1.2',
        'diuresis_24h': '${__Random(600,1800)}', 'observaciones': MARCA}),
    ('escala_apache', 'Escala APACHE II', {
        'hora_registro': HORA, 'temperatura': '37.6', 'pam': '${__Random(62,85)}',
        'frecuencia_cardiaca': '${__Random(80,120)}', 'frecuencia_respiratoria': '${__Random(14,26)}',
        'fio2': '0.40', 'pao2': '${__Random(70,100)}', 'paco2': '38', 'ph': '7.36', 'sodio': '138',
        'potasio': '4.0', 'creatinina': '1.2', 'hematocrito': '34', 'leucocitos': '12', 'glasgow': '14',
        'edad': '60', 'tipo_ingreso': 'NO_QUIRURGICO', 'observaciones': MARCA}),
]

TABLEROS = [
    ('03 Tablero de signos vitales', 'Vitales', 'tablero_vitales', 'últimas tomas', '"takes"', 'Devuelve las tomas'),
    ('04 Tablero APACHE II', 'APACHE II', 'tablero_apache', 'últimas evaluaciones', '"scores"', 'Devuelve las evaluaciones'),
    ('05 Tablero SOFA', 'SOFA', 'tablero_sofa', 'últimas evaluaciones', '"scores"', 'Devuelve las evaluaciones'),
]


def x(s):
    return escape(s, {'"': '&quot;'})


def args(params):
    if not params:
        return '<collectionProp name="Arguments.arguments"/>'
    out = ['<collectionProp name="Arguments.arguments">']
    for k, v in params.items():
        out.append(
            f'<elementProp name="{x(k)}" elementType="HTTPArgument">'
            f'<boolProp name="HTTPArgument.always_encode">true</boolProp>'
            f'<stringProp name="Argument.name">{x(k)}</stringProp>'
            f'<stringProp name="Argument.value">{x(v)}</stringProp>'
            f'<stringProp name="Argument.metadata">=</stringProp>'
            f'<boolProp name="HTTPArgument.use_equals">true</boolProp></elementProp>')
    out.append('</collectionProp>')
    return ''.join(out)


def http(nombre, ruta, children='', metodo='GET', params=None):
    return (
        f'<HTTPSamplerProxy guiclass="HttpTestSampleGui" testclass="HTTPSamplerProxy" testname="{x(nombre)}">'
        f'<stringProp name="HTTPSampler.path">{x(ruta)}</stringProp>'
        f'<stringProp name="HTTPSampler.method">{metodo}</stringProp>'
        f'<boolProp name="HTTPSampler.follow_redirects">true</boolProp>'
        f'<boolProp name="HTTPSampler.use_keepalive">true</boolProp>'
        f'<elementProp name="HTTPsampler.Arguments" elementType="Arguments" guiclass="HTTPArgumentsPanel" testclass="Arguments">'
        f'{args(params)}</elementProp></HTTPSamplerProxy><hashTree>{children}</hashTree>')


def contiene(nombre, texto, mensaje, negar=False):
    # 16 = substring, 20 = not substring
    return (
        f'<ResponseAssertion guiclass="AssertionGui" testclass="ResponseAssertion" testname="{x(nombre)}">'
        f'<collectionProp name="Asserion.test_strings"><stringProp name="1">{x(texto)}</stringProp></collectionProp>'
        f'<stringProp name="Assertion.custom_message">{x(mensaje)}</stringProp>'
        f'<stringProp name="Assertion.test_field">Assertion.response_data</stringProp>'
        f'<boolProp name="Assertion.assume_success">false</boolProp>'
        f'<intProp name="Assertion.test_type">{20 if negar else 16}</intProp></ResponseAssertion><hashTree/>')


def extraer(nombre, variable, regex, defecto):
    return (
        f'<RegexExtractor guiclass="RegexExtractorGui" testclass="RegexExtractor" testname="{x(nombre)}">'
        f'<stringProp name="RegexExtractor.useHeaders">false</stringProp>'
        f'<stringProp name="RegexExtractor.refname">{x(variable)}</stringProp>'
        f'<stringProp name="RegexExtractor.regex">{x(regex)}</stringProp>'
        f'<stringProp name="RegexExtractor.template">$1$</stringProp>'
        f'<stringProp name="RegexExtractor.default">{x(defecto)}</stringProp>'
        f'<boolProp name="RegexExtractor.default_empty_value">false</boolProp>'
        f'<stringProp name="RegexExtractor.match_number">1</stringProp></RegexExtractor><hashTree/>')


def pausa(nombre, minimo, maximo):
    return (
        f'<TestAction guiclass="TestActionGui" testclass="TestAction" testname="{x(nombre)}">'
        f'<intProp name="ActionProcessor.action">1</intProp><intProp name="ActionProcessor.target">0</intProp>'
        f'<stringProp name="ActionProcessor.duration">${{__Random({minimo},{maximo})}}</stringProp></TestAction><hashTree/>')


LECTURA = pausa('Lectura de la pantalla', '${__P(pausa_min,2000)}', '${__P(pausa_max,5000)}')
CARGA = pausa('Completar el formulario', '${__P(carga_min,5000)}', '${__P(carga_max,15000)}')


def transaccion(nombre, contenido):
    return (
        f'<TransactionController guiclass="TransactionControllerGui" testclass="TransactionController" testname="{x(nombre)}">'
        f'<boolProp name="TransactionController.includeTimers">false</boolProp>'
        f'<boolProp name="TransactionController.parent">false</boolProp></TransactionController>'
        f'<hashTree>{contenido}</hashTree>')


def si(nombre, condicion, contenido):
    return (
        f'<IfController guiclass="IfControllerPanel" testclass="IfController" testname="{x(nombre)}">'
        f'<stringProp name="IfController.condition">{x(condicion)}</stringProp>'
        f'<boolProp name="IfController.evaluateAll">false</boolProp>'
        f'<boolProp name="IfController.useExpression">true</boolProp></IfController>'
        f'<hashTree>{contenido}</hashTree>')


def csv(nombre, archivo, variables):
    return (
        f'<CSVDataSet guiclass="TestBeanGUI" testclass="CSVDataSet" testname="{x(nombre)}">'
        f'<stringProp name="filename">{archivo}</stringProp><stringProp name="fileEncoding">UTF-8</stringProp>'
        f'<stringProp name="variableNames">{variables}</stringProp><boolProp name="ignoreFirstLine">false</boolProp>'
        f'<stringProp name="delimiter">,</stringProp><boolProp name="quotedData">false</boolProp>'
        f'<boolProp name="recycle">true</boolProp><boolProp name="stopThread">false</boolProp>'
        f'<stringProp name="shareMode">shareMode.all</stringProp></CSVDataSet><hashTree/>')


def visor(gui, nombre):
    campos = ''.join(f'<{c}>true</{c}>' for c in (
        'time', 'latency', 'timestamp', 'success', 'label', 'code', 'message', 'threadName', 'dataType',
        'assertions', 'subresults', 'fieldNames', 'saveAssertionResultsFailureMessage', 'bytes', 'sentBytes',
        'url', 'threadCounts', 'idleTime', 'connectTime'))
    return (
        f'<ResultCollector guiclass="{gui}" testclass="ResultCollector" testname="{x(nombre)}">'
        f'<boolProp name="ResultCollector.error_logging">false</boolProp><objProp><name>saveConfig</name>'
        f'<value class="SampleSaveConfiguration">{campos}<encoding>false</encoding><responseData>false</responseData>'
        f'<samplerData>false</samplerData><xml>false</xml><responseHeaders>false</responseHeaders>'
        f'<requestHeaders>false</requestHeaders><responseDataOnError>false</responseDataOnError>'
        f'<assertionsResultsToSave>0</assertionsResultsToSave></value></objProp>'
        f'<stringProp name="filename"></stringProp></ResultCollector><hashTree/>')


def groovy(nombre, codigo, tipo='JSR223PostProcessor'):
    return (
        f'<{tipo} guiclass="TestBeanGUI" testclass="{tipo}" testname="{x(nombre)}">'
        f'<stringProp name="scriptLanguage">groovy</stringProp><stringProp name="parameters"></stringProp>'
        f'<stringProp name="filename"></stringProp><stringProp name="cacheKey">true</stringProp>'
        f'<stringProp name="script">{x(codigo)}</stringProp></{tipo}><hashTree/>')


# After the login: SESION = 1 only when OpenEMR did not show the login form again.
MARCAR_SESION = groovy('Marcar si hay sesión', (
    "def ok = prev.getResponseCode() == '200' && !prev.getResponseDataAsString().contains('id=\"login_form\"')\n"
    "vars.put('SESION', ok ? '1' : '0')"))
# On every response: if OpenEMR shows the login form, the session is gone (log in again).
SESION_PERDIDA = groovy('Detectar sesión perdida', (
    "if (prev.getResponseDataAsString().contains('id=\"login_form\"')) { vars.put('SESION', '0') }"))


def inicio_sesion():
    login = http('Ingresar usuario y contraseña', '/interface/main/main_screen.php?auth=login&site=default',
                 MARCAR_SESION
                 + contiene('Entró a OpenEMR', 'id="login_form"', 'El inicio de sesión volvió a la pantalla de login', negar=True),
                 'POST', {'new_login_session_management': '1', 'languageChoice': '4',
                          'authUser': '${USUARIO}', 'clearPass': '${CLAVE}'})
    # Logs in when there is no session (first round, failed login or lost session); after
    # a failed login it waits before trying again instead of going through the screens.
    return si('Sin sesión: iniciar sesión', '${__jexl3("${SESION}" != "1")}', (
        transaccion('00 Inicio de sesión',
                    http('Pantalla de inicio de sesión', '/interface/login/login.php?site=default') + login)
        + si('Si no pudo entrar', '${__jexl3("${SESION}" != "1")}',
             pausa('Esperar antes de reintentar', '10000', '20000'))))


def ronda():
    sesion = contiene('Sesión abierta', 'id="login_form"', 'La sesión se cerró (volvió al login)', negar=True)
    pasos = [transaccion('01 Lista de internados', http(
        'Lista: página', '${RUTA}/lista_internados.php',
        contiene('Muestra la tabla de internados', 'id="inp_table"', 'No se recibió la lista de internados')) + LECTURA)]
    pasos.append(transaccion('02 Ubicación y traslados', http(
        'Ubicación y traslados: página', '${RUTA}/editar_internado.php?id=${FE}', sesion) + LECTURA))
    for trans, corto, base, ultimas, clave, aserto in TABLEROS:
        pasos.append(transaccion(trans, (
            http(f'{corto}: página', f'${{RUTA}}/{base}.php', sesion)
            + http(f'{corto}: {ultimas}', f'${{RUTA}}/{base}_data.php?action=latest',
                   contiene('Devuelve las internaciones', '"admissions"', 'La respuesta no trae las internaciones'))
            + http(f'{corto}: evolución del paciente', f'${{RUTA}}/{base}_data.php?action=series&encounter=${{ENC}}&hours=0',
                   contiene(aserto, clave, 'La respuesta no trae la evolución'))
            + LECTURA)))
    ids = ''.join(extraer(f'Último registro de {nombre}', f'ID_{d}',
                          rf'openEncounterForm\(&quot;{d}&quot;, &quot;[^&]*&quot;, (?:&quot;)?(\d+)', '0')
                  for d, nombre, _ in FORMULARIOS)
    pasos.append(transaccion('06 Encuentro: formularios cargados', (
        http('Encuentro: abrir la internación',
             '/interface/patient_file/encounter/encounter_top.php?set_encounter=${ENC}&pid=${PID}', sesion)
        + http('Encuentro: formularios cargados', '/interface/patient_file/encounter/forms.php', sesion + ids)
        + LECTURA)))
    for n, (d, nombre, campos) in enumerate(FORMULARIOS, start=7):
        base = f'{RUTA_FORMS}/{d}'
        nuevo = http(f'{nombre}: abrir nuevo', f'{base}/new.php?mode=new&id=0&pid=${{PID}}&encounter=${{ENC}}&from=list',
                     sesion + extraer('Token del formulario', 'CSRF', r'name="csrf_token_form" value="([^"]+)"', 'SIN_TOKEN'))
        guardar = http(f'{nombre}: guardar', f'{base}/save.php',
                       contiene('Guardado (vuelve a la lista)', 'lista_internados.php', 'El formulario no se guardó'),
                       'POST', {'csrf_token_form': '${CSRF}', 'pid': '${PID}', 'encounter': '${ENC}', 'id': '0',
                                'from': 'list', **campos})
        editar = si(f'Si el paciente ya tiene {nombre}', f'${{__jexl3("${{ID_{d}}}" != "0")}}', (
            http(f'{nombre}: abrir para editar', f'{base}/view.php?id=${{ID_{d}}}&pid=${{PID}}&encounter=${{ENC}}&from=list', sesion)
            + LECTURA
            + http(f'{nombre}: imprimir', f'{base}/print.php?id=${{ID_{d}}}&pid=${{PID}}&encounter=${{ENC}}', sesion)))
        pasos.append(transaccion(f'{n:02d} {nombre}', nuevo + CARGA + guardar + LECTURA + editar))
    return ''.join(pasos)


def plan():
    servidor = (
        '<ConfigTestElement guiclass="HttpDefaultsGui" testclass="ConfigTestElement" testname="Servidor">'
        '<stringProp name="HTTPSampler.protocol">https</stringProp>'
        '<stringProp name="HTTPSampler.domain">${__P(host,openemr-ips.duckdns.org)}</stringProp>'
        '<stringProp name="HTTPSampler.port">443</stringProp>'
        '<stringProp name="HTTPSampler.contentEncoding">UTF-8</stringProp>'
        '<stringProp name="HTTPSampler.connect_timeout">10000</stringProp>'
        '<stringProp name="HTTPSampler.response_timeout">30000</stringProp>'
        '<stringProp name="HTTPSampler.implementation">HttpClient4</stringProp>'
        '<elementProp name="HTTPsampler.Arguments" elementType="Arguments" guiclass="HTTPArgumentsPanel" testclass="Arguments">'
        '<collectionProp name="Arguments.arguments"/></elementProp></ConfigTestElement><hashTree/>')
    cabeceras = (
        '<HeaderManager guiclass="HeaderPanel" testclass="HeaderManager" testname="Cabeceras">'
        '<collectionProp name="HeaderManager.headers">'
        '<elementProp name="" elementType="Header"><stringProp name="Header.name">User-Agent</stringProp>'
        '<stringProp name="Header.value">Mozilla/5.0 (JMeter; prueba de carga de la tesis)</stringProp></elementProp>'
        '<elementProp name="" elementType="Header"><stringProp name="Header.name">Accept-Language</stringProp>'
        '<stringProp name="Header.value">es-PY,es;q=0.9</stringProp></elementProp>'
        '</collectionProp></HeaderManager><hashTree/>')
    hilos = (
        '<ThreadGroup guiclass="ThreadGroupGui" testclass="ThreadGroup" testname="Personal de enfermería">'
        '<stringProp name="ThreadGroup.num_threads">${__P(usuarios,10)}</stringProp>'
        '<stringProp name="ThreadGroup.ramp_time">${__P(rampa,60)}</stringProp>'
        '<boolProp name="ThreadGroup.scheduler">true</boolProp>'
        '<stringProp name="ThreadGroup.duration">${__P(duracion,300)}</stringProp>'
        '<stringProp name="ThreadGroup.delay"></stringProp>'
        '<boolProp name="ThreadGroup.same_user_on_next_iteration">true</boolProp>'
        '<stringProp name="ThreadGroup.on_sample_error">continue</stringProp>'
        '<elementProp name="ThreadGroup.main_controller" elementType="LoopController" guiclass="LoopControlPanel" testclass="LoopController">'
        '<stringProp name="LoopController.loops">-1</stringProp>'
        '<boolProp name="LoopController.continue_forever">false</boolProp></elementProp></ThreadGroup>'
        '<hashTree><CookieManager guiclass="CookiePanel" testclass="CookieManager" testname="Sesión del usuario">'
        '<collectionProp name="CookieManager.cookies"/><boolProp name="CookieManager.clearEachIteration">false</boolProp>'
        '<boolProp name="CookieManager.controlledByThreadGroup">false</boolProp></CookieManager><hashTree/>'
        + SESION_PERDIDA + inicio_sesion()
        + si('Con sesión: recorrer las pantallas', '${__jexl3("${SESION}" == "1")}', ronda()) + '</hashTree>')
    visores = (
        '<!-- Live views for the JMeter window. In command-line mode (ejecutar.sh) they write\n'
        '     nothing: results go to resultados.jtl and the HTML report. -->'
        + visor('SummaryReport', 'Informe resumido') + visor('StatVisualizer', 'Informe agregado (percentiles)')
        + visor('RespTimeGraphVisualizer', 'Gráfico de tiempos de respuesta')
        + visor('ViewResultsFullVisualizer', 'Árbol de resultados'))
    variables = (
        '<elementProp name="TestPlan.user_defined_variables" elementType="Arguments" guiclass="ArgumentsPanel" testclass="Arguments">'
        '<collectionProp name="Arguments.arguments"><elementProp name="RUTA" elementType="Argument">'
        '<stringProp name="Argument.name">RUTA</stringProp>'
        '<stringProp name="Argument.value">/interface/modules/custom_modules/oe-module-nursing/public/dashboard</stringProp>'
        '<stringProp name="Argument.metadata">=</stringProp></elementProp></collectionProp></elementProp>')
    cabecera = (
        '<?xml version="1.0" encoding="UTF-8"?>\n'
        '<!-- Generated by generar_plan.py: edit that script, not this file.\n'
        '  Properties (-J): host, usuarios (10), rampa (60 s), duracion (300 s), pausa_min/pausa_max\n'
        '  (reading a screen, 2000-5000 ms), carga_min/carga_max (filling a form, 5000-15000 ms).\n'
        '  credenciales.csv (not in git): "user,password" lines. pacientes.csv: pid,encounter,admission id\n'
        '  of the test patients (forms are saved only for them). -->\n')
    return (cabecera + '<jmeterTestPlan version="1.2" properties="5.0" jmeter="5.6.3"><hashTree>'
            '<TestPlan guiclass="TestPlanGui" testclass="TestPlan" testname="Módulo de enfermería - carga y estrés">'
            '<boolProp name="TestPlan.functional_mode">false</boolProp>'
            '<boolProp name="TestPlan.serialize_threadgroups">false</boolProp>' + variables + '</TestPlan><hashTree>'
            + servidor + cabeceras + csv('Credenciales', 'credenciales.csv', 'USUARIO,CLAVE')
            + csv('Pacientes de prueba', 'pacientes.csv', 'PID,ENC,FE') + hilos + visores
            + '</hashTree></hashTree></jmeterTestPlan>\n')


if __name__ == '__main__':
    Path(__file__).with_name('plan_carga.jmx').write_text(plan(), encoding='utf-8')
    print('plan_carga.jmx generado')
