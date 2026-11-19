<?php

namespace SIU\Guarani\php\rest\v2\status;

use SIUToba\rest\rest;
use SIUToba\rest\lib\modelable;

class recurso_status implements modelable
{
	
    public static function _get_modelos(){

        $status = array(
            'resultado'	=> array('type' => 'string'),
        );
				
        return array('Status' => $status);
    }

    /**
     * GET /status
     *
     * @notes
     * Devuelve código de estado 200 (OK) si la API se encuentra activa.<br>
     *
     * @summary Datos de conexión con la API
	 * @responses 200 Se pudo conectar correctamente con la API
     * @responses 404 No se pudo conectar con la API
     */
    function get_list()
    {
        $datos = ["resultado" => "OK"];
        rest::response()->get($datos);
    }

}