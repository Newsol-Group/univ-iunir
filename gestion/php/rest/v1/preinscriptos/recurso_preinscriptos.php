<?php

namespace SIU\Guarani\php\rest\v1\preinscriptos;

use SIUToba\rest\rest;
use SIUToba\rest\lib\modelable;
use PERS\Guarani\php\rest\v1\modelos\rest_preinscriptos;

class recurso_preinscriptos implements modelable
{
	
	/**
     * @var rest_preinscriptos
     */
    protected $modelo;

	function __construct()
	{
		$this->modelo = \guarani::rest(rest_preinscriptos::class);
	}

    public static function _get_modelos() 
    {
        $preinscripto = array(
			'usuario'           => array('type' => 'string'),
			'id_preinscripcion' => array('type'	=> 'string'),
        );
        
        $turno = array(
			'tipo_actividad' => array('type' => 'string'),
			'tipo_persona' => array('type' => 'string'),
			'legajo' => array('type' => 'string'),
			'actividad' => array('type' => 'string'),
			'horario' => array('type' => 'string'),
			'ubicacion' => array('type' => 'string'),
            'modalidad' => array('type' => 'string'),
        );
        
        return array('Preinscripto' => $preinscripto, 'Turno' => $turno);
    }
    
    /**
	 * GET /preinscriptos
	 * 
	 * @param_query $usuario string  Usuario
	 * @param_query $pais string  País
	 * @param_query $tipo_documento integer Tipo de documento de la persona
	 * @param_query $numero_documento string Número de documento de la persona
	 * 
	 * @notes
	 * Devuelve una lista de preinscriptos. Buscar por país, tipo y número de documento, o usuario<br>
	 * 
	 * @summary Personas
	 * @responses 200 array {"$ref":"Preinscripto"}
	 * @responses 404 La persona no existe
	 * @responses 400 Error en los parámetros
	 */
	function get_list()
	{
		$datos = $this->modelo->get_list();
		rest::response()->get($datos);
	}
    
    /**
	 * GET /personas/id_preinscripcion/agenda
	 * 
 	 * @param_query $fecha string Fecha a buscar actividades
	 * 
	 * @notes
	 * Devuelve la agenda del día de un preinscripto.<br>
	 * 
	 * @summary Personas
	 * @responses 200 array {"$ref":"Turno"}
	 * @responses La persona no existe
	 * @responses 400 Error en los parámetros
	 */
    function get_agenda_list($id_preinscripcion)
    {
        $datos = $this->modelo->get_datos_agenda_dia($id_preinscripcion);
		rest::response()->get($datos);
    }

}
