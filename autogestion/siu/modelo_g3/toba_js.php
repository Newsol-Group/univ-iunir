<?php

class toba_js
{

    /**
     * Reemplaza los strings multilinea por cadenas válidas en JS
     */
    static function string($cadena)
    {
        return pasar_a_unica_linea($cadena);
    }
    
}