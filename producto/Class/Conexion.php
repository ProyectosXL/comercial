<?php

class Conexion{
    
    // ======================================
    // CONFIGURACIÓN - ELIGE UNA DE ESTAS OPCIONES:
    // ======================================
    
    // OPCIÓN 1: SQL Server Express (más común en desarrollo)
    //private $host_central = 'localhost\\SQLEXPRESS';
    
    // OPCIÓN 2: SQL Server estándar
    private $host_central = 'SERVIDOR';
    
    // OPCIÓN 3: SQL Server con IP
    // private $host_central = '127.0.0.1';
    
    // OPCIÓN 4: SQL Server con alias
    // private $host_central = '(local)';
    
    // ======================================
    
    private $database_central = 'LAKER_SA';      // CAMBIA: Nombre de tu base de datos
    private $user = 'sa';                            // CAMBIA: Tu usuario
    private $pass = 'Axoft1988';                              // CAMBIA: Tu contraseña
    private $character = 'UTF-8';
    private $env = 'DEV';

    function __construct(){
        // Configuración directa para desarrollo local
        
        // Configurar todas las bases usando la configuración central para local
        $this->database_uy = $this->database_central;
        $this->database_tangobis = $this->database_central;
        $this->database_suc_uy = $this->database_central;
        $this->host_apps = $this->host_central;
        $this->database_apps = $this->database_central;
        $this->host_locales = $this->host_central;
        $this->database_locales = $this->database_central;
        $this->pass_locales = $this->pass;
        $this->prefix = ($this->env == 'DEV') ? '[LAKERBIS].locales_lakers.dbo.' : '';
    }

    private function servidor($nameServer) {
        
        if($nameServer == 'central'){
            return array($this->host_central, $this->database_central);
        }elseif($nameServer == 'locales'){
            return array($this->host_locales, $this->database_locales);
        }elseif($nameServer == 'uy'){
            return array($this->host_central, $this->database_uy);
        }elseif($nameServer == 'tangobis'){
            return array($this->host_central, $this->database_tangobis);
        }elseif($nameServer == 'suc_uy'){
            return array($this->host_central, $this->database_suc_uy);
        }elseif($nameServer == 'apps'){
            return array($this->host_apps, $this->database_apps);
        }else{
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            return array($_SESSION['conexion_dns'] ?? $this->host_central, $_SESSION['base_nombre'] ?? $this->database_central);
        }
    }

    public function conectar($nameServer = null) {
        try {

            $serverDB = $this->servidor($nameServer);

            if($this->env == 'PROD' && (strtolower($serverDB[0]) == strtolower('lakerbis'))){
                $pass = $this->pass_locales;
            } else {
                $pass = $this->pass;
            }

            $params = array( 
                "Database" => $serverDB[1], 
                "UID" => $this->user, 
                "PWD" => $pass, 
                "CharacterSet" => $this->character,
                "ConnectionPooling" => false,        // Evitar problemas de pool
                "ConnectRetryCount" => 0,           // Sin reintentos automáticos
                "LoginTimeout" => 10                // Timeout más largo
            );

            $cid = sqlsrv_connect($serverDB[0], $params);

            if(!$cid) {
                // Mostrar errores detallados para debugging
                $errors = sqlsrv_errors();
                $errorMsg = "Errores de conexión SQL Server:\n";
                foreach($errors as $error) {
                    $errorMsg .= "- " . $error['message'] . "\n";
                }
                error_log($errorMsg);
                
                // Mostrar solo el primer error en pantalla
                echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<h4>Error de conexión SQL Server:</h4>";
                echo "<p><strong>Servidor:</strong> " . htmlspecialchars($serverDB[0]) . "</p>";
                echo "<p><strong>Base de datos:</strong> " . htmlspecialchars($serverDB[1]) . "</p>";
                echo "<p><strong>Usuario:</strong> " . htmlspecialchars($this->user) . "</p>";
                echo "<p><strong>Error:</strong> " . htmlspecialchars($errors[0]['message']) . "</p>";
                echo "<p><em>Verifica que SQL Server esté ejecutándose y que los datos de conexión sean correctos.</em></p>";
                echo "</div>";
                
                return false;
            }

            // Conexión exitosa
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['cid'] = $cid;
            return $cid;
            
        } catch (Exception $e) {
            error_log("Exception en conexión: " . $e->getMessage());
            echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            return false;
        }
    }

    private function buscarLocal($nameLocal){

        $prefix = ($this->env == 'DEV') ? '[LAKERBIS].locales_lakers.dbo.' : '';

        if($this->env == 'DEV'){
            $database = $this->database_central;
            $pass = $this->pass;
        } else {
            $database = $this->database_locales;
            $pass = $this->pass_locales;
        }

        $sql = "select * from ".$prefix." sucursales_lakers where cod_client = '$nameLocal'";

        $params = array( 
            "Database" => $this->database_central, 
            "UID" => $this->user, 
            "PWD" => $this->pass, 
            "CharacterSet" => $this->character
        );

        $cid = sqlsrv_connect($this->host_central, $params);

        $stmt = sqlsrv_query($cid, $sql);

        try {
            $v = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $v[] = $row;
            }
    
            return $v[0] ?? [];

        } catch (\Throwable $th) {
            error_log("Error en buscarLocal: " . print_r($th, true));
            return [];
        }
    }
    
}