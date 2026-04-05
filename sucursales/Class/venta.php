<?php

if (!extension_loaded('sqlsrv')) {
    die('SQLSRV extension not loaded');
}

class Venta
{
    private $cid;
    private $cid_central;

    function __construct()
    {
        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/Class/Conexion.php';
        $this->cid = new Conexion();
        $this->cid_central = $this->cid->conectar('central');
        $this->cid_lakerbis = $this->cid->conectar('locales');
    } 

    public function traerVentasPropios($desde = null, $hasta = null, $suc = '%'){
        if ($desde === null) {
            $desde = date('Y-m-d', strtotime('-30 days'));
        }
        if ($hasta === null) {
            $hasta = date('Y-m-d');
        }

        $sql = "EXEC [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].[SJ_VENTAS_LOCALES_PROPIOS_MOBILE] @DESDE = ?, @HASTA = ?, @SUC = ?";
        $params = array($desde, $hasta, $suc);
        
        // Habilitar el manejo de errores de SQL Server
        if( sqlsrv_configure("WarningsReturnAsErrors", 0) === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            // Si la consulta falla, imprime información de depuración
            echo "Error en la consulta SQL:<br>";
            echo "SQL: $sql<br>";
            echo "Parámetros: ";
            print_r($params);
            echo "<br>";
            if( ($errors = sqlsrv_errors() ) != null) {
                foreach( $errors as $error ) {
                    echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
                    echo "code: ".$error[ 'code']."<br />";
                    echo "message: ".$error[ 'message']."<br />";
                }
            }
            return array(); // Devolver un array vacío en caso de error
        }
        
        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }
        return $rows;
    }

    public function traerVentasFranquicias($desde = null, $hasta = null, $suc = '%'){
        if (!$this->cid_central) {
            die("Error: No se pudo establecer conexión con la base de datos.");
        }
    
        $desde = $desde ? date('Y-m-d', strtotime($desde)) : date('Y-m-d', strtotime('-30 days'));
        $hasta = $hasta ? date('Y-m-d', strtotime($hasta)) : date('Y-m-d');
    
        $sql = "EXEC [XL-LAKERBIS].[FRANQUICIAS_LAKERS].[dbo].[SJ_VENTAS_FRANQUICIAS_MOBILE] @DESDE = ?, @HASTA = ?, @SUC = ?";
        $params = array($desde, $hasta, $suc);
        
        // Habilitar el manejo de errores de SQL Server
        if( sqlsrv_configure("WarningsReturnAsErrors", 0) === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            // Si la consulta falla, imprime información de depuración
            echo "Error en la consulta SQL:<br>";
            echo "SQL: $sql<br>";
            echo "Parámetros: ";
            print_r($params);
            echo "<br>";
            if( ($errors = sqlsrv_errors() ) != null) {
                foreach( $errors as $error ) {
                    echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
                    echo "code: ".$error[ 'code']."<br />";
                    echo "message: ".$error[ 'message']."<br />";
                }
            }
            return array(); // Devolver un array vacío en caso de error
        }
        
        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }
        return $rows;
    }

    public function traerVentasUruguay($desde = null, $hasta = null, $suc = '%'){
        if ($desde === null) {
            $desde = date('Y-m-d', strtotime('-30 days'));
        }
        if ($hasta === null) {
            $hasta = date('Y-m-d');
        }

        $sql = "EXEC [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].SJ_VENTAS_ML_URUGUAY_MOBILE2 @DESDE = ?, @HASTA = ?, @SUC = ?";
        $params = array($desde, $hasta, $suc);
        
        // Habilitar el manejo de errores de SQL Server
        if( sqlsrv_configure("WarningsReturnAsErrors", 0) === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            // Si la consulta falla, imprime información de depuración
            echo "Error en la consulta SQL:<br>";
            echo "SQL: $sql<br>";
            echo "Parámetros: ";
            print_r($params);
            echo "<br>";
            if( ($errors = sqlsrv_errors() ) != null) {
                foreach( $errors as $error ) {
                    echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
                    echo "code: ".$error[ 'code']."<br />";
                    echo "message: ".$error[ 'message']."<br />";
                }
            }
            return array(); // Devolver un array vacío en caso de error
        }
        
        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }
        return $rows;
    }

    public function traerVentasTodos($desde = null, $hasta = null, $suc = '%'){
        if ($desde === null) {
            $desde = date('Y-m-d', strtotime('-30 days'));
        }
        if ($hasta === null) {
            $hasta = date('Y-m-d');
        }

        $sql = "EXEC [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].[SJ_VENTAS_LOCALES_TODOS_MOBILE] @DESDE = ?, @HASTA = ?, @SUC = ?";
        $params = array($desde, $hasta, $suc);
        
        // Habilitar el manejo de errores de SQL Server
        if( sqlsrv_configure("WarningsReturnAsErrors", 0) === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            // Si la consulta falla, imprime información de depuración
            echo "Error en la consulta SQL:<br>";
            echo "SQL: $sql<br>";
            echo "Parámetros: ";
            print_r($params);
            echo "<br>";
            if( ($errors = sqlsrv_errors() ) != null) {
                foreach( $errors as $error ) {
                    echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
                    echo "code: ".$error[ 'code']."<br />";
                    echo "message: ".$error[ 'message']."<br />";
                }
            }
            return array(); // Devolver un array vacío en caso de error
        }
        
        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }
        return $rows;
    }
    
    public function traerVentasVsObjetivosPropios($desde = null, $hasta = null){
        if ($desde === null) {
            $desde = date('Y-m-d', strtotime('-30 days'));
        }
        if ($hasta === null) {
            $hasta = date('Y-m-d');
        }

        $sql = "EXEC [XL-APPS].[POWER_BI_CONTROL].[dbo].RO_SP_VENTAS_VS_OBJETIVOS_PROPIOS_PPP @DESDE = ?, @HASTA = ?";
        $params = array($desde, $hasta);
        
        // Habilitar el manejo de errores de SQL Server
        if( sqlsrv_configure("WarningsReturnAsErrors", 0) === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            // Si la consulta falla, imprime información de depuración
            echo "Error en la consulta SQL:<br>";
            echo "SQL: $sql<br>";
            echo "Parámetros: ";
            print_r($params);
            echo "<br>";
            if( ($errors = sqlsrv_errors() ) != null) {
                foreach( $errors as $error ) {
                    echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
                    echo "code: ".$error[ 'code']."<br />";
                    echo "message: ".$error[ 'message']."<br />";
                }
            }
            return array(); // Devolver un array vacío en caso de error
        }
        
        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }
        return $rows;
    }

    public function traerCumplimientoObjetivosPropios($desde = null, $hasta = null){
        if ($desde === null) {
            $desde = date('Y-m-d', strtotime('-30 days'));
        }
        if ($hasta === null) {
            $hasta = date('Y-m-d');
        }

        $sql = "EXEC [XL-APPS].[POWER_BI_CONTROL].[dbo].[RO_SP_VENTAS_VS_OBJETIVOS_PROPIOS_PPP_CUMP_OBJ] @DESDE = ?, @HASTA = ?";
        $params = array($desde, $hasta);
        
        // Habilitar el manejo de errores de SQL Server
        if( sqlsrv_configure("WarningsReturnAsErrors", 0) === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            // Si la consulta falla, imprime información de depuración
            echo "Error en la consulta SQL:<br>";
            echo "SQL: $sql<br>";
            echo "Parámetros: ";
            print_r($params);
            echo "<br>";
            if( ($errors = sqlsrv_errors() ) != null) {
                foreach( $errors as $error ) {
                    echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
                    echo "code: ".$error[ 'code']."<br />";
                    echo "message: ".$error[ 'message']."<br />";
                }
            }
            return array(); // Devolver un array vacío en caso de error
        }
        
        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }
        return $rows;
    }


    public function traerVentasVsObjetivosFranquicias($desde = null, $hasta = null){
        if ($desde === null) {
            $desde = date('Y-m-d', strtotime('-30 days'));
        }
        if ($hasta === null) {
            $hasta = date('Y-m-d');
        }

        $sql = "EXEC [XL-APPS].[POWER_BI_CONTROL_FRANQUICIAS].[dbo].[RO_SP_VENTAS_VS_OBJETIVOS_FRANQUICIAS_PPP] @DESDE = ?, @HASTA = ?";
        $params = array($desde, $hasta);
        
        // Habilitar el manejo de errores de SQL Server
        if( sqlsrv_configure("WarningsReturnAsErrors", 0) === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            // Si la consulta falla, imprime información de depuración
            echo "Error en la consulta SQL:<br>";
            echo "SQL: $sql<br>";
            echo "Parámetros: ";
            print_r($params);
            echo "<br>";
            if( ($errors = sqlsrv_errors() ) != null) {
                foreach( $errors as $error ) {
                    echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
                    echo "code: ".$error[ 'code']."<br />";
                    echo "message: ".$error[ 'message']."<br />";
                }
            }
            return array(); // Devolver un array vacío en caso de error
        }
        
        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }
        return $rows;
    }

    public function traerVentasVsObjetivosUruguay($desde = null, $hasta = null){
        if ($desde === null) {
            $desde = date('Y-m-d', strtotime('-30 days'));
        }
        if ($hasta === null) {
            $hasta = date('Y-m-d');
        }

        $sql = "EXEC [XL-APPS].[POWER_BI_CONTROL_URUGUAY].[dbo].[RO_SP_VENTAS_VS_OBJETIVOS_URUGUAY_PPP] @DESDE = ?, @HASTA = ?";
        $params = array($desde, $hasta);
        
        // Habilitar el manejo de errores de SQL Server
        if( sqlsrv_configure("WarningsReturnAsErrors", 0) === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            // Si la consulta falla, imprime información de depuración
            echo "Error en la consulta SQL:<br>";
            echo "SQL: $sql<br>";
            echo "Parámetros: ";
            print_r($params);
            echo "<br>";
            if( ($errors = sqlsrv_errors() ) != null) {
                foreach( $errors as $error ) {
                    echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
                    echo "code: ".$error[ 'code']."<br />";
                    echo "message: ".$error[ 'message']."<br />";
                }
            }
            return array(); // Devolver un array vacío en caso de error
        }
        
        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }
        return $rows;
    }

    public function traerVentasVsObjetivosTodos($desde = null, $hasta = null){
        if ($desde === null) {
            $desde = date('Y-m-d', strtotime('-30 days'));
        }
        if ($hasta === null) {
            $hasta = date('Y-m-d');
        }

        $sql = "EXEC [XL-APPS].[POWER_BI_CONTROL].[dbo].[RO_SP_VENTAS_VS_OBJETIVOS_TODOS_PPP] @DESDE = ?, @HASTA = ?";
        $params = array($desde, $hasta);
        
        // Habilitar el manejo de errores de SQL Server
        if( sqlsrv_configure("WarningsReturnAsErrors", 0) === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            // Si la consulta falla, imprime información de depuración
            echo "Error en la consulta SQL:<br>";
            echo "SQL: $sql<br>";
            echo "Parámetros: ";
            print_r($params);
            echo "<br>";
            if( ($errors = sqlsrv_errors() ) != null) {
                foreach( $errors as $error ) {
                    echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
                    echo "code: ".$error[ 'code']."<br />";
                    echo "message: ".$error[ 'message']."<br />";
                }
            }
            return array(); // Devolver un array vacío en caso de error
        }
        
        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }
        return $rows;
    }


}