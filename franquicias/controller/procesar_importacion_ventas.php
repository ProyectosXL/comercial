<?php
// Incluir configuración de sesiones y autenticación
require_once '../config_sesiones_fixed.php';
require_once '../class/AuthFranquicia.php';
require_once '../class/DatabaseConfig.php';

// Verificar autenticación
if (!AuthFranquicia::verificarSesion()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar que sea usuario de franquicia con acceso especial
if (($_SESSION['user_role'] ?? '') !== 'franquicia' || !AuthFranquicia::verificarAccesoEspecial()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// Obtener datos de la solicitud
$input = json_decode(file_get_contents('php://input'), true);
$datos = $input['datos'] ?? [];

if (empty($datos)) {
    echo json_encode(['success' => false, 'message' => 'No hay datos para procesar']);
    exit;
}

// Obtener información de la sesión
$usuario = $_SESSION['usuario_franquicia'] ?? '';
$nroSucursal = $_SESSION['franquicia_id'] ?? ''; // Este es el NRO_SUCURS que necesitamos

if (empty($nroSucursal)) {
    echo json_encode(['success' => false, 'message' => 'No se pudo obtener el número de sucursal']);
    exit;
}

// Calcular el período actual (YYYYMM)
$periodoActual = date('Ym'); // Formato: 202509

try {
    // Conectar a ambas bases de datos
    $conexionLaker = DatabaseConfig::conectarLakerSA(); // Para validar artículos
    $conexionSistemas = DatabaseConfig::conectarSistemas(); // Para guardar ventas

    // Iniciar transacción en sistemas
    $conexionSistemas->beginTransaction();

    // Validar que todos los artículos sean válidos en LAKER_SA
    $articulosValidar = array_column($datos, 'articulo');
    $placeholders = str_repeat('?,', count($articulosValidar) - 1) . '?';
    
    $sqlValidar = "SELECT COD_ARTICU FROM STA11 WHERE COD_ARTICU IN ($placeholders) AND COD_ARTICU LIKE '[XO]%'";
    $stmtValidar = $conexionLaker->prepare($sqlValidar);
    $stmtValidar->execute($articulosValidar);
    $articulosValidos = $stmtValidar->fetchAll(PDO::FETCH_COLUMN);

    // Verificar que todos los artículos sean válidos
    foreach ($datos as $item) {
        if (!in_array($item['articulo'], $articulosValidos)) {
            throw new Exception("Artículo inválido encontrado: " . $item['articulo']);
        }
    }

    // Crear la tabla si no existe en sistemas y asegurar que tenga todas las columnas
    $sqlCrearTabla = "
        IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='ventas_franquicias_sin_tango' AND xtype='U')
        BEGIN
            CREATE TABLE ventas_franquicias_sin_tango (
                id int IDENTITY(1,1) PRIMARY KEY,
                nro_sucursal varchar(10) NOT NULL,
                periodo varchar(6) NOT NULL,
                codigo_articulo varchar(50) NOT NULL,
                rubro varchar(100) NULL,
                categoria_padre varchar(100) NULL,
                cantidad int NOT NULL,
                fecha_carga datetime DEFAULT GETDATE(),
                usuario_carga varchar(50) NULL
            )
        END
        
        -- Agregar columnas que podrían faltar si la tabla ya existía
        IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ventas_franquicias_sin_tango' AND COLUMN_NAME = 'id')
        BEGIN
            -- Solo agregar la columna sin PRIMARY KEY para evitar conflictos
            -- Si la tabla no tiene PK, se agregará después
            ALTER TABLE ventas_franquicias_sin_tango ADD id int IDENTITY(1,1)
        END
        
        IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ventas_franquicias_sin_tango' AND COLUMN_NAME = 'usuario_carga')
        BEGIN
            ALTER TABLE ventas_franquicias_sin_tango ADD usuario_carga varchar(50) NULL
        END
        
        IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ventas_franquicias_sin_tango' AND COLUMN_NAME = 'rubro')
        BEGIN
            ALTER TABLE ventas_franquicias_sin_tango ADD rubro varchar(100) NULL
        END
        
        IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ventas_franquicias_sin_tango' AND COLUMN_NAME = 'categoria_padre')
        BEGIN
            ALTER TABLE ventas_franquicias_sin_tango ADD categoria_padre varchar(100) NULL
        END
    ";
    $conexionSistemas->exec($sqlCrearTabla);

    // Eliminar registros existentes de esta sucursal para el período actual (reemplazar datos)
    $sqlEliminar = "DELETE FROM ventas_franquicias_sin_tango WHERE nro_sucursal = ? AND periodo = ?";
    $stmtEliminar = $conexionSistemas->prepare($sqlEliminar);
    $stmtEliminar->execute([$nroSucursal, $periodoActual]);

    // Preparar query para insertar nuevos datos - usar solo las columnas que sabemos que existen
    $sqlInsertar = "INSERT INTO ventas_franquicias_sin_tango 
                    (nro_sucursal, periodo, codigo_articulo, cantidad, fecha_carga, rubro, categoria_padre, usuario_carga) 
                    VALUES (?, ?, ?, ?, GETDATE(), ?, ?, ?)";
    $stmtInsertar = $conexionSistemas->prepare($sqlInsertar);

    $procesados = 0;
    foreach ($datos as $item) {
        $cantidad = intval($item['cantidad']);
        if ($cantidad > 0) { // Solo insertar cantidades positivas
            $codigoArticulo = $item['articulo'];
            
            // Obtener rubro y categoría de sistemas
            $rubro = 'Sin rubro';
            $categoria = 'Sin categoría';
            
            try {
                $sqlRubroCategoria = "SELECT RUBRO, CATEGORIA_PADRE 
                                     FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA 
                                     WHERE COD_ARTICU = ?";
                $stmtRubroCategoria = $conexionSistemas->prepare($sqlRubroCategoria);
                $stmtRubroCategoria->execute([$codigoArticulo]);
                $datosAdicionales = $stmtRubroCategoria->fetch(PDO::FETCH_ASSOC);
                
                if ($datosAdicionales) {
                    $rubro = trim($datosAdicionales['RUBRO']) ?: 'Sin rubro';
                    $categoria = trim($datosAdicionales['CATEGORIA_PADRE']) ?: 'Sin categoría';
                }
            } catch (Exception $e) {
                error_log("Error obteniendo rubro/categoría para $codigoArticulo: " . $e->getMessage());
            }
            
            // Insertar el registro
            $stmtInsertar->execute([
                $nroSucursal,
                $periodoActual,
                $codigoArticulo,
                $cantidad,
                $rubro,
                $categoria,
                $usuario
            ]);
            $procesados++;
        }
    }

    // Confirmar transacción
    $conexionSistemas->commit();

    error_log("DEBUG IMPORTACION: Procesados $procesados registros para sucursal $nroSucursal en período $periodoActual");

    echo json_encode([
        'success' => true,
        'message' => 'Importación completada exitosamente',
        'procesados' => $procesados,
        'nro_sucursal' => $nroSucursal,
        'periodo' => $periodoActual,
        'tabla' => 'ventas_franquicias_sin_tango'
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($conexionSistemas) && $conexionSistemas->inTransaction()) {
        $conexionSistemas->rollback();
    }
    
    error_log("Error procesando importación: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar importación: ' . $e->getMessage()
    ]);
}
?>
