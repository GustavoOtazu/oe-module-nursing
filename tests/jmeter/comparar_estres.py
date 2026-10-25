#!/usr/bin/env python3
"""Compares two stress runs from JMeter's console output (the "summary +" lines,
one every 30 s): errors and requests served without error per minute against
the active virtual users, while users are being added. Writes an HTML page
with two SVG charts and the data table.

  python3 comparar_estres.py salida1.txt "Nombre 1" salida2.txt "Nombre 2" salida.html
"""

import html
import re
import sys

COLORES = ['#2a78d6', '#eb6834']  # validated categorical slots 1 and 2


def cargar(archivo):
    """(active users, % errors, requests without error per minute) of each 30 s
    stretch while users are being added (up to the first stretch at the maximum)."""
    puntos, maximo = [], 0
    for linea in open(archivo, encoding='utf-8'):
        m = re.search(r'summary \+ +(\d+) in.*Err: +(\d+) \(([\d.]+)%\) Active: (\d+)', linea)
        if not m:
            continue
        n, e, p, a = int(m[1]), int(m[2]), float(m[3]), int(m[4])
        if a < maximo or (puntos and a == maximo):
            break  # users are no longer being added
        maximo = a
        puntos.append((a, p, (n - e) * 2))
    return puntos


def grafico(series, idx, titulo, eje_y, ymax, paso, unidad):
    w, h, izq, der, arr, aba = 760, 330, 60, 180, 30, 50
    aw, ah = w - izq - der, h - arr - aba
    xmax = max(r[0] for _, _, d in series for r in d)
    X = lambda a: izq + a / xmax * aw
    Y = lambda v: arr + ah - min(v, ymax) / ymax * ah
    g = [f'<svg viewBox="0 0 {w} {h}" role="img" aria-label="{html.escape(titulo)}">']
    for v in range(0, ymax + 1, paso):
        g.append(f'<line x1="{izq}" x2="{izq + aw}" y1="{Y(v):.1f}" y2="{Y(v):.1f}" class="grid"/>'
                 f'<text x="{izq - 8}" y="{Y(v) + 4:.1f}" class="tick" text-anchor="end">{v}{unidad}</text>')
    for a in range(0, xmax + 1, 10):
        g.append(f'<text x="{X(a):.1f}" y="{arr + ah + 18}" class="tick" text-anchor="middle">{a}</text>')
    g.append(f'<line x1="{izq}" x2="{izq + aw}" y1="{arr + ah}" y2="{arr + ah}" class="axis"/>')
    g.append(f'<text x="{izq + aw / 2}" y="{h - 8}" class="lab" text-anchor="middle">Usuarios virtuales activos</text>')
    g.append(f'<text transform="translate(16,{arr + ah / 2}) rotate(-90)" class="lab" text-anchor="middle">{eje_y}</text>')
    # end labels: the second one moves away when both lines end close together
    finales = [Y(d[-1][idx]) + 4 for _, _, d in series]
    if abs(finales[0] - finales[1]) < 14:
        arriba = 0 if finales[0] <= finales[1] else 1
        finales[arriba] = min(finales) - 7
        finales[1 - arriba] = finales[arriba] + 14
    for i, (nombre, color, datos) in enumerate(series):
        puntos = ' '.join(f'{X(r[0]):.1f},{Y(r[idx]):.1f}' for r in datos)
        g.append(f'<polyline points="{puntos}" fill="none" stroke="{color}" stroke-width="2" stroke-linejoin="round"/>')
        for r in datos:
            g.append(f'<circle cx="{X(r[0]):.1f}" cy="{Y(r[idx]):.1f}" r="4" fill="{color}" stroke="var(--surface)" stroke-width="2">'
                     f'<title>{html.escape(nombre)}: {r[0]} usuarios, {r[idx]:.0f}{unidad}</title></circle>')
        u = datos[-1]
        g.append(f'<text x="{X(u[0]) + 8:.1f}" y="{finales[i]:.1f}" class="dlab">{html.escape(nombre)}</text>')
    g.append('</svg>')
    return '\n'.join(g)


def main():
    f1, n1, f2, n2, salida = sys.argv[1:6]
    series = [(n1, COLORES[0], cargar(f1)), (n2, COLORES[1], cargar(f2))]
    ok_max = max(r[2] for _, _, d in series for r in d)
    graficos = (
        '<h2>Porcentaje de pedidos con error</h2>'
        + grafico(series, 1, 'Porcentaje de pedidos con error', 'Pedidos con error (%)', 100, 20, ' %')
        + '<h2>Pedidos atendidos sin error por minuto</h2>'
        + grafico(series, 2, 'Pedidos atendidos sin error por minuto', 'Pedidos sin error por minuto',
                  int((ok_max // 50 + 1) * 50), 50, ''))
    filas = ''
    for a, p, ok in series[0][2]:
        b = min(series[1][2], key=lambda r: abs(r[0] - a))
        filas += f'<tr><td>{a}</td><td>{p:.0f} %</td><td>{ok}</td><td>{b[0]}</td><td>{b[1]:.0f} %</td><td>{b[2]}</td></tr>'
    leyenda = ''.join(f'<span class="key"><i style="background:{c}"></i>{html.escape(n)}</span>' for n, c, _ in series)
    pagina = f'''<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Comparación de estrés</title>
<style>
:root{{--surface:#fcfcfb;--ink:#0b0b0b;--ink2:#52514e;--grid:#e4e3df}}
@media (prefers-color-scheme:dark){{:root{{--surface:#1a1a19;--ink:#fff;--ink2:#c3c2b7;--grid:#3a3a37}}}}
body{{background:var(--surface);color:var(--ink);font:15px/1.45 system-ui,sans-serif;max-width:820px;margin:24px auto;padding:0 16px}}
h1{{font-size:21px;margin:0 0 4px}} h2{{font-size:16px;margin:28px 0 6px}} p{{color:var(--ink2);margin:4px 0}}
svg{{width:100%;height:auto}} .grid{{stroke:var(--grid)}} .axis{{stroke:var(--ink2)}}
.tick{{fill:var(--ink2);font-size:12px}} .lab{{fill:var(--ink2);font-size:13px}} .dlab{{fill:var(--ink);font-size:12px}}
.key{{display:inline-flex;align-items:center;gap:6px;margin-right:18px;font-size:13px}}
.key i{{width:14px;height:3px;border-radius:2px;display:inline-block}}
table{{border-collapse:collapse;font-size:13px;margin-top:8px;width:100%}}
th,td{{padding:4px 8px;border-bottom:1px solid var(--grid);text-align:right}} th{{color:var(--ink2);font-weight:600}}
</style></head><body>
<h1>Prueba de estrés: {html.escape(n1)} frente a {html.escape(n2)}</h1>
<p>Apache JMeter, hasta 100 usuarios virtuales (uno nuevo cada 6 s). Servidor: Oracle Cloud
VM.Standard.E2.1.Micro (1/8 de núcleo garantizado, 1 GB de RAM). Cada punto es un tramo de 30 s
mientras se suman usuarios.</p>
<div>{leyenda}</div>{graficos}
<h2>Datos</h2>
<table><tr><th>Usuarios</th><th>Errores</th><th>Sin error/min</th><th>Usuarios</th><th>Errores</th><th>Sin error/min</th></tr>
<tr><th colspan="3">{html.escape(n1)}</th><th colspan="3">{html.escape(n2)}</th></tr>{filas}</table>
</body></html>'''
    open(salida, 'w', encoding='utf-8').write(pagina)
    print(salida)


if __name__ == '__main__':
    main()
