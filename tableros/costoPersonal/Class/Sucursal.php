<?php

class Sucursal
{
    private $cid;
    private $cid_central;
    private $cid_locales;
    private $conexion; 
    private $cid_uy;
    
    function __construct()
    {

        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/class/conexion.php';
        $this->cid = new Conexion();

        $this->cid_central = $this->cid->conectar('central');
        $this->cid_locales =($this->cid->env == 'DEV') ? $this->cid->conectar('central') : $this->cid->conectar('locales');
        $this->cid_uy = $this->cid->conectar('uy');

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
            
            $this->conexion = $this->cid->conectar('uy');
        }else{
            $this->conexion = $this->cid->conectar('central');

        }

    } 
    
    public function traerLocales($orderByName = null)
    {

        if((isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy')){

            $sql = "SELECT NRO_SUCURSAL, DESC_SUCURSAL, NRO_SUCURSAL as ID, DESC_SUCURSAL as SUCURSAL FROM LAKERBIS.LOCALES_LAKERS.DBO.SUCURSALES_LAKERS  WHERE CANAL = 'EXTERIOR' ";

        }else{

            $sql = "SELECT NRO_SUCURSAL, DESC_SUCURSAL, NRO_SUCURSAL as ID, DESC_SUCURSAL as SUCURSAL FROM LAKERBIS.LOCALES_LAKERS.DBO.SUCURSALES_LAKERS WHERE CANAL = 'PROPIOS' AND HABILITADO = 1 ";
     
        }

        if($orderByName == true){

            $sql = $sql."ORDER BY SUCURSAL";

        }else{

            $sql = $sql."ORDER BY ID"; 
            
        }    


        $stmt = sqlsrv_query($this->conexion, $sql);

        try{
            
            $rows = array();

            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }

            return $rows;
        
        } catch (\Throwable $th){
            print_r($th);
        }
    }
    
    /**
     * Obtiene información de una sucursal por su ID
     */
    public function obtenerSucursalPorId($idSucursal)
    {
        if((isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy')){
            $sql = "SELECT NRO_SUCURSAL, DESC_SUCURSAL, NRO_SUCURSAL as ID, DESC_SUCURSAL as SUCURSAL FROM LAKERBIS.LOCALES_LAKERS.DBO.SUCURSALES_LAKERS WHERE CANAL = 'EXTERIOR' AND NRO_SUCURSAL = ?";
        } else {
            $sql = "SELECT NRO_SUCURSAL, DESC_SUCURSAL, NRO_SUCURSAL as ID, DESC_SUCURSAL as SUCURSAL FROM LAKERBIS.LOCALES_LAKERS.DBO.SUCURSALES_LAKERS 
                    WHERE ((CANAL = 'PROPIOS' AND HABILITADO = 1) OR NRO_SUCURSAL = '16') AND NRO_SUCURSAL = ?";
        }
        
        $stmt = sqlsrv_prepare($this->conexion, $sql, array($idSucursal));
        
        if ($stmt === false) {
            throw new Exception('Error al preparar consulta de sucursal');
        }
        
        if (!sqlsrv_execute($stmt)) {
            throw new Exception('Error al ejecutar consulta de sucursal');
        }
        
        $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
        if (!$result) {
            throw new Exception('Sucursal no encontrada');
        }
        
        return $result;
    }
}