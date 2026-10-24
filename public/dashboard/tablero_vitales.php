<?php

/**
 * Vital Signs Dashboard
 *
 * One card per current admission with its latest vital signs take, filterable
 * by room, and the evolution of the chosen patient's vital signs in charts.
 * Data comes from tablero_vitales_data.php (OpenEMR's own vitals form, only the
 * takes recorded in each admission). Charts use the Chart.js bundled with OpenEMR,
 * with dashed reference lines at the usual alarm values and the line broken
 * where more than 8 h passed between two takes.
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
$module_url = $web_root . '/interface/modules/custom_modules/oe-module-nursing/public/dashboard';
?>
<!DOCTYPE html>
<html>
<head>
    <?php Header::setupHeader(['fontawesome', 'chart']); ?>
    <title><?php echo xlt('Vital Signs Dashboard'); ?></title>
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
        <h2 class="mb-0 mr-3"><?php echo xlt('Vital Signs Dashboard'); ?></h2>
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
                <button class="btn btn-outline-primary active" data-hours="24">24 h</button>
                <button class="btn btn-outline-primary" data-hours="48">48 h</button>
                <button class="btn btn-outline-primary" data-hours="168"><?php echo xlt('7 days'); ?></button>
                <button class="btn btn-outline-primary" data-hours="0"><?php echo xlt('Whole admission'); ?></button>
            </div>
        </div>
        <p class="text-muted" id="vs-evo-empty" hidden><?php echo xlt('No vital signs in this period.'); ?></p>
        <div class="row" id="vs-evo-charts">
            <div class="col-lg-6"><div class="vs-chart"><h6><?php echo xlt('Blood pressure'); ?> (mmHg)</h6><div class="vs-canvas"><canvas id="vs-c-bp"></canvas></div></div></div>
            <div class="col-lg-6"><div class="vs-chart"><h6><?php echo xlt('Heart and respiratory rate'); ?></h6><div class="vs-canvas"><canvas id="vs-c-rate"></canvas></div></div></div>
            <div class="col-lg-6"><div class="vs-chart"><h6><?php echo xlt('Temperature'); ?> (°C)</h6><div class="vs-canvas"><canvas id="vs-c-temp"></canvas></div></div></div>
            <div class="col-lg-6"><div class="vs-chart"><h6><?php echo xlt('Oxygen saturation'); ?> (%)</h6><div class="vs-canvas"><canvas id="vs-c-spo2"></canvas></div></div></div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var DATA_URL = <?php echo js_escape($module_url . '/tablero_vitales_data.php'); ?>;
    var LIST_URL = <?php echo js_escape($module_url . '/lista_internados.php'); ?>;
    var VITALS_URL = <?php echo js_escape($web_root . '/interface/patient_file/encounter/encounter_top.php'); ?>;
    var TXT = {
        room: <?php echo js_escape(xl('Ward')); ?>,
        bed: <?php echo js_escape(xl('Bed')); ?>,
        record: <?php echo js_escape(xl('Reg. No.')); ?>,
        noVitals: <?php echo js_escape(xl('No vital signs recorded in this admission.')); ?>,
        lastTake: <?php echo js_escape(xl('Last take')); ?>,
        hoursAgo: <?php echo js_escape(xl('%s h ago')); ?>,
        daysAgo: <?php echo js_escape(xl('%s days ago')); ?>,
        stale: <?php echo js_escape(xl('More than 6 h without vital signs')); ?>,
        evolution: <?php echo js_escape(xl('Evolution')); ?>,
        record_vitals: <?php echo js_escape(xl('Record vital signs')); ?>,
        vitalSigns: <?php echo js_escape(xl('Vital Signs')); ?>,
        updated: <?php echo js_escape(xl('Updated')); ?>,
        bp: <?php echo js_escape(xl('BP')); ?>,
        map: <?php echo js_escape(xl('MAP')); ?>,
        hr: <?php echo js_escape(xl('HR')); ?>,
        rr: <?php echo js_escape(xl('RR')); ?>,
        temp: <?php echo js_escape(xl('Temp.')); ?>,
        spo2: 'SpO2',
        systolic: <?php echo js_escape(xl('Systolic')); ?>,
        diastolic: <?php echo js_escape(xl('Diastolic')); ?>,
        noAdmissions: <?php echo js_escape(xl('There are no patients admitted.')); ?>,
        loadError: <?php echo js_escape(xl('The data could not be loaded.')); ?>,
        noData: <?php echo js_escape(xl('No data in this period')); ?>,
        beatsMin: <?php echo js_escape(xl('beats/min')); ?>,
        breathsMin: <?php echo js_escape(xl('breaths/min')); ?>
    };
    var STALE_HOURS = 6;
    // Longer than this between two takes, the line is broken instead of joined.
    var GAP_MS = 8 * 3600 * 1000;
    var GRID = 'rgba(128,128,128,0.2)';
    var admissions = [];
    var selected = null;
    var hours = 24;
    var charts = {};

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
                TXT.lastTake + ': ' + a.last.when + (a.last.hours_ago !== null ? ' (' + ago(a.last.hours_ago) + ')' : ''));
            if (stale) {
                when.title = TXT.stale;
                when.prepend(el('i', 'fa fa-exclamation-triangle mr-1'));
            }
            c.appendChild(when);
            var grid = el('div', 'vs-values');
            [
                [TXT.bp, a.last.bps !== null && a.last.bpd !== null ? fmt(a.last.bps) + '/' + fmt(a.last.bpd) : '—'],
                [TXT.map, fmt(a.last.map)],
                [TXT.hr, fmt(a.last.pulse)],
                [TXT.rr, fmt(a.last.respiration)],
                [TXT.temp + (a.last.temp_method ? ' (' + a.last.temp_method + ')' : ''), a.last.temp_c !== null ? fmt(a.last.temp_c) + ' °C' : '—'],
                [TXT.spo2, a.last.spo2 !== null ? fmt(a.last.spo2) + ' %' : '—']
            ].forEach(function (v) {
                var d = el('div');
                d.appendChild(el('small', null, v[0]));
                d.appendChild(el('strong', null, v[1]));
                grid.appendChild(d);
            });
            c.appendChild(grid);
        } else {
            c.appendChild(el('div', 'vs-empty', TXT.noVitals));
        }
        var actions = el('div', 'd-flex');
        var evo = el('button', 'btn btn-primary btn-sm mr-2', TXT.evolution);
        evo.addEventListener('click', function () { showEvolution(a); });
        if (!a.last) { evo.disabled = true; }
        var rec = el('button', 'btn btn-outline-secondary btn-sm', TXT.record_vitals);
        rec.addEventListener('click', function () {
            go(VITALS_URL + '?set_encounter=' + encodeURIComponent(a.encounter) + '&pid=' + encodeURIComponent(a.pid)
                + '&formname=vitals&formdesc=' + encodeURIComponent(TXT.vitalSigns));
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

    // Dashed horizontal lines at reference values: [{ value, label, colour, axis }].
    // The label sits on the side of its axis, on a box of the page background so
    // it stays readable when a series line passes under it.
    function pageBackground() {
        var nodes = [document.body, document.documentElement];
        for (var i = 0; i < nodes.length; i++) {
            var c = getComputedStyle(nodes[i]).backgroundColor;
            if (c && c !== 'transparent' && c !== 'rgba(0, 0, 0, 0)') { return c; }
        }
        return '#fff';
    }
    var refLines = {
        id: 'vsRefLines',
        afterDatasetsDraw: function (chart, args, opts) {
            var area = chart.chartArea;
            var ctx = chart.ctx;
            (opts.lines || []).forEach(function (l) {
                var scale = chart.scales[l.axis || 'y'];
                if (!scale || l.value < scale.min || l.value > scale.max) { return; }
                var y = scale.getPixelForValue(l.value);
                ctx.save();
                ctx.strokeStyle = l.colour;
                ctx.fillStyle = l.colour;
                ctx.lineWidth = 1;
                ctx.setLineDash([6, 4]);
                ctx.beginPath();
                ctx.moveTo(area.left, y);
                ctx.lineTo(area.right, y);
                ctx.stroke();
                ctx.font = '11px sans-serif';
                ctx.textBaseline = 'bottom';
                // labels of the right axis go on the right, so they do not overlap
                // the left axis ones when both fall at about the same height
                var right = (l.axis || 'y') === 'y1';
                var w = ctx.measureText(l.label).width;
                var x = right ? area.right - 4 - w : area.left + 4;
                ctx.fillStyle = pageBackground();
                ctx.fillRect(x - 2, y - 14, w + 4, 13);
                ctx.fillStyle = l.colour;
                ctx.textAlign = 'left';
                ctx.fillText(l.label, x, y - 2);
                ctx.restore();
            });
        }
    };

    function axis(color, extra) {
        return Object.assign({ ticks: { color: color }, grid: { color: GRID } }, extra || {});
    }

    // scales: y (and y1, for a second axis on the right) on top of the common style.
    function lineChart(id, datasets, scales, lines) {
        if (charts[id]) { charts[id].destroy(); delete charts[id]; }
        var canvas = document.getElementById(id);
        var box = canvas.parentNode;
        var old = box.querySelector('.vs-nodata');
        if (old) { old.remove(); }
        var points = datasets.reduce(function (n, d) { return n + d.data.length; }, 0);
        canvas.hidden = points === 0;
        if (points === 0) {
            box.appendChild(el('div', 'vs-nodata', TXT.noData));
            return;
        }
        var color = getComputedStyle(document.body).color;
        var allScales = {
            x: axis(color, { type: 'time', time: { tooltipFormat: 'dd/MM HH:mm', displayFormats: { minute: 'HH:mm', hour: 'dd/MM HH:mm', day: 'dd/MM' } } })
        };
        Object.keys(scales).forEach(function (k) {
            var s = scales[k];
            if (s.title) { s.title = { display: true, text: s.title, color: color }; }
            if (k === 'y1') { s.position = 'right'; s.grid = { drawOnChartArea: false }; }
            allScales[k] = axis(color, s);
        });
        charts[id] = new Chart(canvas, {
            type: 'line',
            data: { datasets: datasets },
            plugins: [refLines],
            options: {
                animation: false,
                parsing: false,
                maintainAspectRatio: false,
                spanGaps: GAP_MS,
                // the tooltip shows every value taken at the same time
                interaction: { mode: 'x', intersect: false },
                plugins: { legend: { labels: { color: color } }, vsRefLines: { lines: lines || [] } },
                scales: allScales
            }
        });
    }
    function series(takes, key, label, colour, yAxis) {
        return {
            yAxisID: yAxis || 'y',
            label: label,
            borderColor: colour,
            backgroundColor: colour,
            pointRadius: 3,
            data: takes.filter(function (t) { return t[key] !== null; }).map(function (t) { return { x: new Date(t.date).getTime(), y: t[key] }; })
        };
    }

    function loadSeries() {
        restore();
        fetch(DATA_URL + '?action=series&encounter=' + encodeURIComponent(selected.encounter) + '&hours=' + hours, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var takes = d.takes || [];
                document.getElementById('vs-evo-empty').hidden = takes.length > 0;
                document.getElementById('vs-evo-charts').hidden = takes.length === 0;
                if (takes.length === 0) { return; }
                lineChart('vs-c-bp', [
                    series(takes, 'bps', TXT.systolic, '#e74c3c'),
                    series(takes, 'bpd', TXT.diastolic, '#3498db'),
                    series(takes, 'map', TXT.map, '#9b59b6')
                ], { y: { suggestedMin: 40, suggestedMax: 180 } }, [
                    { value: 65, label: TXT.map + ' 65', colour: '#9b59b6' }
                ]);
                // heart and respiratory rate move in very different ranges: one axis each
                lineChart('vs-c-rate', [
                    series(takes, 'pulse', TXT.hr, '#e67e22'),
                    series(takes, 'respiration', TXT.rr, '#16a085', 'y1')
                ], {
                    y: { suggestedMin: 40, suggestedMax: 150, title: TXT.hr + ' (' + TXT.beatsMin + ')' },
                    y1: { suggestedMin: 0, suggestedMax: 50, title: TXT.rr + ' (' + TXT.breathsMin + ')' }
                }, [
                    { value: 100, label: TXT.hr + ' 100', colour: '#e67e22' },
                    { value: 60, label: TXT.hr + ' 60', colour: '#e67e22' },
                    { value: 20, label: TXT.rr + ' 20', colour: '#16a085', axis: 'y1' },
                    { value: 12, label: TXT.rr + ' 12', colour: '#16a085', axis: 'y1' }
                ]);
                lineChart('vs-c-temp', [series(takes, 'temp_c', TXT.temp, '#c0392b')],
                    { y: { suggestedMin: 35, suggestedMax: 40 } }, [
                        { value: 38, label: '38 °C', colour: '#c0392b' },
                        { value: 36, label: '36 °C', colour: '#c0392b' }
                    ]);
                lineChart('vs-c-spo2', [series(takes, 'spo2', TXT.spo2, '#2980b9')],
                    { y: { suggestedMin: 85, max: 100 } }, [
                        { value: 90, label: TXT.spo2 + ' 90 %', colour: '#2980b9' }
                    ]);
            });
    }

    // The smallest period that still shows the latest take, so the charts never
    // open empty for a patient whose last vital signs are older than 24 h.
    function rangeFor(a) {
        var h = a.last && a.last.hours_ago !== null ? a.last.hours_ago : 0;
        return h <= 24 ? 24 : (h <= 48 ? 48 : (h <= 168 ? 168 : 0));
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
