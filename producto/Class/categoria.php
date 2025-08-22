<?php

class Categoria
{
    private $cid_central;

    function __construct(){
        // Intentar usar conexión local primero, luego la del servidor
        if (file_exists(__DIR__ . '/Conexion.php')) {
            // Usar conexión local (sin .env)
            require_once __DIR__ . '/Conexion.php';
        } else {
            // Usar conexión del servidor (con .env)
            require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/abastecimiento/Class/Conexion.php';
        }
        
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
        // Modificado para incluir el campo ACTIVO
        $sql = "SELECT *, CASE WHEN ACTIVO = 1 OR ACTIVO IS NULL THEN 1 ELSE 0 END AS ESTADO_ACTIVO 
                FROM RO_V_RUBROS_CATEGORIAS_CODIFICACION 
                WHERE RUBRO = ? 
                ORDER BY CATEGORIA ASC";

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

            // Insertar nueva categoría - ACTIVO por defecto en 1 (activo)
            $insertSql = "INSERT INTO SJ_CATEGORIAS (CATEGORIA, DESC_CATEGORIA, DESC_CATEGORIA_PADRE, RUBRO, ACTIVO) VALUES (?, ?, ?, ?, 1)";
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

    // NUEVO MÉTODO: Actualizar estado de categoría
    public function actualizarEstadoCategoria($siglaRubro, $codCategoria, $nuevoEstado) {
        $sql = "UPDATE SJ_CATEGORIAS SET ACTIVO = ? WHERE CATEGORIA = ? AND RUBRO = ?";
        $params = array($nuevoEstado, $codCategoria, $siglaRubro);

        try {
            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
            
            if (!$stmt) {
                throw new Exception("Error al preparar actualización de estado: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_execute($stmt);
            
            if (!$result) {
                throw new Exception("Error al ejecutar actualización de estado: " . print_r(sqlsrv_errors(), true));
            }
            
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
}

?>