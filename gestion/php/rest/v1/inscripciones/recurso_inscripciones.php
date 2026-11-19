<?php

namespace SIU\Guarani\php\rest\v1\inscripciones;

use SIUToba\rest\rest;
use SIUToba\rest\lib\modelable;
use PERS\Guarani\php\rest\v1\modelos\rest_inscripciones;

class recurso_inscripciones implements modelable
{

    /**
     * @var rest_inscripciones
     */
    protected $modelo;

    public static function _get_modelos(){
		
		$inscripcion = array (  		'inscripcion' => array('type' => 'integer'),
										'nro_transaccion' => array('type' => 'integer'),
										'apellido' => array('type' => 'string'),
										'nombres' => array('type' => 'string'),
										'genero' => array('type' => 'string'),
										'nro_documento' => array('type' => 'string'),
										'tipo_documento' => array('type' => 'integer'),
										'pais_documento' => array('type' => 'integer'),
										'email' => array('type' => 'string'),
										'telefono' => array('type' => 'string'),
										'fecha_inscripcion' => array('type' => 'string'),
										'estado' => array('type' => 'string', '_mapeo' => 'estado_real'),
										'propuesta' => array('type' => 'integer'),
										'id_actividad' => array('type' => 'integer'),
										'id_comision' => array('type' => 'integer'),
										'sq_token' => array('type' => 'string'),
		);
		
        return array('Inscripcion'	=>	$inscripcion);
    }

    function __construct()
    {
		$this->modelo = \guarani::rest(rest_inscripciones::class);
    }
	
	/**
	 * GET /inscripciones
	 * 
	 * @param_query $comision integer Filtro de una comision en particular	 
	 * @param_query $nro_transaccion integer Filtro por número de transaccion
	 * @param_query $historicas boolean Filtro por inscripciones históricas (true) o vigentes (false). Por defecto trae vigentes (false).
	 * @param_query $estado_inscripcion string Aceptadas y pendientes (AP), Rechazadas y dadas de baja (RB) o Todas las Inscripciones (T). Por defecto trae todas (T).
	 * @notes
	 * Devuelve una lista de inscripciones a cursada.<br>	 
	 * 
	 * @summary Inscripciones a cursada
	 * @responses 200 array {"$ref":"Inscripcion"}
	 * @responses 400 Error en los parámetros
	 */
	function get_list()
	{
		$datos = $this->modelo->get_list();
		return rest::response()->get_list($datos);
	}

	/**
	 * GET /inscripciones/id_inscripcion
	 *
	 * @notes
	 * Devuelve los datos de la inscripción a cursada {id_inscripcion}. <br>
	 *
	 * @summary Datos de la inscripción a cursada {id_inscripcion}
	 * @responses 200 {"$ref":"Inscripcion"}
	 * @responses 404 La inscripción a cursada {id_inscripcion} no existe
	 * @responses 400 Error en los parámetros
	 */
	function get($id_inscripcion)
	{
		$datos = $this->modelo->get($id_inscripcion);
		rest::response()->get($datos);
	}
}