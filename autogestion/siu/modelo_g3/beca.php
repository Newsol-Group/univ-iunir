<?php

namespace siu\modelo_g3;

class beca extends \beca
{
	
    /**
     * @return \GuzzleHttp\Client
     */
    protected static function get_cliente_rest()
    {
        if (!isset(static::$cliente_tehuelche)) {
            $cliente = \toba::servicio_web_rest('tehuelche');
            static::$cliente_tehuelche = $cliente;
        }
        return static::$cliente_tehuelche;
    }
	
}