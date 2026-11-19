<?php

namespace siu\modelo_g3;

use SIU\Chulupi\kernel;

/**
 * Clase hack de Autogestión para consumir los WS de SIU-Araí Reportes
 * @see https://documentacion.siu.edu.ar/reportes/docs/arquitectura/ SIU-Araí: Reportes.
 * @package Araí
 */
class arai_reportes extends \arai_reportes {

    /**
     * @return GuzzleHttp\Client El cliente de Guzzle.
     */
    public static function getClienteRest()
    {
        if (is_null(static::$clienteGuzzle)) {
            $cliente = \toba::servicio_web_rest('arai_reportes');
            static::$clienteGuzzle = $cliente;
        }
        return static::$clienteGuzzle;
    }

    protected static function getDirTemp()
    {
        return kernel::proyecto()->get_dir_temp();
    }
}