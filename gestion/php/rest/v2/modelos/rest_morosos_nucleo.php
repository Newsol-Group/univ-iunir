<?php

namespace SIU\Guarani\php\rest\v2\modelos;

use SIUToba\rest\rest;
use SIUToba\rest\lib\rest_error;
use SIUToba\rest\lib\rest_filtro_sql;
use SIUToba\rest\lib\rest_validador;
use SIU\Guarani\php\rest\v2\morosos\recurso_morosos;

class rest_morosos_nucleo
{
    /**
     * @var \act_morosos
     */
    protected $modelo_act;

    function __construct()
    {
        $this->modelo_act = \guarani::act('act_morosos');
    }

    protected function get_modelo($nombre)
    {
        $modelos = recurso_morosos::_get_modelos();
        return $modelos[$nombre];
    }

    /**
     * @return rest_filtro_sql
     */
    function get_filtro_base(){
        $filtro = new rest_filtro_sql();
        return $filtro;
    }

    function post($data)
    {
        $modelos = recurso_morosos::_get_modelos();
        $modelo_notificacion = $modelos['Notificacion'];
        //Valido el modelo
        rest_validador::validar($data, $modelo_notificacion);
        //Valido cada transaccion
        foreach($data['transacciones'] as $transaccion){
            $es_valido = rest_validador::es_valido($transaccion, rest_validador::TIPO_INT);
            //Si la transaccion es invalida
            if(!$es_valido) throw new rest_error(400, \guarani_mensajes::get('800_error_arreglo_numeros_enteros', ['transacciones']));
        }

		list($rs, $nros_transacciones_no_encontradas) = $this->modelo_act->notificar_morosos($data['fecha'], $data['transacciones']);

		if($rs) {
			$resultado = "ok";
			if (!empty($nros_transacciones_no_encontradas)) {
				$resultado .= " - las siguientes transacciones no fueron encontradas [".implode(', ', $nros_transacciones_no_encontradas)."]";
			}
			rest::response()->post(['resultado' => $resultado]);
		} else {
			throw new rest_error(500, \guarani_mensajes::get('800_fallo_notificacion_morosos'));
		}
    }

}