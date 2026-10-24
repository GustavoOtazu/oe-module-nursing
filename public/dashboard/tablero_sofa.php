<?php

/**
 * SOFA Dashboard
 *
 * One card per current admission with its latest SOFA score and its change from
 * the previous one (Sepsis-3: an increase of 2 or more points), filterable by
 * room, and the evolution of the chosen patient's score in charts: the total,
 * the six organ systems, the PaO2/FiO2 ratio and creatinine and bilirubin. Data
 * comes from tablero_sofa_data.php (the module's SOFA form, only the scores
 * recorded in each admission). Charts come from tablero_graficos.js, the same
 * ones as the vital signs and APACHE II dashboards.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once(dirname(__DIR__, 5) . "/globals.php");

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Core\Header;
use OpenEMR\Core\OEGlobalsBag;

if (!AclMain::aclCheckCore('encounters', 'notes')) {
    die(xlt('Access denied'));
}

$web_root = OEGlobalsBag::getInstance()->getString('webroot');
$module_url = $web_root . '/interface/modules/custom_modules/oe-module-nursing/public';
?>
<!DOCTYPE html>
<html>
<head>
    <?php Header::setupHeader(['fontawesome', 'chart']); ?>
    <script src="<?php echo attr($module_url . '/dashboard/tablero_graficos.js?v=' . filemtime(__DIR__ . '/tablero_graficos.js')); ?>"></script>
    <title><?php echo xlt('SOFA Dashboard'); ?></title>
    <style>
        /* No background/colour here: inherit the active theme (light or dark). */
        .vs-card { border: 1px solid rgba(128, 128, 128, 0.35); border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; height: calc(100% - 16px); }
        .vs-card.selected { border-color: #0d6efd; box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.35); }
        .vs-card .vs-bed { font-size: 12px; opacity: 0.8; }
        .vs-card .vs-name { font-weight: 600; margin: 2px 0 6px; }
        .vs-card .vs-when { font-size: 12px; opacity: 0.85; }
        .vs-card .vs-when.stale { color: #e0a800; opacity: 1; }
        .vs-values { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin: 8px 0 10px; }
        .vs-values div { background: rgba(128, 128, 128, 0.10); border-radius: 6px; padding: 4px 6px; text-align: center; }
        .vs-values div.vs-total { grid-column: span 3; }
        .vs-values div.vs-total strong { font-size: 22px; }
        .vs-values div.vs-total .vs-delta { font-size: 13px; margin-left: 6px; }
        .vs-values div.vs-total .vs-delta.up { color: #e74c3c; }
        .vs-values small { display: block; font-size: 11px; opacity: 0.75; }
        .vs-values strong { font-size: 15px; }
        .vs-empty { font-size: 13px; opacity: 0.75; margin: 10px 0; }
        .vs-chart { border: 1px solid rgba(128, 128, 128, 0.35); border-radius: 8px; padding: 10px; margin-bottom: 16px; }
        .vs-chart h6 { margin-bottom: 6px; }
        .vs-canvas { position: relative; height: 240px; }
        .vs-nodata { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; opacity: 0.7; }
    </style>
</head>
<body class="body_top">
<div class="container-fluid mt-3" style="width: 95%;">
    <div class="d-flex flex-wrap align-items-center mb-3">
        <h2 class="mb-0 mr-3"><?php echo xlt('SOFA Dashboard'); ?></h2>
        <a href="#" id="vs-back" class="btn btn-secondary btn-sm mr-2"><i class="fa fa-arrow-left"></i>&nbsp;<?php echo xlt('Inpatient List'); ?></a>
        <label for="vs-room" class="mb-0 mr-1"><?php echo xlt('Ward'); ?>:</label>
        <select id="vs-room" class="form-control form-control-sm w-auto mr-2">
            <option value=""><?php echo xlt('All'); ?></option>
        </select>
        <button id="vs-refresh" class="btn btn-primary btn-sm mr-2"><i class="fa fa-sync"></i>&nbsp;<?php echo xlt('Refresh'); ?></button>
        <small class="text-muted" id="vs-updated"></small>
    </div>

    <div class="row" id="vs-cards"></div>

    <div id="vs-evolution" hidden>
        <hr>
        <div class="d-flex flex-wrap align-items-center mb-2">
            <h4 class="mb-0 mr-3"><?php echo xlt('Evolution'); ?>: <span id="vs-evo-name"></span></h4>
            <div class="btn-group btn-group-sm" role="group" id="vs-range">
                <button class="btn btn-outline-primary active" data-hours="48">48 h</button>
                <button class="btn btn-outline-primary" data-hours="168"><?php echo xlt('7 days'); ?></button>
                <button class="btn btn-outline-primary" data-hours="720"><?php echo xlt('30 days'); ?></button>
                <button class="btn btn-outline-primary" data-hours="0"><?php echo xlt('Whole admission'); ?></button>
            </div>
        </div>
        <p class="text-muted" id="vs-evo-empty" hidden><?php echo xlt('No SOFA scores in this period.'); ?></p>
        <div class="row" id="vs-evo-charts">
            <div class="col-lg-6"><div class="vs-chart"><h6><?php echo xlt('SOFA Score'); ?> (<?php echo xlt('points'); ?>)</h6><div class="vs-canvas"><canvas id="so-c-total"></canvas></div></div></div>
            <div class="col-lg-6"><div class="vs-chart"><h6><?php echo xlt('Organ systems'); ?> (<?php echo xlt('points'); ?>)</h6><div class="vs-canvas"><canvas id="so-c-systems"></canvas></div></div></div>
            <div class="col-lg-6"><div class="vs-chart"><h6>PaO2/FiO2 (mmHg)</h6><div class="vs-canvas"><canvas id="so-c-pafi"></canvas></div></div></div>
            <div class="col-lg-6"><div class="vs-chart"><h6><?php echo xlt('Creatinine'); ?> / <?php echo xlt('Bilirubin'); ?> (mg/dL)</h6><div class="vs-canvas"><canvas id="so-c-labs"></canvas></div></div></div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var DATA_URL = <?php echo js_escape($module_url . '/dashboard/tablero_sofa_data.php'); ?>;
    var LIST_URL = <?php echo js_escape($module_url . '/dashboard/lista_internados.php'); ?>;
    var FORM_URL = <?php echo js_escape($module_url . '/forms/escala_sofa/new.php'); ?>;
    var TXT = {
        room: <?php echo js_escape(xl('Ward')); ?>,
        bed: <?php echo js_escape(xl('Bed')); ?>,
        record: <?php echo js_escape(xl('Reg. No.')); ?>,
        noScores: <?php echo js_escape(xl('No SOFA score recorded in this admission.')); ?>,
        lastScore: <?php echo js_escape(xl('Last score')); ?>,
        hoursAgo: <?php echo js_escape(xl('%s h ago')); ?>,
        daysAgo: <?php echo js_escape(xl('%s days ago')); ?>,
        stale: <?php echo js_escape(xl('More than 24 h without SOFA')); ?>,
        evolution: <?php echo js_escape(xl('Evolution')); ?>,
        recordScore: <?php echo js_escape(xl('Record SOFA')); ?>,
        updated: <?php echo js_escape(xl('Updated')); ?>,
        total: 'SOFA',
        resp: <?php echo js_escape(xl('Respiratory system')); ?>,
        coag: <?php echo js_escape(xl('Coagulation')); ?>,
        liver: <?php echo js_escape(xl('Liver')); ?>,
        cardio: <?php echo js_escape(xl('Cardiovascular')); ?>,
        cns: <?php echo js_escape(xl('CNS')); ?>,
        renal: <?php echo js_escape(xl('Renal')); ?>,
        evaluated: <?php echo js_escape(xl('Systems')); ?>,
        delta: <?php echo js_escape(xl('Change from the previous score')); ?>,
        risePoints: <?php echo js_escape(xl('Increase of 2 or more points (Sepsis-3)')); ?>,
        pafi: 'PaO2/FiO2',
        creatinine: <?php echo js_escape(xl('Creatinine')); ?>,
        bilirubin: <?php echo js_escape(xl('Bilirubin')); ?>,
        noAdmissions: <?php echo js_escape(xl('There are no patients admitted.')); ?>,
        loadError: <?php echo js_escape(xl('The data could not be loaded.')); ?>,
        noData: <?php echo js_escape(xl('No data in this period')); ?>
    };
    // SOFA is scored about once a day.
    var STALE_HOURS = 24;
    // Longer than this between two scores, the line is broken instead of joined.
    var GAP_MS = 48 * 3600 * 1000;
    // Organ systems of SOFA.
    var VARIABLES = 6;
    var admissions = [];
    var selected = null;
    var hours = 48;
    // A single score is shown in a one day window.
    var charts = TableroGraficos.create({ noData: TXT.noData, gapMs: GAP_MS, minSpanMs: 24 * 3600 * 1000 });

    function el(tag, cls, text) {
        var e = document.createElement(tag);
        if (cls) { e.className = cls; }
        if (text !== undefined && text !== null) { e.textContent = text; }
        return e;
    }
    function fmt(v) {
        return (v === null || v === undefined) ? '—' : String(Math.round(v * 10) / 10);
    }
    function ago(h) {
        return h > 48 ? TXT.daysAgo.replace('%s', String(Math.round(h / 24))) : TXT.hoursAgo.replace('%s', fmt(h));
    }
    // OpenEMR keeps the session alive with top.restoreSession(); the page also
    // works on its own (outside OpenEMR's tabs), where it does not exist.
    function restore() {
        if (typeof top.restoreSession === 'function') {
            top.restoreSession();
        }
    }
    function go(url) {
        restore();
        if (top.RTop) {
            top.RTop.location = url;
        } else {
            window.location = url;
        }
    }

    function card(a) {
        var col = el('div', 'col-xl-3 col-lg-4 col-md-6');
        var c = el('div', 'vs-card' + (selected && selected.encounter === a.encounter ? ' selected' : ''));
        c.appendChild(el('div', 'vs-bed', TXT.room + ' ' + (a.room || '—') + ' · ' + TXT.bed + ' ' + (a.bed || '—')
            + (a.record ? ' · ' + TXT.record + ' ' + a.record : '')));
        c.appendChild(el('div', 'vs-name', a.patient));
        if (a.last) {
            var stale = a.last.hours_ago !== null && a.last.hours_ago > STALE_HOURS;
            var when = el('div', 'vs-when' + (stale ? ' stale' : ''),
                TXT.lastScore + ': ' + a.last.when + (a.last.hours_ago !== null ? ' (' + ago(a.last.hours_ago) + ')' : ''));
            if (stale) {
                when.title = TXT.stale;
                when.prepend(el('i', 'fa fa-exclamation-triangle mr-1'));
            }
            c.appendChild(when);
            var grid = el('div', 'vs-values');
            [
                [TXT.total, fmt(a.last.total), 'vs-total'],
                [TXT.resp, fmt(a.last.resp)],
                [TXT.coag, fmt(a.last.coag)],
                [TXT.liver, fmt(a.last.liver)],
                [TXT.cardio, fmt(a.last.cardio)],
                [TXT.cns, fmt(a.last.cns)],
                [TXT.renal, fmt(a.last.renal)]
            ].forEach(function (v) {
                var d = el('div', v[2] || null);
                d.appendChild(el('small', null, v[0]));
                var value = el('strong', null, v[1]);
                d.appendChild(value);
                // the total also shows its change from the previous score
                if (v[2] === 'vs-total' && a.delta !== null) {
                    var up = a.delta >= 2;
                    var delta = el('span', 'vs-delta' + (up ? ' up' : ''), (a.delta > 0 ? '+' : '') + a.delta);
                    delta.title = up ? TXT.risePoints : TXT.delta;
                    if (up) { delta.prepend(el('i', 'fa fa-arrow-up mr-1')); }
                    value.appendChild(delta);
                }
                grid.appendChild(d);
            });
            c.appendChild(grid);
            c.appendChild(el('div', 'vs-when mb-2', TXT.evaluated + ': '
                + (a.last.evaluated !== null ? a.last.evaluated + '/' + VARIABLES : '—')));
        } else {
            c.appendChild(el('div', 'vs-empty', TXT.noScores));
        }
        var actions = el('div', 'd-flex');
        var evo = el('button', 'btn btn-primary btn-sm mr-2', TXT.evolution);
        evo.addEventListener('click', function () { showEvolution(a); });
        if (!a.last) { evo.disabled = true; }
        var rec = el('button', 'btn btn-outline-secondary btn-sm', TXT.recordScore);
        rec.addEventListener('click', function () {
            go(FORM_URL + '?mode=new&id=0&pid=' + encodeURIComponent(a.pid) + '&encounter=' + encodeURIComponent(a.encounter) + '&from=list');
        });
        actions.appendChild(evo);
        actions.appendChild(rec);
        c.appendChild(actions);
        col.appendChild(c);
        return col;
    }

    function render() {
        var room = document.getElementById('vs-room').value;
        var box = document.getElementById('vs-cards');
        box.textContent = '';
        var shown = admissions.filter(function (a) { return room === '' || a.room === room; });
        if (shown.length === 0) {
            box.appendChild(el('p', 'col-12 text-muted', TXT.noAdmissions));
        }
        shown.forEach(function (a) { box.appendChild(card(a)); });
    }

    function fillRooms() {
        var sel = document.getElementById('vs-room');
        var current = sel.value;
        var rooms = [];
        admissions.forEach(function (a) { if (a.room && rooms.indexOf(a.room) < 0) { rooms.push(a.room); } });
        rooms.sort();
        while (sel.options.length > 1) { sel.remove(1); }
        rooms.forEach(function (r) { sel.appendChild(new Option(r, r)); });
        sel.value = rooms.indexOf(current) >= 0 ? current : '';
    }

    function load() {
        restore();
        fetch(DATA_URL + '?action=latest', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                admissions = d.admissions || [];
                fillRooms();
                render();
                document.getElementById('vs-updated').textContent = TXT.updated + ': ' + d.now;
                if (selected) {
                    var again = admissions.filter(function (a) { return a.encounter === selected.encounter; })[0];
                    if (again) { loadSeries(); }
                }
            })
            .catch(function () {
                document.getElementById('vs-updated').textContent = TXT.loadError;
            });
    }

    // The parts of the score as stacked bars.
    function bars(scores, key, label, colour) {
        var s = charts.series(scores, key, label, colour);
        s.type = 'bar';
        s.maxBarThickness = 28;
        return s;
    }

    function loadSeries() {
        restore();
        fetch(DATA_URL + '?action=series&encounter=' + encodeURIComponent(selected.encounter) + '&hours=' + hours, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var scores = d.scores || [];
                document.getElementById('vs-evo-empty').hidden = scores.length > 0;
                document.getElementById('vs-evo-charts').hidden = scores.length === 0;
                if (scores.length === 0) { return; }
                charts.draw('so-c-total', [charts.series(scores, 'total', TXT.total, '#16a085')],
                    { y: { min: 0, max: 24, ticks: { stepSize: 4 } } });
                charts.draw('so-c-systems', [
                    bars(scores, 'resp', TXT.resp, '#2980b9'),
                    bars(scores, 'coag', TXT.coag, '#c0392b'),
                    bars(scores, 'liver', TXT.liver, '#d4ac0d'),
                    bars(scores, 'cardio', TXT.cardio, '#e67e22'),
                    bars(scores, 'cns', TXT.cns, '#9b59b6'),
                    bars(scores, 'renal', TXT.renal, '#27ae60')
                ], { y: { stacked: true, min: 0, max: 24, ticks: { stepSize: 4 } } });
                // limits of the SOFA respiratory points (1 below 400 ... 4 below 100)
                charts.draw('so-c-pafi', [charts.series(scores, 'pafi', TXT.pafi, '#2980b9')],
                    { y: { suggestedMin: 0, suggestedMax: 500 } }, [
                        { value: 400, label: '400', colour: '#2980b9' },
                        { value: 300, label: '300', colour: '#2980b9' },
                        { value: 200, label: '200', colour: '#2980b9' },
                        { value: 100, label: '100', colour: '#2980b9' }
                    ]);
                // 1.2 mg/dL: where both start to score; 2.0: second point of both
                charts.draw('so-c-labs', [
                    charts.series(scores, 'creatinine', TXT.creatinine, '#27ae60'),
                    charts.series(scores, 'bilirubin', TXT.bilirubin, '#d4ac0d')
                ], { y: { suggestedMin: 0, suggestedMax: 4 } }, [
                    { value: 1.2, label: '1.2', colour: '#888' },
                    { value: 2, label: '2.0', colour: '#888' }
                ]);
            });
    }

    // The smallest period that still shows the latest score, so the charts never
    // open empty for a patient whose last score is older than 48 h.
    function rangeFor(a) {
        var h = a.last && a.last.hours_ago !== null ? a.last.hours_ago : 0;
        return h <= 48 ? 48 : (h <= 168 ? 168 : (h <= 720 ? 720 : 0));
    }

    function showEvolution(a) {
        selected = a;
        hours = rangeFor(a);
        document.querySelectorAll('#vs-range button').forEach(function (x) {
            x.classList.toggle('active', parseInt(x.dataset.hours, 10) === hours);
        });
        document.getElementById('vs-evo-name').textContent = a.patient + ' — ' + TXT.room + ' ' + (a.room || '—') + ' · ' + TXT.bed + ' ' + (a.bed || '—');
        document.getElementById('vs-evolution').hidden = false;
        render();
        loadSeries();
        document.getElementById('vs-evolution').scrollIntoView({ behavior: 'smooth' });
    }

    document.getElementById('vs-room').addEventListener('change', render);
    document.getElementById('vs-refresh').addEventListener('click', load);
    document.getElementById('vs-back').addEventListener('click', function (e) { e.preventDefault(); go(LIST_URL); });
    document.querySelectorAll('#vs-range button').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('#vs-range button').forEach(function (x) { x.classList.toggle('active', x === b); });
            hours = parseInt(b.dataset.hours, 10);
            if (selected) { loadSeries(); }
        });
    });

    load();
    setInterval(load, 60000);
})();
</script>
</body>
</html>
