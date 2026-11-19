<?php

namespace SIU\Guarani\php\rest\v2\convocatorias;

use SIUToba\rest\rest;
use SIUToba\rest\lib\rest_validador;
use SIUToba\rest\lib\modelable;
use PERS\Guarani\php\rest\v2\modelos\rest_convocatorias;

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

                // Documento para el aspirante
                $documentoAspirante = [
                    'requisito' => ['type' => 'integer'],
                    'nombre' => ['type' => 'string'],
                    'extension' => ['type' => 'string'],
                    'uuid_rdi' => ['type' => 'string'],
                    'obligatorio' => ['type' => 'string'],
                    'no_posee' => ['type' => 'string'],
                ];

                // Reapertura
                $reapertura = [
                    'fecha_reapertura' => ['type' => 'string', 'example' => '2024-01-10 11:45:36'],
                    'fecha_respuesta' => ['type' => 'string', 'example' => '2024-01-20 10:28:00'],
                    'datos_censales' => [
                        'type' => 'array',
                        'items' => ['$ref' => 'DatoCensal'],
                        'example' => [
                            ['campo' => 'cantidad_hijos', 'etiqueta' => 'Cantidad de hijos', 'valor' => '2']
                       ]
                    ],
                    'documentos' => [
                        'type' => 'array',
                        'items' => ['$ref' => 'DocumentoAspirante'],
                        'example' => [
                            ['requisito' => 502, 'nombre' => 'Recibo de Sueldo', 'extension' => 'pdf', 'uuid_rdi' => '52be88c7-5a90-49b1-9604-8810303456', 'obligatorio' => 'S', 'no_posee' => 'N'],
                        ]
                    ]
                ];


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
			'fecha_respuesta' => array('type' => 'string', 'example' => '2024-01-05 09:30:00'),
                        'documentos' => [
                            'type' => 'array',
                            'items' => ['$ref' => 'DocumentoAspirante'],
                            'example' => [
                                ['requisito' => 501, 'nombre' => 'Comprobante de Alquiler', 'extension' => null, 'uuid_rdi' => null, 'obligatorio' => 'N', 'no_posee' => 'S'],
                                ['requisito' => 502, 'nombre' => 'Recibo de Sueldo', 'extension' => 'pdf', 'uuid_rdi' => 'f90b06eb-73de-4bd3-bddc-5601f7343522', 'obligatorio' => 'S', 'no_posee' => 'N'],
                            ]
                        ],
                        'reaperturas' => ['type' => 'array', 'items' => ['$ref' => 'Reapertura']],
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

                // Documento para la definición
                $documentoDefinicion = [
                    'requisito' => ['type' => 'integer'],
                    'nombre' => ['type' => 'string'],
                    'obligatorio' => ['type' => 'string']
                ];

		$definicion_convocatoria = array(
			'titulo' => array('type' => 'string'),
			'descripcion' => array('type' => 'string'),
			'fecha_desde' => array('type' => 'string'),
			'fecha_hasta' => array('type' => 'string'),
			'fecha_evaluacion_ha_desde' => array('type' => 'string'),
			'fecha_evaluacion_ha_hasta' => array('type' => 'string'),
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
                        'documentos' => [
                            'type' => 'array',
                            'items' => ['$ref' => 'DocumentoDefinicion'],
                            'example' => [
                                ['requisito' => 501, 'nombre' => 'Comprobante de Alquiler', 'obligatorio' => 'N'],
                                ['requisito' => 502, 'nombre' => 'Recibo de Sueldo', 'obligatorio' => 'S']
                            ]
                        ]
		);

		$definicion_convocatoria_put = array(
			'fecha_desde' => array('type' => 'string', '_validar' => array(rest_validador::TIPO_DATE => array('format' => 'Y-m-d'))),
			'fecha_hasta' => array('type' => 'string', '_validar' => array(rest_validador::OBLIGATORIO, rest_validador::TIPO_DATE => array('format' => 'Y-m-d'))),
			'fecha_evaluacion_ha_desde' => array('type' => 'string', '_validar' => array(rest_validador::TIPO_DATE => array('format' => 'Y-m-d'))),
			'fecha_evaluacion_ha_hasta' => array('type' => 'string', '_validar' => array(rest_validador::OBLIGATORIO, rest_validador::TIPO_DATE => array('format' => 'Y-m-d'))),
			'descripcion' => array('type' => 'string', '_validar' => array(rest_validador::OBLIGATORIO, rest_validador::TIPO_TEXTO))
		);

		return array('Convocatoria' => $convocatoria,
			'DatoCensal' => $dato_censal,
			'Nacionalidad' => $nacionalidad,
			'DatosAcademicos' => $datos_academicos,
			'DocumentoAspirante' => $documentoAspirante,
			'Reapertura' => $reapertura,
			'Alcance' => $alcance,
			'Entidades' => $entidades,
			'Modalidad' => $modalidad,
			'Ubicacion' => $ubicacion,
			'AspiranteConvocatoria' => $aspirante_convocatoria,
			'DefinicionConvocatoria' => $definicion_convocatoria,
			'DatoCensalDefinicion' => $dato_censal_definicion,
			'DocumentoDefinicion' => $documentoDefinicion,
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
         * @param_query $cuil string Filtro por el CUIL del aspirante
         * @param_query $con_reaperturas string
         * S = devuelve solo aspirantes con reaperturas,
         * N = devuelve solo aspirantes sin reaperturas,
         * por defecto devuelve todos los aspirantes.
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