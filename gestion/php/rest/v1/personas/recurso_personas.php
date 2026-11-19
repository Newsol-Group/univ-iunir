<?php

namespace SIU\Guarani\php\rest\v1\personas;

use SIUToba\rest\rest;
use SIUToba\rest\lib\modelable;
use PERS\Guarani\php\rest\v1\modelos\rest_personas;

class recurso_personas implements modelable
{
	/**
	 *
	 * @var rest_personas
	 */
	protected $modelo;

	function __construct()
	{
		$this->modelo = \guarani::rest(rest_personas::class);
	}

	public static function _get_modelos()
	{
		$contacto = array(
			'email'	=> array('type' => 'string'),
			'telefono' => array('type'	=> 'string'),
			'persona' => array('type' => 'integer'),
        );

		$persona = array(
			'apellido' => array('type' => 'string'),
			'nombres' => array('type' => 'string'),
			'nombres_elegido' => array('type' => 'string'),
			'apellido_nombres_ley' => array('type' => 'string'),
			'genero' => array('type' => 'string'),
			'fecha_nacimiento' => array('type' => 'string'),
			'nacionalidad' => array('type' => 'string'),
			'legajo' => array('type' => 'string'),
			'pais_origen' => array('type' => 'string'),
			'codigo_pais_procedencia' => array('type' => 'string'),
			'nombre_pais_procedencia' => array('type' => 'string'),
			'codigo_provincia_procedencia' => array('type' => 'string'),
			'nombre_provincia_procedencia' => array('type' => 'string'),
			'codigo_localidad_procedencia' => array('type' => 'string'),
			'nombre_localidad_procedencia' => array('type' => 'string'),
			'direccion_procedencia' => array('type' => 'string'),
			'telefono_fijo' => array('type' => 'string'),
			'telefono_celular' => array('type' => 'string'),
			'mail' => array('type' => 'string'),
			'documento' => array('type' => 'string'),
		);

		$historia = array(
			'titulo_araucano' => array('type' => 'string'),
			'titulo_nombre' => array('type' => 'string'),
			'responsable_academica' => array('type' => 'string'),
			'propuesta' => array('type' => 'string'),
			'propuesta_nombre' => array('type' => 'string'),
			'plan_alumno' => array('type' => 'string'),
			'titulo_esta_cumplido' => array('type' => 'string'),
			'nro_resolucion_ministerial' => array('type' => 'string'),
			'nro_resolucion_coneau' => array('type' => 'string'),
			'nro_resolucion_spu' => array('type' => 'string'),
			'nro_disposicion_spu' => array('type' => 'string'),
			'nro_resolucion_institucion' => array('type' => 'string'),
			'fecha_ingreso' => array('type' => 'string'),
			'fecha_egreso' => array('type' => 'string'),
			'tiene_sanciones' => array('type' => 'string'),
			'titulo_anterior_nivel' => array('type' => 'string'),
			'titulo_anterior_origen' => array('type' => 'string'),
			'titulo_anterior_nacionalidad' => array('type' => 'string'),
			'titulo_anterior_institucion' => array('type' => 'string'),
			'titulo_anterior_denominacion' => array('type' => 'string'),
			'titulo_anterior_revalidado' => array('type' => 'string'),
			'titulo_anterior_nro_resolucion' => array('type' => 'string'),
			'titulo_apto_ejercicio' => array('type' => 'string'),
			'plan_vigente' => array('type' => 'string'),
			'tipo' => array('type' => 'string'),
			'actividad_nombre' => array('type' => 'string'),
			'actividad_codigo' => array('type' => 'string'),
			'creditos' => array('type' => 'string'),
			'fecha' => array('type' => 'string'),
			'nota' => array('type' => 'string'),
			'resultado' => array('type' => 'string'),
			'folio_fisico' => array('type' => 'string'),
			'acta_resolucion' => array('type' => 'string'),
			'promedio' => array('type' => 'string'),
			'promedio_sin_aplazos' => array('type' => 'string'),
			'forma_aprobacion' => array ('type' => 'string'),
			'es_optativa' => array('type' => 'string'),
		);
        
        $agenda = array(
			'tipo_actividad' => array('type' => 'string'),
			'tipo_persona' => array('type' => 'string'),
			'legajo' => array('type' => 'string'),
			'actividad' => array('type' => 'string'),
			'horario' => array('type' => 'string'),
			'ubicacion' => array('type' => 'string'),
            'modalidad' => array('type' => 'string'),
        );

		return array('Contacto' => $contacto, 'Persona' => $persona, 'Historia' => $historia, 'Agenda' => $agenda);
	}

	/**
	 * GET /personas
	 * 
	 * @param_query $usuario string  Usuario
	 * @param_query $pais string  País
	 * @param_query $tipo_documento integer Tipo de documento de la persona
	 * @param_query $numero_documento string Número de documento de la persona
	 * 
	 * @notes
	 * Devuelve una lista de personas. Buscar por país tipo y número de documento, o usuario<br>
	 * 
	 * @summary Personas
	 * @responses 200 array {"$ref":"Contacto"}
	 * @responses 404 La persona no existe
	 * @responses 400 Error en los parámetros
	 */
	function get_list()
	{
		$datos = $this->modelo->get_list();
		rest::response()->get($datos);
	}

	/**
	 * GET /personas/id_persona/datospersonales
	 * 
	 * @notes
	 * Devuelve datos de una persona.<br>
	 * 
	 * @summary Personas
	 * @responses 200 array {"$ref":"Persona"}
	 * @responses 404 La persona no existe
	 * @responses 400 Error en los parámetros
	 */
	function get_datospersonales_list($persona)
	{
		$datos = $this->modelo->get_datos_personales($persona);
		rest::response()->get($datos);
	}

	/**
	 * GET /personas/id_persona/datosanalitico
	 * 
	 * @param_query $codigo_titulo_araucano string Código del título Araucano
	 * 
	 * @notes
	 * Devuelve la historia academica de una persona.<br>
	 * 
	 * @summary Personas
	 * @responses 200 array {"$ref":"Historia"}
	 * @responses La persona no existe
	 * @responses 400 Error en los parámetros
	 */
	function get_datosanalitico_list($persona)
	{
		$datos = $this->modelo->get_datos_analitico($persona);
		rest::response()->get($datos);
	}
    
    /**
	 * GET /personas/id_persona/agenda
	 * 
 	 * @param_query $fecha string Fecha a buscar actividades
 	 * @param_query $modalidad bool solo busca presenciales.
	 * 
	 * @notes
	 * Devuelve la agenda del día de una persona.<br>
	 * 
	 * @summary Personas
	 * @responses 200 array {"$ref":"Agenda"}
	 * @responses La persona no existe
	 * @responses 400 Error en los parámetros
	 */
    function get_agenda_list($persona)
    {
        $datos = $this->modelo->get_datos_agenda_dia($persona);
		rest::response()->get($datos);
    }
}