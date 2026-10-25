# Pruebas de carga y estrés (Apache JMeter)

Plan de prueba de todas las pantallas del módulo con [Apache JMeter](https://jmeter.apache.org/) 5.6.

Cada usuario virtual representa a una persona de enfermería con su propia sesión: inicia
sesión una vez y después, en ciclo (una "ronda"), toma uno de los pacientes de prueba y
recorre todas las pantallas del módulo:

| Paso | Pantalla | Pedidos |
|---|---|---|
| 01 | Lista de internados | página |
| 02 | Ubicación y traslados | página de la internación |
| 03–05 | Tableros de signos vitales, APACHE II y SOFA | página, últimos valores de todos, evolución del paciente |
| 06 | Encuentro | abrir la internación, formularios cargados (el resumen de cada uno) |
| 07–16 | Los 10 formularios de enfermería | abrir uno nuevo, guardarlo, abrir el último para editar, imprimirlo |

Entre pantallas hay una pausa de lectura (2 a 5 s) y antes de guardar un formulario, una
pausa de carga (5 a 15 s). Cada respuesta se verifica: que la sesión siga abierta, que
lleguen los datos y que el formulario se guarde.

Los formularios se guardan solo en los pacientes de prueba de `pacientes.csv`, con la
observación `PRUEBA DE CARGA JMETER`; `borrar_datos_prueba.sql` los borra al terminar.

## Archivos

| Archivo | Para qué |
|---|---|
| `generar_plan.py` | Genera `plan_carga.jmx` (se edita este script, no el plan) |
| `plan_carga.jmx` | Plan de prueba; se puede abrir en la interfaz de JMeter |
| `ejecutar.sh` | Corre un escenario en modo consola y genera el informe HTML |
| `pacientes.csv` | Pacientes de prueba: `pid,encuentro,id de la internación` |
| `credenciales.csv` | Usuarios de prueba, una línea `usuario,contraseña`. **No se sube a git** |
| `borrar_datos_prueba.sql` | Borra los formularios que guardó la prueba |
| `medir_encuentro.sh` | Mide la pantalla del encuentro (tiempo, tamaño y, con acceso a la base, consultas) con un usuario |

## Escenarios

| Escenario | Usuarios | Arranque | Duración |
|---|---|---|---|
| `prueba` | 1 | inmediato | 1 min |
| `rendimiento` | 10 | 60 s | 5 min |
| `estres` | hasta 100 | uno nuevo cada 6 s | 12 min |

```sh
export JAVA_HOME=/usr/lib/jvm/java-21-openjdk-amd64
JMETER=~/.local/opt/apache-jmeter-5.6.3/bin/jmeter ./ejecutar.sh rendimiento
```

Los resultados quedan en `resultados/<escenario>-<fecha>/` (`resultados.jtl`, `jmeter.log` y
el informe en `informe/index.html`). Cualquier propiedad del plan se puede cambiar al final,
por ejemplo `./ejecutar.sh estres -Jusuarios=150`.

Para ver la prueba en vivo: `jmeter -Jlanguage=es -t plan_carga.jmx` abre la interfaz con
el plan y sus visores (informe resumido, percentiles, gráfico de tiempos y árbol de resultados).
