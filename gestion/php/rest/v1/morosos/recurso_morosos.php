<?php

namespace SIU\Guarani\php\rest\v1\morosos;

use SIUToba\rest\rest;
use SIUToba\rest\lib\rest_validador;
use SIUToba\rest\lib\modelable;
use PERS\Guarani\php\rest\v1\modelos\rest_morosos;

class recurso_morosos implements modelable
{

    /**
     * @var rest_morosos
     */
    protected $modelo;

    public static function _get_modelos(){

        $notificacion = array(
            'fecha'     => array(
                'type'		=> 'string',
                '_validar'	=> array(
                    rest_validador::OBLIGATORIO,
                    rest_validador::TIPO_DATE => array(
                        'format' => 'Y-m-d'
                    )
                ),
            ),

            'transacciones' => array(
                'type'		=> 'array',
                'items'		=> array(
                    'type' => 'integer'
                ),
                '_validar'	=> array(
                    rest_validador::TIPO_ARREGLO
                )
            ),

        );

        return array('Notificacion' => $notificacion);
    }

    function __construct()
    {
		$this->modelo = \guarani::rest(rest_morosos::class);
    }

    /**
     * POST /morosos
     *
     * Recibe notificaciones de mora de pagos de cursadas en Cursos de Extensión o Posgrados desde SQ.
     *
     * @param_body $notificaciones_mora Notificacion [required] Campos de una notificación.
     *
     * @summary Recibe notificaciones de mora de pagos de cursadas en Cursos de Extensión o Posgrados desde SQ.
     *
     * @responses  201 Exito - Se notificaron exitosamente todos los morosos
     * @responses  400 Errores de validación
     * @responses  500 Falló la notificación de morosos
     **/
    function post_list()
    {
        $this->modelo->post(rest::request()->get_body_json());
    }

}