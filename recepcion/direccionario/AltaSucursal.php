<?php
require_once __DIR__ . '/../../../nodo/Class/bootstrap.php';
require_once __DIR__ . '/class/Direccionario.php';

// Exige sesión activa y válida (de lo contrario redirige automáticamente a /sistemas/login.php)
Auth::exigir();

$user = Auth::usuario();
$username = Auth::username();
$esAdmin = Auth::esAdmin();
$rol = $_SESSION['rol'] ?? ($user['rol'] ?? '');
$rolNom = strtoupper(trim($user['rol_nombre'] ?? $rol ?? ''));
$sectorCod = strtoupper($user['sector_codigo'] ?? '');
$sectorNom = strtoupper($user['sector_nombre'] ?? '');
$subsectorNom = strtoupper($user['subsector_nombre'] ?? '');

$es_admin_sistemas = $esAdmin 
                  || in_array(strtolower($rol), ['admin', 'sistemas', 'gerencia_adm', 'superadmin', 'controltotal']) 
                  || in_array(strtolower($username ?? ''), ['admin', 'sistemas', 'francopertus', 'franco.pertus'])
                  || in_array($sectorCod, ['SISTEMAS', 'IT']);

// Rol Recepcionista (Comercial y Ventas -> Analistas -> Recepcionista)
$es_recepcionista = ($rolNom === 'RECEPCIONISTA' || ($user['rol_id'] ?? 0) == 18)
                 || ($sectorNom === 'COMERCIAL Y VENTAS' && $subsectorNom === 'ANALISTAS' && $rolNom === 'RECEPCIONISTA');

$puede_crear  = $es_admin_sistemas;
$puede_editar = $es_admin_sistemas || $es_recepcionista;

$idSucursal = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$clonarId   = isset($_GET['clonar']) ? (int)$_GET['clonar'] : 0;
$esEdicion  = ($idSucursal > 0);
$esClonar   = ($clonarId > 0);

if ($esEdicion && !$puede_editar) {
    http_response_code(403);
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Acceso Denegado</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh"><div class="card p-5 text-center shadow-sm" style="max-width:480px"><h3 class="text-danger mb-3">Acceso Denegado</h3><p class="text-muted">No posee permisos para editar sucursales.</p><a href="index.php" class="btn btn-primary mt-3">Volver al Direccionario</a></div></body></html>';
    exit;
}

if (!$esEdicion && !$puede_crear) {
    http_response_code(403);
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Acceso Denegado</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh"><div class="card p-5 text-center shadow-sm" style="max-width:480px"><h3 class="text-danger mb-3">Acceso Denegado</h3><p class="text-muted">No posee permisos para crear nuevas sucursales. Solo puede editar sucursales existentes.</p><a href="index.php" class="btn btn-primary mt-3">Volver al Direccionario</a></div></body></html>';
    exit;
}

$direccionario = new Direccionario();
$canales       = $direccionario->getCanales();
$provincias    = $direccionario->getProvincias();
$tipos_local   = $direccionario->getTiposLocal();
$grupos        = $direccionario->getGruposEmpresarios();

$sucursalData = null;
if ($idSucursal > 0) {
    $sucursalData = $direccionario->getSucursalById($idSucursal);
} elseif ($clonarId > 0) {
    $sucursalData = $direccionario->getSucursalById($clonarId);
    if ($sucursalData) {
        $sucursalData['NRO_SUCURSAL'] = '';
        $sucursalData['DESC_SUCURSAL'] = trim($sucursalData['DESC_SUCURSAL'] ?? '') . ' (COPIA)';
    }
}

// Lista estándar de provincias argentinas
$listaProvincias = [
    'BUENOS AIRES', 'CAPITAL FEDERAL', 'CATAMARCA', 'CHACO', 'CHUBUT', 'CORDOBA',
    'CORRIENTES', 'ENTRE RIOS', 'FORMOSA', 'JUJUY', 'LA PAMPA', 'LA RIOJA',
    'MENDOZA', 'MISIONES', 'NEUQUEN', 'RIO NEGRO', 'SALTA', 'SAN JUAN',
    'SAN LUIS', 'SANTA CRUZ', 'SANTA FE', 'SANTIAGO DEL ESTERO', 'TIERRA DEL FUEGO',
    'TUCUMAN', 'URUGUAY'
];

$listaCanales = ['PROPIOS', 'FRANQUICIAS', 'CERRADO', 'CENTRAL', 'CENTRAL URUGUAY', 'EXTERIOR'];
$listaTiposLocal = ['SHOPPING', 'CALLE'];

$mensaje = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($esEdicion && !$puede_editar) {
            throw new Exception("No tiene permisos para editar esta sucursal.");
        }
        if (!$esEdicion && !$puede_crear) {
            throw new Exception("No tiene permisos para crear una nueva sucursal.");
        }

        $direccionario->guardarSucursal($_POST, $esEdicion);
        $nro = (int)($_POST['nro_sucursal'] ?? 0);
        $desc = trim($_POST['desc_sucursal'] ?? '');
        $mensaje = "Sucursal #{$nro} - {$desc} guardada exitosamente.";
        
        // Recargar datos actualizados
        if ($esEdicion) {
            $sucursalData = $direccionario->getSucursalById($nro);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$valNroSucursal = htmlspecialchars($sucursalData['NRO_SUCURSAL'] ?? '');
$valCodClient   = htmlspecialchars(trim($sucursalData['COD_CLIENT'] ?? ''));
$valDesc        = htmlspecialchars(trim($sucursalData['DESC_SUCURSAL'] ?? ''));
$valDireccion   = htmlspecialchars(trim($sucursalData['DIRECCION'] ?? ''));
$valLocalidad   = htmlspecialchars(trim($sucursalData['LOCALIDAD'] ?? ''));
$valProvincia   = trim($sucursalData['PROVINCIA'] ?? '');
$valTelefono    = htmlspecialchars(trim($sucursalData['TELEFONO'] ?? ''));
$valMail        = htmlspecialchars(trim($sucursalData['MAIL'] ?? ''));
$valCanal       = trim($sucursalData['CANAL'] ?? '');
$valTipoLocal   = trim($sucursalData['TIPO_LOCAL'] ?? '');
$valCodDeposi   = htmlspecialchars(trim($sucursalData['COD_DEPOSI'] ?? ''));
$valHorario     = htmlspecialchars(trim($sucursalData['HORARIO'] ?? ''));
$valMailGrupo   = htmlspecialchars(trim($sucursalData['MAIL_GRUP_EMP'] ?? ''));
$valTango       = (trim($sucursalData['TANGO'] ?? '') === 'SI' || !isset($sucursalData));
$valTienda      = (trim($sucursalData['TIENDA'] ?? '') === 'SI' || !isset($sucursalData));
$valVtex        = (trim($sucursalData['INTEGRA_VTEX'] ?? '') === 'SI');
$valRetiroExp   = (trim($sucursalData['RETIRO_EXPRES'] ?? '') === 'SI');
$valSucMadre    = htmlspecialchars($sucursalData['NRO_SUC_MADRE'] ?? '');

// Días de atención
$valLunes     = !isset($sucursalData) || !empty($sucursalData['LUNES']);
$valMartes    = !isset($sucursalData) || !empty($sucursalData['MARTES']);
$valMiercoles = !isset($sucursalData) || !empty($sucursalData['MIERCOLES']);
$valJueves    = !isset($sucursalData) || !empty($sucursalData['JUEVES']);
$valViernes   = !isset($sucursalData) || !empty($sucursalData['VIERNES']);
$valSabado    = !isset($sucursalData) || !empty($sucursalData['SABADO']);
$valDomingo   = isset($sucursalData) ? !empty($sucursalData['DOMINGO']) : false;

$tituloPagina = $esEdicion ? "Editar Sucursal #{$valNroSucursal} · Direccionario XL" : "Alta de Sucursal · Direccionario XL";
$tituloHeader = $esEdicion ? "Editar Sucursal #{$valNroSucursal}" : "Alta de Sucursal";
$subtituloHeader = $esEdicion ? "Actualizar datos y configuración de la sucursal en el Direccionario" : "Registrar una nueva sucursal o punto de venta en el Direccionario general";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tituloPagina ?></title>
    <link rel="shortcut icon" href="/sistemas/assets/css/icono.jpg">

    <!-- Google Fonts: Sora + DM Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #0D5C63;
            --primary-hover: #084B51;
            --primary-light: #eaf4f5;
            --primary-border: rgba(13, 92, 99, 0.2);
            --bg-page: #f4f7f8;
            --bg-card: #ffffff;
            --text-title: #111827;
            --text-body: #374151;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --radius-card: 16px;
            --font-display: 'Sora', sans-serif;
            --font-body: 'DM Sans', sans-serif;
        }

        body {
            font-family: var(--font-body);
            background-color: var(--bg-page);
            color: var(--text-body);
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .form-container {
            max-width: 980px;
            margin: 0 auto;
        }

        .header-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-title-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            box-shadow: 0 4px 12px rgba(13, 92, 99, 0.25);
        }

        .header-title {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-title);
            margin: 0;
            line-height: 1.2;
        }

        .header-subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin: 0;
        }

        .form-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-card);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            overflow: hidden;
        }

        .form-section-header {
            padding: 1rem 1.5rem;
            background: #fafbfc;
            border-bottom: 1px solid var(--border);
            font-family: var(--font-display);
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-title);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-section-header i {
            color: var(--primary);
            font-size: 1.1rem;
        }

        .form-section-body {
            padding: 1.5rem;
        }

        .form-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .form-label .req {
            color: #e53e3e;
        }

        .form-control, .form-select {
            border: 1.5px solid #d1d5db;
            border-radius: 10px;
            padding: 0.6rem 0.85rem;
            font-size: 0.9rem;
            color: var(--text-title);
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(13, 92, 99, 0.12);
        }

        .form-text-muted {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        /* Toggle switches modernos */
        .switch-group {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.85rem 1.1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            transition: background 0.15s ease;
        }

        .switch-group:hover {
            background: #f1f5f9;
        }

        .switch-label-wrap {
            display: flex;
            flex-direction: column;
        }

        .switch-title {
            font-size: 0.88rem;
            font-weight: 600;
            color: #1f2937;
        }

        .switch-desc {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .form-check-input {
            width: 2.5rem;
            height: 1.35rem;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        /* Botones de días */
        .dias-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .btn-dia {
            border: 1.5px solid #d1d5db;
            background: #ffffff;
            color: #4b5563;
            border-radius: 8px;
            padding: 0.45rem 0.85rem;
            font-weight: 600;
            font-size: 0.82rem;
            cursor: pointer;
            transition: all 0.18s ease;
            user-select: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-dia input {
            display: none;
        }

        .btn-dia.active, .btn-dia:has(input:checked) {
            background: var(--primary);
            border-color: var(--primary);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(13, 92, 99, 0.25);
        }

        .card-footer-box {
            padding: 1.25rem 1.5rem;
            background: #fafbfc;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-save {
            background: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            padding: 0.65rem 1.6rem;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-save:hover {
            background: var(--primary-hover);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 92, 99, 0.25);
        }

        .btn-cancel {
            background: #ffffff;
            color: #4b5563;
            border: 1.5px solid #d1d5db;
            border-radius: 10px;
            padding: 0.65rem 1.3rem;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-cancel:hover {
            background: #f3f4f6;
            color: #111827;
            border-color: #9ca3af;
        }
    </style>
</head>
<body>

<div class="form-container">

    <!-- Header -->
    <div class="header-box">
        <div class="header-title-wrap">
            <div class="header-icon">
                <i class="bi bi-shop"></i>
            </div>
            <div>
                <h1 class="header-title"><?= $tituloHeader ?></h1>
                <p class="header-subtitle"><?= $subtituloHeader ?></p>
            </div>
        </div>
        <div>
            <a href="index.php" class="btn-cancel">
                <i class="bi bi-arrow-left"></i> Volver al Direccionario
            </a>
        </div>
    </div>

    <?php if ($mensaje): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div><?= htmlspecialchars($mensaje) ?></div>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div><?= htmlspecialchars($error) ?></div>
    </div>
    <?php endif; ?>

    <form method="POST" action="" class="needs-validation" novalidate id="formAltaSucursal">

        <div class="form-card mb-4">
            <!-- Sección 1: Identificación y Ubicación -->
            <div class="form-section-header">
                <i class="bi bi-geo-alt-fill"></i> Datos Principales y Ubicación
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="nro_sucursal">Nro Sucursal <span class="req">*</span></label>
                        <input type="number" class="form-control" id="nro_sucursal" name="nro_sucursal" value="<?= $valNroSucursal ?>" placeholder="Ej: 110" <?= $esEdicion ? 'readonly' : 'required' ?>>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="cod_client">Cód. Cliente <span class="req">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="cod_client" name="cod_client" value="<?= $valCodClient ?>" placeholder="Ej: GTNUNI" maxlength="8" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="desc_sucursal">Nombre Sucursal <span class="req">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="desc_sucursal" name="desc_sucursal" value="<?= $valDesc ?>" placeholder="Ej: ALTO PALERMO" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="direccion">Dirección <span class="req">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="direccion" name="direccion" value="<?= $valDireccion ?>" placeholder="Calle, número, piso o local" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="localidad">Localidad <span class="req">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="localidad" name="localidad" value="<?= $valLocalidad ?>" placeholder="Ej: CAPITAL FEDERAL" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="provincia">Provincia <span class="req">*</span></label>
                        <select class="form-select" id="provincia" name="provincia" required>
                            <option value="" disabled <?= empty($valProvincia) ? 'selected' : '' ?>>Seleccione...</option>
                            <?php foreach ($listaProvincias as $prov): ?>
                            <option value="<?= $prov ?>" <?= ($valProvincia === $prov) ? 'selected' : '' ?>><?= $prov ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Sección 2: Contacto y Segmentación -->
            <div class="form-section-header border-top">
                <i class="bi bi-telephone-fill"></i> Contacto y Segmentación
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="telefono">Teléfono <span class="req">*</span></label>
                        <input type="text" class="form-control" id="telefono" name="telefono" value="<?= $valTelefono ?>" placeholder="Ej: 4717-6295" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="mail">Email Sucursal <span class="req">*</span></label>
                        <input type="email" class="form-control" id="mail" name="mail" value="<?= $valMail ?>" placeholder="sucursal@xl.com.ar" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="canal">Canal Comercial <span class="req">*</span></label>
                        <select class="form-select" id="canal" name="canal" required>
                            <option value="" disabled <?= empty($valCanal) ? 'selected' : '' ?>>Seleccione...</option>
                            <?php foreach ($listaCanales as $c): ?>
                            <option value="<?= $c ?>" <?= ($valCanal === $c) ? 'selected' : '' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="tipo_local">Tipo de Punto de Venta <span class="req">*</span></label>
                        <select class="form-select" id="tipo_local" name="tipo_local" required>
                            <option value="" disabled <?= empty($valTipoLocal) ? 'selected' : '' ?>>Seleccione...</option>
                            <?php foreach ($listaTiposLocal as $tipo): ?>
                            <option value="<?= $tipo ?>" <?= ($valTipoLocal === $tipo) ? 'selected' : '' ?>><?= $tipo ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="cod_deposi">Cód. Depósito <span class="req">*</span></label>
                        <input type="text" class="form-control" id="cod_deposi" name="cod_deposi" value="<?= $valCodDeposi ?>" placeholder="Ej: 02" maxlength="3" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="horario">Horario de Atención <span class="req">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="horario" name="horario" value="<?= $valHorario ?>" placeholder="Ej: LUN A DOM 10 A 22" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="mail_grupo_emp">Emails Empresa / Grupo (opcional)</label>
                        <textarea class="form-control" id="mail_grupo_emp" name="mail_grupo_emp" rows="2" placeholder="Ingrese uno por línea o sepárelos por coma o punto y coma"><?= $valMailGrupo ?></textarea>
                        <div class="form-text-muted">Se almacenará normalizado con punto y coma (;) para notificaciones automáticas.</div>
                    </div>
                </div>
            </div>

            <!-- Sección 3: Días de Atención -->
            <div class="form-section-header border-top">
                <i class="bi bi-calendar-week-fill"></i> Días de Atención Comercial
            </div>
            <div class="form-section-body">
                <label class="form-label d-block mb-2">Seleccione los días en que el local se encuentra abierto:</label>
                <div class="dias-wrap">
                    <label class="btn-dia <?= $valLunes ? 'active' : '' ?>">
                        <input type="checkbox" name="lunes" value="1" <?= $valLunes ? 'checked' : '' ?>> Lunes
                    </label>
                    <label class="btn-dia <?= $valMartes ? 'active' : '' ?>">
                        <input type="checkbox" name="martes" value="1" <?= $valMartes ? 'checked' : '' ?>> Martes
                    </label>
                    <label class="btn-dia <?= $valMiercoles ? 'active' : '' ?>">
                        <input type="checkbox" name="miercoles" value="1" <?= $valMiercoles ? 'checked' : '' ?>> Miércoles
                    </label>
                    <label class="btn-dia <?= $valJueves ? 'active' : '' ?>">
                        <input type="checkbox" name="jueves" value="1" <?= $valJueves ? 'checked' : '' ?>> Jueves
                    </label>
                    <label class="btn-dia <?= $valViernes ? 'active' : '' ?>">
                        <input type="checkbox" name="viernes" value="1" <?= $valViernes ? 'checked' : '' ?>> Viernes
                    </label>
                    <label class="btn-dia <?= $valSabado ? 'active' : '' ?>">
                        <input type="checkbox" name="sabado" value="1" <?= $valSabado ? 'checked' : '' ?>> Sábado
                    </label>
                    <label class="btn-dia <?= $valDomingo ? 'active' : '' ?>">
                        <input type="checkbox" name="domingo" value="1" <?= $valDomingo ? 'checked' : '' ?>> Domingo
                    </label>
                </div>
            </div>

            <!-- Sección 4: Configuración Técnica e Integraciones -->
            <div class="form-section-header border-top">
                <i class="bi bi-toggles"></i> Integraciones y Estado
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="switch-group">
                            <div class="switch-label-wrap">
                                <span class="switch-title">Integración Tango</span>
                                <span class="switch-desc">Sincroniza comprobantes y stock con Tango Gestión</span>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="tango" name="tango" value="1" <?= $valTango ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="switch-group">
                            <div class="switch-label-wrap">
                                <span class="switch-title">Tienda Física Activa</span>
                                <span class="switch-desc">Local disponible para ventas</span>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="tienda" name="tienda" value="1" <?= $valTienda ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="switch-group">
                            <div class="switch-label-wrap">
                                <span class="switch-title">Integración VTEX</span>
                                <span class="switch-desc">Punto de retiro o disponibilidad omnicanal</span>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="integra_vtex" name="integra_vtex" value="1" <?= $valVtex ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="switch-group">
                            <div class="switch-label-wrap">
                                <span class="switch-title">Retiro Express</span>
                                <span class="switch-desc">Habilita modalidad click and collect express</span>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="retiro_expres" name="retiro_expres" value="1" <?= $valRetiroExp ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="nro_suc_madre">N° Sucursal Madre (si aplica)</label>
                        <input type="number" class="form-control" id="nro_suc_madre" name="nro_suc_madre" value="<?= $valSucMadre ?>" placeholder="Ej: 2">
                        <div class="form-text-muted">Indicar únicamente si es anexo o sub-sucursal.</div>
                    </div>
                </div>
            </div>

            <!-- Footer / Botones -->
            <div class="card-footer-box">
                <a href="index.php" class="btn-cancel">
                    <i class="bi bi-x-lg"></i> Cancelar
                </a>
                <button type="submit" class="btn-save" id="btnGuardar">
                    <i class="bi bi-check2-circle"></i> <?= $esEdicion ? 'Actualizar Sucursal' : 'Guardar Sucursal' ?>
                </button>
            </div>

        </div>

    </form>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Toggle styling para botones de días
    $('.btn-dia').on('click', function(e) {
        if (e.target.tagName !== 'INPUT') {
            var $input = $(this).find('input');
            $input.prop('checked', !$input.prop('checked')).trigger('change');
        }
    });
    $('.btn-dia input').on('change', function() {
        $(this).closest('.btn-dia').toggleClass('active', this.checked);
    });
});
</script>

</body>
</html>
