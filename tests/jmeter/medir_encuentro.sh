#!/bin/sh
# Loads the encounter view (forms.php) of one admission N times with a test user and
# prints the response time and size. The database query count needs DB access:
# set CONSULTAS to a command that prints MariaDB's global "Questions" counter.
#   ./medir_encuentro.sh 12605277 1135496 3
set -e
cd "$(dirname "$0")"
ENC=${1:?encuentro}; PID=${2:?pid}; N=${3:-3}
B=https://${HOST:-openemr-ips.duckdns.org}
CJ=$(mktemp); trap 'rm -f "$CJ"' EXIT
U=$(sed -n 1p credenciales.csv | cut -d, -f1)
curl -s -c "$CJ" -b "$CJ" "$B/interface/login/login.php?site=default" -o /dev/null
sed -n 1p credenciales.csv | cut -d, -f2 | tr -d '\n' | curl -s -c "$CJ" -b "$CJ" -L -o /dev/null \
    --data-urlencode "authUser=$U" --data-urlencode "clearPass@-" -d new_login_session_management=1 -d languageChoice=4 \
    "$B/interface/main/main_screen.php?auth=login&site=default"
curl -s -c "$CJ" -b "$CJ" -o /dev/null "$B/interface/patient_file/encounter/encounter_top.php?set_encounter=$ENC&pid=$PID"
for i in $(seq 1 "$N"); do
    A=$([ -n "$CONSULTAS" ] && sh -c "$CONSULTAS" || echo 0)
    R=$(curl -s -c "$CJ" -b "$CJ" -o /dev/null -w "%{http_code} %{time_total} %{size_download}" "$B/interface/patient_file/encounter/forms.php")
    Z=$([ -n "$CONSULTAS" ] && sh -c "$CONSULTAS" || echo 0)
    echo "vez $i: código, segundos, bytes = $R; consultas = $((Z - A - 1))"
done
