<?php

namespace SIU\Guarani\php\rest\v2\comisiones;

use SIUToba\rest\rest;
use SIUToba\rest\lib\rest_validador;
use SIUToba\rest\lib\modelable;
use PERS\Guarani\php\rest\v2\modelos\rest_comisiones;

class recurso_comisiones implements modelable
{

	/**
	 * @var rest_comisiones
	 */
	protected $modelo;

	public static function _get_modelos(){
		
		$turno				= array('turno'		=> array('type'		=> 'integer', '_validar' => array(rest_validador::TIPO_INT)),
									'nombre'	=> array('type'		=> 'string', '_mapeo'	=> 'nombre'));

		$ubicacion			= array('ubicacion' => array('type'		=> 'integer', '_validar' => array(rest_validador::TIPO_INT)),
									'nombre'	=> array('type'		=> 'string')
								);

		$actividad			= array('codigo'	=> array('type'		=> 'string', '_mapeo'	=> 'codigo_actividad'),
									'nombre'	=> array('type'		=> 'string', '_mapeo'	=> 'nombre_actividad'));

		$horarioComision	= array('dia'		=> array('type'		=> 'string', '_mapeo'	=> 'horario_dia'),
									'inicio'	=> array('type'		=> 'string', '_mapeo'	=> 'horario_inicio'),
									'fin'		=> array('type'		=> 'string', '_mapeo'	=> 'horario_fin'));

		$periodo_lectivo	= array('periodo_lectivo'	=> array('type'		=> 'integer', '_validar' => array(rest_validador::TIPO_INT)),
									'nombre'			=> array('type'		=> 'string'));

		$docenteComision	= array('responsabilidad'	=> array('type'		=> 'string'),
									'docente'			=> array('type'		=> 'integer'),
									'legajo'			=> array('type'		=> 'string'),
									'documento'			=> array('type'		=> 'string'),
									'apellido'			=> array('type'		=> 'string'),
									'nombres'			=> array('type'		=> 'string'));

		$comision =  array(	'comision'	=> array('type'		=> 'integer', '_validar'	=> array(rest_validador::TIPO_INT)),
							'nombre'			=> array('type'		=> 'string'),
							'catedra'			=> array('type'		=> 'string', '_mapeo'	=> 'nombre_catedra'),
							'modalidades'		=> array('type'		=> 'array', '_mapeo'	=> 'nombre_modalidad', 'items'		=> array('type' => 'string')),
							'instancias'		=> array('type'		=> 'array', '_mapeo'	=> 'nombre_instancias', 'items'		=> array('type' => 'string')),
							'turno'				=> array('_mapeo'	=> 'turno_arr', '$ref'		=> '#/definitions/Turno'),
							'ubicacion'			=> array('_mapeo'	=> 'ubicacion_arr', '$ref'		=> '#/definitions/Ubicacion'),
							'actividad'			=> array('_mapeo'	=> 'actividad_arr', '$ref'		=> '#/definitions/Actividad'),
							'periodo_lectivo'	=> array('_mapeo'	=> 'periodo_lectivo_arr', '$ref'		=> '#/definitions/PeriodoLectivo'),
							'horarios'			=> array('type'	=> 'array', 'items'	=> array('$ref'=> 'HorarioComision')),
							'docentes'			=> array('type'	=> 'array', 'items'	=> array('$ref'=> 'DocenteComision'))
			);
	
		return array('Comision'					=> $comision,
					'Actividad'					=> $actividad,
					'HorarioComision'			=> $horarioComision,
					'PeriodoLectivo'			=> $periodo_lectivo,
					'Turno'						=> $turno,
					'Ubicacion'					=> $ubicacion,
					'DocenteComision'			=> $docenteComision
					);
	}

	function __construct()
	{
		$this->modelo = \guarani::rest(rest_comisiones::class);
	}

	/**
	 * GET /comisiones
	 * 
	 * @param_query $nombre string Nombre de la comision (Filtro)
	 * @param_query $actividad string Nombre de la actividad (Filtro)
	 * @param_query $actividad_codigo string (defecto: null) Pertenecientes a la actividad con este código
	 * @param_query $con_horarios string (defecto: 0) [1/0] Incluir campos de horarios
	 * @param_query $con_docentes string (defecto: 0) [1/0] Incluir campos de docentes
	 * @param_query $order string (defecto: +actividad) +/-[actividad,comision] Ordenar por nombres de actividad y/o comisión
	 * @param_query $limit integer (defecto: 20) Limitar a esta cantidad de registros
	 * @param_query $page integer (defecto: 0) Página
	 * 
	 * @notes
	 * Devuelve una lista de comisiones activas.<br>
	 * El dato de horarios y docentes es opcional, hay que pasarlo como parámetro.<br>
	 * Los Filtros se definen como <b>condicion;valor</b>, donde <b>condicion</b> puede ser: <br/>
	 * entre, es_mayor_que, desde, es_mayor_igual_que, es_menor_que, es_menor_igual_que, hasta, es_igual_a, es_distinto_de, contiene, no_contiene, comienza_con, termina_con
	 * 
	 * @summary Comisiones activas
	 * @responses 200 array {"$ref":"Comision"}
	 */
	function get_list()
	{
		$datos = $this->modelo->get_list();
		rest::response()->get_list($datos);
	}

	/**
	 * GET /comisiones/id_comision
	 *
	 * @notes 
	 * Devuelve los datos de la comisión {id_comision}, incluyendo los horarios y docentes. <br>
	 * No controla que la comisión esté activa.<br>
	 * Devuelve también un arreglo con los docentes de la comisión y su responsabilidad en la misma.<br>
	 * Para que devuelva los docentes, el mismo debe estar activo y el período lectivo también.
	 *
	 * @summary Datos de la comisión {id_comision}
	 * @responses 200 {"$ref":"Comision"}
	 * @responses 404 La comisión {id_comision} no existe
	 */
	function get($id_comision)
	{
		$datos = $this->modelo->get($id_comision);
		rest::response()->get($datos);
	}

	/**
	 * GET /comisiones/id_comision/docentes
	 *
	 * @notes 
	 * Devuelve la lista de docentes de la comision {id_comision} junto a su responsabilidad en la misma.<br>
	 * Para que devuelva el docente, el período lectivo debe estar activo.
	 *
	 * @summary Docentes de la comisión {id_comision}
	 * @responses 200 array {"$ref":"DocenteComision"}
	 * @responses 404 La comisión {id_comision} no existe
	 */
    function get_docentes_list($id_comision)
    {
	    $comision = $this->modelo->get($id_comision); // para ver si existe
	    $datos = $comision['docentes'];
	    rest::response()->get_list($datos);
    }

}