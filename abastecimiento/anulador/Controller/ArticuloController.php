<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../Class/Articulo.php';
$articuloHandler = new Articulo();

$action = isset($_GET['action']) ? $_GET['action'] : '';
$pais = isset($_POST['pais']) ? $_POST['pais'] : 'AR'; // AR o UY

header('Content-Type: application/json');

switch ($action) {
    case 'obtener_sugerencias':
        obtenerSugerencias($articuloHandler, $pais);
        break;

    case 'validar_lote':
        validarLote($articuloHandler, $pais);
        break;

    case 'inhabilitar':
        inhabilitarLote($articuloHandler, $pais);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no permitida']);
        break;
}

function validarLote($handler, $pais) {
    $articulosRaw = isset($_POST['articulos']) ? $_POST['articulos'] : '[]';
    $articulos = json_decode($articulosRaw, true);

    if (!is_array($articulos)) {
        echo json_encode([
            'success' => false,
            'message' => '❌ Formato de datos de lote de artículos inválido'
        ]);
        return;
    }

    $articulos_data = [];
    $cant_disponibles = 0;
    $cant_ya_inhabilitados = 0;
    $cant_inexistentes = 0;

    foreach ($articulos as $art) {
        $cod_articu = isset($art['COD_ARTICU']) ? trim($art['COD_ARTICU']) : '';
        if ($cod_articu === '') {
            continue;
        }

        try {
            $info = $handler->validarArticulo($cod_articu, $pais);
            if ($info === null) {
                $estado = 'Artículo inexistente';
                $valido = false;
                $descripcion = '---';
                $cant_inexistentes++;
            } else {
                $descripcion = isset($info['DESCRIPCIO']) ? trim($info['DESCRIPCIO']) : '';
                $perfil = isset($info['PERFIL']) ? trim($info['PERFIL']) : '';

                if ($perfil === 'N') {
                    $estado = 'Ya se encuentra inhabilitado';
                    $valido = false;
                    $cant_ya_inhabilitados++;
                } else {
                    $estado = 'Disponible para inhabilitar';
                    $valido = true;
                    $cant_disponibles++;
                }
            }

            $articulos_data[] = [
                'cod_articu' => $cod_articu,
                'descripcion' => $descripcion,
                'estado' => $estado,
                'valido' => $valido
            ];

        } catch (Exception $e) {
            $articulos_data[] = [
                'cod_articu' => $cod_articu,
                'descripcion' => 'Error al validar',
                'estado' => 'Error: ' . $e->getMessage(),
                'valido' => false
            ];
            $cant_inexistentes++; // clasificar como inexistente/error
        }
    }

    echo json_encode([
        'success' => true,
        'articulos_data' => $articulos_data,
        'cant_disponibles' => $cant_disponibles,
        'cant_ya_inhabilitados' => $cant_ya_inhabilitados,
        'cant_inexistentes' => $cant_inexistentes
    ]);
}

function inhabilitarLote($handler, $pais) {
    $articulosRaw = isset($_POST['articulos']) ? $_POST['articulos'] : '[]';
    $articulos = json_decode($articulosRaw, true);

    if (!is_array($articulos)) {
        echo json_encode([
            'success' => false,
            'message' => '❌ Formato de datos inválido'
        ]);
        return;
    }

    $total_procesados = count($articulos);
    $inhabilitados_exito = 0;
    $omitidos = 0;
    $inexistentes = 0;
    $errores = 0;

    foreach ($articulos as $art) {
        $cod_articu = isset($art['cod_articu']) ? trim($art['cod_articu']) : '';
        $estado_previo = isset($art['estado']) ? trim($art['estado']) : '';

        if ($estado_previo === 'Ya se encuentra inhabilitado') {
            $omitidos++;
            continue;
        }

        if ($estado_previo === 'Artículo inexistente') {
            $inexistentes++;
            continue;
        }

        if (empty($cod_articu)) {
            $errores++;
            continue;
        }

        try {
            $resultado = $handler->inhabilitarArticulo($cod_articu, $pais);
            if ($resultado) {
                $inhabilitados_exito++;
            } else {
                $errores++;
            }
        } catch (Exception $e) {
            $errores++;
        }
    }

    echo json_encode([
        'success' => true,
        'total_procesados' => $total_procesados,
        'inhabilitados' => $inhabilitados_exito,
        'omitidos' => $omitidos,
        'inexistentes' => $inexistentes,
        'errores' => $errores
    ]);
}

function obtenerSugerencias($handler, $pais) {
    try {
        $sugerencias = $handler->obtenerSugerenciasInhabilitacion($pais);
        if ($sugerencias === false) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener sugerencias de la base de datos']);
            return;
        }
        echo json_encode([
            'success' => true,
            'sugerencias' => $sugerencias
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
