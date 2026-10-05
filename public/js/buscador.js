/**
 * Autocompletado para los formularios de carga.
 *
 * Reemplaza a los <select> que traían el catálogo entero embebido en el HTML.
 * Con los datos de demostración eso son treinta productos; con el catálogo real
 * de un comercio son miles, y la pantalla tardaba segundos en abrir.
 *
 * Se activa solo sobre cualquier elemento con [data-buscador] y no necesita
 * inicialización, así que también funciona en las filas que se agregan después
 * de cargar la página.
 *
 * Atributos:
 *   data-url        endpoint que responde [{id, etiqueta, detalle?, ...}]
 *   data-minimo     caracteres antes de buscar (por defecto 1)
 *   data-al-elegir  nombre de un evento que se dispara con el item elegido
 */
(function () {
    'use strict';

    const ESPERA_MS = 220;
    const abiertos = new WeakMap();

    /** El input oculto que viaja en el formulario (el id), y el visible (el texto). */
    function partes(raiz) {
        return {
            valor: raiz.querySelector('[data-buscador-valor]'),
            texto: raiz.querySelector('[data-buscador-texto]'),
            lista: raiz.querySelector('[data-buscador-lista]'),
        };
    }

    function cerrar(raiz) {
        const { lista } = partes(raiz);
        if (lista) {
            lista.hidden = true;
            lista.innerHTML = '';
        }
        abiertos.delete(raiz);
    }

    function elegir(raiz, item) {
        const { valor, texto } = partes(raiz);
        valor.value = item.id;
        texto.value = item.etiqueta;
        cerrar(raiz);

        // Dispara change para que los totales y el precio se recalculen igual
        // que cuando había un <select>.
        valor.dispatchEvent(new Event('change', { bubbles: true }));

        const evento = raiz.dataset.alElegir;
        if (evento) {
            raiz.dispatchEvent(new CustomEvent(evento, { bubbles: true, detail: item }));
        }
    }

    function pintar(raiz, items) {
        const { lista } = partes(raiz);
        if (!lista) return;

        if (!items.length) {
            lista.innerHTML = '<li class="r-buscador-vacio">Sin resultados</li>';
            lista.hidden = false;
            return;
        }

        lista.innerHTML = items
            .map(function (item, i) {
                const detalle = item.detalle
                    ? '<span class="r-buscador-detalle">' + escapar(item.detalle) + '</span>'
                    : '';
                return (
                    '<li class="r-buscador-opcion" role="option" data-indice="' + i + '">' +
                    escapar(item.etiqueta) + detalle +
                    '</li>'
                );
            })
            .join('');

        lista.hidden = false;
        abiertos.set(raiz, { items: items, foco: -1 });
    }

    function escapar(texto) {
        const div = document.createElement('div');
        div.textContent = texto == null ? '' : String(texto);
        return div.innerHTML;
    }

    async function buscar(raiz) {
        const { texto } = partes(raiz);
        const q = texto.value.trim();
        const minimo = parseInt(raiz.dataset.minimo || '1', 10);

        if (q.length < minimo) {
            cerrar(raiz);
            return;
        }

        try {
            const respuesta = await fetch(raiz.dataset.url + '?q=' + encodeURIComponent(q), {
                headers: { Accept: 'application/json' },
            });

            if (!respuesta.ok) {
                cerrar(raiz);
                return;
            }

            // Puede haber llegado otra tecla mientras tanto.
            if (texto.value.trim() !== q) return;

            pintar(raiz, normalizar(await respuesta.json()));
        } catch (e) {
            cerrar(raiz);
        }
    }

    /**
     * Acepta tanto la forma propia del autocompletado como la del buscador de
     * productos, que ya existía para el lector de código de barras y devuelve
     * el producto completo.
     */
    function normalizar(datos) {
        if (!Array.isArray(datos)) return [];

        return datos.map(function (d) {
            if (d.etiqueta) return d;

            return Object.assign({}, d, {
                etiqueta: d.nombre,
                detalle: [d.codigo, d.stock != null ? 'stock ' + d.stock : null]
                    .filter(Boolean)
                    .join(' · '),
            });
        });
    }

    function mover(raiz, delta) {
        const estado = abiertos.get(raiz);
        if (!estado) return;

        const opciones = raiz.querySelectorAll('.r-buscador-opcion');
        if (!opciones.length) return;

        estado.foco = (estado.foco + delta + opciones.length) % opciones.length;

        opciones.forEach(function (o, i) {
            o.classList.toggle('is-foco', i === estado.foco);
        });
        opciones[estado.foco].scrollIntoView({ block: 'nearest' });
    }

    let temporizador = null;

    document.addEventListener('input', function (e) {
        const texto = e.target.closest('[data-buscador-texto]');
        if (!texto) return;

        const raiz = texto.closest('[data-buscador]');
        const { valor } = partes(raiz);

        // Si venía algo elegido y el usuario reescribe, la elección se cae: no
        // puede quedar un id que no se corresponde con el texto que se ve.
        valor.value = '';

        clearTimeout(temporizador);
        temporizador = setTimeout(function () {
            buscar(raiz);
        }, ESPERA_MS);
    });

    document.addEventListener('keydown', function (e) {
        const texto = e.target.closest('[data-buscador-texto]');
        if (!texto) return;

        const raiz = texto.closest('[data-buscador]');
        const estado = abiertos.get(raiz);

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            mover(raiz, 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            mover(raiz, -1);
        } else if (e.key === 'Enter') {
            if (estado && estado.foco >= 0) {
                e.preventDefault();
                elegir(raiz, estado.items[estado.foco]);
            }
        } else if (e.key === 'Escape') {
            cerrar(raiz);
        }
    });

    document.addEventListener('click', function (e) {
        const opcion = e.target.closest('.r-buscador-opcion');

        if (opcion) {
            const raiz = opcion.closest('[data-buscador]');
            const estado = abiertos.get(raiz);
            if (estado) {
                elegir(raiz, estado.items[parseInt(opcion.dataset.indice, 10)]);
            }
            return;
        }

        // Un click afuera cierra lo que esté abierto.
        document.querySelectorAll('[data-buscador]').forEach(function (raiz) {
            if (!raiz.contains(e.target)) cerrar(raiz);
        });
    });

    // Si se envía el formulario con texto escrito pero nada elegido, el campo
    // oculto va vacío y la validación del servidor lo rechaza. Avisamos antes.
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.querySelector('[data-buscador]')) return;

        const incompleto = Array.from(form.querySelectorAll('[data-buscador]')).find(function (raiz) {
            const { valor, texto } = partes(raiz);
            return texto.value.trim() !== '' && valor.value === '';
        });

        if (incompleto) {
            e.preventDefault();
            const { texto } = partes(incompleto);
            texto.focus();
            texto.setCustomValidity('Elegí una opción de la lista.');
            texto.reportValidity();
            setTimeout(function () {
                texto.setCustomValidity('');
            }, 2000);
        }
    });
})();
