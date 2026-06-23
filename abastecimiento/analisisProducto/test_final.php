<?php
session_start();
require_once 'Class/Articulo.php';
require_once '../Class/Conexion.php';

$_SESSION['entorno'] = 'central';
$artObj = new Articulo();

try {
    $sql = "
    SELECT 
        j.name,
        ja.start_execution_date,      
        ja.stop_execution_date,
        CASE 
            WHEN ja.start_execution_date IS NOT NULL AND ja.stop_execution_date IS NULL THEN 'RUNNING'
            ELSE 'IDLE / FINISHED'
        END as JobStatus
    FROM [XL-LAKERBIS].msdb.dbo.sysjobactivity ja
    LEFT JOIN [XL-LAKERBIS].msdb.dbo.sysjobs j ON ja.job_id = j.job_id
    WHERE j.name = 'COM - ANALISIS COMERCIALES ARG'
    AND ja.session_id = (SELECT MAX(session_id) FROM [XL-LAKERBIS].msdb.dbo.sysjobactivity)
    ";
    $res = $artObj->retornarArray($sql);
    print_r($res);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

try {
    $sql = "SELECT COUNT(*) as Total FROM [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].FP_ANALISIS_PRODUCTO_COMERCIAL";
    $res = $artObj->retornarArray($sql);
    echo "Current Count of FP_ANALISIS_PRODUCTO_COMERCIAL: " . $res[0]['Total'] . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
