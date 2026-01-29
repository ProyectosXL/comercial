# 🔧 Snippets Reutilizables para Modernización

## 📌 Uso
Estos snippets están listos para copiar y pegar en las próximas páginas que se modernicen.

---

## 1. HTML Head Completo

```html
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Título de la Página - XL Extra</title>
    <link rel="icon" type="image/jpg" href="images/LOGO XL 2018.jpg">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- CSS específico de esta página -->
    <link rel="stylesheet" href="css/[NOMBRE_ARCHIVO].css">
</head>
```

**Reemplazar:** `[NOMBRE_ARCHIVO]` con el nombre del archivo PHP sin extensión

---

## 2. Navbar Estándar

```html
<!-- Navbar -->
<nav class="navbar navbar-expand-md navbar-dark bg-dark">
    <div class="container-fluid">
        <!-- Brand -->
        <img class="navbar-brand" src="images/LOGO XL 2018.jpg" width="40" height="40" alt="XL Extra">

        <!-- Toggler Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="bi bi-house"></i> Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pedidosAsignados.php">
                        <i class="bi bi-person-standing"></i> Resumen por cliente
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="resumenPorPedido.php">
                        <i class="bi bi-receipt"></i> Resumen por pedido
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
```

---

## 3. Contenedor Principal

```html
<!-- Contenedor principal -->
<div class="container-fluid px-4 py-4">
    
    <!-- Aquí va el contenido de la página -->
    
</div>
```

---

## 4. Card de Total (Individual)

```html
<div class="col-md-3 col-sm-6">
    <div class="card total-card">
        <div class="card-body d-flex align-items-center">
            <div class="icon-wrapper primary me-3">
                <i class="bi bi-box-seam"></i>
            </div>
            <div>
                <h6 class="card-title mb-0">Título del Total</h6>
                <h3 class="card-value" id="idDelTotal">0</h3>
            </div>
        </div>
    </div>
</div>
```

**Variantes de color:**
- `icon-wrapper primary` - Azul
- `icon-wrapper success` - Verde
- `icon-wrapper info` - Cian
- `icon-wrapper warning` - Amarillo
- `icon-wrapper danger` - Rojo

---

## 5. Sección Completa de Cards de Totales

```html
<!-- Cards de totales -->
<div class="totals-section">
    <div class="row g-3">
        <div class="col-md-3 col-sm-6">
            <div class="card total-card">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-wrapper primary me-3">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div>
                        <h6 class="card-title mb-0">Total Artículos</h6>
                        <h3 class="card-value" id="totalArticulos">0</h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="card total-card">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-wrapper success me-3">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <h6 class="card-title mb-0">Total Clientes</h6>
                        <h3 class="card-value" id="totalClientes">0</h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="card total-card">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-wrapper info me-3">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div>
                        <h6 class="card-title mb-0">Importe Total</h6>
                        <h3 class="card-value" id="importeTotal">$0</h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="card total-card">
                <div class="card-body">
                    <h6 class="card-title mb-2">
                        <i class="bi bi-search me-2"></i>Búsqueda Rápida
                    </h6>
                    <input type="text" id="busquedaRapida" 
                           placeholder="Buscar en tabla..." 
                           class="form-control">
                </div>
            </div>
        </div>
    </div>
</div>
```

---

## 6. Tabla Moderna con Iconos

```html
<!-- Tabla de datos -->
<div class="table-container">
    <table class="table table-hover table-striped table-pedidos">
        <thead>
            <tr>
                <th><i class="bi bi-hash"></i>ID</th>
                <th><i class="bi bi-person"></i>Nombre</th>
                <th><i class="bi bi-envelope"></i>Email</th>
                <th><i class="bi bi-telephone"></i>Teléfono</th>
                <th><i class="bi bi-gear"></i>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($datos as $dato) { ?>
            <tr>
                <td><?= $dato->id ?></td>
                <td><?= $dato->nombre ?></td>
                <td><?= $dato->email ?></td>
                <td><?= $dato->telefono ?></td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-success btn-action" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-warning btn-action" title="Detalle">
                            <i class="bi bi-info-circle"></i>
                        </button>
                        <button class="btn btn-danger btn-action" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
```

---

## 7. Botones de Acción

```html
<!-- Botón Editar -->
<button class="btn btn-success btn-action btn-edit" title="Editar">
    <i class="bi bi-pencil"></i>
</button>

<!-- Botón Ver/Detalle -->
<button class="btn btn-warning btn-action btn-view" title="Ver detalle">
    <i class="bi bi-info-circle"></i>
</button>

<!-- Botón Eliminar -->
<button class="btn btn-danger btn-action btn-delete" title="Eliminar">
    <i class="bi bi-trash"></i>
</button>

<!-- Botón Descargar -->
<button class="btn btn-primary btn-action btn-download" title="Descargar">
    <i class="bi bi-download"></i>
</button>

<!-- Botón Imprimir -->
<button class="btn btn-secondary btn-action btn-print" title="Imprimir">
    <i class="bi bi-printer"></i>
</button>
```

---

## 8. Sección de Filtros

```html
<!-- Sección de filtros -->
<form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <div class="filter-section">
        <div class="row align-items-end g-3">
            <div class="col-md-4">
                <label for="filtro1" class="form-label">
                    <i class="bi bi-funnel me-2"></i>Filtro 1
                </label>
                <select id="filtro1" class="form-select" name="filtro1">
                    <option value="">Todos...</option>
                    <!-- Opciones -->
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="filtro2" class="form-label">
                    <i class="bi bi-calendar me-2"></i>Filtro 2
                </label>
                <input type="date" id="filtro2" class="form-control" name="filtro2">
            </div>
            
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary btn-filter w-100">
                    <i class="bi bi-search"></i> Buscar
                </button>
            </div>
        </div>
    </div>
</form>
```

---

## 9. Modal Básico Bootstrap 5

```html
<!-- Modal -->
<div class="modal fade" id="miModal" tabindex="-1" aria-labelledby="miModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="miModalLabel">
                    <i class="bi bi-info-circle me-2"></i>Título del Modal
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            
            <div class="modal-body">
                <!-- Contenido del modal -->
                <p>Contenido aquí...</p>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Cerrar
                </button>
                <button type="button" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Aceptar
                </button>
            </div>
        </div>
    </div>
</div>
```

---

## 10. Scripts al Final del Body

```html
</body>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- JavaScript específico de esta página -->
<script src="js/[NOMBRE_ARCHIVO].js"></script>

</html>
```

**Reemplazar:** `[NOMBRE_ARCHIVO]` con el nombre del archivo PHP sin extensión

---

## 11. Estructura Básica CSS (archivo.css)

```css
/* ========================================
   CSS específico para [NOMBRE_ARCHIVO].php
   Gestión de Pedidos - XL Extra
   ======================================== */

/* ========================================
   Variables CSS
   ======================================== */
:root {
    --primary-color: #198754;
    --warning-color: #ffc107;
    --dark-bg: #343a40;
    --light-bg: #f8f9fa;
    --border-color: #dee2e6;
    --hover-bg: #f1f3f5;
    --text-muted: #6c757d;
}

/* ========================================
   Estilos generales de la página
   ======================================== */
body {
    background-color: var(--light-bg);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* ========================================
   Navbar personalizado
   ======================================== */
.navbar-nav .nav-link {
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.navbar-nav .nav-link:hover {
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
}

/* ========================================
   Cards de totales
   ======================================== */
.total-card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
}

.total-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

/* ========================================
   Tabla de datos
   ======================================== */
.table-container {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    overflow-x: auto;
}

.table-pedidos thead th {
    background-color: var(--dark-bg);
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
    padding: 1rem 0.75rem;
    border: none;
    vertical-align: middle;
}

.table-pedidos tbody tr:hover {
    background-color: var(--hover-bg);
}

/* ========================================
   Botones de acción
   ======================================== */
.btn-action {
    padding: 0.4rem 0.7rem;
    border-radius: 6px;
    transition: all 0.3s ease;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 38px;
    min-height: 38px;
}

.btn-action:hover {
    transform: scale(1.05);
}

/* ========================================
   Responsive Design
   ======================================== */
@media (max-width: 768px) {
    .table-container {
        padding: 1rem;
    }
    
    .btn-action {
        min-width: 34px;
        min-height: 34px;
    }
}
```

---

## 12. Estructura Básica JavaScript (archivo.js)

```javascript
/* ========================================
   JavaScript específico para [NOMBRE_ARCHIVO].php
   Gestión de Pedidos - XL Extra
   ======================================== */

/**
 * Inicialización cuando el DOM está listo
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('[NOMBRE_ARCHIVO].js cargado correctamente');
    
    // Inicializar funcionalidades
    initTotales();
    initBusquedaRapida();
    initBotones();
});

/* ========================================
   Cálculo de totales
   ======================================== */

/**
 * Inicializa el cálculo de totales
 */
function initTotales() {
    // Implementar cálculos aquí
}

/* ========================================
   Búsqueda rápida
   ======================================== */

/**
 * Inicializa la búsqueda rápida
 */
function initBusquedaRapida() {
    const inputBusqueda = document.getElementById('busquedaRapida');
    
    if (inputBusqueda) {
        inputBusqueda.addEventListener('input', filtrarTabla);
    }
}

/**
 * Filtra las filas de la tabla
 */
function filtrarTabla() {
    const input = document.getElementById('busquedaRapida');
    const filtro = input.value.toUpperCase();
    const tabla = document.querySelector('.table-pedidos tbody');
    
    if (!tabla) return;
    
    const filas = tabla.getElementsByTagName('tr');
    
    for (let i = 0; i < filas.length; i++) {
        const fila = filas[i];
        const textoFila = fila.textContent || fila.innerText;
        
        if (textoFila.toUpperCase().indexOf(filtro) > -1) {
            fila.style.display = '';
        } else {
            fila.style.display = 'none';
        }
    }
}

/* ========================================
   Botones y eventos
   ======================================== */

/**
 * Inicializa eventos de botones
 */
function initBotones() {
    // Event listeners para botones
}

/* ========================================
   Utilidades
   ======================================== */

/**
 * Formatea un número como moneda argentina
 */
function formatearMoneda(valor) {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 0
    }).format(valor);
}

/**
 * Formatea un número con separadores de miles
 */
function formatearNumero(valor) {
    return new Intl.NumberFormat('de-DE').format(valor);
}
```

---

## 13. Iconos Bootstrap Icons Comunes

```html
<!-- Navegación -->
<i class="bi bi-house"></i>           <!-- Inicio/Home -->
<i class="bi bi-arrow-left"></i>      <!-- Volver -->
<i class="bi bi-arrow-right"></i>     <!-- Siguiente -->

<!-- Acciones -->
<i class="bi bi-pencil"></i>          <!-- Editar -->
<i class="bi bi-trash"></i>           <!-- Eliminar -->
<i class="bi bi-eye"></i>             <!-- Ver -->
<i class="bi bi-check"></i>           <!-- Confirmar -->
<i class="bi bi-x"></i>               <!-- Cancelar -->
<i class="bi bi-plus"></i>            <!-- Agregar -->
<i class="bi bi-download"></i>        <!-- Descargar -->
<i class="bi bi-upload"></i>          <!-- Subir -->
<i class="bi bi-printer"></i>         <!-- Imprimir -->

<!-- Información -->
<i class="bi bi-info-circle"></i>     <!-- Información -->
<i class="bi bi-question-circle"></i> <!-- Ayuda -->
<i class="bi bi-exclamation-triangle"></i> <!-- Advertencia -->

<!-- Personas y contacto -->
<i class="bi bi-person"></i>          <!-- Usuario -->
<i class="bi bi-people"></i>          <!-- Usuarios -->
<i class="bi bi-person-badge"></i>    <!-- ID/Badge -->
<i class="bi bi-envelope"></i>        <!-- Email -->
<i class="bi bi-telephone"></i>       <!-- Teléfono -->

<!-- Comercial -->
<i class="bi bi-cart"></i>            <!-- Carrito -->
<i class="bi bi-receipt"></i>         <!-- Recibo/Pedido -->
<i class="bi bi-box-seam"></i>        <!-- Paquete -->
<i class="bi bi-currency-dollar"></i> <!-- Dinero -->
<i class="bi bi-credit-card"></i>     <!-- Pago -->

<!-- Ubicación -->
<i class="bi bi-geo-alt"></i>         <!-- Ubicación -->
<i class="bi bi-building"></i>        <!-- Edificio -->

<!-- Tiempo -->
<i class="bi bi-calendar"></i>        <!-- Calendario -->
<i class="bi bi-clock"></i>           <!-- Reloj -->

<!-- Filtros y búsqueda -->
<i class="bi bi-funnel"></i>          <!-- Filtro -->
<i class="bi bi-search"></i>          <!-- Buscar -->

<!-- Configuración -->
<i class="bi bi-gear"></i>            <!-- Configuración -->
<i class="bi bi-sliders"></i>         <!-- Ajustes -->
```

---

## 14. Alertas/Notificaciones

```html
<!-- Success -->
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-2"></i>
    <strong>¡Éxito!</strong> Operación completada correctamente.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>

<!-- Error -->
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Error:</strong> Ha ocurrido un problema.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>

<!-- Warning -->
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-circle me-2"></i>
    <strong>Atención:</strong> Verifica los datos ingresados.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>

<!-- Info -->
<div class="alert alert-info alert-dismissible fade show" role="alert">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Información:</strong> Ten en cuenta lo siguiente.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
```

---

## 15. Loading Spinner

```html
<!-- Spinner simple -->
<div class="spinner-border text-primary" role="status">
    <span class="visually-hidden">Cargando...</span>
</div>

<!-- Spinner en botón -->
<button class="btn btn-primary" type="button" disabled>
    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
    Cargando...
</button>

<!-- Overlay con spinner centrado -->
<div class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" 
     style="background-color: rgba(0,0,0,0.5); z-index: 9999;">
    <div class="spinner-border text-light" style="width: 3rem; height: 3rem;" role="status">
        <span class="visually-hidden">Cargando...</span>
    </div>
</div>
```

---

## 🎯 Instrucciones de Uso

1. **Copiar el snippet necesario**
2. **Reemplazar [NOMBRE_ARCHIVO] con el nombre real**
3. **Ajustar IDs y nombres según necesidad**
4. **Agregar funcionalidad específica**
5. **Probar en navegador**

---

**Nota:** Estos snippets están basados en la modernización de index.php y siguen las mismas convenciones de diseño y código.
