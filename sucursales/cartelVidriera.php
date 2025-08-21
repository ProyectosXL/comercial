
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiquetas de Precios</title>
    <link rel="icon" type="image/jpg" href="../assets/css/images/icono.jpg">
    <link rel="stylesheet" href="css/cartelVidriera.css">
    <link rel="stylesheet" href="css/etiquetaPequena.css">
    <link rel="stylesheet" href="css/print-rules.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>
<body>
    <div class="container">
        <!-- Tabs Navigation -->
        <div class="tabs">
            <button class="tab-btn active" data-tab="cartel-vidriera">
                <i class="fas fa-store-alt"></i> Cartel Vidriera
            </button>
            <button class="tab-btn" data-tab="etiqueta-pequena">
                <i class="fas fa-tags"></i> Etiqueta Pequeña
            </button>
        </div>

        <!-- Cartel Vidriera Tab -->
        <div class="tab-content active" id="cartel-vidriera">
            <div class="card input-form">
                <div class="card-header">
                    <h2><i class="fas fa-tag"></i> Generador de Etiqueta de Precios</h2>
                </div>
                <div class="card-body">
                    <form id="priceForm">
                        <div class="form-grid">
                            <?php for ($i = 1; $i <= 7; $i++): ?>
                            <div class="form-group">
                                <label for="codigo<?php echo $i; ?>">Código Artículo <?php echo $i; ?>:</label>
                                <input type="text" id="codigo<?php echo $i; ?>" name="codigo<?php echo $i; ?>" class="form-control">
                            </div>
                            <?php endfor; ?>
                        </div>
                        
                        <div class="button-group">
                            <button type="button" id="generarBtn" class="btn btn-primary"><i class="fas fa-magic"></i> Generar Etiqueta</button>
                            <button type="button" id="imprimirBtn" class="btn btn-success" disabled><i class="fas fa-print"></i> Imprimir</button>
                            <button type="reset" id="limpiarBtn" class="btn btn-secondary"><i class="fas fa-eraser"></i> Limpiar</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div id="messageContainer" class="message-container"></div>
            <div id="priceTagContainer"></div>
        </div>

        <!-- Etiqueta Pequeña Tab -->
        <div class="tab-content" id="etiqueta-pequena">
            <div class="card input-form">
                <div class="card-header">
                    <h2><i class="fas fa-tags"></i> Generador de Etiquetas Pequeñas</h2>
                </div>
                <div class="card-body">
                    <form id="smallTagForm">
                        <div class="form-group">
                            <label for="smallTagCode">Código Artículo:</label>
                            <div class="input-with-buttons">
                                <input type="text" id="smallTagCode" name="smallTagCode" class="form-control" placeholder="Ingrese código y presione Enter">
                                <div class="etiqueta-buttons">
                                    <button type="button" id="printSmallTagsBtn" class="btn btn-success" disabled><i class="fas fa-print"></i> Imprimir</button>
                                    <button type="button" id="clearSmallTagsBtn" class="btn btn-secondary"><i class="fas fa-eraser"></i> Limpiar</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div id="smallTagsContainer" class="small-tags-grid"></div>
        </div>
        
        <div class="loading-overlay" id="loadingOverlay">
            <div class="spinner"></div>
            <p>Cargando datos...</p>
        </div>
    </div>
    
    <script src="js/cartelVidriera.js"></script>
    <script src="js/etiquetaPequena.js"></script>
    <script src="js/tabs.js"></script>
</body>
</html>