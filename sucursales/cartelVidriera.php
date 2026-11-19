<?php
/**
 * Carteles de precios: cartel de vidriera (hasta 7 artículos) y etiquetas
 * pequeñas con código de barras. Usa el layout de XL Gestión (vive en
 * sistemas/, se incluye por la raíz del sitio).
 *
 * Archivos de la pantalla:
 *   css/carteles.css                  estilos (pantalla + impresión)
 *   js/tabs.js                        pestañas
 *   js/cartelVidriera.js              cartel de vidriera
 *   js/etiquetaPequena.js             etiquetas pequeñas
 *   Controller/obtener_precios.php    precios por código (POST JSON)
 *
 * Los css/styles.css, print-rules.css, etiquetaPequena.css y
 * cartelVidriera.css de esta carpeta eran de la versión anterior; esta
 * pantalla ya no los usa.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/sistemas/xlgestion/bootstrap.php';

Permisos::requiere('operaciones.ventas.carteles_vidriera');

function buscarUshuaiaRecursivo($elemento) {
    if (is_array($elemento) || is_object($elemento)) {
        foreach ($elemento as $key => $valor) {
            if (buscarUshuaiaRecursivo($key) || buscarUshuaiaRecursivo($valor)) {
                return true;
            }
        }
    } else if (is_string($elemento)) {
        $normalizado = strtolower(trim($elemento));
        if ($normalizado === 'xlushuaia' || $normalizado === 'ushuaia') {
            return true;
        }
    }
    return false;
}

// Ushuaia no muestra el precio sin impuestos nacionales.
$esUshuaia = buscarUshuaiaRecursivo($_SESSION) ||
             buscarUshuaiaRecursivo($_GET) ||
             buscarUshuaiaRecursivo($_POST);

$XLG_TITULO    = 'Carteles de precios';
$XLG_COL_CLASE = 'xlg-col--ancha';
include XLG_ROOT . '/xlgestion/views/layout_apertura.php';

$cvVer = '1';
?>

<link rel="stylesheet" href="css/carteles.css?v=<?= $cvVer ?>">
<script>
    const USR_USHUAIA = <?= $esUshuaia ? 'true' : 'false' ?>;
</script>

<div class="cv">

    <header class="cv-head">
        <p class="xlg-eyebrow">Ventas</p>
        <h2 class="xlg-page-title">Carteles de precios</h2>
        <p class="cv-bajada">Armá el cartel de vidriera o las etiquetas pequeñas con los precios vigentes.</p>
    </header>

    <!-- Pestañas: tabs.js usa .tab-btn / .tab-content y data-tab -->
    <div class="cv-tabs" role="tablist" aria-label="Tipo de cartel">
        <button type="button" class="tab-btn active" data-tab="cartel-vidriera" role="tab">
            <i class="fas fa-store-alt" aria-hidden="true"></i> Cartel vidriera
        </button>
        <button type="button" class="tab-btn" data-tab="etiqueta-pequena" role="tab">
            <i class="fas fa-tags" aria-hidden="true"></i> Etiquetas pequeñas
        </button>
    </div>

    <!-- Cartel vidriera ------------------------------------------------------>
    <div class="tab-content active" id="cartel-vidriera" role="tabpanel">
        <div class="cv-layout">

            <section class="xlg-card cv-form-card input-form" aria-labelledby="cvCartelTitulo">
                <div class="xlg-card-head">
                    <span class="xlg-card-ico" aria-hidden="true"><i class="fas fa-tag"></i></span>
                    <div>
                        <p class="xlg-eyebrow">Cartel vidriera</p>
                        <h2 class="xlg-card-title" id="cvCartelTitulo">Artículos del cartel</h2>
                    </div>
                </div>

                <p class="cv-ayuda">Cargá hasta <strong>7 códigos</strong>. Podés corregir el nombre de cada artículo directamente en el cartel.</p>

                <form id="priceForm" class="cv-form" autocomplete="off">
                    <ol class="cv-codigos">
                        <?php for ($i = 1; $i <= 7; $i++): ?>
                            <li class="cv-codigo">
                                <label class="cv-codigo-nro" for="codigo<?= $i ?>"><?= $i ?></label>
                                <input type="text" id="codigo<?= $i ?>" name="codigo<?= $i ?>"
                                       class="cv-input" placeholder="Código artículo <?= $i ?>"
                                       spellcheck="false" autocapitalize="characters"
                                       aria-label="Código de artículo <?= $i ?>">
                            </li>
                        <?php endfor; ?>
                    </ol>

                    <div class="cv-form-pie">
                        <button type="reset" id="limpiarBtn" class="cv-btn cv-btn--peligro">
                            <i class="fas fa-eraser" aria-hidden="true"></i> Limpiar
                        </button>
                        <button type="button" id="generarBtn" class="cv-btn cv-btn--primario">
                            <i class="fas fa-magic" aria-hidden="true"></i> Generar cartel
                        </button>
                    </div>
                </form>

                <div id="messageContainer" class="message-container" aria-live="polite"></div>
            </section>

            <section class="xlg-card cv-preview" aria-labelledby="cvPreviewTitulo">
                <div class="cv-preview-head">
                    <div>
                        <p class="xlg-eyebrow">Vista previa</p>
                        <h2 class="xlg-card-title" id="cvPreviewTitulo">Cartel 13,8 × 19 cm</h2>
                    </div>
                    <button type="button" id="imprimirBtn" class="cv-btn cv-btn--primario" disabled>
                        <i class="fas fa-print" aria-hidden="true"></i> Imprimir
                    </button>
                </div>

                <div class="cv-vacio" id="cvCartelVacio">
                    <i class="fas fa-store-alt" aria-hidden="true"></i>
                    <p>Cargá los códigos y tocá <strong>Generar cartel</strong>.</p>
                </div>

                <div id="priceTagContainer" class="cv-cartel-wrap"></div>
            </section>

        </div>
    </div>

    <!-- Etiquetas pequeñas --------------------------------------------------->
    <div class="tab-content" id="etiqueta-pequena" role="tabpanel">

        <section class="xlg-card cv-etq-form-card input-form" aria-labelledby="cvEtqTitulo">
            <div class="xlg-card-head">
                <span class="xlg-card-ico" aria-hidden="true"><i class="fas fa-tags"></i></span>
                <div>
                    <p class="xlg-eyebrow">Etiquetas pequeñas</p>
                    <h2 class="xlg-card-title" id="cvEtqTitulo">Agregar artículo</h2>
                </div>
            </div>

            <form id="smallTagForm" class="cv-etq-form" autocomplete="off">
                <div class="cv-input-wrap">
                    <span class="cv-input-ico" aria-hidden="true"><i class="fas fa-barcode"></i></span>
                    <input type="text" id="smallTagCode" name="smallTagCode" class="cv-input cv-input--ico"
                           placeholder="Ingresá el código y presioná Enter" spellcheck="false"
                           autocapitalize="characters" aria-label="Código de artículo">
                </div>
                <button type="submit" class="cv-btn cv-btn--primario">
                    <i class="fas fa-plus" aria-hidden="true"></i> Agregar
                </button>
            </form>
        </section>

        <section class="xlg-card cv-hoja" aria-labelledby="cvHojaTitulo">
            <div class="cv-preview-head">
                <div>
                    <p class="xlg-eyebrow">Hoja de impresión · 4 × 5,2 cm c/u</p>
                    <h2 class="xlg-card-title" id="cvHojaTitulo">
                        Etiquetas <span class="xlg-chip cv-contador xlg-num" id="smallTagsCount">0</span>
                    </h2>
                </div>
                <div class="cv-acciones">
                    <button type="button" id="clearSmallTagsBtn" class="cv-btn cv-btn--peligro">
                        <i class="fas fa-eraser" aria-hidden="true"></i> Limpiar
                    </button>
                    <button type="button" id="printSmallTagsBtn" class="cv-btn cv-btn--primario" disabled>
                        <i class="fas fa-print" aria-hidden="true"></i> Imprimir
                    </button>
                </div>
            </div>

            <div class="cv-vacio" id="smallTagsVacio">
                <i class="fas fa-tags" aria-hidden="true"></i>
                <p>Todavía no agregaste etiquetas.</p>
            </div>

            <div id="smallTagsContainer" class="small-tags-grid"></div>
        </section>
    </div>

    <div class="loading-overlay" id="loadingOverlay" role="status" aria-live="polite">
        <div class="cv-loading-card">
            <div class="spinner" aria-hidden="true"></div>
            <p>Cargando precios…</p>
        </div>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
<script src="js/cartelVidriera.js?v=<?= $cvVer ?>"></script>
<script src="js/etiquetaPequena.js?v=<?= $cvVer ?>"></script>
<script src="js/tabs.js?v=<?= $cvVer ?>"></script>

<?php include XLG_ROOT . '/xlgestion/views/layout_cierre.php'; ?>
