/**
 * Charts shared by the nursing dashboards (vital signs, APACHE II).
 *
 * Uses the Chart.js bundled with OpenEMR (Header::setupHeader(['chart'])): time
 * axis, dashed reference lines at the usual alarm values, the line broken where
 * too much time passed between two records and a message instead of an empty
 * chart when the period has no data.
 *
 *   var charts = TableroGraficos.create({ noData: '...', gapMs: 8 * 3600 * 1000, minSpanMs: 2 * 3600 * 1000 });
 *   charts.draw('canvas-id', datasets, { y: {...}, y1: {...} }, [{ value, label, colour, axis }]);
 *   charts.series(rows, 'key', 'Label', '#colour', 'y1');
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
(function () {
    'use strict';

    var GRID = 'rgba(128,128,128,0.2)';

    function pageBackground() {
        var nodes = [document.body, document.documentElement];
        for (var i = 0; i < nodes.length; i++) {
            var c = getComputedStyle(nodes[i]).backgroundColor;
            if (c && c !== 'transparent' && c !== 'rgba(0, 0, 0, 0)') { return c; }
        }
        return '#fff';
    }

    // Dashed horizontal lines at reference values: [{ value, label, colour, axis }].
    // The label sits on the side of its axis, on a box of the page background so
    // it stays readable when a series line passes under it.
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

    function create(settings) {
        var charts = {};

        // scales: y (and y1, for a second axis on the right) on top of the common
        // style; a scale with stacked: true also stacks the x axis (bar charts).
        function draw(id, datasets, scales, lines) {
            if (charts[id]) { charts[id].destroy(); delete charts[id]; }
            var canvas = document.getElementById(id);
            var box = canvas.parentNode;
            var old = box.querySelector('.vs-nodata');
            if (old) { old.remove(); }
            var points = datasets.reduce(function (n, d) { return n + d.data.length; }, 0);
            canvas.hidden = points === 0;
            if (points === 0) {
                var msg = document.createElement('div');
                msg.className = 'vs-nodata';
                msg.textContent = settings.noData;
                box.appendChild(msg);
                return;
            }
            var color = getComputedStyle(document.body).color;
            var allScales = {
                x: axis(color, { type: 'time', time: { tooltipFormat: 'dd/MM HH:mm', displayFormats: { millisecond: 'HH:mm', second: 'HH:mm', minute: 'HH:mm', hour: 'dd/MM HH:mm', day: 'dd/MM' } } })
            };
            // Range of the time axis, set here instead of leaving it to Chart.js.
            var xs = [];
            datasets.forEach(function (d) { d.data.forEach(function (p) { xs.push(p.x); }); });
            var first = Math.min.apply(null, xs);
            var last = Math.max.apply(null, xs);
            var minSpan = settings.minSpanMs || 2 * 3600 * 1000;
            var hour = 3600 * 1000;
            if (last - first >= minSpan) {
                // some room before the first and after the last record, so they do not
                // sit on the edge (under the reference line labels, or half a bar cut),
                // rounded so the ticks fall on round times
                var pad = (last - first) * 0.06;
                var round = last - first < 6 * hour ? hour / 6 : hour;
                allScales.x.min = Math.floor((first - pad) / round) * round;
                allScales.x.max = Math.ceil((last + pad) / round) * round;
            } else {
                // With a single record (or records very close together) Chart.js zooms
                // the time axis down to milliseconds: show at least minSpanMs around them.
                var middle = (first + last) / 2;
                // whole hours, so the ticks fall on round times
                allScales.x.min = Math.floor((middle - minSpan / 2) / hour) * hour;
                allScales.x.max = Math.ceil((middle + minSpan / 2) / hour) * hour;
                // and one tick every hour or every few hours, not every 42 minutes
                allScales.x.time.unit = 'hour';
                allScales.x.ticks.stepSize = Math.max(1, Math.ceil((allScales.x.max - allScales.x.min) / hour / 12));
            }
            Object.keys(scales).forEach(function (k) {
                var s = scales[k];
                if (s.title) { s.title = { display: true, text: s.title, color: color }; }
                if (s.ticks) { s.ticks = Object.assign({ color: color }, s.ticks); }
                if (k === 'y1') { s.position = 'right'; s.grid = { drawOnChartArea: false }; }
                // stacked bars: the x axis stacks too
                if (s.stacked) { allScales.x.stacked = true; }
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
                    spanGaps: settings.gapMs,
                    // a point on a fixed limit (Glasgow 15, SpO2 100 %) is drawn whole,
                    // not cut by the edge of the chart
                    clip: 6,
                    // the tooltip shows every value recorded at the same time
                    interaction: { mode: 'x', intersect: false },
                    plugins: { legend: { labels: { color: color } }, vsRefLines: { lines: lines || [] } },
                    scales: allScales
                }
            });
        }

        // One dataset from the rows that have a value for key.
        function series(rows, key, label, colour, yAxis) {
            return {
                yAxisID: yAxis || 'y',
                label: label,
                borderColor: colour,
                backgroundColor: colour,
                pointRadius: 3,
                data: rows.filter(function (t) { return t[key] !== null; }).map(function (t) { return { x: new Date(t.date).getTime(), y: t[key] }; })
            };
        }

        return { draw: draw, series: series };
    }

    window.TableroGraficos = { create: create };
})();
