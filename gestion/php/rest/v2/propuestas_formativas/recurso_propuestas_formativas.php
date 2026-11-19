<?php

namespace SIU\Guarani\php\rest\v2\propuestas_formativas;

use SIUToba\rest\rest;
use SIUToba\rest\lib\rest_validador;
use SIUToba\rest\lib\modelable;
use PERS\Guarani\php\rest\v2\modelos\rest_propuestas_formativas;

class recurso_propuestas_formativas implements modelable
{

	/**
	 * @var rest_propuestas_formativas
	 */
	protected $modelo;

	public static function _get_modelos(){
		
		$institucion = [
			'institucion'		=> ['type' => 'integer', '_validar' => [rest_validador::OBLIGATORIO, rest_validador::TIPO_INT]],
			'codigo'			=> ['type' => 'string', '_validar'	=> [rest_validador::TIPO_TEXTO, rest_validador::TIPO_LONGITUD => 10]],
			'nombre'			=> ['type' => 'string', '_validar'	=> [rest_validador::OBLIGATORIO, rest_validador::TIPO_TEXTO, rest_validador::TIPO_LONGITUD => 255]],
			'nombre_abreviado'	=> ['type' => 'string', '_validar'	=> [rest_validador::OBLIGATORIO, rest_validador::TIPO_TEXTO, rest_validador::TIPO_LONGITUD => 50]],
		];

		$responsableAcademica = [
			'responsable_academica' => ['type' => 'integer', '_validar' => [rest_validador::OBLIGATORIO, rest_validador::TIPO_INT]],
			'codigo'				=> ['type' => 'string', '_validar'	=> [rest_validador::OBLIGATORIO, rest_validador::TIPO_TEXTO, rest_validador::TIPO_LONGITUD => 10]],
			'nombre'				=> ['type' => 'string', '_validar'	=> [rest_validador::OBLIGATORIO, rest_validador::TIPO_TEXTO, rest_validador::TIPO_LONGITUD => 200]],
			'institucion'			=> ['$ref' => '#/definitions/Institucion', '_validar' => [rest_validador::OBLIGATORIO]],
		];

		$tipoPropuesta = [
			'tipo'				=> ['type' => 'integer', '_validar' => [rest_validador::OBLIGATORIO, rest_validador::TIPO_INT]],
			'descripcion'		=> ['type' => 'string', '_validar'	=> [rest_validador::OBLIGATORIO, rest_validador::TIPO_TEXTO, rest_validador::TIPO_LONGITUD => 100]],
		];

		$propuesta = [
			'propuesta'					=> ['type' => 'integer', '_validar' => [rest_validador::OBLIGATORIO, rest_validador::TIPO_INT]],
			'codigo'					=> ['type' => 'string', '_validar'	=> [rest_validador::OBLIGATORIO, rest_validador::TIPO_TEXTO, rest_validador::TIPO_LONGITUD => 10]],
			'nombre'					=> ['type' => 'string', '_validar'	=> [rest_validador::OBLIGATORIO, rest_validador::TIPO_TEXTO, rest_validador::TIPO_LONGITUD => 255]],
			'nombre_abreviado'			=> ['type' => 'string', '_validar'	=> [rest_validador::OBLIGATORIO, rest_validador::TIPO_TEXTO, rest_validador::TIPO_LONGITUD => 50]],
			'tipo'						=> ['$ref' => '#/definitions/TipoPropuesta', '_validar' => [rest_validador::OBLIGATORIO]],
			'responsables_academicas'	=> ['type' => 'array', 'items' => ['$ref' => 'ResponsableAcademica'], '_validar' => [rest_validador::OBLIGATORIO]],
		];

		return [
			'Institucion'			=> $institucion,
			'ResponsableAcademica'	=> $responsableAcademica,
			'TipoPropuesta'			=> $tipoPropuesta,
			'Propuesta'				=> $propuesta,
			];
	}

	function __construct()
	{
		$this->modelo = \guarani::rest(rest_propuestas_formativas::class);
	}

	/**
	 * GET /propuestas-formativas
	 * 
	 * @param_query $propuesta_codigo string Código de la propuesta (Filtro)
	 * @param_query $propuesta_nombre string Nombre de la propuesta (Filtro)
	 * @param_query $ra_codigo string Código de la Responsable Académica  (Filtro)
	 * @param_query $ra_nombre string Código de la Responsable Académica  (Filtro)
	 * 
	 * @param_query $order string (defecto: +propuesta_nombre) +/-[propuesta_codigo,propuesta_nombre] Ordenar por código/nombre de propuesta
	 * 
	 * @notes
	 * Devuelve una lista de propuestas formativas.<br>
	 * Los Filtros se definen como <b>condicion;valor</b>, donde <b>condicion</b> puede ser: <br/>
	 * entre, es_mayor_que, desde, es_mayor_igual_que, es_menor_que, es_menor_igual_que, hasta, es_igual_a, es_distinto_de, contiene, no_contiene, comienza_con, termina_con
	 * 
	 * @summary Propuestas formativas
	 * @responses 200 array {"$ref":"Propuesta"}
	 */
	function get_list()
	{
		$datos = $this->modelo->get_list();
		rest::response()->get_list($datos);
	}

	/**
	 * GET /propuestas-formativas/id_propuesta
	 *
	 * @notes 
	 * Devuelve los datos de la propuesta formativa {id_propuesta}. <br>
	 *
	 * @summary Datos de la propuesta formativa {id_propuesta}
	 * @responses 200 {"$ref":"Propuesta"}
	 * @responses 404 La propuesta formativa {id_propuesta} no existe
	 */
	function get($id_propuesta)
	{
		$datos = $this->modelo->get($id_propuesta);
		rest::response()->get($datos);
	}

}
