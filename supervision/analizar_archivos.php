<?php
// Script para limpiar archivos duplicados y probar la numeración

error_reporting(E_ALL);
ini_set('display_errors', 1);

$directorio = __DIR__ . '/../imagenes/compSupervision/';

echo "<h2>Archivos en el directorio de supervisión</h2>";
echo "<p><strong>Directorio:</strong> $directorio</p>";

if (is_dir($directorio)) {
    $archivos = scandir($directorio);
    $archivos = array_filter($archivos, function($archivo) {
        return $archivo !== '.' && $archivo !== '..';
    });
    
    sort($archivos);
    
    echo "<h3>Total de archivos: " . count($archivos) . "</h3>";
    
    // Agrupar archivos por concepto/tipo
    $grupos = [];
    foreach ($archivos as $archivo) {
        // Extraer el patrón: semana_supervisora_id_type_numero.ext
        if (preg_match('/^(\d+)_([A-Z_]+)_(\d+)_(tarjeta|efectivo)_(\d+)\.(.+)$/', $archivo, $matches)) {
            $clave = $matches[1] . '_' . $matches[2] . '_' . $matches[3] . '_' . $matches[4];
            if (!isset($grupos[$clave])) {
                $grupos[$clave] = [];
            }
            $grupos[$clave][] = [
                'archivo' => $archivo,
                'numero' => (int)$matches[5],
                'extension' => $matches[6]
            ];
        }
    }
    
    echo "<h3>Análisis por concepto/tipo:</h3>";
    foreach ($grupos as $clave => $archivosGrupo) {
        echo "<h4>$clave</h4>";
        
        // Ordenar por número
        usort($archivosGrupo, function($a, $b) {
            return $a['numero'] - $b['numero'];
        });
        
        $problemasNumeracion = [];
        $numerosSinUsar = [];
        
        // Verificar numeración
        for ($i = 1; $i <= count($archivosGrupo); $i++) {
            $encontrado = false;
            foreach ($archivosGrupo as $archivo) {
                if ($archivo['numero'] == $i) {
                    $encontrado = true;
                    break;
                }
            }
            if (!$encontrado) {
                $numerosSinUsar[] = $i;
            }
        }
        
        // Buscar duplicados de numeración
        $numerosUsados = [];
        foreach ($archivosGrupo as $archivo) {
            $num = $archivo['numero'];
            if (in_array($num, $numerosUsados)) {
                $problemasNumeracion[] = "Número $num duplicado";
            } else {
                $numerosUsados[] = $num;
            }
        }
        
        echo "<ul>";
        foreach ($archivosGrupo as $archivo) {
            $color = in_array($archivo['numero'], $numerosUsados) ? 'black' : 'red';
            echo "<li style='color: $color'>" . $archivo['archivo'] . "</li>";
        }
        echo "</ul>";
        
        if (!empty($problemasNumeracion)) {
            echo "<p style='color: red'><strong>Problemas:</strong> " . implode(', ', $problemasNumeracion) . "</p>";
        }
        if (!empty($numerosSinUsar)) {
            echo "<p style='color: orange'><strong>Números sin usar:</strong> " . implode(', ', $numerosSinUsar) . "</p>";
        }
        if (empty($problemasNumeracion) && empty($numerosSinUsar)) {
            echo "<p style='color: green'><strong>✓ Numeración correcta</strong></p>";
        }
        
        echo "<hr>";
    }
    
} else {
    echo "<p style='color: red'>El directorio no existe</p>";
}
?>