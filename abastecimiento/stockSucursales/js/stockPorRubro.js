/**
 * ==========================================================================
 * Stock por Rubro - XL Gestión
 * Lógica de cliente, carga asíncrona, filtros por rubro/destino y KPIs
 * ==========================================================================
 */

$(document).ready(function () {
    let articulosOriginales = [];
    let articulosFiltrados = [];
    let ordenActual = { columna: 'CANT_STOCK', ascendente: false };

    // Inicializar rubros al cargar la página
    cargarRubros();

    // Eventos
    $('#btnConsultar').on('click', function () {
        consultarStockRubro();
    });

    $('#selectRubro').on('change', function () {
        if ($(this).val()) {
            $('#btnConsultar').prop('disabled', false);
            consultarStockRubro();
        }
    });

    $('#selectDestino').on('change', function () {
        filtrarArticulosEnMemoria();
    });

    $('#inputFiltroRapido').on('input', function () {
        filtrarArticulosEnMemoria();
    });

    $('#btnLimpiarFiltro').on('click', function () {
        $('#inputFiltroRapido').val('').trigger('input');
    });

    $('#btnExportarExcel').on('click', function () {
        exportarExcel();
    });

    // Ordenamiento por columna
    $('#tablaArticulos thead th[data-sort]').on('click', function () {
        const columna = $(this).data('sort');
        if (ordenActual.columna === columna) {
            ordenActual.ascendente = !ordenActual.ascendente;
        } else {
            ordenActual.columna = columna;
            ordenActual.ascendente = columna === 'COD_ARTICU' || columna === 'DESCRIPCIO';
        }
        ordenarYRenderizar();
    });

    /**
     * Carga el listado de rubros desde el controller
     */
    function cargarRubros() {
        $.ajax({
            url: 'Controller/StockRubroController.php?accion=traerRubros',
            type: 'GET',
            dataType: 'json',
            success: function (data) {
                const $select = $('#selectRubro');
                $select.empty();

                if (!Array.isArray(data) || data.length === 0) {
                    $select.append('<option value="" disabled selected>No se encontraron rubros disponibles</option>');
                    return;
                }

                $select.append('<option value="" disabled selected>-- Seleccione un rubro --</option>');
                data.forEach(function (item) {
                    if (item.RUBRO) {
                        const rubroVal = item.RUBRO.trim();
                        $select.append(`<option value="${rubroVal}">${rubroVal}</option>`);
                    }
                });
            },
            error: function () {
                $('#selectRubro').empty().append('<option value="" disabled selected>Error al cargar rubros</option>');
            }
        });
    }

    /**
     * Consulta los artículos con stock del rubro seleccionado
     */
    function consultarStockRubro() {
        const rubro = $('#selectRubro').val();
        if (!rubro) return;

        // Siempre consultar solo lo que posea stock
        const soloConStock = true;

        // Estado visual de carga
        toggleCargando(true);

        $.ajax({
            url: 'Controller/StockRubroController.php?accion=traerPorRubro',
            type: 'POST',
            data: {
                rubro: rubro,
                soloConStock: soloConStock
            },
            dataType: 'json',
            success: function (response) {
                toggleCargando(false);

                if (response && response.error) {
                    alert('Error: ' + response.error);
                    return;
                }

                if (!Array.isArray(response)) {
                    articulosOriginales = [];
                } else {
                    articulosOriginales = response;
                }

                $('#tableTitle').text(`Artículos de ${rubro}`);
                $('#btnExportarExcel').removeClass('d-none').addClass('d-flex');

                popularSelectDestinos(articulosOriginales);
                actualizarKPIs(articulosOriginales);
                filtrarArticulosEnMemoria();
            },
            error: function () {
                toggleCargando(false);
                alert('Ocurrió un error al consultar los datos del rubro.');
            }
        });
    }

    /**
     * Llena dinámicamente el combo de Destinos con los existentes en el rubro
     */
    function popularSelectDestinos(articulos) {
        const $selectDestino = $('#selectDestino');
        const destinoSeleccionado = $selectDestino.val() || 'TODOS';
        
        const destinosSet = new Set();
        articulos.forEach(function (a) {
            const dest = (a.DESTINO || '').trim();
            if (dest) {
                destinosSet.add(dest);
            }
        });

        const destinosArray = Array.from(destinosSet).sort();

        $selectDestino.empty();
        $selectDestino.append('<option value="TODOS">Todos los destinos</option>');
        
        destinosArray.forEach(function (d) {
            $selectDestino.append(`<option value="${escapeHtml(d)}">${escapeHtml(d)}</option>`);
        });

        if (destinosSet.has(destinoSeleccionado)) {
            $selectDestino.val(destinoSeleccionado);
        } else {
            $selectDestino.val('TODOS');
        }

        $selectDestino.prop('disabled', destinosArray.length === 0);
    }

    /**
     * Filtra los artículos en base a búsqueda rápida y destino seleccionado
     */
    function filtrarArticulosEnMemoria() {
        const termino = $('#inputFiltroRapido').val().toLowerCase().trim();
        const destinoFiltro = $('#selectDestino').val() || 'TODOS';

        articulosFiltrados = articulosOriginales.filter(function (art) {
            // Filtro por destino
            if (destinoFiltro !== 'TODOS') {
                const destinoArt = (art.DESTINO || '').trim();
                if (destinoArt !== destinoFiltro) {
                    return false;
                }
            }

            // Filtro por término de búsqueda rápida
            if (termino) {
                const cod = (art.COD_ARTICU || '').toLowerCase();
                const desc = (art.DESCRIPCIO || '').toLowerCase();
                const color = (art.COLOR || '').toLowerCase();
                const destino = (art.DESTINO || '').toLowerCase();

                if (!cod.includes(termino) && !desc.includes(termino) && !color.includes(termino) && !destino.includes(termino)) {
                    return false;
                }
            }

            return true;
        });

        actualizarKPIs(articulosFiltrados);
        ordenarYRenderizar();
    }

    /**
     * Ordena la lista filtrada y renderiza las filas
     */
    function ordenarYRenderizar() {
        const col = ordenActual.columna;
        const asc = ordenActual.ascendente;

        articulosFiltrados.sort(function (a, b) {
            let valA = a[col] ?? '';
            let valB = b[col] ?? '';

            if (col === 'CANT_STOCK' || col === 'PRECIO' || col === 'PRECIO_S_IVA' || col === 'IVA_PORC') {
                valA = parseFloat(valA) || 0;
                valB = parseFloat(valB) || 0;
            } else {
                valA = valA.toString().toLowerCase();
                valB = valB.toString().toLowerCase();
            }

            if (valA < valB) return asc ? -1 : 1;
            if (valA > valB) return asc ? 1 : -1;
            return 0;
        });

        // Actualizar iconos de orden en thead
        $('#tablaArticulos thead th[data-sort]').each(function () {
            const thCol = $(this).data('sort');
            const $icon = $(this).find('.sort-icon');
            $icon.removeClass('fa-sort fa-sort-up fa-sort-down text-primary text-muted');

            if (thCol === col) {
                $icon.addClass(asc ? 'fa-sort-up text-primary' : 'fa-sort-down text-primary');
            } else {
                $icon.addClass('fa-sort text-muted');
            }
        });

        renderizarTabla();
    }

    /**
     * Genera el HTML de la tabla
     */
    function renderizarTabla() {
        const $tbody = $('#tbodyArticulos');
        $tbody.empty();

        if (articulosFiltrados.length === 0) {
            $tbody.html(`
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="fas fa-search me-2 opacity-50"></i> No se encontraron artículos para mostrar
                    </td>
                </tr>
            `);
            $('#badgeTotalFiltrados').text('0 resultados');
            $('#footerInfo').text('Mostrando 0 de ' + articulosOriginales.length + ' artículos');
            return;
        }

        const fragment = document.createDocumentFragment();

        articulosFiltrados.forEach(function (art) {
            const tr = document.createElement('tr');

            const stock = parseInt(art.CANT_STOCK) || 0;
            const stockClass = stock > 0 ? 'badge-stock-positive' : 'badge-stock-zero';
            const precioFinal = parseFloat(art.PRECIO) || 0;
            const precioSinIva = parseFloat(art.PRECIO_S_IVA) || 0;
            const precioAnterior = art.PRECIO_ANTERIOR ? parseFloat(art.PRECIO_ANTERIOR) : null;
            const esSale = art.LIQUIDACION === 'SI';
            const destino = (art.DESTINO || '').trim();
            const ivaEntero = Math.round(parseFloat(art.IVA_PORC) || 21);

            let saleBadgeHtml = esSale ? '<span class="badge badge-sale ms-1">SALE</span>' : '';
            let precioAnteriorHtml = precioAnterior && precioAnterior > precioFinal
                ? `<span class="precio-anterior">$ ${formatearMoneda(precioAnterior)}</span>`
                : '';

            let destinoHtml = destino
                ? `<span class="badge-destino">${escapeHtml(destino)}</span>`
                : '<span class="text-muted small">-</span>';

            tr.innerHTML = `
                <td class="ps-3 fw-bold font-monospace text-primary text-nowrap">
                    ${escapeHtml(art.COD_ARTICU)}
                    ${saleBadgeHtml}
                </td>
                <td class="fw-semibold">${escapeHtml(art.DESCRIPCIO)}</td>
                <td class="text-secondary">${escapeHtml(art.COLOR || '-')}</td>
                <td class="text-center">${destinoHtml}</td>
                <td class="text-center">
                    <span class="badge-stock ${stockClass}">${stock}</span>
                </td>
                <td class="text-end font-monospace text-secondary">$ ${formatearMoneda(precioSinIva)}</td>
                <td class="text-center text-muted fw-semibold small">${ivaEntero}%</td>
                <td class="text-end pe-3 font-monospace fw-bold text-dark">
                    ${precioAnteriorHtml}
                    $ ${formatearMoneda(precioFinal)}
                </td>
            `;

            fragment.appendChild(tr);
        });

        $tbody[0].appendChild(fragment);

        $('#badgeTotalFiltrados').text(`${articulosFiltrados.length} resultados`);
        $('#footerInfo').text(`Mostrando ${articulosFiltrados.length} de ${articulosOriginales.length} artículos`);
    }

    /**
     * Actualiza las tarjetas de métricas (solo artículos con stock)
     */
    function actualizarKPIs(lista) {
        let totalArticulos = lista.length;
        let totalStock = 0;
        const destinosSet = new Set();

        lista.forEach(function (a) {
            const s = parseInt(a.CANT_STOCK) || 0;
            totalStock += s;
            const d = (a.DESTINO || '').trim();
            if (d) {
                destinosSet.add(d);
            }
        });

        $('#kpiTotalArticulos').text(totalArticulos.toLocaleString());
        $('#kpiTotalUnidades').text(totalStock.toLocaleString());
        $('#kpiTotalDestinos').text(destinosSet.size.toLocaleString());

        $('#kpiContainer').removeClass('d-none');
    }

    /**
     * Gestión de spinners y visibilidad de componentes
     */
    function toggleCargando(cargando) {
        if (cargando) {
            $('#btnConsultar').prop('disabled', true);
            $('#btnConsultarIcon').addClass('d-none');
            $('#btnConsultarSpinner').removeClass('d-none');
            $('#btnConsultarText').text('Consultando...');

            $('#emptyState').addClass('d-none');
            $('#tableCard').addClass('d-none');
            $('#kpiContainer').addClass('d-none');
            $('#loadingState').removeClass('d-none');
        } else {
            $('#btnConsultar').prop('disabled', false);
            $('#btnConsultarIcon').removeClass('d-none');
            $('#btnConsultarSpinner').addClass('d-none');
            $('#btnConsultarText').text('Consultar');

            $('#loadingState').addClass('d-none');
            $('#tableCard').removeClass('d-none');
        }
    }

    /**
     * Exportación a Excel con SheetJS
     */
    function exportarExcel() {
        if (!articulosFiltrados || articulosFiltrados.length === 0) {
            alert('No hay datos para exportar.');
            return;
        }

        const rubro = $('#selectRubro').val() || 'Rubro';
        const dataExport = articulosFiltrados.map(function (art) {
            return {
                'Artículo': art.COD_ARTICU,
                'Descripción': art.DESCRIPCIO,
                'Color': art.COLOR,
                'Destino': art.DESTINO,
                'Stock': parseInt(art.CANT_STOCK) || 0,
                'Precio (sin IVA)': parseFloat(art.PRECIO_S_IVA) || 0,
                'IVA (%)': Math.round(parseFloat(art.IVA_PORC) || 21),
                'Precio Final': parseFloat(art.PRECIO) || 0,
                'Liquidación': art.LIQUIDACION
            };
        });

        const ws = XLSX.utils.json_to_sheet(dataExport);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Stock_Rubro");

        const fecha = new Date().toISOString().slice(0, 10);
        XLSX.writeFile(wb, `Stock_${rubro}_${fecha}.xlsx`);
    }

    function formatearMoneda(val) {
        return Number(val).toLocaleString('es-AR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
