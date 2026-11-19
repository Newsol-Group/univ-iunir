<?php

namespace SIU\Guarani\php\rest\v1\convocatorias;

use SIUToba\rest\rest;
use SIUToba\rest\lib\rest_validador;
use SIUToba\rest\lib\modelable;
use PERS\Guarani\php\rest\v1\modelos\rest_convocatorias;

class recurso_convocatorias implements modelable
{

    /**
     * @var rest_convocatorias
     */
    protected $modelo;
	
	function __construct()
    {
		$this->modelo = \guarani::rest(rest_convocatorias::class);
    }

	public static function _get_modelos()
	{
		$convocatoria = array(
			'convocatoria' => array('type' => 'integer', '_validar' => array(rest_validador::TIPO_INT)),
			'titulo' => array('type' => 'string')
		);

		$dato_censal = array(
			'campo' => array('type' => 'string'),
			'etiqueta' => array('type' => 'string'),
			'valor' => array('type' => 'string'),
		);

		$nacionalidad = array(
			'valor' => array('type' => 'string'),
			'descripcion' => array('type' => 'string'),
		);

		$datos_academicos = array(
			'propuesta' => array('type' => 'integer'),
			'descripcion' => array('type' => 'string'),
			'promedio_con_aplazos' => array('type' => 'float'),
			'promedio_sin_aplazos' => array('type' => 'float'),
			'cantidad_actividades_aprobadas' => array('type' => 'integer')
		);

		$aspirante_convocatoria = array(
			'pais_documento' => array('type' => 'integer'),
			'tipo_documento' => array('type' => 'integer'),
			'nro_documento' => array('type' => 'string'),
			'apellido' => array('type' => 'string'),
			'nombres' => array('type' => 'string'),
			'genero' => array('type' => 'string'),
			'identidad_genero' => array('type' => 'integer'),
			'identidad_genero_otro' => array('type' => 'string'),
			'fecha_nacimiento' => array('type' => 'string'),
			'nacionalidad' => array('type' => 'array', 'items' => array('$ref' => 'Nacionalidad')),
			'cuil' => array('type' => 'string'),
			'email' => array('type' => 'string'),
			'datos_censales' => array('type' => 'array', 'items' => array('$ref' => 'DatoCensal')),
			'datos_academicos' => array('type' => 'array', 'items' => array('$ref' => 'DatosAcademicos')),
			'formulario' => array('type' => 'integer'),
			'hash' => array('type' => 'string'),
			'fecha_respuesta' => array('type' => 'string')
		);
		
		$entidades = array(
			'valor' =>  array('type' => 'integer'),
			'descripcion' => array('type' => 'string'),
		);
		
		
		$alcance = array(
			'valor' =>  array('type' => 'integer'),
			'descripcion' => array('type' => 'string'),
			'entidades' => array('type' => 'array', 'items' => array('$ref' => 'Entidades')),
		);
		
		$modalidad = array(
			'valor' =>  array('type' => 'integer'),
			'descripcion' => array('type' => 'string')
		);
		
		$ubicacion = array(
			'valor' =>  array('type' => 'integer'),
			'descripcion' =>  array('type' => 'string'),
			'tipo' => array('type' => 'string'),
			'localidad' => array('type' => 'integer')
		);
		
		$dato_censal_definicion = array(
			array('etiqueta' => array('type' => 'string'))
			
		);

		$definicion_convocatoria = array(
			'titulo' => array('type' => 'string'),
			'descripcion' => array('type' => 'string'),
			'fecha_desde' => array('type' => 'string'),
			'fecha_hasta' => array('type' => 'string'),
			'fecha_evaluacion_ha_desde' => array('type' => 'string'),
			'fecha_evaluacion_ha' => array('type' => 'string'),
			'activo' => array('type' => 'string'),
			'kolla_id_habilitacion' => array('type' => 'integer'),
			'kolla_unidad_gestion' => array('type' => 'integer'),
			'edad_desde' => array('type' => 'integer'),
			'edad_hasta' => array('type' => 'integer'),
			'trabaja' => array('type' => 'string'),
			'tiene_hijos' => array('type' => 'string'),
			'alquila' => array('type' => 'string'),
			'tiene_cobertura_de_salud' => array('type' => 'string'),
			'vive_cursa_misma_localidad' => array('type' => 'string'),
			'alcance' => array('type' => 'array', 'items' => array('$ref' => 'Alcance')),
			'modalidades' => array('type' => 'array', 'items' => array('$ref' => 'Modalidad')),
			'ubicaciones' => array('type' => 'array', 'items' => array('$ref' => 'Ubicacion')),
			'datos_censales' => array('type' => 'array', 'items' => array('$ref' => 'DatoCensalDefinicion')),
		);

		$definicion_convocatoria_put = array(
			'fecha_desde' => array('type' => 'string', '_validar' => array(rest_validador::TIPO_DATE => array('format' => 'Y-m-d'))),
			'fecha_hasta' => array('type' => 'string', '_validar' => array(rest_validador::OBLIGATORIO, rest_validador::TIPO_DATE => array('format' => 'Y-m-d'))),
			'fecha_evaluacion_ha_desde' => array('type' => 'string', '_validar' => array(rest_validador::TIPO_DATE => array('format' => 'Y-m-d'))),
			'fecha_evaluacion_ha' => array('type' => 'string', '_validar' => array(rest_validador::OBLIGATORIO, rest_validador::TIPO_DATE => array('format' => 'Y-m-d'))),
			'descripcion' => array('type' => 'string', '_validar' => array(rest_validador::OBLIGATORIO, rest_validador::TIPO_TEXTO))
		);

		return array('Convocatoria' => $convocatoria,
			'DatoCensal' => $dato_censal,
			'Nacionalidad' => $nacionalidad,
			'DatosAcademicos' => $datos_academicos,
			'Alcance' => $alcance,
			'Entidades' => $entidades,
			'Modalidad' => $modalidad,
			'Ubicacion' => $ubicacion,
			'AspiranteConvocatoria' => $aspirante_convocatoria,
			'DefinicionConvocatoria' => $definicion_convocatoria,
			'DatoCensalDefinicion' => $dato_censal_definicion,
			'DefinicionConvocatoriaPut' => $definicion_convocatoria_put
			);
	}

  
	/**
	 * GET /convocatorias
	 * 
	 * @param_query $titulo string Título de la convocatoria (Filtro)
	 * 
	 * @notes
	 * Devuelve una lista de convocatorias.<br>
	 * Los Filtros se definen como <b>condicion;valor</b>, donde <b>condicion</b> puede ser: <br/>
	 * entre, es_mayor_que, desde, es_mayor_igual_que, es_menor_que, es_menor_igual_que, hasta, es_igual_a, es_distinto_de, contiene, no_contiene, comienza_con, termina_con
	 * 
	 * @summary Convocatorias
	 * @responses 200 array {"$ref":"Convocatoria"}
	 */
	function get_list()
	{
		$datos = $this->modelo->get_list();
		rest::response()->get_list($datos);
	}
	
	/**
	 * GET /convocatorias/id_convocatoria/aspirantes
	 *
	 * @notes 
	 * Devuelve la lista de los aspirantes a la convocatoria {id_convocatoria}.<br>
	 *
	 * @summary Aspirantes a la convocatoria {id_convocatoria}
	 * @responses 200 array {"$ref":"AspiranteConvocatoria"}
	 * @responses 404 La convocatoria {id_convocatoria} no existe
	 * @responses 400 Error en los parámetros
	 */
    function get_aspirantes_list($id_convocatoria)
    {
	    $aspirantes = $this->modelo->get_aspirantes($id_convocatoria); // para ver si existe
	    rest::response()->get_list($aspirantes);
    }
	
	
	/**
	 * GET /convocatorias/id_convocatoria/definicion
	 *
	 * @notes Permite obtener la definición del relevamiento asociado con una convocatoria.<br>
	 *
	 * @summary Definición de la convocatoria {id_convocatoria}
	 * @responses 200 array {"$ref":"DefinicionConvocatoria"}
	 * @responses 404 La convocatoria {id_convocatoria} no existe
	 * @responses 400 Error en los parámetros
	 */
    function get_definicion_list($id_convocatoria)
    {
	    $definicion = $this->modelo->get_definicion($id_convocatoria); // para ver si existe
	    rest::response()->get($definicion);
    }

	/**
	 * PUT /convocatorias/id_convocatoria
	 * 
	 * @notes
	 * Modifica los datos del relevamiento asociado a una convocatoria.<br>
	 *        
	 * @param_body $convocatoria DefinicionConvocatoriaPut [required] Datos de la convocatoria
	 * 
	 * @summary Modificación de la convocatoria {id_convocatoria}
	 * @responses  204 La convocatoria se modificó con éxito
	 * @responses  400 Errores de validación
     * @responses  404 Convocatoria no encontrada
	 * @responses  500 Falló la modificación de la convocatoria
	 **/
	function put($id_convocatoria)
	{
		$this->modelo->put($id_convocatoria, rest::request()->get_body_json());		
		rest::response()->put();
	}
}