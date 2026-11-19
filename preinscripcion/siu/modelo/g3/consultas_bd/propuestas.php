<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;
use siu\modelo\g3\interface_propuestas;
use siu\modelo\util;
use siu\modelo\propuesta;
use siu\modelo\opciones;
use siu\modelo\planes;
use siu\modelo\periodo_de_inscripcion;
use siu\modelo\comunes;

class propuestas extends entidad_g3 implements interface_propuestas
{
	protected static $instancia;
	
	protected $tiempo_cache = 30;
	
	protected $sql = array(
		'select' => 'propuesta, nombre, nombre_abreviado, codigo, propuesta_tipo, estado',
		'from' => 'sga_propuestas',
		'where' =>	// Activas
					"estado = 'A' " . 
					// Que tengan planes activos (vigentes o no vigentes)
					"AND EXISTS(	
						SELECT	1 
						FROM	sga_planes 
						WHERE	sga_propuestas.propuesta = sga_planes.propuesta 
						AND		sga_planes.estado in ('".planes::estado_activo."', '".planes::estado_vigente."')
					)" . 
					// Que no sean propuestas de tipo Cursos
					"AND propuesta_tipo <> '" . propuesta::tipo_CURSOS . "'",
		'order_by' => 'nombre',
		'clave' => 'propuesta',
		'campo_nombre' => 'nombre'
	);

	static function instancia()
	{
		$clase = substr(strrchr(get_class(), "\\"), 1);
		if (!isset(self::$instancia)) {
			self::$instancia = kernel::localizador()->instanciar("modelo\\g3\\consultas_bd\\$clase");
		}
		return self::$instancia;
	}
	
	function test($id)
	{
		// Se ejecuta el test general
		$resultado = parent::test($id);
		
		// Si falla el test general, no se continúa con el test particular
		if (!$resultado['resultado']) {
			return $resultado;
		}
		
		// Se testean las funciones particualres
		try {
			$datos = $resultado['datos'];
			$datos['nombre_abreviado'] = $this->get_nombre_abreviado($id);
			$datos['tipo'] = $this->get_tipo_propuesta($id);
			$datos['tipos_propuesta'] = $this->get_tipos_propuesta_sin_perfil_datos();
			$datos['ubicaciones'] = $this->get_ubicaciones_propuesta($id);
			$datos['modalidades'] = $this->get_modalidades_propuesta($id);
			$datos['descr_comprobante'] = $this->get_descr_comprobante($id);
			$datos['propuestas_1'] = $this->get_propuestas_disponibles_preinscripcion($id);
			$datos['propuestas_2'] = $this->get_propuestas_disponibles_preinscripcion($id, array('1'));
			$datos['propuestas_sin_pd'] = $this->get_propuestas_sin_perfil_datos();
			$salida = array(
				'resultado' => true,
				'datos' => $datos,
				'msj' => 'OK',
			);
		} catch (\Exception $ex) {
			$salida = array(
				'resultado' => false,
				'msj' => $ex->getMessage(),
			);
		}
		return $salida;
	}
	
	//--------------------------------------------------------------------------
	//---- Implementación interface_propuestas ---------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Obtiene el nombre abreviado de una propuesta
	 * 
	 * @param int $id
	 * @return string
	 */
	function get_nombre_abreviado($id = null)
	{
		$datos = $this->get_entidad($id);
		return $datos['nombre_abreviado'];
	}
	
	/**
	 * Obtiene el ID de tipo de propuesta a partir de una propuesta dada.
	 * 
	 * @param int $propuesta
	 * @return int
	 */
	function get_id_tipo_propuesta($propuesta)
	{
		$propuesta = kernel::db()->quote($propuesta);
		
		$sql = "SELECT 
					sga_propuestas_tipos.propuesta_tipo
				FROM 
					sga_propuestas,
					sga_propuestas_tipos
				WHERE
					sga_propuestas.propuesta = $propuesta
					AND sga_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo";
		
		$datos = $this->consultar_fila($sql);
		return $datos['propuesta_tipo'];
	}
	
	/**
	 * Obtiene la DESCRIPCIÓN de tipo de propuesta a partir de una propuesta dada.
	 * 
	 * @param int $propuesta
	 * @return string
	 */
	function get_tipo_propuesta($propuesta)
	{
		$propuesta = kernel::db()->quote($propuesta);
		
		$sql = "SELECT 
					sga_propuestas_tipos.propuesta_tipo,
					sga_propuestas_tipos.descripcion
				FROM 
					sga_propuestas,
					sga_propuestas_tipos
				WHERE
					sga_propuestas.propuesta = $propuesta
					AND sga_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo";
		
		$datos = $this->consultar_fila($sql);
		return formatear($datos['descripcion']);
	}
	
	/**
	 * Obtiene la lista de todos los tipos de propuesta (sin perfil de datos)
	 */
	function get_tipos_propuesta_sin_perfil_datos()
	{
		$sql = "SELECT 
					sga_propuestas_tipos.propuesta_tipo,
					sga_propuestas_tipos.descripcion
				FROM 
					sga_propuestas,
					sga_propuestas_tipos
				WHERE
					-- Se excluye el tipo de propuesta curso
					sga_propuestas_tipos.propuesta_tipo <> '" . propuesta::tipo_CURSOS . "'
				";
		
		$datos = $this->consultar($sql);
		return util::opciones()->armar_combo_opciones($datos, 'propuesta_tipo', 'descripcion', false, false);
	}
	
	/**
	 * Obtiene las ubicaciones donde se ofrece una propuesta y habilitadas
	 * 
	 * @param int $propuesta
	 * @return array
	 */
	function get_ubicaciones_propuesta($propuesta)
	{
		$propuesta = kernel::db()->quote($propuesta);
		$plan_estado_activo = kernel::db()->quote(planes::estado_activo);
		$plan_estado_vigente = kernel::db()->quote(planes::estado_vigente);
		$pi_tipo_todos = kernel::db()->quote(periodo_de_inscripcion::tipo_todos);
		$pi_tipo_preinscripcion = kernel::db()->quote(periodo_de_inscripcion::tipo_preinscripcion);
		$si = kernel::db()->quote(comunes::si);
		
		$where_perfil_datos = $this->filtro_perfil_datos_ubicacion();
		if (!empty($where_perfil_datos)) {
			$where_perfil_datos = ' AND ' . $where_perfil_datos;
		}
		
		$sql = "SELECT DISTINCT
				sga_ubicaciones.ubicacion,
				sga_ubicaciones.nombre
			FROM sga_planes
			JOIN sga_planes_versiones	ON (sga_planes_versiones.plan = sga_planes.plan)
			JOIN sga_propuestas_oferta	ON (sga_propuestas_oferta.propuesta = sga_planes.propuesta)
			JOIN sga_ubicaciones		ON (sga_ubicaciones.ubicacion = sga_propuestas_oferta.ubicacion)
			JOIN sga_per_insc_ubicacion	ON (sga_per_insc_ubicacion.ubicacion = sga_propuestas_oferta.ubicacion)
			JOIN vw_periodos_insc_propuesta	ON (vw_periodos_insc_propuesta.periodo_inscripcion = sga_per_insc_ubicacion.periodo_inscripcion)
			JOIN sga_periodos_inscripcion_aplanado	ON (sga_periodos_inscripcion_aplanado.periodo_insc  = vw_periodos_insc_propuesta.periodo_insc AND sga_periodos_inscripcion_aplanado.plan_version = sga_planes.version_actual)

			WHERE sga_planes.propuesta = $propuesta
			AND sga_planes_versiones.estado IN ($plan_estado_activo, $plan_estado_vigente)
			AND vw_periodos_insc_propuesta.periodo_insc_tipo IN ($pi_tipo_todos, $pi_tipo_preinscripcion)
			AND CURRENT_DATE BETWEEN vw_periodos_insc_propuesta.fecha_inicio AND vw_periodos_insc_propuesta.fecha_fin
			AND vw_periodos_insc_propuesta.habilitado = $si
			AND vw_periodos_insc_propuesta.habilitado_interfaz IN (0,3)
			AND sga_planes.inscripcion_habilitada = $si
			$where_perfil_datos
		";

		$datos = $this->consultar($sql);
		return util::opciones()->armar_combo_opciones($datos, 'ubicacion', 'nombre', true, false);
	}
	
	/**
	 * Obtiene las modalidades disponibles y habilitadas para una propuesta
	 * 
	 * @param int $propuesta
	 * @return array
	 */
	function get_modalidades_propuesta($propuesta, $ubicacion)
	{
		$propuesta = kernel::db()->quote($propuesta);
		$ubicacion = kernel::db()->quote($ubicacion);
		$plan_estado_activo = kernel::db()->quote(planes::estado_activo);
		$plan_estado_vigente = kernel::db()->quote(planes::estado_vigente);
		$pi_tipo_todos = kernel::db()->quote(periodo_de_inscripcion::tipo_todos);
		$pi_tipo_preinscripcion = kernel::db()->quote(periodo_de_inscripcion::tipo_preinscripcion);
		$si = kernel::db()->quote(comunes::si);
		
		$sql = "SELECT DISTINCT
				sga_planes_modalidad.modalidad,
				sga_modalidad_cursada.nombre
			FROM sga_planes
			JOIN sga_planes_versiones	ON (sga_planes_versiones.plan = sga_planes.plan)
			JOIN sga_planes_modalidad	ON (sga_planes_modalidad.plan = sga_planes.plan)
			JOIN sga_modalidad_cursada	ON (sga_modalidad_cursada.modalidad = sga_planes_modalidad.modalidad)
			JOIN sga_per_insc_modalidad	ON (sga_per_insc_modalidad.modalidad = sga_planes_modalidad.modalidad)
			JOIN vw_periodos_insc_propuesta	ON (vw_periodos_insc_propuesta.periodo_inscripcion = sga_per_insc_modalidad.periodo_inscripcion)
			JOIN sga_per_insc_ubicacion	ON (sga_per_insc_ubicacion.periodo_inscripcion = vw_periodos_insc_propuesta.periodo_inscripcion AND sga_per_insc_ubicacion.ubicacion = $ubicacion)
			JOIN sga_periodos_inscripcion_aplanado	ON (sga_periodos_inscripcion_aplanado.periodo_insc  = vw_periodos_insc_propuesta.periodo_insc AND sga_periodos_inscripcion_aplanado.plan_version = sga_planes.version_actual)

			WHERE sga_planes.propuesta = $propuesta
			AND sga_planes_versiones.estado IN ($plan_estado_activo, $plan_estado_vigente)
			AND vw_periodos_insc_propuesta.periodo_insc_tipo IN ($pi_tipo_todos, $pi_tipo_preinscripcion)
			AND CURRENT_DATE BETWEEN vw_periodos_insc_propuesta.fecha_inicio AND vw_periodos_insc_propuesta.fecha_fin
			AND vw_periodos_insc_propuesta.habilitado = $si
			AND vw_periodos_insc_propuesta.habilitado_interfaz IN (0,3)
			AND sga_planes.inscripcion_habilitada = $si
		";

		$datos = $this->consultar($sql);
		return util::opciones()->armar_combo_opciones($datos, 'modalidad', 'nombre', true, false);
	}

	/**
	 * Obtiene todas las propuestas disponibles y habilitadas para Preinscripción, a partir de
	 * una Responsable Académica dada, excluyendo las recibidas como segundo parámetro
	 * 
	 * @param int $ra
	 * @param array $excluir propuestas a excluir del listado
	 * @return array
	 */
function get_propuestas_disponibles_preinscripcion($ra, $excluir = array())
	{
		$si = kernel::db()->quote(comunes::si);
		$ra = kernel::db()->quote($ra);
		
		if (!empty($excluir)) {
			$where_excluir = ' AND sga_propuestas.propuesta NOT IN (' . implode(',', $excluir) . ')';
		} else {
			$where_excluir = '';
		}
		
		$filtro_perfil_datos = $this->filtro_perfil_datos();
		if (!empty($filtro_perfil_datos)) {
			$where_perfil_datos = ' AND ' . $filtro_perfil_datos;
		} else {
			$where_perfil_datos = '';
		}
		
		$sql = "SELECT	DISTINCT 
					sga_planes.propuesta,
					sga_propuestas.nombre
				FROM 	sga_propuestas_ra
				JOIN 	sga_propuestas		ON (sga_propuestas.propuesta = sga_propuestas_ra.propuesta)
				JOIN	sga_planes		ON (sga_planes.propuesta = sga_propuestas.propuesta)
				JOIN	sga_planes_versiones	ON (sga_planes_versiones.plan = sga_planes.plan)
				JOIN	sga_periodos_inscripcion_aplanado 	ON (sga_periodos_inscripcion_aplanado.plan_version = sga_planes_versiones.plan_version)
				JOIN	sga_periodos_inscripcion_fechas	ON (sga_periodos_inscripcion_fechas.periodo_insc = sga_periodos_inscripcion_aplanado.periodo_insc)
				JOIN	sga_periodos_inscripcion		ON (sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion)
				JOIN	sga_periodos_inscripcion_propuesta	ON (sga_periodos_inscripcion_propuesta.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion)

				WHERE	sga_propuestas_ra.responsable_academica = $ra
				AND sga_periodos_inscripcion_fechas.habilitado = 'S'
				AND sga_periodos_inscripcion_fechas.habilitado_interfaz IN (0,3)
				AND CURRENT_DATE BETWEEN sga_periodos_inscripcion_fechas.fecha_inicio AND sga_periodos_inscripcion_fechas.fecha_fin
				AND sga_periodos_inscripcion_propuesta.periodo_insc_tipo IN ('3', '4') -- Períodos de tipo: 'Todos' y 'Preinscripcion'
				AND sga_propuestas.propuesta_tipo <> '" . propuesta::tipo_CURSOS . "'
				AND sga_planes.inscripcion_habilitada = $si
				$where_excluir
				$where_perfil_datos
				";
		
		$datos = $this->consultar($sql);
		
		$salida = util::opciones()->armar_combo_opciones($datos, 'propuesta', 'nombre', true, false);
		if (empty($salida)) {
			$salida[opciones::NO_SELECCION] = kernel::traductor()->trans('sin_propuestas');
		}
		return $salida;
	}	
	
	/**
	 * Obtiene el listado de propuestas que tienen planes vigentes y no sean de tipo Cursos, sin pasar
	 * por el filtro del perfil de datos
	 * 
	 * @return array
	 */
	function get_propuestas_sin_perfil_datos()
	{
		$sql = "SELECT	sga_propuestas.propuesta,
				sga_propuestas.nombre
			FROM	sga_propuestas
			WHERE	EXISTS(SELECT	1 
					FROM	sga_planes 
					WHERE	sga_propuestas.propuesta = sga_planes.propuesta 
					AND	sga_planes.estado in ('".planes::estado_activo."', '".planes::estado_vigente."')
					)
			AND sga_propuestas.propuesta_tipo <> '" . propuesta::tipo_CURSOS . "'
			";
		
		$datos = $this->consultar($sql);
		return util::opciones()->armar_combo_opciones($datos, 'propuesta', 'nombre', false, false);
	}
	
	//--------------------------------------------------------------------------
	
	protected function filtro_perfil_datos()
	{
		$config = util::consultas()->get_config_perfil_datos();
		$filtro = '';
		// Perfil de datos por tipo de propuesta
		if (!empty($config['propuesta_tipo'])) {
			$filtro = "sga_propuestas.propuesta_tipo IN ('" . implode("', '", $config['propuesta_tipo']) . "')";
		}
		// Perfil de datos por propuesta
		if (!empty($config['propuesta'])) {
			if (!empty($filtro)) {
				$filtro .= ' AND ';
			}
			$filtro .= "sga_propuestas.propuesta IN ('" . implode("', '", $config['propuesta']) . "')";
		}
		return $filtro;
	}
	
	protected function filtro_perfil_datos_ubicacion()
	{
		$config = util::consultas()->get_config_perfil_datos();
		$filtro = '';
		// Perfil de datos por ubicacion
		if (!empty($config['ubicacion'])) {
			$filtro = "sga_ubicaciones.ubicacion IN ('" . implode("', '", $config['ubicacion']) . "')";
		}
		return $filtro;
	}
}
