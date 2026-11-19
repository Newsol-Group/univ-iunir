<?php

namespace SIU\Guarani\php\rest\v1\docentes;

use SIUToba\rest\rest;
use SIUToba\rest\lib\rest_validador;
use SIUToba\rest\lib\modelable;
use PERS\Guarani\php\rest\v1\modelos\rest_docentes;

class recurso_docentes implements modelable
{
	/**
	 * @var rest_docentes
	 */
	protected $modelo;

	function __construct()
	{
		$this->modelo = \guarani::rest(rest_docentes::class);
	}

	public static function _get_modelos()
	{
		// Datos del docente.
		$docente = array(
			'docente' => array('type' => 'integer', '_validar' => array(rest_validador::TIPO_INT)),
			'legajo' => array('type' => 'string'),
			'pais_documento' => array('type' => 'integer'),
			'tipo_documento' => array('type' => 'integer'),
			'nro_documento' => array('type' => 'string'),
			'apellido' => array('type' => 'string'),
			'nombres' => array('type' => 'string'),
			'email' => array('type' => 'string'),
		);

		// Datos de la responsabilidad del docente en la comisión y de la comisión en sí.
		$responsabilidad_comision = array('responsabilidad' => array('type' => 'string'),
			'comision' => array('$ref' => '#/definitions/Comision'));

		$ubicacion = array('ubicacion' => array('type' => 'integer', '_validar' => array(rest_validador::TIPO_INT)),
			'nombre' => array('type' => 'string'));

		$actividad = array('codigo'	=> array('type' => 'string', '_mapeo' => 'codigo_actividad'),
			'nombre' => array('type' => 'string', '_mapeo' => 'nombre_actividad'));

		$mesa_examen = array('mesa_examen' => array('type' => 'integer', '_validar'	=> array(rest_validador::TIPO_INT)),
			'nombre' => array('type' => 'string'),
			'catedra' => array('type' => 'string', '_mapeo'	=> 'nombre_catedra'),
			'nombre_modalidades' => array('type' => 'string'),
			'nombre_instancias' => array('type' => 'string'),
			'ubicacion' => array('type' => 'array', 'items' => array('$ref' => 'Ubicacion')),
			'actividad' => array('type' => 'array', 'items' => array('$ref' => 'Actividad')),
		);

		$llamado_mesa = array('llamado_mesa' => array('type' => 'integer', '_validar' => array(rest_validador::TIPO_INT)),
			'fecha' => array('type' => 'string'),
			'hora_inicio' => array('type' => 'string'),
			'hora_finalizacion' => array('type' => 'string'),
			'cupo' => array('type' => 'integer'),
			'mesa_examen' => array('$ref' => '#/definitions/MesaExamen'));

		$rol_llamado_mesa = array('rol' => array('type' => 'string'),
			'llamado_mesa' => array('$ref' => '#/definitions/LlamadoMesa'));

		return array('Docente' => $docente,
			'ResponsabilidadComision' => $responsabilidad_comision,
			'Ubicacion' => $ubicacion,
			'Actividad' => $actividad,
			'MesaExamen' => $mesa_examen,
			'LlamadoMesa' => $llamado_mesa,
			'RolLlamadoMesa' => $rol_llamado_mesa);
	}

	/**
	 * GET /docentes
	 *
	 * @param_query $apellido string Apellido del docente (filtro)
	 * @param_query $nombres string Nombres del docente (filtro)
	 * @param_query $tipo_documento integer Tipo de documento del docente (filtro)
	 * @param_query $nro_documento string Número de documento del docente (filtro)
	 * @param_query $order string (defecto: +apellido,+nombres) +/-[apellido,nombres,legajo] Ordena por apellido, nombres y/o legajo
	 * @param_query $limit integer (defecto: 20, ilimitado: 0) Limita a esta cantidad de registros
	 * @param_query $page integer Página
	 * 
	 * @notes
	 * Devuelve una lista de docentes activos.<br>
	 * Los filtros para el tipo de dato <b>string</b> se definen como <b>condicion;valor</b>, donde <b>condicion</b> puede ser: <br/>
	 * entre, es_mayor_que, desde, es_mayor_igual_que, es_menor_que, es_menor_igual_que, hasta, es_igual_a, es_distinto_de, contiene, no_contiene, comienza_con, termina_con.
	 * 
	 * @summary Docentes activos
	 * @responses 200 array {"$ref":"Docente"}
	 */
	function get_list()
	{
		$datos = $this->modelo->get_list();
		rest::response()->get_list($datos);
	}

	/**
	 * GET /docentes/id_docente
	 *
	 * @notes 
	 * Devuelve los datos del docente {id_docente} e información de las comisiones en las que tiene alguna responsabilidad.<br>
	 * El docente debe estar activo.
	 * Las comisiones en las que tiene alguna responsabilidad pueden no estar activas.
	 *
	 * @summary Datos del docente {id_docente} y comisiones en las que tiene alguna responsabilidad
	 * @responses 200 {"$ref":"Docente"}
	 * @responses 404 El docente {id_docente} no existe
	 */
	function get($id_docente)
	{
		$datos = $this->modelo->get($id_docente);
		rest::response()->get($datos);
	}

	/**
	 * GET /docentes/id_docente/comisiones
	 *
	 * @notes
	 * Devuelve información de las comisiones en las que el docente {id_docente} tiene una responsabilidad.<br>
	 * El docente debe estar activo.<br>
	 * Las comisiones que se muestran no necesariamente están activas y corresponden a períodos lectivos vigentes.
	 * 
	 * @summary Comisiones vinculadas al docente {id_docente}
	 * @responses 200 array{"$ref":"ResponsabilidadComision"}
	 * @responses 404 El docente {id_docente} no existe 
	 */
	function get_comisiones_list($id_docente)
	{
		$this->modelo->get($id_docente); // para ver si existe
		$datos = $this->modelo->get_comisiones($id_docente);
		rest::response()->get($datos);
	}

	/**
	 * GET /docentes/id_docente/mesas
	 *
	 * @notes
	 * Devuelve información de las mesas de examen en las que el docente {id_docente} tiene un rol.<br>
	 * El docente debe estar activo.<br>
	 * Las mesas de examen que se muestran no necesariamente están activas y corresponden a turnos vigentes.
	 * 
	 * @summary Mesas de examen vinculadas al docente {id_docente}
	 * @responses 200 array{"$ref":"RolLlamadoMesa"}
	 * @responses 404 El docente {id_docente} no existe 
	 */
	function get_mesas_list($id_docente)
	{
		$this->modelo->get($id_docente);
		$datos = $this->modelo->get_mesas($id_docente);
		rest::response()->get($datos);
	}
}