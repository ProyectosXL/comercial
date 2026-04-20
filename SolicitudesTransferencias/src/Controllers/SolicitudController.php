<?php
// src/Controllers/SolicitudController.php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/TransferenciaModel.php';
require_once __DIR__ . '/../Services/NotificationService.php';

class SolicitudController extends BaseController {

    /**
     * Listar todas las solicitudes (vista principal).
     */
    public function index() {
        $pageTitle = 'Solicitudes de Transferencias';
        $solicitudes = TransferenciaModel::obtenerSolicitudes();
        require_once __DIR__ . '/../Views/solicitud/index.php';
    }

    /**
     * Mostrar formulario para crear nueva solicitud.
     */
    public function crear() {
        $pageTitle = 'Nueva Solicitud';
        $tipos = TransferenciaModel::getConfig('TIPO_TRANSFERENCIA');
        $usuarios = TransferenciaModel::getConfig('USUARIO');
        $sucursales = TransferenciaModel::getConfig('SUCURSAL');
        require_once __DIR__ . '/../Views/solicitud/crear.php';
    }

    /**
     * Ver detalle de una solicitud.
     */
    public function detalle($id) {
        $pageTitle = 'Detalle Solicitud #' . $id;
        $solicitud = TransferenciaModel::obtenerSolicitudPorId($id);
        if (!$solicitud) {
            die("Solicitud no encontrada.");
        }
        $isLogistica = (isset($_GET['mode']) && $_GET['mode'] === 'logistics');
        require_once __DIR__ . '/../Views/solicitud/detalle.php';
    }

    /**
     * API: Buscar artículos por código (AJAX autocomplete).
     */
    public function buscarArticulos() {
        header('Content-Type: application/json');
        $termino = isset($_GET['q']) ? trim($_GET['q']) : '';
        
        if (strlen($termino) < 3) {
            echo json_encode([]);
            return;
        }

        try {
            $articulos = TransferenciaModel::buscarArticulos($termino);
            echo json_encode($articulos);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * API: Validar stock de un artículo (AJAX).
     */
    public function validarStock() {
        header('Content-Type: application/json');
        $codArticulo = isset($_GET['cod']) ? trim($_GET['cod']) : '';
        
        if (empty($codArticulo)) {
            echo json_encode(['stock' => 0]);
            return;
        }

        try {
            $stock = TransferenciaModel::getStockDisponible($codArticulo);
            echo json_encode(['stock' => $stock]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * POST: Guardar nueva solicitud.
     */
    public function guardar() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos inválidos']);
            return;
        }

        // Validar campos requeridos
        $required = ['tipo_transferencia', 'usuario', 'origen', 'destino', 'articulos'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "El campo {$field} es obligatorio"]);
                return;
            }
        }

        if (empty($data['articulos']) || !is_array($data['articulos'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Debe agregar al menos un artículo']);
            return;
        }

        // Validar stock para cada artículo
        $erroresStock = TransferenciaModel::validarStock($data['articulos']);
        if (!empty($erroresStock)) {
            http_response_code(400);
            $mensajes = [];
            foreach ($erroresStock as $err) {
                $mensajes[] = "Artículo {$err['cod_articu']} ({$err['descripcion']}): solicitado {$err['cantidad_solicitada']}, disponible {$err['stock_disponible']}";
            }
            echo json_encode([
                'error' => 'Stock insuficiente',
                'detalle' => $mensajes,
                'articulos_sin_stock' => $erroresStock
            ]);
            return;
        }

        try {
            $datos = [
                'fecha' => date('Y-m-d H:i:s'),
                'tipo_transferencia' => $data['tipo_transferencia'],
                'usuario' => $data['usuario'],
                'origen' => $data['origen'],
                'destino' => $data['destino'],
                'observaciones' => $data['observaciones'] ?? '',
                'articulos' => $data['articulos'],
                'creado_por' => $_SESSION['user']['name'] ?? 'Sistema'
            ];

            $id = TransferenciaModel::crearSolicitud($datos);

            // Enviar email de notificación
            try {
                $solicitudCreada = TransferenciaModel::obtenerSolicitudPorId($id);
                $notificador = new NotificationService();
                $notificador->notificarNuevaSolicitud($solicitudCreada);
            } catch (\Exception $e) {
                error_log("Error enviando email de nueva solicitud: " . $e->getMessage());
            }

            echo json_encode([
                'success' => true,
                'message' => "Solicitud #{$id} creada exitosamente",
                'id' => $id
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al crear la solicitud: ' . $e->getMessage()]);
        }
    }

    /**
     * POST: Finalizar solicitud (agregar N° transferencia y cambiar estado).
     */
    public function finalizar() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id']) || empty($data['nro_transferencia'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID y N° Transferencia son obligatorios']);
            return;
        }

        try {
            $resultado = TransferenciaModel::finalizarSolicitud($data['id'], $data['nro_transferencia']);

            if ($resultado) {
                // Enviar email de notificación
                try {
                    $solicitudFinalizada = TransferenciaModel::obtenerSolicitudPorId($data['id']);
                    $notificador = new NotificationService();
                    $notificador->notificarSolicitudFinalizada($solicitudFinalizada);
                } catch (\Exception $e) {
                    error_log("Error enviando email de solicitud finalizada: " . $e->getMessage());
                }

                echo json_encode([
                    'success' => true,
                    'message' => "Solicitud #{$data['id']} finalizada exitosamente"
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Solicitud no encontrada']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al finalizar: ' . $e->getMessage()]);
        }
    }

    /**
     * POST: Actualizar N° Transferencia sin finalizar.
     */
    public function actualizarNro() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id']) || empty($data['nro_transferencia'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID y N° Transferencia son obligatorios']);
            return;
        }

        try {
            TransferenciaModel::actualizarNroTransferencia($data['id'], $data['nro_transferencia']);
            echo json_encode(['success' => true, 'message' => 'N° Transferencia actualizado']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * POST: Eliminar una solicitud.
     */
    public function eliminar() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de solicitud requerido']);
            return;
        }

        try {
            $success = TransferenciaModel::eliminarSolicitud($id);
            echo json_encode(['success' => $success]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
