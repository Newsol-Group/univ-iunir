<?php

namespace SIU\Guarani\php\rest\v2\inscripciones_mesas;

use SIUToba\rest\rest;
use SIUToba\rest\lib\modelable;
use PERS\Guarani\php\rest\v2\modelos\rest_inscripciones_mesas;

class recurso_inscripciones_mesas implements modelable
{
    /**
     * @var rest_inscripciones_mesas
     */
    protected $modelo;

    public static function _get_modelos()
	{	
		$inscripcion_mesa = array('inscripcion' => array('type' => 'integer'),
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
			'estado' => array('type' => 'string', '_mapeo'	=> 'nombre_estado'),
			'llamado_mesa' => array('type' => 'integer')
		);
        return array('InscripcionMesa' => $inscripcion_mesa);
    }

    function __construct()
    {
		$this->modelo = \guarani::rest(rest_inscripciones_mesas::class);
    }

	/**
	 * GET /inscripciones-mesas
	 * 
	 * @param_query $llamado_mesa integer Filtro por un llamado de mesa de examen	 
	 * @param_query $nro_transaccion integer Filtro por número de transacción
	 * @param_query $historicas boolean (defecto: false) Filtro por inscripciones históricas (true) o vigentes (false)
	 * 
	 * @notes
	 * Devuelve una lista de inscripciones a exámenes.<br>
	 * 
	 * @summary Inscripciones a exámenes
	 * @responses 200 array {"$ref":"InscripcionMesa"}
	 */
	function get_list()
	{
		$datos = $this->modelo->get_list();
		return rest::response()->get_list($datos);
	}
}