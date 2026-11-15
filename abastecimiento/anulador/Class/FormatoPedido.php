<?php

class FormatoPedido
{
    // Formatea el pedido a 14 caracteres como lo guarda Tango: espacio + 13 dígitos con ceros a la izquierda
    public static function nroPedido($pedido)
    {
        $limpio = preg_replace('/[\s\x{00A0}]+/u', '', strval($pedido));
        if ($limpio === null) {
            $limpio = trim(strval($pedido));
        }

        if (strlen($limpio) > 13) {
            $limpio = substr($limpio, -13);
        }

        return ' ' . str_pad($limpio, 13, '0', STR_PAD_LEFT);
    }
}
