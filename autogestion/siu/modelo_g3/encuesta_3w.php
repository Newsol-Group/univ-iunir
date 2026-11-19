<?php
namespace siu\modelo_g3;

//http://php.net/manual/en/language.oop5.late-static-bindings.php

class encuesta_3w extends \encuesta {


    /**
     * @return \GuzzleHttp\Client
     */
    protected static function get_cliente_rest()
    {
        if(!isset(self::$cliente_kolla)){
            $cliente = \toba::servicio_web_rest('kolla');
            self::$cliente_kolla = $cliente;
        }
        return self::$cliente_kolla;
    }

}