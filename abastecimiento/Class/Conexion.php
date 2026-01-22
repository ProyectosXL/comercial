
<?php

class Conexion{

    function __construct(){
        require_once(__DIR__.'/classEnv.php');

        $vars = new DotEnv(__DIR__ . '/../../../.env');
        $this->envVars = $vars->listVars();
        
        $this->host_central = $this->envVars['HOST_CENTRAL'];
        $this->database_central = $this->envVars['DATABASE_CENTRAL'];
        $this->database_uy = $this->envVars['DATABASE_UY'];
        $this->host_apps = $this->envVars['HOST_APPS'];
        $this->database_power = $this->envVars['DATABASE_POWER'];
        $this->database_apps = $this->envVars['DATABASE_APPS'];
        $this->host_locales = $this->envVars['HOST_LOCALES'];
        $this->database_locales = $this->envVars['DATABASE_LOCALES'];
        $this->user = $this->envVars['USER'];
        $this->pass = $this->envVars['PASS'];
        $this->pass_locales = $this->envVars['PASS_LOCALES'];
        $this->character = $this->envVars['CHARACTER'];
        $this->env = $this->envVars['ENV'];
        $this->prefix = ($this->env == 'DEV') ? '[XL-LAKERBIS].locales_lakers.dbo.' : '';
    }

    private function servidor($nameServer) {
        
        if($nameServer == 'central'){
            return array($this->host_central, $this->database_central);
        }elseif($nameServer == 'locales'){
            return array($this->host_locales, $this->database_locales);
        }elseif($nameServer == 'uy'){
            return array($this->host_central, $this->database_uy);
        }elseif($nameServer == 'apps'){
            return array($this->host_apps, $this->database_apps);
        }elseif($nameServer == 'power'){
            return array($this->host_apps, $this->database_power);
}else{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // NUEVO CÓDIGO: Si la sesión está vacía, usa la conexión CENTRAL por defecto
    if(empty($_SESSION['conexion_dns']) || empty($_SESSION['base_nombre'])) {
        // Asumiendo que quieres conectar a la base central por defecto
        return array($this->host_central, $this->database_central);
    }

    return array($_SESSION['conexion_dns'], $_SESSION['base_nombre']);
}

    }

    public function conectar($nameServer = null) {
        try {

            $serverDB = $this->servidor($nameServer);

            if($this->env == 'PROD' && (strtolower($serverDB[0]) == strtolower('XL-LAKERBIS'))){
                $pass = $this->pass_locales;
            } else {
                $pass = $this->pass;
            }

            $params = array( 
                "Database" => $serverDB[1], 
                "UID" => $this->user, 
                "PWD" => $pass, 
                "CharacterSet" => $this->character
            );

$cid = sqlsrv_connect($serverDB[0], $params);

if(!$cid) {
    // CÓDIGO DE DIAGNÓSTICO (Borrar después de arreglar)
    echo "<h3>Error Fatal de Conexión SQL</h3>";
    echo "<b>Intentando conectar a:</b> " . $serverDB[0] . "<br>";
    echo "<b>Usuario:</b> " . $this->user . "<br>";
    echo "<b>Base de datos:</b> " . $serverDB[1] . "<br><br>";
    echo "<b>Detalle del error:</b><br>";
    die(print_r(sqlsrv_errors(), true));
}

// este cid va a cambiar mil veces
            $_SESSION['cid'] = $cid;
            return $cid;
            
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    private function buscarLocal($nameLocal){

        $prefix = ($this->env == 'DEV') ? '[XL-LAKERBIS].locales_lakers.dbo.' : '';

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

            // $next_result = sqlsrv_next_result($stmt);

            while ($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)) {

                $v[] = $row;

            }
    
            return $v[0];

        } catch (\Throwable $th) {

            print_r($th);

        }
    }
    
}
