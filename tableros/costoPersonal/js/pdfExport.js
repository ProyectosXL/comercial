/**
 * pdfExport.js – Costo de Personal
 * Exporta pestañas del dashboard a PDF multi-página.
 * Requiere html2canvas >= 1.4.1 y jsPDF >= 2.5.1 (UMD).
 * Misma implementación que costoOcupacion/js/pdfExport.js, adaptada para IDs del módulo CP.
 */

async function exportarPDF(containerId, titulo, filename) {

    const contenedor = document.getElementById(containerId);
    if (!contenedor) {
        Swal.fire({
            icon: 'error', title: 'Error',
            text: `No se encontró el contenedor #${containerId}`,
            confirmButtonColor: '#e74c3c', confirmButtonText: 'Cerrar',
        });
        return;
    }

    Swal.fire({
        title: 'Generando PDF…',
        text:  'Por favor espere mientras se procesa el documento.',
        allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false,
        didOpen: () => Swal.showLoading(),
    });

    try {
        let naturalW, naturalH;
        {
            const clone = contenedor.cloneNode(true);
            clone.style.cssText = [
                'position:absolute','top:0','left:0','visibility:hidden',
                'pointer-events:none','overflow:visible!important',
                'width:max-content!important','max-width:none!important',
                'height:auto!important','max-height:none!important',
            ].join(';');
            clone.querySelectorAll('*').forEach(el => {
                el.style.setProperty('overflow',   'visible', 'important');
                el.style.setProperty('overflow-x', 'visible', 'important');
                el.style.setProperty('overflow-y', 'visible', 'important');
                el.style.setProperty('max-width',  'none',    'important');
                el.style.setProperty('max-height', 'none',    'important');
                el.style.setProperty('height',     'auto',    'important');
                if (getComputedStyle(el).position === 'sticky') {
                    el.style.setProperty('position', 'relative', 'important');
                }
            });
            document.body.appendChild(clone);
            naturalW = clone.scrollWidth;
            naturalH = clone.scrollHeight;
            document.body.removeChild(clone);
        }

        const HTML2CANVAS_SCALE = 1.8;
        const containerRect = contenedor.getBoundingClientRect();
        const rowBottomsPx  = Array.from(contenedor.querySelectorAll('tr'))
            .map(tr => (tr.getBoundingClientRect().bottom - containerRect.top) * HTML2CANVAS_SCALE)
            .filter(y => y > 0);

        await new Promise(r => setTimeout(r, 60));

        const canvas = await html2canvas(contenedor, {
            scale: 1.8, backgroundColor: '#ffffff',
            useCORS: true, logging: false,
            scrollX: 0, scrollY: 0,
            width: naturalW, height: naturalH,
            windowWidth: naturalW + 200, windowHeight: naturalH + 200,
            onclone: (clonedDoc, clonedEl) => {
                function liberar(el) {
                    el.style.setProperty('overflow',   'visible', 'important');
                    el.style.setProperty('overflow-x', 'visible', 'important');
                    el.style.setProperty('overflow-y', 'visible', 'important');
                    el.style.setProperty('height',     'auto',    'important');
                    el.style.setProperty('max-height', 'none',    'important');
                    el.style.setProperty('width',      'auto',    'important');
                    el.style.setProperty('max-width',  'none',    'important');
                    try {
                        if (getComputedStyle(el).position === 'sticky') {
                            el.style.setProperty('position', 'relative', 'important');
                            el.style.removeProperty('top');
                            el.style.removeProperty('left');
                        }
                    } catch (_) {}
                    if (el.scrollTop  !== undefined) el.scrollTop  = 0;
                    if (el.scrollLeft !== undefined) el.scrollLeft = 0;
                }
                let ancestor = clonedEl.parentElement;
                while (ancestor && ancestor !== clonedDoc.body) { liberar(ancestor); ancestor = ancestor.parentElement; }
                liberar(clonedEl);
                clonedEl.style.setProperty('width', 'max-content', 'important');
                clonedEl.querySelectorAll('*').forEach(liberar);
            },
        });

        const imgWidth  = canvas.width;
        const imgHeight = canvas.height;

        const SCREEN_SCALE  = 1.8;
        const SCREEN_DPI    = 96;
        const readableScale = 25.4 / (SCREEN_DPI * SCREEN_SCALE);
        const HEADER_HEIGHT = 18;
        const MARGIN        = 10;

        const contentWidthMm = imgWidth * readableScale;
        const MIN_PAGE_W     = 297;
        const pageW  = Math.max(MIN_PAGE_W, contentWidthMm + MARGIN * 2);
        const pageH  = 210;
        const availW = pageW - MARGIN * 2;
        const availH = pageH - HEADER_HEIGHT - MARGIN;
        const scale  = availW / imgWidth;
        const maxPxPerPage = availH / scale;

        function buildPageStarts(totalPx, maxPx, rowBottoms) {
            const starts = [0];
            let cur = 0;
            while (cur < totalPx) {
                let next = cur + maxPx;
                if (next >= totalPx) break;
                if (rowBottoms.length > 0) {
                    let bestCut = -1;
                    for (let i = rowBottoms.length - 1; i >= 0; i--) {
                        if (rowBottoms[i] > cur && rowBottoms[i] <= next) { bestCut = rowBottoms[i]; break; }
                    }
                    if (bestCut > cur) next = bestCut;
                }
                starts.push(Math.round(next));
                cur = next;
            }
            return starts;
        }

        const pageStarts = buildPageStarts(imgHeight, maxPxPerPage, rowBottomsPx);
        const totalPages  = pageStarts.length;

        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({ orientation: 'landscape', unit: 'mm', format: [pageW, pageH] });

        const ahora    = new Date();
        const fechaStr = ahora.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
        const horaStr  = ahora.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
        const fechaHora = `Generado el ${fechaStr} a las ${horaStr}`;

        for (let page = 0; page < totalPages; page++) {
            if (page > 0) pdf.addPage([pageW, pageH], 'landscape');

            pdf.setFillColor(44, 62, 80);
            pdf.rect(0, 0, pageW, HEADER_HEIGHT, 'F');
            pdf.setFont('helvetica', 'bold'); pdf.setFontSize(11); pdf.setTextColor(255, 255, 255);
            pdf.text(titulo, MARGIN, HEADER_HEIGHT / 2 + 2);
            pdf.setFont('helvetica', 'normal'); pdf.setFontSize(8);
            pdf.text(fechaHora, pageW - MARGIN, HEADER_HEIGHT / 2 + 2, { align: 'right' });
            if (totalPages > 1) {
                pdf.setFontSize(7);
                pdf.text(`Página ${page + 1} de ${totalPages}`, pageW / 2, HEADER_HEIGHT / 2 + 2, { align: 'center' });
            }

            const pxStart  = pageStarts[page];
            const pxEnd    = page + 1 < totalPages ? pageStarts[page + 1] : imgHeight;
            const pxHeight = Math.round(pxEnd - pxStart);

            const sliceCanvas = document.createElement('canvas');
            sliceCanvas.width  = imgWidth;
            sliceCanvas.height = pxHeight;
            const ctx = sliceCanvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, imgWidth, pxHeight);
            ctx.drawImage(canvas, 0, pxStart, imgWidth, pxHeight, 0, 0, imgWidth, pxHeight);

            pdf.addImage(sliceCanvas.toDataURL('image/jpeg', 0.93), 'JPEG', MARGIN, HEADER_HEIGHT, availW, pxHeight * scale);
        }

        pdf.save(`${filename}_${ahora.toISOString().split('T')[0]}.pdf`);

    } catch (err) {
        console.error('Error al generar PDF:', err);
        Swal.fire({
            icon: 'error', title: 'Error al generar PDF',
            text: err.message || 'Ocurrió un error inesperado.',
            confirmButtonColor: '#e74c3c', confirmButtonText: 'Cerrar',
        });
        return;
    }

    Swal.close();
}

// ── Vincular botones de PDF ──────────────────────────────────────────────────
$(document).ready(function () {

    // Tab 2: Reporte por Sucursal
    $(document).on('click', '#cpRpfBtnPDF', function () {
        exportarPDF('cpRpfTableSection', 'Reporte por Sucursal – Costo de Personal', 'cp_reporte_sucursal');
    });

    // Tab 3: Reporte a Fecha
    $(document).on('click', '#cpRafBtnPDF', function () {
        exportarPDF('cpRafTableSection', 'Reporte a Fecha – Costo de Personal', 'cp_reporte_fecha');
    });

    // Tab 4: Productividad
    $(document).on('click', '#cpProdBtnPDF', function () {
        exportarPDF('cpProdContent', 'Productividad – Costo de Personal', 'cp_productividad');
    });

    // Tab 5: Comparar Sucursales
    $(document).on('click', '#cpCmpBtnPDF', function () {
        exportarPDF('cpCmpTableSection', 'Comparación de Sucursales – Costo de Personal', 'cp_comparar_sucursales');
    });
});
