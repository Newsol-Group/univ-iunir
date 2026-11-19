<?php
namespace siu\modelo_g3;

class sidcer extends \sidcer {

	const MSJ_NO_HAY_REGISTROS = 'No hay registros que cumplan los criterios indicados.';

    /**
     * @return \GuzzleHttp\Client
     */
    protected static function get_cliente_rest()
    {
        if (!isset(static::$cliente_sidcer)) {
            $cliente = \toba::servicio_web_rest('sidcer');
            static::$cliente_sidcer = $cliente;
        }
        return static::$cliente_sidcer;
    }

}