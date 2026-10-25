#!/bin/sh
# Runs the JMeter plan in command-line mode and builds the HTML report.
#   ./ejecutar.sh rendimiento   normal load: 10 users for 5 minutes
#   ./ejecutar.sh estres        stress: up to 100 users, one more every 6 s, for 12 minutes
#   ./ejecutar.sh prueba        smoke test: 1 user for 1 minute (checks login and assertions)
# Extra JMeter properties can follow, e.g. ./ejecutar.sh estres -Jusuarios=150
# JMETER: path to the jmeter launcher (default: jmeter in PATH).
set -e
cd "$(dirname "$0")"
ESCENARIO=${1:-prueba}
shift || true
case "$ESCENARIO" in
    rendimiento) PROPS="-Jusuarios=10 -Jrampa=60 -Jduracion=300" ;;
    estres)      PROPS="-Jusuarios=100 -Jrampa=600 -Jduracion=720" ;;
    prueba)      PROPS="-Jusuarios=1 -Jrampa=1 -Jduracion=60" ;;
    *) echo "Escenario desconocido: $ESCENARIO (rendimiento, estres o prueba)"; exit 1 ;;
esac
if [ ! -f credenciales.csv ]; then
    echo "Falta credenciales.csv (una línea usuario,contraseña; ver credenciales.csv.ejemplo)"; exit 1
fi
SALIDA="resultados/$ESCENARIO-$(date +%Y%m%d-%H%M)"
mkdir -p "$SALIDA"
"${JMETER:-jmeter}" -n -t plan_carga.jmx $PROPS "$@" \
    -l "$SALIDA/resultados.jtl" -j "$SALIDA/jmeter.log" \
    -Jjmeter.save.saveservice.output_format=csv \
    -Jjmeter.save.saveservice.subresults=false \
    -Jjmeter.reportgenerator.report_title="Módulo de enfermería - $ESCENARIO" \
    -Jjmeter.reportgenerator.overall_granularity=10000 \
    -e -o "$SALIDA/informe"
echo "Informe: $SALIDA/informe/index.html"
