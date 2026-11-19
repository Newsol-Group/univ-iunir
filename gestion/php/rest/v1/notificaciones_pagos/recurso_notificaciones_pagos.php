<?php

namespace SIU\Guarani\php\rest\v1\notificaciones_pagos;

use SIUToba\rest\rest;
use SIUToba\rest\lib\rest_validador;
use SIUToba\rest\lib\modelable;
use PERS\Guarani\php\rest\v1\modelos\rest_notificaciones_pagos;

class recurso_notificaciones_pagos implements modelable
{

    /**
     * @var rest_notificaciones_pagos
     */
    protected $modelo;

    public static function _get_modelos(){

        $notificacion = array(
            'transaccion'     => array(
                'type'		=> 'integer',
                '_validar'	=> array(
                    rest_validador::OBLIGATORIO,
                    rest_validador::TIPO_INT
                )
            ),

            'estado'     => array(
                'type'		=> 'string',
                'enum'		=> array(\cobro::ESTADO_PAGO_PAGO, \cobro::ESTADO_PAGO_VENCIO),
                '_validar'	=> array(
                    rest_validador::OBLIGATORIO,
                    rest_validador::TIPO_ENUM => array(
                        \cobro::ESTADO_PAGO_PAGO,
                        \cobro::ESTADO_PAGO_VENCIO
                    )
                )
            ),
			
			'concepto'     => array(
                'type'		=> 'string',
                'enum'		=> array(\cobro::CONCEPTO_NP_COMISION, \cobro::CONCEPTO_NP_PROPUESTA),
                '_validar'	=> array(
                    rest_validador::TIPO_ENUM => array(
                        \cobro::CONCEPTO_NP_COMISION,
                        \cobro::CONCEPTO_NP_PROPUESTA
                    )
                )
            ),
			
        );

        return array('Notificacion_pago' => $notificacion);
    }

    function __construct()
    {
		$this->modelo = \guarani::rest(rest_notificaciones_pagos::class);
    }

    /**
     * POST /notificaciones-pagos
     *
     * Recibe notificaciones de cobranzas o de vencimientos emitidas por SQ.
	 * 
	 * @see https://repositorio.siu.edu.ar/trac/guarani/wiki/API_3 Caso de Uso
     *
     * @param_body $notificacion_pago Notificacion_pago [required] Campos de una notificación.
     *
     * @summary Recibe notificaciones de cobranzas o de vencimientos emitidas por SQ.
     *
     * @responses  201 Exito - Se notificó exitosamente el pago
     * @responses  400 Errores de validación
     * @responses  404 La inscripción con Nº transacción {transaccion} no existe
     * @responses  500 Falló la notificación del pago
     **/
    function post_list()
    {
        $this->modelo->post(rest::request()->get_body_json());
        rest::response()->post(array('resultado' => 'ok'));
    }

}