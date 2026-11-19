<?php
// Nodo vive en htdocs/nodo; el módulo está en htdocs/comercial/direccionario (antes en recepcion/direccionario), por eso sube dos niveles.
require_once __DIR__ . '/../../nodo/Class/bootstrap.php';
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

$puede_crear    = $es_admin_sistemas || Auth::puede('direccionario.crear');
$puede_editar   = $es_admin_sistemas || $es_recepcionista || Auth::puede('direccionario.editar');
$puede_eliminar = $es_admin_sistemas || $es_recepcionista || Auth::puede('direccionario.eliminar');

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
$sucursalOrigenNro = null;
$sucursalOrigenDesc = '';
if ($idSucursal > 0) {
    $sucursalData = $direccionario->getSucursalById($idSucursal);
    if (!$sucursalData) {
        $error = "No se encontró la sucursal #{$idSucursal}.";
    }
} elseif ($clonarId > 0) {
    $sucursalData = $direccionario->getSucursalById($clonarId);
    if ($sucursalData) {
        $sucursalOrigenNro = $sucursalData['NRO_SUCURSAL'] ?? $clonarId;
        $sucursalOrigenDesc = trim($sucursalData['DESC_SUCURSAL'] ?? '');
        $sucursalData['NRO_SUCURSAL'] = '';
        $sucursalData['COD_CLIENT'] = '';
        $sucursalData['DESC_SUCURSAL'] = $sucursalOrigenDesc . ' (COPIA)';
        // Una sucursal nueva siempre arranca habilitada y visible en contabilidad
        $sucursalData['HABILITADO'] = 1;
        $sucursalData['HABILITADO_CONT'] = 1;
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
$warning = null;
$error = $error ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($esEdicion && !$puede_editar) {
            throw new Exception("No tiene permisos para editar esta sucursal.");
        }
        if (!$esEdicion && !$puede_crear) {
            throw new Exception("No tiene permisos para crear una nueva sucursal.");
        }

        $resGuardar = $direccionario->guardarSucursal($_POST, $esEdicion, $es_admin_sistemas, [
            'clonar_de' => $clonarId > 0 ? $clonarId : null,
            'usuario'   => $user,
        ]);
        $nro = (int)($_POST['nro_sucursal'] ?? 0);
        $desc = trim($_POST['desc_sucursal'] ?? '');
        $mensaje = "Sucursal #{$nro} - {$desc} guardada exitosamente.";
        if (!empty($resGuardar['warning'])) {
            $warning = $resGuardar['warning'];
        }

        // Recargar datos actualizados
        $sucursalData = $direccionario->getSucursalById($nro);
        if (!$esEdicion && $nro > 0) {
            $idSucursal = $nro;
            $esEdicion = true;
            $esClonar = false;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        // Preservar los valores enviados en POST si falló el guardado
        $sucursalData = array_merge($sucursalData ?? [], [
            'NRO_SUCURSAL'                  => $_POST['nro_sucursal'] ?? '',
            'COD_CLIENT'                    => $_POST['cod_client'] ?? '',
            'DESC_SUCURSAL'                 => $_POST['desc_sucursal'] ?? '',
            'DIRECCION'                     => $_POST['direccion'] ?? '',
            'LOCALIDAD'                     => $_POST['localidad'] ?? '',
            'PROVINCIA'                     => $_POST['provincia'] ?? '',
            'TELEFONO'                      => $_POST['telefono'] ?? '',
            'MAIL'                          => $_POST['mail'] ?? '',
            'CANAL'                         => $_POST['canal'] ?? '',
            'TIPO_LOCAL'                    => $_POST['tipo_local'] ?? '',
            'COD_DEPOSI'                    => $_POST['cod_deposi'] ?? '',
            'HORARIO'                       => $_POST['horario'] ?? '',
            'MAIL_GRUP_EMP'                 => $_POST['mail_grupo_emp'] ?? '',
            'FECHA_CIERRE'                  => $_POST['fecha_cierre'] ?? '',
            'NRO_SUC_MADRE'                 => $_POST['nro_suc_madre'] ?? '',
            'TANGO'                         => !empty($_POST['tango']) ? 1 : 0,
            'HABILITADO'                    => (!empty($_POST['habilitado']) || !empty($_POST['tienda'])) ? 1 : 0,
            'INTEGRA_VTEX'                  => !empty($_POST['integra_vtex']) ? 1 : 0,
            'RETIRO_EXPRES'                 => !empty($_POST['retiro_expres']) ? 1 : 0,
            'HABILITADO_CONT'               => !empty($_POST['habilitado_cont']) ? 1 : 0,
            'DASHBOARD_BI'                  => !empty($_POST['dashboard_bi']) ? 1 : 0,
            'SUC_MADRE'                     => !empty($_POST['suc_madre']) ? 1 : 0,
            'EMPRESA_FERRETERIA'            => !empty($_POST['empresa_ferreteria']) ? 1 : 0,
            'FACTURA_ELECT'                 => !empty($_POST['factura_elect']) ? 1 : 0,
            'INTEGRACION_MERCADOPAGO_MADRE' => !empty($_POST['integracion_mercadopago_madre']) ? 1 : 0,
            'INTEGRACION_MERCADOPAGO_HIJA'  => !empty($_POST['integracion_mercadopago_hija']) ? 1 : 0,
            'LAPOS_INTEGRADO'               => !empty($_POST['lapos_integrado']) ? 1 : 0,
            'LAPOS_MODELO'                  => $_POST['lapos_modelo'] ?? '',
            'VERSION_SOFT_LAPOS'            => $_POST['version_soft_lapos'] ?? '',
            'CABLE_LAPOS_INTEGRADO'         => $_POST['cable_lapos_integrado'] ?? '',
            'LINK_PEDIDO'                   => $_POST['link_pedido'] ?? '',
            'LINK_SALES'                    => $_POST['link_sales'] ?? '',
            'BASE_NOMBRE'                   => $_POST['base_nombre'] ?? '',
            'N_LLAVE_TANGO'                 => $_POST['n_llave_tango'] ?? '',
            'CONEXION_DNS'                  => $_POST['conexion_dns'] ?? '',
            'CONEXION_DNS_ALT'              => $_POST['conexion_dns_alt'] ?? '',
            'USUARIO_DNS'                   => $_POST['usuario_dns'] ?? '',
            'CLAVE_DNS'                     => $_POST['clave_dns'] ?? '',
            'CONEXION_TEAMVIEWER_MADRE'     => $_POST['conexion_teamviewer_madre'] ?? '',
            'CONEXION_TEAMVIEWER_HIJA'      => $_POST['conexion_teamviewer_hija'] ?? '',
            'CLAVE_TEAMVIEWER'              => $_POST['clave_teamviewer'] ?? '',
            'USUARIO_PC_MADRE'              => $_POST['usuario_pc_madre'] ?? '',
            'LUNES'                         => !empty($_POST['lunes']) ? 1 : 0,
            'MARTES'                        => !empty($_POST['martes']) ? 1 : 0,
            'MIERCOLES'                     => !empty($_POST['miercoles']) ? 1 : 0,
            'JUEVES'                        => !empty($_POST['jueves']) ? 1 : 0,
            'VIERNES'                       => !empty($_POST['viernes']) ? 1 : 0,
            'SABADO'                        => !empty($_POST['sabado']) ? 1 : 0,
            'DOMINGO'                       => !empty($_POST['domingo']) ? 1 : 0,
        ]);
    }
}

$valNroSucursal = htmlspecialchars((string)($sucursalData['NRO_SUCURSAL'] ?? ''));
$valCodClient   = htmlspecialchars(trim((string)($sucursalData['COD_CLIENT'] ?? '')));
$valDesc        = htmlspecialchars(trim((string)($sucursalData['DESC_SUCURSAL'] ?? '')));
$valDireccion   = htmlspecialchars(trim((string)($sucursalData['DIRECCION'] ?? '')));
$valLocalidad   = htmlspecialchars(trim((string)($sucursalData['LOCALIDAD'] ?? '')));
$valProvincia   = trim((string)($sucursalData['PROVINCIA'] ?? ''));
$valTelefono    = htmlspecialchars(trim((string)($sucursalData['TELEFONO'] ?? '')));
$valMail        = htmlspecialchars(trim((string)($sucursalData['MAIL'] ?? '')));
$valCanal       = trim((string)($sucursalData['CANAL'] ?? ''));
$valTipoLocal   = trim((string)($sucursalData['TIPO_LOCAL'] ?? ''));
$valCodDeposi   = htmlspecialchars(trim((string)($sucursalData['COD_DEPOSI'] ?? '')));
$valHorario     = htmlspecialchars(trim((string)($sucursalData['HORARIO'] ?? '')));
$valFechaCierre = htmlspecialchars(trim((string)($sucursalData['FECHA_CIERRE'] ?? '')));

// Mostrar un email por línea en el textarea para editar cómodo
$rawMailGrupo   = trim((string)($sucursalData['MAIL_GRUP_EMP'] ?? ''));
$mailGrupoLines = $rawMailGrupo !== '' ? implode("\n", preg_split('/[\s,;]+/', $rawMailGrupo, -1, PREG_SPLIT_NO_EMPTY)) : '';
$valMailGrupo   = htmlspecialchars($mailGrupoLines);

$valTango       = !isset($sucursalData) ? true : (!empty($sucursalData['TANGO']) || ($sucursalData['TANGO'] ?? '') === 'SI');
$valTienda      = !isset($sucursalData) ? true : (isset($sucursalData['HABILITADO']) ? !empty($sucursalData['HABILITADO']) : (($sucursalData['TIENDA'] ?? '') === 'SI'));
$valVtex        = isset($sucursalData) && (!empty($sucursalData['INTEGRA_VTEX']) || ($sucursalData['INTEGRA_VTEX'] ?? '') === 'SI');
$valRetiroExp   = isset($sucursalData) && (!empty($sucursalData['RETIRO_EXPRES']) || ($sucursalData['RETIRO_EXPRES'] ?? '') === 'SI');
$valSucMadreNro = htmlspecialchars((string)($sucursalData['NRO_SUC_MADRE'] ?? ''));
// Habilitado Contabilidad: por defecto activo (alta o sin dato)
$valHabCont     = !isset($sucursalData['HABILITADO_CONT']) || !empty($sucursalData['HABILITADO_CONT']);
$valDashboardBi = !isset($sucursalData) ? true : !empty($sucursalData['DASHBOARD_BI']);
$valEsSucMadre  = isset($sucursalData) && !empty($sucursalData['SUC_MADRE']);
$valFerreteria  = isset($sucursalData) && !empty($sucursalData['EMPRESA_FERRETERIA']);
$valFacturaElec = isset($sucursalData) && !empty($sucursalData['FACTURA_ELECT']);

// Campos Admin/Sistemas (MercadoPago, LAPOS, Técnica, TeamViewer)
$valMpMadre     = isset($sucursalData) && !empty($sucursalData['INTEGRACION_MERCADOPAGO_MADRE']);
$valMpHija      = isset($sucursalData) && !empty($sucursalData['INTEGRACION_MERCADOPAGO_HIJA']);
$valLaposInt    = isset($sucursalData) && !empty($sucursalData['LAPOS_INTEGRADO']);
$valLaposMod    = htmlspecialchars(trim((string)($sucursalData['LAPOS_MODELO'] ?? '')));
$valLaposVer    = htmlspecialchars(trim((string)($sucursalData['VERSION_SOFT_LAPOS'] ?? '')));
$valLaposCable  = htmlspecialchars(trim((string)($sucursalData['CABLE_LAPOS_INTEGRADO'] ?? '')));
$valLinkPedido  = htmlspecialchars(trim((string)($sucursalData['LINK_PEDIDO'] ?? '')));
$valLinkSales   = htmlspecialchars(trim((string)($sucursalData['LINK_SALES'] ?? '')));
$valBaseNombre  = htmlspecialchars(trim((string)($sucursalData['BASE_NOMBRE'] ?? '')));
$valLlaveTango  = htmlspecialchars(trim((string)($sucursalData['N_LLAVE_TANGO'] ?? '')));
$valConexionDns = htmlspecialchars(trim((string)($sucursalData['CONEXION_DNS'] ?? '')));
$valDnsAlt      = htmlspecialchars(trim((string)($sucursalData['CONEXION_DNS_ALT'] ?? '')));
$valUsuarioDns  = htmlspecialchars(trim((string)($sucursalData['USUARIO_DNS'] ?? '')));
$valClaveDns    = htmlspecialchars(trim((string)($sucursalData['CLAVE_DNS'] ?? '')));
$valTvMadre     = htmlspecialchars(trim((string)($sucursalData['CONEXION_TEAMVIEWER_MADRE'] ?? '')));
$valTvHija      = htmlspecialchars(trim((string)($sucursalData['CONEXION_TEAMVIEWER_HIJA'] ?? '')));
$valClaveTv     = htmlspecialchars(trim((string)($sucursalData['CLAVE_TEAMVIEWER'] ?? '')));
$valUserPcMadre = htmlspecialchars(trim((string)($sucursalData['USUARIO_PC_MADRE'] ?? '')));

// Asegurar que valores existentes en BD fuera de las listas fijas aparezcan en los selects
if ($valProvincia !== '' && !in_array($valProvincia, $listaProvincias, true)) {
    $listaProvincias[] = $valProvincia;
}
if ($valCanal !== '' && !in_array($valCanal, $listaCanales, true)) {
    $listaCanales[] = $valCanal;
}
if ($valTipoLocal !== '' && !in_array($valTipoLocal, $listaTiposLocal, true)) {
    $listaTiposLocal[] = $valTipoLocal;
}

// Días de atención
$valLunes     = !isset($sucursalData) || !empty($sucursalData['LUNES']);
$valMartes    = !isset($sucursalData) || !empty($sucursalData['MARTES']);
$valMiercoles = !isset($sucursalData) || !empty($sucursalData['MIERCOLES']);
$valJueves    = !isset($sucursalData) || !empty($sucursalData['JUEVES']);
$valViernes   = !isset($sucursalData) || !empty($sucursalData['VIERNES']);
$valSabado    = !isset($sucursalData) || !empty($sucursalData['SABADO']);
$valDomingo   = isset($sucursalData) ? !empty($sucursalData['DOMINGO']) : false;

if ($esEdicion) {
    $tituloPagina    = "Editar Sucursal #{$valNroSucursal} · Direccionario XL";
    $tituloHeader    = "Editar Sucursal #{$valNroSucursal}";
    $subtituloHeader = $valDesc !== '' ? "Sucursal: {$valNroSucursal} - {$valDesc}" : "Actualizar datos y configuración de la sucursal en el Direccionario";
} elseif ($esClonar) {
    $tituloPagina    = "Clonar Sucursal · Direccionario XL";
    $tituloHeader    = "Clonar Sucursal";
    $subtituloHeader = "Nueva sucursal a partir de #{$sucursalOrigenNro} - {$sucursalOrigenDesc}";
} else {
    $tituloPagina    = "Alta de Sucursal · Direccionario XL";
    $tituloHeader    = "Alta de Sucursal";
    $subtituloHeader = "Registrar una nueva sucursal o punto de venta en el Direccionario general";
}
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
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

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

        .btn-danger-outline {
            background: #ffffff;
            color: #dc2626;
            border: 1.5px solid #fecaca;
            border-radius: 10px;
            padding: 0.65rem 1.2rem;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-danger-outline:hover {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #ef4444;
        }
    </style>
</head>
<body>

<div class="form-container">

    <!-- Header -->
    <div class="header-box">
        <div class="header-title-wrap">
            <div class="header-icon">
                <i class="bi <?= $esClonar ? 'bi-copy' : 'bi-shop' ?>"></i>
            </div>
            <div>
                <h1 class="header-title"><?= $tituloHeader ?></h1>
                <p class="header-subtitle"><?= htmlspecialchars($subtituloHeader) ?></p>
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <?php if ($esEdicion): ?>
            <button type="button" class="btn-cancel" id="btnVerHistorial" data-id="<?= (int)$idSucursal ?>" title="Ver historial de ediciones">
                <i class="bi bi-clock-history"></i> Historial
            </button>
            <?php endif; ?>
            <?php if ($esEdicion && $puede_crear): ?>
            <a href="AltaSucursal.php?clonar=<?= (int)$idSucursal ?>" class="btn-cancel" title="Clonar esta sucursal">
                <i class="bi bi-copy"></i> Clonar
            </a>
            <?php endif; ?>
            <?php if ($esEdicion && $puede_eliminar): ?>
            <button type="button" class="btn-danger-outline" id="btnDeshabilitarHeader" data-id="<?= (int)$idSucursal ?>" data-nombre="<?= $valDesc ?>">
                <i class="bi bi-trash3"></i> Deshabilitar
            </button>
            <?php endif; ?>
            <a href="index.php" class="btn-cancel">
                <i class="bi bi-arrow-left"></i> Volver al Direccionario
            </a>
        </div>
    </div>

    <?php if ($esClonar && $sucursalOrigenNro): ?>
    <div class="alert alert-info d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-info-circle-fill fs-5"></i>
        <div>
            Se precargaron todos los datos de la sucursal <strong>#<?= htmlspecialchars((string)$sucursalOrigenNro) ?> - <?= htmlspecialchars($sucursalOrigenDesc) ?></strong>.
            Complete el <strong>Nro Sucursal</strong> y el <strong>Cód. Cliente</strong> nuevos antes de guardar.
        </div>
    </div>
    <?php endif; ?>

    <?php if ($mensaje): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div><?= htmlspecialchars($mensaje) ?></div>
    </div>
    <?php endif; ?>

    <?php if ($warning): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-exclamation-circle-fill fs-5"></i>
        <div><?= htmlspecialchars($warning) ?></div>
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
                <i class="bi bi-geo-alt-fill"></i> Información Básica y Ubicación
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="nro_sucursal">Nro Sucursal <span class="req">*</span></label>
                        <input type="number" class="form-control" id="nro_sucursal" name="nro_sucursal" value="<?= $valNroSucursal ?>" placeholder="Ej: 110" <?= $esEdicion ? 'readonly' : 'required min="1"' ?>>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="cod_client">Cód. Cliente <span class="req">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="cod_client" name="cod_client" value="<?= $valCodClient ?>" placeholder="Ej: GTNUNI" maxlength="8" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="desc_sucursal">Nombre Sucursal <span class="req">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="desc_sucursal" name="desc_sucursal" value="<?= $valDesc ?>" placeholder="Ej: ALTO PALERMO" maxlength="50" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="direccion">Dirección <span class="req">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="direccion" name="direccion" value="<?= $valDireccion ?>" placeholder="Calle, número, piso o local" maxlength="80" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="localidad">Localidad <span class="req">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="localidad" name="localidad" value="<?= $valLocalidad ?>" placeholder="Ej: CAPITAL FEDERAL" maxlength="50" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="provincia">Provincia <span class="req">*</span></label>
                        <select class="form-select" id="provincia" name="provincia" required>
                            <option value="" disabled <?= empty($valProvincia) ? 'selected' : '' ?>>Seleccione...</option>
                            <?php foreach ($listaProvincias as $prov): ?>
                            <option value="<?= htmlspecialchars($prov) ?>" <?= ($valProvincia === $prov) ? 'selected' : '' ?>><?= htmlspecialchars($prov) ?></option>
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
                        <input type="text" class="form-control" id="telefono" name="telefono" value="<?= $valTelefono ?>" placeholder="Ej: 4717-6295" maxlength="50" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="mail">Email Sucursal</label>
                        <input type="email" class="form-control" id="mail" name="mail" value="<?= $valMail ?>" placeholder="sucursal@xl.com.ar" maxlength="50">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="canal">Canal Comercial</label>
                        <select class="form-select" id="canal" name="canal">
                            <option value="" <?= empty($valCanal) ? 'selected' : '' ?>>Seleccione...</option>
                            <?php foreach ($listaCanales as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= ($valCanal === $c) ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="tipo_local">Tipo de Punto de Venta</label>
                        <select class="form-select" id="tipo_local" name="tipo_local">
                            <option value="" <?= empty($valTipoLocal) ? 'selected' : '' ?>>Seleccione...</option>
                            <?php foreach ($listaTiposLocal as $tipo): ?>
                            <option value="<?= htmlspecialchars($tipo) ?>" <?= ($valTipoLocal === $tipo) ? 'selected' : '' ?>><?= htmlspecialchars($tipo) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="cod_deposi">Cód. Depósito</label>
                        <input type="text" class="form-control" id="cod_deposi" name="cod_deposi" value="<?= $valCodDeposi ?>" placeholder="Ej: 2" maxlength="2">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="nro_suc_madre">N° Sucursal Madre</label>
                        <input type="number" class="form-control" id="nro_suc_madre" name="nro_suc_madre" value="<?= $valSucMadreNro ?>" placeholder="Ej: 2">
                        <div class="form-text-muted">Solo si es anexo o sub-sucursal.</div>
                    </div>
                    <?php if ($es_admin_sistemas): ?>
                    <div class="col-md-3">
                        <label class="form-label" for="fecha_cierre">Fecha de Cierre</label>
                        <input type="date" class="form-control" id="fecha_cierre" name="fecha_cierre" value="<?= $valFechaCierre ?>">
                    </div>
                    <?php endif; ?>

                    <div class="col-12">
                        <label class="form-label" for="horario">Horario de Atención</label>
                        <input type="text" class="form-control text-uppercase" id="horario" name="horario" value="<?= $valHorario ?>" placeholder="Ej: LUN A DOM 10 A 22" maxlength="50">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="mail_grupo_emp">Emails Empresa / Grupo (opcional)</label>
                        <textarea class="form-control" id="mail_grupo_emp" name="mail_grupo_emp" rows="3" placeholder="Ingrese uno por línea o sepárelos por coma o punto y coma"><?= $valMailGrupo ?></textarea>
                        <div class="form-text-muted">Se almacenará normalizado con punto y coma (;) para notificaciones automáticas (máx. 200 caracteres).</div>
                    </div>
                </div>
            </div>

            <!-- Sección 3: Días de Atención -->
            <div class="form-section-header border-top">
                <i class="bi bi-calendar-week-fill"></i> Días de Atención Comercial
            </div>
            <div class="form-section-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <label class="form-label mb-0">Seleccione los días en que el local se encuentra abierto:</label>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDiasLV">Lun a Vie</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDiasLS">Lun a Sáb</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDiasTodos">Todos</button>
                    </div>
                </div>
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

            <!-- Sección 4: Configuración Operativa e Integraciones -->
            <div class="form-section-header border-top">
                <i class="bi bi-toggles"></i> Configuración Operativa e Integraciones
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="switch-group">
                            <div class="switch-label-wrap">
                                <span class="switch-title">Tienda Física Activa (Habilitado)</span>
                                <span class="switch-desc">Local disponible para ventas y visible en Direccionario</span>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="habilitado" name="habilitado" value="1" <?= $valTienda ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

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
                        <div class="switch-group">
                            <div class="switch-label-wrap">
                                <span class="switch-title">Habilitado Contabilidad</span>
                                <span class="switch-desc">Visible en Control de Sucursales aunque el local esté deshabilitado</span>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="habilitado_cont" name="habilitado_cont" value="1" <?= $valHabCont ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="switch-group">
                            <div class="switch-label-wrap">
                                <span class="switch-title">Dashboard PBI</span>
                                <span class="switch-desc">Incluir sucursal en tableros de Power BI</span>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="dashboard_bi" name="dashboard_bi" value="1" <?= $valDashboardBi ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="switch-group">
                            <div class="switch-label-wrap">
                                <span class="switch-title">Sucursal Madre</span>
                                <span class="switch-desc">Indica si la sucursal opera como sucursal principal / madre</span>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="suc_madre" name="suc_madre" value="1" <?= $valEsSucMadre ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="switch-group">
                            <div class="switch-label-wrap">
                                <span class="switch-title">Empresa Ferretería</span>
                                <span class="switch-desc">Marca si pertenece a razón social / anexo ferretería</span>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="empresa_ferreteria" name="empresa_ferreteria" value="1" <?= $valFerreteria ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <?php if ($es_admin_sistemas): ?>
                    <div class="col-md-6">
                        <div class="switch-group">
                            <div class="switch-label-wrap">
                                <span class="switch-title">Factura Electrónica</span>
                                <span class="switch-desc">Habilita facturación electrónica</span>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="factura_elect" name="factura_elect" value="1" <?= $valFacturaElec ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($es_admin_sistemas): ?>
            <!-- Sección 5: Integraciones MercadoPago -->
            <div class="form-section-header border-top">
                <i class="bi bi-credit-card-fill"></i> Integraciones MercadoPago
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="switch-group mb-0">
                            <div class="switch-label-wrap">
                                <span class="switch-title">Integración MercadoPago Madre</span>
                                <span class="switch-desc">QR / Point integrado en caja madre</span>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="integracion_mercadopago_madre" name="integracion_mercadopago_madre" value="1" <?= $valMpMadre ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="switch-group mb-0">
                            <div class="switch-label-wrap">
                                <span class="switch-title">Integración MercadoPago Hija</span>
                                <span class="switch-desc">QR / Point integrado en caja hija</span>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="integracion_mercadopago_hija" name="integracion_mercadopago_hija" value="1" <?= $valMpHija ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección 6: Configuración LAPOS -->
            <div class="form-section-header border-top">
                <i class="bi bi-calculator-fill"></i> Configuración LAPOS
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <div class="switch-group mb-1">
                            <div class="switch-label-wrap">
                                <span class="switch-title">LAPOS Integrado</span>
                                <span class="switch-desc">Terminal de cobro integrada al sistema de facturación</span>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="lapos_integrado" name="lapos_integrado" value="1" <?= $valLaposInt ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="lapos_modelo">Modelo LAPOS</label>
                        <input type="text" class="form-control" id="lapos_modelo" name="lapos_modelo" value="<?= $valLaposMod ?>" maxlength="30" placeholder="Ej: VX520 / Move 2500">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="version_soft_lapos">Versión Software LAPOS</label>
                        <input type="text" class="form-control" id="version_soft_lapos" name="version_soft_lapos" value="<?= $valLaposVer ?>" maxlength="30">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="cable_lapos_integrado">Cable LAPOS Integrado</label>
                        <input type="text" class="form-control" id="cable_lapos_integrado" name="cable_lapos_integrado" value="<?= $valLaposCable ?>" maxlength="30">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="link_pedido">Link Pedido</label>
                        <input type="text" class="form-control" id="link_pedido" name="link_pedido" value="<?= $valLinkPedido ?>" maxlength="50">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="link_sales">Link Sales</label>
                        <input type="text" class="form-control" id="link_sales" name="link_sales" value="<?= $valLinkSales ?>" maxlength="50">
                    </div>
                </div>
            </div>

            <!-- Sección 7: Configuración Técnica -->
            <div class="form-section-header border-top">
                <i class="bi bi-hdd-network-fill"></i> Configuración Técnica
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="base_nombre">Base Nombre</label>
                        <input type="text" class="form-control" id="base_nombre" name="base_nombre" value="<?= $valBaseNombre ?>" maxlength="50" placeholder="Ej: XL_UNICENTER">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="n_llave_tango">N° Llave Tango</label>
                        <input type="text" class="form-control" id="n_llave_tango" name="n_llave_tango" value="<?= $valLlaveTango ?>" maxlength="20">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="conexion_dns">Conexión DNS</label>
                        <input type="text" class="form-control" id="conexion_dns" name="conexion_dns" value="<?= $valConexionDns ?>" maxlength="50" placeholder="Ej: 10.245.243.12,6089">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="conexion_dns_alt">Conexión DNS Alternativa</label>
                        <input type="text" class="form-control" id="conexion_dns_alt" name="conexion_dns_alt" value="<?= $valDnsAlt ?>" maxlength="100" placeholder="Ej: suc.dnsalias.com,6089">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="usuario_dns">Usuario DNS</label>
                        <input type="text" class="form-control" id="usuario_dns" name="usuario_dns" value="<?= $valUsuarioDns ?>" maxlength="30">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="clave_dns">Clave DNS</label>
                        <input type="text" class="form-control" id="clave_dns" name="clave_dns" value="<?= $valClaveDns ?>" maxlength="30">
                    </div>
                </div>
            </div>

            <!-- Sección 8: Configuración TeamViewer y PC -->
            <div class="form-section-header border-top">
                <i class="bi bi-pc-display"></i> Configuración TeamViewer y PC
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="conexion_teamviewer_madre">Conexión TeamViewer Madre</label>
                        <input type="text" class="form-control" id="conexion_teamviewer_madre" name="conexion_teamviewer_madre" value="<?= $valTvMadre ?>" maxlength="30">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="conexion_teamviewer_hija">Conexión TeamViewer Hija</label>
                        <input type="text" class="form-control" id="conexion_teamviewer_hija" name="conexion_teamviewer_hija" value="<?= $valTvHija ?>" maxlength="30">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="clave_teamviewer">Clave TeamViewer</label>
                        <input type="text" class="form-control" id="clave_teamviewer" name="clave_teamviewer" value="<?= $valClaveTv ?>" maxlength="30">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="usuario_pc_madre">Usuario PC Madre</label>
                        <input type="text" class="form-control" id="usuario_pc_madre" name="usuario_pc_madre" value="<?= $valUserPcMadre ?>" maxlength="30">
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Footer / Botones -->
            <div class="card-footer-box">
                <div class="d-flex flex-wrap gap-2">
                    <a href="index.php" class="btn-cancel">
                        <i class="bi bi-x-lg"></i> Cancelar
                    </a>
                    <?php if ($esEdicion && $puede_crear): ?>
                    <a href="AltaSucursal.php?clonar=<?= (int)$idSucursal ?>" class="btn-cancel">
                        <i class="bi bi-copy"></i> Clonar Sucursal
                    </a>
                    <?php endif; ?>
                    <?php if ($esEdicion && $puede_eliminar): ?>
                    <button type="button" class="btn-danger-outline" id="btnDeshabilitarFooter" data-id="<?= (int)$idSucursal ?>" data-nombre="<?= $valDesc ?>">
                        <i class="bi bi-trash3"></i> Deshabilitar Sucursal
                    </button>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn-save" id="btnGuardar">
                    <i class="bi bi-check2-circle"></i>
                    <?php if ($esEdicion): ?>
                        Guardar Cambios
                    <?php elseif ($esClonar): ?>
                        Crear Sucursal Clonada
                    <?php else: ?>
                        Guardar Sucursal
                    <?php endif; ?>
                </button>
            </div>

        </div>

    </form>

</div>

<?php if ($esEdicion): ?>
<!-- Modal Historial de Ediciones -->
<div class="modal fade" id="modalHistorialSucursal" tabindex="-1" aria-labelledby="modalHistorialLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px; overflow:hidden;">
            <div class="modal-header" style="background:#fafbfc; border-bottom:1px solid #e5e7eb;">
                <h5 class="modal-title fw-bold" id="modalHistorialLabel" style="font-family:var(--font-display); font-size:1.05rem;">
                    <i class="bi bi-clock-history me-2" style="color:var(--primary);"></i>Historial de Ediciones · Sucursal #<?= (int)$idSucursal ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="historialModalBody" style="background:#f8fafc;">
                <div class="text-center text-muted py-4">Cargando historial...</div>
            </div>
            <div class="modal-footer" style="background:#fafbfc; border-top:1px solid #e5e7eb;">
                <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    function setDias(activos) {
        var mapa = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        mapa.forEach(function(dia) {
            var $chk = $('input[name="' + dia + '"]');
            $chk.prop('checked', activos.indexOf(dia) !== -1).trigger('change');
        });
    }
    $('#btnDiasLV').on('click', function() { setDias(['lunes','martes','miercoles','jueves','viernes']); });
    $('#btnDiasLS').on('click', function() { setDias(['lunes','martes','miercoles','jueves','viernes','sabado']); });
    $('#btnDiasTodos').on('click', function() { setDias(['lunes','martes','miercoles','jueves','viernes','sabado','domingo']); });

    // Mayúsculas automáticas en campos de texto clave
    $('#cod_client, #desc_sucursal, #direccion, #localidad, #horario').on('input', function() {
        var start = this.selectionStart;
        var end = this.selectionEnd;
        this.value = this.value.toUpperCase();
        if (typeof start === 'number' && typeof end === 'number') {
            this.setSelectionRange(start, end);
        }
    });

    // Normalizar emails antes de enviar y validar formulario Bootstrap 5
    function normalizeEmails(text) {
        if (!text) return '';
        return text.split(/[\s,;]+/).filter(Boolean).join(';');
    }

    var confirmedSubmit = false;
    $('#formAltaSucursal').on('submit', function(e) {
        var form = this;
        var $mailGrp = $('#mail_grupo_emp');
        if ($mailGrp.length && $mailGrp.val()) {
            $mailGrp.val(normalizeEmails($mailGrp.val()));
        }

        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
            $(form).addClass('was-validated');
            var firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) firstInvalid.focus();
            return;
        }

        if (confirmedSubmit) return;

        <?php if ($esEdicion || $esClonar): ?>
        e.preventDefault();
        Swal.fire({
            title: <?= $esClonar ? "'¿Crear sucursal clonada?'" : "'¿Guardar cambios?'" ?>,
            text: <?= $esClonar ? "'Se creará una nueva sucursal con los datos ingresados.'" : "'Se actualizarán los datos de la sucursal en el Direccionario.'" ?>,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0D5C63',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (result.isConfirmed) {
                confirmedSubmit = true;
                form.submit();
            }
        });
        <?php endif; ?>
    });

    // Deshabilitar / eliminar sucursal desde edición
    $('#btnDeshabilitarHeader, #btnDeshabilitarFooter').on('click', function() {
        var id = $(this).data('id');
        var nombre = $(this).data('nombre') || ('#' + id);
        Swal.fire({
            title: '¿Deshabilitar sucursal?',
            text: '¿Está seguro de deshabilitar la sucursal #' + id + ' (' + nombre + ')?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, deshabilitar',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: 'controller/direccionario_ajax.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'eliminar', nro_sucursal: id },
                success: function(res) {
                    if (res && res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucursal deshabilitada',
                            text: res.message || 'Se deshabilitó correctamente.',
                            confirmButtonColor: '#0D5C63'
                        }).then(function() {
                            window.location.href = 'AltaSucursal.php?id=' + id;
                        });
                    } else {
                        Swal.fire('Error', (res && res.message) || 'No se pudo deshabilitar la sucursal.', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Error de conexión al deshabilitar la sucursal.', 'error');
                }
            });
        });
    });

    // Modal Historial de ediciones
    function escHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    $('#btnVerHistorial').on('click', function() {
        var nro = $(this).data('id');
        var $body = $('#historialModalBody');
        $body.html('<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm me-2"></div>Cargando historial...</div>');
        var modalEl = document.getElementById('modalHistorialSucursal');
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        $.ajax({
            url: 'controller/direccionario_ajax.php',
            type: 'GET',
            dataType: 'json',
            data: { action: 'historial', nro_sucursal: nro, limit: 50 },
            success: function(res) {
                if (!res || !res.success) {
                    $body.html('<div class="alert alert-danger mb-0">' + escHtml((res && res.message) || 'No se pudo cargar el historial.') + '</div>');
                    return;
                }
                var items = res.historial || [];
                if (!items.length) {
                    $body.html('<div class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Todavía no hay registros de cambios para esta sucursal.</div>');
                    return;
                }
                var html = '<div class="d-flex flex-column gap-3">';
                items.forEach(function(h) {
                    var badgeClass = 'bg-secondary';
                    if (h.accion === 'EDICION') badgeClass = 'bg-primary';
                    else if (h.accion === 'ALTA') badgeClass = 'bg-success';
                    else if (h.accion === 'CLONACION') badgeClass = 'bg-info text-dark';
                    else if (h.accion === 'DESHABILITACION') badgeClass = 'bg-danger';

                    var usuarioTxt = h.nombre_usuario || h.username || 'Usuario desconocido';
                    if (h.username && h.nombre_usuario && h.username.toLowerCase() !== h.nombre_usuario.toLowerCase()) {
                        usuarioTxt += ' (@' + h.username + ')';
                    }
                    var metaSub = [h.rol_nombre, h.sector_nombre, h.ip_address ? ('IP ' + h.ip_address) : ''].filter(Boolean).join(' · ');

                    var cambiosHtml = '';
                    var keys = Object.keys(h.campos_modificados || {});
                    if (keys.length) {
                        cambiosHtml += '<div class="table-responsive mt-2"><table class="table table-sm table-bordered mb-0 bg-white" style="font-size:0.8rem;"><thead><tr class="table-light"><th>Campo</th><th>Valor anterior</th><th>Valor nuevo</th></tr></thead><tbody>';
                        keys.forEach(function(k) {
                            var c = h.campos_modificados[k] || {};
                            var ant = (c.anterior === null || c.anterior === undefined || c.anterior === '') ? '<em>vacío</em>' : escHtml(c.anterior);
                            var nue = (c.nuevo === null || c.nuevo === undefined || c.nuevo === '') ? '<em>vacío</em>' : escHtml(c.nuevo);
                            cambiosHtml += '<tr><td class="fw-semibold">' + escHtml(k) + '</td><td class="text-muted">' + ant + '</td><td class="fw-medium">' + nue + '</td></tr>';
                        });
                        cambiosHtml += '</tbody></table></div>';
                    }

                    html += '<div class="card border-0 shadow-sm" style="border-radius:12px;">'
                         +    '<div class="card-body p-3">'
                         +      '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">'
                         +        '<div><span class="badge ' + badgeClass + ' me-2">' + escHtml(h.accion) + '</span><strong>' + escHtml(usuarioTxt) + '</strong></div>'
                         +        '<small class="text-muted"><i class="bi bi-calendar3 me-1"></i>' + escHtml(h.fecha_hora) + '</small>'
                         +      '</div>'
                         +      (metaSub ? '<div class="text-muted mb-1" style="font-size:0.76rem;">' + escHtml(metaSub) + '</div>' : '')
                         +      '<div style="font-size:0.85rem;">' + escHtml(h.descripcion) + '</div>'
                         +      cambiosHtml
                         +    '</div>'
                         +  '</div>';
                });
                html += '</div>';
                $body.html(html);
            },
            error: function() {
                $body.html('<div class="alert alert-danger mb-0">Error de conexión al consultar el historial.</div>');
            }
        });
    });
});
</script>

</body>
</html>
