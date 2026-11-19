<?php
namespace siu\modelo_g3;

use SIU\Chulupi\kernel;

class cobro extends \cobro {


    /**
     * @return \GuzzleHttp\Client
     */
    protected static function get_cliente_rest()
    {
        if (!isset(static::$cliente_sanaviron)) {
            $cliente = \toba::servicio_web_rest('sanaviron');
            static::$cliente_sanaviron = $cliente;
        }
        return static::$cliente_sanaviron;
    }

    public static function get_url_consumo_SQ_pagos($token_SQ){

        return kernel::proyecto()->get_url_sq_pagos()."#/consumo/".$token_SQ;

    }

    public static function get_url_portal_SQ_pagos($token_SQ){
		
        return kernel::proyecto()->get_url_sq_pagos()."#/portal/".$token_SQ;

    }

}