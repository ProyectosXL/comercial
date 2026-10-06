<?php

class FormatoPedido
{
    // Formatea el pedido a 14 caracteres como lo guarda Tango: ' 0000' + últimos 9 caracteres
    public static function nroPedido($pedido)
    {
        return ' 0000' . substr(strval($pedido), -9);
    }
}
