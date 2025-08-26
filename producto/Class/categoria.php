<?php

class Categoria
{
    private $cid_central;

    function __construct(){
        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/abastecimiento/Class/Conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');
    } 

    public function traerRubros() {
        $sql = "SELECT * FROM RO_T_RUBROS_CODIFICACION ORDER BY DESC_RUBRO ASC";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        try {
            $rows = array();
    
            while($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }
    
            return $rows;

        } catch (\Throwable $th) {
            error_log("Error en traerRubros: " . print_r($th, true));
            return [];
        }     
    }

    public function traerVistaRubroCategoriaCodificacion($sigla) {
        // CORREGIDO: Consultar directamente las tablas con JOIN para obtener el campo ACTIVO
        $sql = "SELECT 
                    r.SIGLA as RUBRO,
                    r.DESC_RUBRO,
                    c.CATEGORIA,
                    c.DESC_CATEGORIA,
                    c.DESC_CATEGORIA_PADRE,
                    ISNULL(c.ACTIVO, 1) AS ESTADO_ACTIVO
                FROM RO_T_RUBROS_CODIFICACION r
                INNER JOIN SJ_CATEGORIAS c ON r.SIGLA = c.RUBRO
                WHERE r.SIGLA = ?
                ORDER BY c.CATEGORIA ASC";

        $stmt = sqlsrv_prepare($this->cid_central, $sql, array($sigla));
        
        if (!$stmt) {
            error_log("Error preparando consulta: " . print_r(sqlsrv_errors(), true));
            return [];
        }
        
        $result = sqlsrv_execute($stmt);
        
        if (!$result) {
            error_log("Error ejecutando consulta: " . print_r(sqlsrv_errors(), true));
            return [];
        }

        try {
            $rows = array();
    
            while($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }
    
            return $rows;

        } catch (\Throwable $th) {
            error_log("Error en traerVistaRubroCategoriaCodificacion: " . print_r($th, true));
            return [];
        }     
    }

    public function insertarNuevaCategoria($codCategoria, $descCategoria, $descCategoriaPadre, $siglaRubro) {
        // Verificar si ya existe
        $checkSql = "SELECT 1 FROM SJ_CATEGORIAS WHERE CATEGORIA = ? AND RUBRO = ?";
        $checkParams = array($codCategoria, $siglaRubro);

        try {
            $checkStmt = sqlsrv_prepare($this->cid_central, $checkSql, $checkParams);
            
            if (!$checkStmt) {
                throw new Exception("Error al preparar verificación: " . print_r(sqlsrv_errors(), true));
            }
            
            $checkResult = sqlsrv_execute($checkStmt);
            
            if (!$checkResult) {
                throw new Exception("Error al ejecutar verificación: " . print_r(sqlsrv_errors(), true));
            }
   
            if (sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC) !== null) {
                return false; // Ya existe
            }

            // Insertar nueva categoría
            $insertSql = "INSERT INTO SJ_CATEGORIAS (CATEGORIA, DESC_CATEGORIA, DESC_CATEGORIA_PADRE, RUBRO) VALUES (?, ?, ?, ?)";
            $insertParams = array($codCategoria, $descCategoria, $descCategoriaPadre, $siglaRubro);
                            
            $stmt = sqlsrv_prepare($this->cid_central, $insertSql, $insertParams);
            
            if (!$stmt) {
                throw new Exception("Error al preparar inserción: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_execute($stmt);
            
            if (!$result) {
                throw new Exception("Error al ejecutar inserción: " . print_r(sqlsrv_errors(), true));
            }
            
            return true;

        } catch (\Throwable $th) {
            error_log("Error en insertarNuevaCategoria: " . print_r($th, true));
            return false;
        }
    }

    public function actualizarDescripcionCategoria($descCategoria, $descCategoriaPadre, $siglaRubro, $codCategoria) {
        $sql = "UPDATE SJ_CATEGORIAS SET DESC_CATEGORIA = ?, DESC_CATEGORIA_PADRE = ? WHERE CATEGORIA = ? AND RUBRO = ?";
        $params = array($descCategoria, $descCategoriaPadre, $codCategoria, $siglaRubro);

        try {
            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
            
            if (!$stmt) {
                throw new Exception("Error al preparar actualización: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_execute($stmt);
            
            if (!$result) {
                throw new Exception("Error al ejecutar actualización: " . print_r(sqlsrv_errors(), true));
            }
            
            return true;

        } catch (\Throwable $th) {
            error_log("Error en actualizarDescripcionCategoria: " . print_r($th, true));
            return false;
        }     
    }
    // MÉTODO ACTUALIZADO: Actualizar estado de categoría con mejor manejo
    public function actualizarEstadoCategoria($siglaRubro, $codCategoria, $nuevoEstado) {
        // Primero verificar que el registro existe
        $checkSql = "SELECT COUNT(*) as COUNT FROM SJ_CATEGORIAS WHERE CATEGORIA = ? AND RUBRO = ?";
        $checkParams = array($codCategoria, $siglaRubro);
        
        try {
            $checkStmt = sqlsrv_prepare($this->cid_central, $checkSql, $checkParams);
            
            if (!$checkStmt) {
                throw new Exception("Error al preparar verificación: " . print_r(sqlsrv_errors(), true));
            }
            
            $checkResult = sqlsrv_execute($checkStmt);
            
            if (!$checkResult) {
                throw new Exception("Error al ejecutar verificación: " . print_r(sqlsrv_errors(), true));
            }
            
            $row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
            if ($row['COUNT'] == 0) {
                error_log("No existe la categoría $codCategoria en el rubro $siglaRubro");
                return false;
            }
            
            // Actualizar el estado
            $sql = "UPDATE SJ_CATEGORIAS SET ACTIVO = ? WHERE CATEGORIA = ? AND RUBRO = ?";
            $params = array($nuevoEstado, $codCategoria, $siglaRubro);

            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
            
            if (!$stmt) {
                throw new Exception("Error al preparar actualización de estado: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_execute($stmt);
            
            if (!$result) {
                throw new Exception("Error al ejecutar actualización de estado: " . print_r(sqlsrv_errors(), true));
            }
            
            // Verificar que se actualizó al menos una fila
            $rowsAffected = sqlsrv_rows_affected($stmt);
            
            if ($rowsAffected === false || $rowsAffected == 0) {
                error_log("No se actualizó ninguna fila. Rubro: $siglaRubro, Categoria: $codCategoria, Estado: $nuevoEstado");
                return false;
            }
            
            error_log("Estado actualizado correctamente. Rubro: $siglaRubro, Categoria: $codCategoria, Nuevo Estado: $nuevoEstado, Filas afectadas: $rowsAffected");
            return true;

        } catch (\Throwable $th) {
            error_log("Error en actualizarEstadoCategoria: " . print_r($th, true));
            return false;
        }     
    }

    public function insertarNuevoRubro($sigla, $descripcion) {
        // Verificar si ya existe
        $checkSql = "SELECT 1 FROM RO_T_RUBROS_CODIFICACION WHERE SIGLA = ?";
        $checkParams = array($sigla);

        try {
            $checkStmt = sqlsrv_prepare($this->cid_central, $checkSql, $checkParams);
            
            if (!$checkStmt) {
                throw new Exception("Error al preparar verificación de rubro: " . print_r(sqlsrv_errors(), true));
            }
            
            $checkResult = sqlsrv_execute($checkStmt);
            
            if (!$checkResult) {
                throw new Exception("Error al ejecutar verificación de rubro: " . print_r(sqlsrv_errors(), true));
            }
   
            if (sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC) !== null) {
                return false; // Ya existe
            }

            // Insertar nuevo rubro
            $insertSql = "INSERT INTO RO_T_RUBROS_CODIFICACION (SIGLA, DESC_RUBRO) VALUES (?, ?)";
            $insertParams = array($sigla, $descripcion);
                            
            $stmt = sqlsrv_prepare($this->cid_central, $insertSql, $insertParams);
            
            if (!$stmt) {
                throw new Exception("Error al preparar inserción de rubro: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_execute($stmt);
            
            if (!$result) {
                throw new Exception("Error al ejecutar inserción de rubro: " . print_r(sqlsrv_errors(), true));
            }
            
            return true;

        } catch (\Throwable $th) {
            error_log("Error en insertarNuevoRubro: " . print_r($th, true));
            return false;
        }
    }

    public function obtenerSiglasDisponibles() {
        // Obtener todas las letras del alfabeto que no están en uso
        $sql = "SELECT CHAR(number) as SIGLA 
                FROM master..spt_values 
                WHERE type = 'P' 
                AND number BETWEEN 65 AND 90 
                AND CHAR(number) NOT IN (SELECT SIGLA FROM RO_T_RUBROS_CODIFICACION)
                ORDER BY CHAR(number)";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if (!$stmt) {
                throw new Exception("Error al ejecutar consulta de siglas: " . print_r(sqlsrv_errors(), true));
            }

            $siglas = array();
            
            while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $siglas[] = $row['SIGLA'];
            }
            
            return $siglas;

        } catch (\Throwable $th) {
            error_log("Error en obtenerSiglasDisponibles: " . print_r($th, true));
            // Fallback: devolver letras básicas si falla la consulta
            $todasLasLetras = range('A', 'Z');
            $letrasUsadas = $this->obtenerSiglasEnUso();
            return array_diff($todasLasLetras, $letrasUsadas);
        }
    }

    private function obtenerSiglasEnUso() {
        $sql = "SELECT SIGLA FROM RO_T_RUBROS_CODIFICACION";
        
        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if (!$stmt) {
                return [];
            }

            $siglas = array();
            
            while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $siglas[] = $row['SIGLA'];
            }
            
            return $siglas;

        } catch (\Throwable $th) {
            error_log("Error en obtenerSiglasEnUso: " . print_r($th, true));
            return [];
        }
    }

    // MÉTODO OPCIONAL: Para normalizar todos los NULL a 1 (activo)
    public function normalizarEstadosActivo() {
        $sql = "UPDATE SJ_CATEGORIAS SET ACTIVO = 1 WHERE ACTIVO IS NULL";
        
        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if (!$stmt) {
                throw new Exception("Error al normalizar estados: " . print_r(sqlsrv_errors(), true));
            }
            
            $rowsAffected = sqlsrv_rows_affected($stmt);
            error_log("Normalizados $rowsAffected registros de NULL a activo");
            
            return true;

        } catch (\Throwable $th) {
            error_log("Error en normalizarEstadosActivo: " . print_r($th, true));
            return false;
        }
    }
}


?>