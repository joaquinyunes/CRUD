/* =========================================================
   Rhythm · utilidades de UI sin build (se sirven tal cual)
   ---------------------------------------------------------
   1. Ordenamiento de tablas del lado del cliente.
      Se aplica a toda <table class="r-table"> que NO tenga
      ordenamiento del servidor (los <th> con <a class="r-th-sort">).
      Para excluir una tabla: <table class="r-table" data-no-sort>
   ========================================================= */
(function () {
    'use strict';

    var MESES = {
        ene: 0, feb: 1, mar: 2, abr: 3, may: 4, jun: 5,
        jul: 6, ago: 7, sep: 8, oct: 9, nov: 10, dic: 11,
    };

    // "$ 12.500,50" -> 12500.5 · "15/03/2025" -> timestamp · "—" -> null
    function valorDe(celda) {
        var texto = (celda.getAttribute('data-orden') || celda.textContent || '').trim();

        if (!texto || texto === '—' || texto === '-') return { tipo: 'vacio', v: null };

        var fechaIso = texto.match(/(\d{4})-(\d{2})-(\d{2})/);
        if (fechaIso) {
            return { tipo: 'num', v: new Date(+fechaIso[1], +fechaIso[2] - 1, +fechaIso[3]).getTime() };
        }

        var fecha = texto.match(/(\d{1,2})\/(\d{1,2})\/(\d{2,4})/);
        if (fecha) {
            var anio = +fecha[3];
            if (anio < 100) anio += 2000;
            var hora = texto.match(/(\d{1,2}):(\d{2})/);
            return {
                tipo: 'num',
                v: new Date(anio, +fecha[2] - 1, +fecha[1], hora ? +hora[1] : 0, hora ? +hora[2] : 0).getTime(),
            };
        }

        var fechaTexto = texto.match(/(\d{1,2})\s+(ene|feb|mar|abr|may|jun|jul|ago|sep|oct|nov|dic)[a-z.]*\s+(\d{4})/i);
        if (fechaTexto) {
            return { tipo: 'num', v: new Date(+fechaTexto[3], MESES[fechaTexto[2].toLowerCase()], +fechaTexto[1]).getTime() };
        }

        // Numero: tolera $ % espacios, miles con punto y decimales con coma.
        var limpio = texto.replace(/[^\d,.\-]/g, '');
        if (limpio && /\d/.test(limpio)) {
            var normalizado = limpio;
            if (limpio.indexOf(',') > -1) {
                normalizado = limpio.replace(/\./g, '').replace(',', '.');
            } else if ((limpio.match(/\./g) || []).length > 1) {
                normalizado = limpio.replace(/\./g, '');
            }
            var n = parseFloat(normalizado);
            // Solo lo tratamos como numero si el texto es mayormente numerico.
            if (!isNaN(n) && limpio.length >= texto.replace(/[\s$%]/g, '').length - 2) {
                return { tipo: 'num', v: n };
            }
        }

        return { tipo: 'txt', v: texto.toLowerCase() };
    }

    function comparar(a, b) {
        if (a.tipo === 'vacio' && b.tipo === 'vacio') return 0;
        if (a.tipo === 'vacio') return 1;   // los vacios siempre al final
        if (b.tipo === 'vacio') return -1;
        if (a.tipo === 'num' && b.tipo === 'num') return a.v - b.v;
        return String(a.v).localeCompare(String(b.v), 'es', { numeric: true, sensitivity: 'base' });
    }

    function ordenarTabla(tabla, indice, dir) {
        var tbody = tabla.tBodies[0];
        if (!tbody) return;

        var filas = Array.prototype.slice.call(tbody.rows).filter(function (f) {
            return f.cells.length > indice && !f.hasAttribute('data-fila-fija');
        });
        if (filas.length < 2) return;

        var decoradas = filas.map(function (fila, i) {
            return { fila: fila, valor: valorDe(fila.cells[indice]), i: i };
        });

        decoradas.sort(function (x, y) {
            var r = comparar(x.valor, y.valor);
            if (r === 0) return x.i - y.i;          // estable
            return dir === 'asc' ? r : -r;
        });

        var fragmento = document.createDocumentFragment();
        decoradas.forEach(function (d) { fragmento.appendChild(d.fila); });
        tbody.appendChild(fragmento);
    }

    function activar(tabla) {
        if (tabla.hasAttribute('data-no-sort')) return;
        if (tabla.querySelector('th a.r-th-sort')) return;       // ya ordena el servidor
        var thead = tabla.tHead;
        if (!thead || !thead.rows.length) return;
        var tbody = tabla.tBodies[0];
        if (!tbody || tbody.rows.length < 2) return;

        var encabezados = thead.rows[thead.rows.length - 1].cells;

        Array.prototype.forEach.call(encabezados, function (th, indice) {
            var etiqueta = th.textContent.trim();
            if (!etiqueta) return;
            if (/^(acciones|opciones)$/i.test(etiqueta)) return;
            if (th.hasAttribute('data-no-sort')) return;

            var boton = document.createElement('a');
            boton.className = 'r-th-sort';
            boton.href = '#';
            boton.title = 'Ordenar por ' + etiqueta;
            boton.innerHTML =
                '<span>' + th.innerHTML + '</span>' +
                '<svg class="r-th-arrow" width="10" height="12" viewBox="0 0 10 12" aria-hidden="true">' +
                '<path class="r-th-arrow-up" d="M5 1.5 L8.2 5 L1.8 5 Z"/>' +
                '<path class="r-th-arrow-down" d="M5 10.5 L1.8 7 L8.2 7 Z"/></svg>';

            th.innerHTML = '';
            th.appendChild(boton);

            boton.addEventListener('click', function (e) {
                e.preventDefault();
                var dir = boton.getAttribute('data-dir') === 'asc' ? 'desc' : 'asc';

                Array.prototype.forEach.call(encabezados, function (otro) {
                    var a = otro.querySelector('a.r-th-sort');
                    if (a && a !== boton) { a.removeAttribute('data-dir'); a.classList.remove('is-active'); }
                });

                boton.setAttribute('data-dir', dir);
                boton.classList.add('is-active');
                ordenarTabla(tabla, indice, dir);
            });
        });
    }

    function init() {
        document.querySelectorAll('table.r-table').forEach(activar);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
