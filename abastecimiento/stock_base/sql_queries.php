<?php
function getRubros($conn) {
    $sql = "SELECT B.IDFOLDER ID_RUBRO, DESCRIP RUBRO FROM STA11ITC A WITH (NOLOCK)          
            INNER JOIN STA11FLD B          
            ON A.IDFOLDER = B.IDFOLDER          
            WHERE DESCRIP NOT LIKE '[_][^K]%' AND NOT DESCRIP LIKE 'PROMO VILLA%' 
            --and DESCRIP like '%cuero'
            group by B.IDFOLDER, DESCRIP";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSucursales($conn) {
    $sql = "select NRO_SUCURSAL ID_SUCURSAL, COD_CLIENT + ' - '+ DESC_SUCURSAL SUCURSAL 
            from [LAKERBIS].[LOCALES_LAKERS].[dbo].SUCURSALES_LAKERS
            WHERE HABILITADO = 1 AND NRO_SUCURSAL > '1'
            ORDER BY NRO_SUCURSAL";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStockBase($conn, $filterSucursal = null, $filterRubro = null, $orderBy = null, $orderDir = 'ASC') {
    $sql = "SELECT * FROM EB_StockBase WHERE 1=1";

    if ($filterSucursal) {
      $sql .= " AND Codigo_Sucursal = :codigo_sucursal";
    }

    if ($filterRubro) {
       $sql .= " AND Id_Rubro = :id_rubro";
    }
    
    if($orderBy){
        $sql .= " ORDER BY $orderBy $orderDir";
    }
    
    $stmt = $conn->prepare($sql);

    if($filterSucursal){
        $stmt->bindParam(':codigo_sucursal', $filterSucursal);
    }
    if($filterRubro){
        $stmt->bindParam(':id_rubro', $filterRubro);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function insertStockBase($conn, $codigoSucursal, $idRubro, $stockBase) {
    $sql = "INSERT INTO EB_StockBase (Codigo_Sucursal, Id_Rubro, Stock_Base) 
            VALUES (:codigo_sucursal, :id_rubro, :stock_base)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':codigo_sucursal', $codigoSucursal);
    $stmt->bindParam(':id_rubro', $idRubro);
    $stmt->bindParam(':stock_base', $stockBase, PDO::PARAM_INT);
    return $stmt->execute();
}

function updateStockBase($conn, $codigoSucursal, $idRubro, $stockBase) {
    $sql = "UPDATE EB_StockBase SET Stock_Base = :stock_base
            WHERE Codigo_Sucursal = :codigo_sucursal AND Id_Rubro = :id_rubro";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':codigo_sucursal', $codigoSucursal);
    $stmt->bindParam(':id_rubro', $idRubro);
    $stmt->bindParam(':stock_base', $stockBase, PDO::PARAM_INT);
    return $stmt->execute();
}

function deleteStockBase($conn, $codigoSucursal, $idRubro) {
    $sql = "DELETE FROM EB_StockBase WHERE Codigo_Sucursal = :codigo_sucursal AND Id_Rubro = :id_rubro";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':codigo_sucursal', $codigoSucursal);
    $stmt->bindParam(':id_rubro', $idRubro);
    return $stmt->execute();
}
?>