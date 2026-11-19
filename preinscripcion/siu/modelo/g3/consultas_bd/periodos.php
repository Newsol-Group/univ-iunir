<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;
use siu\modelo\g3\interface_periodos;
use siu\modelo\comunes;

class periodos extends _consultas_bd implements interface_periodos
{
	protected static $instancia;
	
	// Se redefine el tiempo de caché para esta entidad a 30 segundos
	protected $tiempo_cache = 30;
	
	static function instancia()
	{
		$clase = substr(strrchr(get_class(), "\\"), 1);
		if (!isset(self::$instancia)) {
			self::$instancia = kernel::localizador()->instanciar("modelo\\g3\\consultas_bd\\$clase");
		}
		return self::$instancia;
	}
	
	//--------------------------------------------------------------------------
	//---- Implementación interface_periodos -----------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Determina si existe un período de inscripción vigente de tipos 
	 * "Preinscripción" o "Todos"
	 * 
	 * @return boolean
	 */
	function existe_periodo_abierto()
	{
		$periodos = $this->get_periodos_preinscripcion_vigentes();
		return (count($periodos) > 0);
	}
	
	protected function get_periodos_preinscripcion_vigentes()
	{
		$sql = "SELECT 	
					sga_periodos_inscripcion.periodo_inscripcion,
					sga_periodos_inscripcion.nombre
				FROM 	
					sga_periodos_inscripcion,
					sga_periodos_inscripcion_propuesta
				WHERE 	
					sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_propuesta.periodo_inscripcion 
					AND sga_periodos_inscripcion_propuesta.periodo_insc_tipo IN ('3', '4') -- Períodos de tipo: 'Todos' y 'Preinscripcion'
					AND EXISTS (
						SELECT 	1
						FROM 	sga_periodos_inscripcion_fechas
						WHERE	sga_periodos_inscripcion_fechas.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion
								AND CURRENT_DATE BETWEEN sga_periodos_inscripcion_fechas.fecha_inicio AND sga_periodos_inscripcion_fechas.fecha_fin 
					)
				";
		
		return $this->consultar($sql);
	}
	
	/**
	 * Obtiene el período de Preinscripción vigente (periodo_insc) para una propuesta dada.
	 * Da prioridad a los períodos de tipo Preinscripción (por encima de los de tipo "Todos").
	 * En caso de haber más de uno, decide por el que comience primero.
	 * 
	 * @param int $propuesta
	 * @param int $ubicacion
	 * @param $modalidad
	 * @return integer $periodo_insc
	 */
	function get_periodo_preinscripcion($propuesta, $ubicacion, $modalidad)
	{
		$propuesta = kernel::db()->quote($propuesta);
		$ubicacion = kernel::db()->quote($ubicacion);
		$modalidad = kernel::db()->quote($modalidad);
		
		$sql = "SELECT DISTINCT
					vw_periodos_insc_propuesta.periodo_insc,
					vw_periodos_insc_propuesta.periodo_inscripcion,
					vw_periodos_insc_propuesta.fecha_inicio,
					vw_periodos_insc_propuesta.periodo_insc_tipo
				FROM vw_periodos_insc_propuesta
				JOIN sga_periodos_inscripcion_aplanado  ON (sga_periodos_inscripcion_aplanado.periodo_insc  = vw_periodos_insc_propuesta.periodo_insc)
				JOIN sga_per_insc_ubicacion             ON (sga_per_insc_ubicacion.periodo_inscripcion      = vw_periodos_insc_propuesta.periodo_inscripcion)
				JOIN sga_per_insc_modalidad             ON (sga_per_insc_modalidad.periodo_inscripcion      = vw_periodos_insc_propuesta.periodo_inscripcion) 
				JOIN sga_planes_versiones               ON (sga_planes_versiones.plan_version               = sga_periodos_inscripcion_aplanado.plan_version)
				JOIN sga_planes                         ON (sga_planes.plan                                 = sga_planes_versiones.plan)
				WHERE 
					-- Períodos de tipo: 'Todos' y 'Preinscripcion'
					vw_periodos_insc_propuesta.periodo_insc_tipo IN ('3', '4') 
					-- De la propuesta especificada
					AND sga_planes.propuesta = $propuesta
					-- De la ubicacion especificada
					AND sga_per_insc_ubicacion.ubicacion = $ubicacion
					-- De la modalidad especificada
					AND sga_per_insc_modalidad.modalidad = $modalidad
					-- Vigente y habilitada
					AND CURRENT_DATE BETWEEN vw_periodos_insc_propuesta.fecha_inicio AND vw_periodos_insc_propuesta.fecha_fin 
					AND vw_periodos_insc_propuesta.habilitado = '" . comunes::si . "'
				ORDER BY
					-- Prioridad para los períodos especificos de tipo 'Preinscripción'
					periodo_insc_tipo DESC,
					-- Prioridad para los primeros en comenzar 
					fecha_inicio 
				LIMIT 1 
				";
		
		$datos = $this->consultar_fila($sql);
		return $datos['periodo_insc'];
	}
	
	/**
	 * Determina si un período de Preinscripción dado se encuentra vigente
	 * 
	 * @param int $periodo_preinscripcion
	 * @return boolean
	 */
	function periodo_vigente($periodo_preinscripcion)
	{
		$periodo_preinscripcion = kernel::db()->quote($periodo_preinscripcion);
		
		$sql = "SELECT 
					1 AS vigente
				FROM 	
					sga_periodos_inscripcion,
					sga_periodos_inscripcion_fechas
				WHERE 	
					sga_periodos_inscripcion.periodo_inscripcion = $periodo_preinscripcion
					AND sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion
					AND CURRENT_DATE BETWEEN sga_periodos_inscripcion_fechas.fecha_inicio AND sga_periodos_inscripcion_fechas.fecha_fin
				";
		
		$res = $this->consultar_fila($sql);
		return !empty($res['vigente']);
	}
	
	/**
	 * Se ejecutan las funciones de acceso a datos
	 * 
	 * @return boolean
	 */
	function test($id)
	{
		try {
			$datos = array();
			$datos['periodo_abierto'] = $this->existe_periodo_abierto();
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

}
?>