<?php
// src/Models/TransferenciaModel.php

class TransferenciaModel {

    /**
     * Buscar artículos por código (autocomplete, mínimo 3 caracteres).
     * Usa XL-TANGO -> LAKER_SA -> MAESTRO_ARTICULOS_CODIGOS_ACTIVOS
     */
    public static function buscarArticulos($termino) {
        $pdo = Database::getConnectionTango();
        $sql = "SELECT TOP 20 COD_ARTICU, DESCRIPCION, RUBRO 
                FROM MAESTRO_ARTICULOS_CODIGOS_ACTIVOS 
                WHERE COD_ARTICU LIKE :termino 
                ORDER BY COD_ARTICU";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':termino' => '%' . $termino . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener stock disponible de un artículo.
     * Usa XL-TANGO -> LAKER_SA -> STOCK_CENTRAL_DISPONIBLE
     */
    public static function getStockDisponible($codArticulo) {
        $pdo = Database::getConnectionTango();
        $sql = "SELECT STOCK_DISPONIBLE 
                FROM STOCK_CENTRAL_DISPONIBLE 
                WHERE COD_ARTICU = :cod";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':cod' => $codArticulo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (float)$row['STOCK_DISPONIBLE'] : 0;
    }

    /**
     * Validar stock para múltiples artículos.
     * Retorna array con artículos que no tienen stock suficiente.
     */
    public static function validarStock($articulos) {
        $errores = [];
        foreach ($articulos as $art) {
            $stockDisponible = self::getStockDisponible($art['cod_articu']);
            if ((float)$art['cantidad'] > $stockDisponible) {
                $errores[] = [
                    'cod_articu' => $art['cod_articu'],
                    'descripcion' => $art['descripcion'],
                    'cantidad_solicitada' => (float)$art['cantidad'],
                    'stock_disponible' => $stockDisponible
                ];
            }
        }
        return $errores;
    }

    /**
     * Crear una nueva solicitud de transferencia.
     * Guarda en XL-APPS -> sistemas -> FP_SOLICITUDES_TRANSFERENCIAS
     */
    public static function crearSolicitud($datos) {
        $pdo = Database::getConnectionSistemas();
        
        $sql = "INSERT INTO FP_SOLICITUDES_TRANSFERENCIAS 
                (FECHA, TIPO_TRANSFERENCIA, USUARIO, ORIGEN, DESTINO, ESTADO, OBSERVACIONES, ARTICULOS, CREADO_POR, CREADO_FECHA) 
                VALUES 
                (:fecha, :tipo, :usuario, :origen, :destino, 'PENDIENTE', :observaciones, :articulos, :creado_por, GETDATE())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':fecha' => $datos['fecha'],
            ':tipo' => $datos['tipo_transferencia'],
            ':usuario' => $datos['usuario'],
            ':origen' => $datos['origen'],
            ':destino' => $datos['destino'],
            ':observaciones' => $datos['observaciones'],
            ':articulos' => json_encode($datos['articulos'], JSON_UNESCAPED_UNICODE),
            ':creado_por' => $datos['creado_por']
        ]);

        return $pdo->lastInsertId();
    }

    /**
     * Obtener todas las solicitudes.
     */
    public static function obtenerSolicitudes($estado = null) {
        $pdo = Database::getConnectionSistemas();
        
        $sql = "SELECT * FROM FP_SOLICITUDES_TRANSFERENCIAS";
        $params = [];

        if ($estado) {
            $sql .= " WHERE ESTADO = :estado";
            $params[':estado'] = $estado;
        }

        $sql .= " ORDER BY ID DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener una solicitud por ID.
     */
    public static function obtenerSolicitudPorId($id) {
        $pdo = Database::getConnectionSistemas();
        $sql = "SELECT * FROM FP_SOLICITUDES_TRANSFERENCIAS WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar N° Transferencia y finalizar.
     */
    public static function finalizarSolicitud($id, $nroTransferencia) {
        $pdo = Database::getConnectionSistemas();
        $sql = "UPDATE FP_SOLICITUDES_TRANSFERENCIAS 
                SET NRO_TRANSFERENCIA = :nro, 
                    ESTADO = 'FINALIZADA',
                    FINALIZADO_FECHA = GETDATE()
                WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nro' => $nroTransferencia,
            ':id' => $id
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Actualizar solo el N° de transferencia (sin finalizar).
     */
    public static function actualizarNroTransferencia($id, $nroTransferencia) {
        $pdo = Database::getConnectionSistemas();
        $sql = "UPDATE FP_SOLICITUDES_TRANSFERENCIAS 
                SET NRO_TRANSFERENCIA = :nro
                WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nro' => $nroTransferencia,
            ':id' => $id
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Eliminar una solicitud por ID.
     */
    public static function eliminarSolicitud($id) {
        $pdo = Database::getConnectionSistemas();
        $sql = "DELETE FROM FP_SOLICITUDES_TRANSFERENCIAS WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    // --- MÉTODOS DE CONFIGURACIÓN DINÁMICA ---

    /**
     * Obtener opciones de configuración por categoría.
     */
    public static function getConfig($categoria) {
        $pdo = Database::getConnectionSistemas();
        $sql = "SELECT * FROM FP_SOLICITUDES_TRANSFERENCIAS_CONFIG 
                WHERE CATEGORIA = :cat AND ACTIVO = 1 
                ORDER BY VALOR ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':cat' => $categoria]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener toda la configuración (para gestión).
     */
    public static function getAllConfig() {
        $pdo = Database::getConnectionSistemas();
        $sql = "SELECT * FROM FP_SOLICITUDES_TRANSFERENCIAS_CONFIG ORDER BY CATEGORIA, VALOR";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guardar nueva opción de configuración.
     */
    public static function guardarConfig($categoria, $valor) {
        $pdo = Database::getConnectionSistemas();
        $sql = "INSERT INTO FP_SOLICITUDES_TRANSFERENCIAS_CONFIG (CATEGORIA, VALOR) VALUES (:cat, :val)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':cat' => $categoria, ':val' => $valor]);
    }

    /**
     * Eliminar opción de configuración.
     */
    public static function eliminarConfig($id) {
        $pdo = Database::getConnectionSistemas();
        $sql = "DELETE FROM FP_SOLICITUDES_TRANSFERENCIAS_CONFIG WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Cambiar estado activo/inactivo de una opción.
     */
    public static function toggleConfig($id) {
        $pdo = Database::getConnectionSistemas();
        $sql = "UPDATE FP_SOLICITUDES_TRANSFERENCIAS_CONFIG 
                SET ACTIVO = CASE WHEN ACTIVO = 1 THEN 0 ELSE 1 END 
                WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
