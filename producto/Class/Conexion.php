<?php

class Conexion{
    
    // ============================================
    // CONFIGURACIÓN LOCAL PARA DESARROLLO
    // MODIFICA ESTOS VALORES SEGÚN TU ENTORNO
    // ============================================
    private $config_local = [
        'HOST_CENTRAL' => 'SERVIDOR',  // Tu servidor SQL Server local
        'DATABASE_CENTRAL' => 'LAKER_SA',      // Tu base de datos
        'USER' => 'sa',                            // Tu usuario
        'PASS' => 'Axoft1988',                              // Tu contraseña
        'CHARACTER' => 'UTF-8',
        'ENV' => 'DEV'
    ];

    function __construct(){
        // Detectar si estamos en local o servidor
        $isLocal = $this->isLocalEnvironment();
        
        if ($isLocal) {
            // Usar configuración local directa
            $this->loadLocalConfig();
        } else {
            // Usar configuración del servidor con .env
            $this->loadServerConfig();
        }
    }
    
    private function isLocalEnvironment() {
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        $httpHost = $_SERVER['HTTP_HOST'] ?? '';
        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        
        // Detectar entorno local
        return (
            stripos($serverName, 'localhost') !== false ||
            stripos($httpHost, 'localhost') !== false ||
            stripos($httpHost, '127.0.0.1') !== false ||
            stripos($documentRoot, 'xampp') !== false ||
            stripos($documentRoot, 'htdocs') !== false
        );
    }
    
    private function loadLocalConfig() {
        // Configuración local sin .env
        $this->host_central = $this->config_local['HOST_CENTRAL'];
        $this->database_central = $this->config_local['DATABASE_CENTRAL'];
        $this->user = $this->config_local['USER'];
        $this->pass = $this->config_local['PASS'];
        $this->character = $this->config_local['CHARACTER'];
        $this->env = $this->config_local['ENV'];
        
        // Configurar el resto usando los mismos valores
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
    
    private function loadServerConfig() {
        try {
            // Intentar cargar configuración del servidor
            require_once(__DIR__.'/../../abastecimiento/Class/classEnv.php');

            $vars = new DotEnv(__DIR__ . '/../../.env');
            $this->envVars = $vars->listVars();
            
            $this->host_central = $this->envVars['HOST_CENTRAL'];
            $this->database_central = $this->envVars['DATABASE_CENTRAL'];
            $this->database_uy = $this->envVars['DATABASE_UY'];
            $this->database_tangobis = $this->envVars['DATABASE_TANGOBIS'];
            $this->database_suc_uy = $this->envVars['DATABASE_SUC_UY'];
            $this->host_apps = $this->envVars['HOST_APPS'];
            $this->database_apps = $this->envVars['DATABASE_APPS'];
            $this->host_locales = $this->envVars['HOST_LOCALES'];
            $this->database_locales = $this->envVars['DATABASE_LOCALES'];
            $this->user = $this->envVars['USER'];
            $this->pass = $this->envVars['PASS'];
            $this->pass_locales = $this->envVars['PASS_LOCALES'];
            $this->character = $this->envVars['CHARACTER'];
            $this->env = $this->envVars['ENV'];
            $this->prefix = ($this->env == 'DEV') ? '[LAKERBIS].locales_lakers.dbo.' : '';
            
        } catch (Exception $e) {
            // Si falla el servidor, usar configuración local como fallback
            error_log("Error cargando configuración del servidor, usando local: " . $e->getMessage());
            $this->loadLocalConfig();
        }
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
                "ConnectionPooling" => false,
                "ConnectRetryCount" => 0,
                "LoginTimeout" => 10
            );

            $cid = sqlsrv_connect($serverDB[0], $params);

            if(!$cid) {
                $errors = sqlsrv_errors();
                $errorMsg = "Error de conexión SQL Server:\n";
                $errorMsg .= "Servidor: " . $serverDB[0] . "\n";
                $errorMsg .= "Database: " . $serverDB[1] . "\n";
                $errorMsg .= "Usuario: " . $this->user . "\n";
                
                if ($errors) {
                    foreach($errors as $error) {
                        $errorMsg .= "Error: " . $error['message'] . "\n";
                    }
                }
                
                error_log($errorMsg);
                
                // Solo mostrar en pantalla si es desarrollo local
                if ($this->isLocalEnvironment()) {
                    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                    echo "<h4>Error de conexión SQL Server (Desarrollo Local):</h4>";
                    echo "<p><strong>Servidor:</strong> " . htmlspecialchars($serverDB[0]) . "</p>";
                    echo "<p><strong>Base de datos:</strong> " . htmlspecialchars($serverDB[1]) . "</p>";
                    echo "<p><strong>Usuario:</strong> " . htmlspecialchars($this->user) . "</p>";
                    if ($errors) {
                        echo "<p><strong>Error:</strong> " . htmlspecialchars($errors[0]['message']) . "</p>";
                    }
                    echo "<p><em>Configura tus datos en producto/Class/Conexion.php líneas 8-13</em></p>";
                    echo "</div>";
                }
                
                return false;
            }

            // este cid va a cambiar mil veces
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['cid'] = $cid;
            return $cid;
            
        } catch (PDOException $e) {
            error_log("PDO Exception: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Exception: " . $e->getMessage());
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