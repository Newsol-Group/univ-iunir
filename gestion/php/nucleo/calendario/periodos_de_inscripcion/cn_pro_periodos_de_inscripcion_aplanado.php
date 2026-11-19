<?php
/**
 * Proceso que genera el aplanado de periodos de inscripcion
 *
 *
 * @package Calendario
 * @subpackage Periodos
 */
class cn_pro_periodos_de_inscripcion_aplanado extends cn_proceso
{

	/**
	 * Se define un método de procesamiento particular porque no se quiere abrir una nueva transacción.
	 */
	function procesar($parametros = NULL, $transaccionar = true, $limpiar_memoria = true)
	{
		$this->procesar_especifico($parametros);
	}

	function procesar_especifico($periodo_inscripcion=null)
	{
		$filtro = '';
		if (is_null($periodo_inscripcion)) {
			// Si no hay período de inscripción se trata de un aplanado desde "REPORTE DE PERÍODOS DE INSCRIPCIÓN POR PROPUESTA".
			// Se aplanan sólo períodos vigentes o futuros de acuerdo a la fecha de hoy.
			$filtro = "CURRENT_DATE <=	CASE
											-- Para turnos y períodos lectivos se toma la fecha de finalización del período
											-- porque siempre va a ser mayor a la de finalización y tope de bajas de sus rangos.
											WHEN sga_periodos.fecha_fin IS NOT NULL THEN sga_periodos.fecha_fin
											-- Para períodos de inscripción a propuesta se toma la fecha de finalización del rango
											-- porque la de tope de bajas no existe y no hay período asociado.
											ELSE sga_periodos_inscripcion_fechas.fecha_fin
										END";
		}
		// Se obtienen los registros asociados de sga_periodos_inscripcion_fechas.
		$periodos_inscripcion_fechas = toba::consulta_php('co_periodos_de_inscripcion')->get_periodos_inscripcion_fechas($periodo_inscripcion, $filtro);
		foreach ($periodos_inscripcion_fechas as $periodo_inscripcion_fechas) {
			$this->aplanar($periodo_inscripcion_fechas);
		}
	}

	function aplanar($periodo_inscripcion_fechas)
	{
		// El primer paso es borrar todos los registros existentes en sga_periodos_inscripcion_aplanado para el rango de fechas.
		guarani::act('act_periodos_de_inscripcion')->eliminar_aplanado($periodo_inscripcion_fechas['periodo_insc']);

		$subtipos = toba::consulta_php('co_periodos_de_inscripcion')->get_subtipos($periodo_inscripcion_fechas['periodo_insc']);
		$entidades = toba::consulta_php('co_periodos_de_inscripcion')->get_entidades($periodo_inscripcion_fechas['periodo_insc']);

		$propuestas = array();
		switch ($periodo_inscripcion_fechas['entidad_tipo']) {
			case g3entidad::tipo_institucion:
				// Para el caso de instituciones, existe siempre una única entidad: aquella institución con gestión académica (la institución de instalación).
				$entidad = current($entidades);
				$planes_versiones = toba::consulta_php('co_planes')->get_planes_versiones_por_institucion($entidad['entidad']);
				if ($periodo_inscripcion_fechas['periodo_generico_tipo'] == periodo_generico::tipo_propuesta) {
					$propuestas = toba::consulta_php('co_propuestas')->get_propuestas_por_institucion($entidad['entidad']);
				}
				break;

			case g3entidad::tipo_responsable_academica:
				// Si se seleccionó la opción 'Algunas Responsables Académicas', en $entidades estarán aquellas entidades correspondientes a las responsables académicas que fueron elegidas.
				$planes_versiones = toba::consulta_php('co_planes')->get_planes_versiones_por_ra($entidades);
				if ($periodo_inscripcion_fechas['periodo_generico_tipo'] == periodo_generico::tipo_propuesta) {
					$propuestas = toba::consulta_php('co_propuestas')->get_propuestas_por_ra($entidades);
				}
				break;

			case g3entidad::tipo_propuesta:
				// Si se seleccionó la opción 'Algunos Tipos de Propuestas', se deben recuperar aquellas propuestas de los subtipos (grado, posgrado, etc) seleccionados.
				// Si se seleccionó la opción 'Algunas Propuestas', en $entidades estarán aquellas entidades correspondientes a las propuestas que fueron elegidas.
				$planes_versiones = toba::consulta_php('co_planes')->get_planes_versiones_por_propuesta($subtipos, $entidades);
				if ($periodo_inscripcion_fechas['periodo_generico_tipo'] == periodo_generico::tipo_propuesta) {
					$propuestas = toba::consulta_php('co_propuestas')->get_propuestas_por_subtipos_entidades($subtipos, $entidades);
				}
				break;

			case g3entidad::tipo_plan_estudio:
				// Si se seleccionó la opción 'Algunos Planes de Estudio', en $entidades estarán aquellas entidades correspondientes a los planes que fueron elegidas.
				$planes_versiones = toba::consulta_php('co_planes')->get_planes_versiones_por_plan($entidades);
				break;

			case g3entidad::tipo_plan_estudio_version:
				// Si se seleccionó la opción 'Algunas Versiones del Plan de Estudios', en $entidades estarán aquellas entidades correspondientes a las versiones de planes que fueron elegidas.
				$planes_versiones = toba::consulta_php('co_planes')->get_planes_versiones($entidades);
				break;
		}

		// Se insertan los registros correspondientes en la tabla de aplanado.
		foreach ($planes_versiones as $plan_version) {
			guarani::act('act_periodos_de_inscripcion')->insertar_aplanado($periodo_inscripcion_fechas['periodo_insc'],$plan_version['plan_version']);
		}
		foreach ($propuestas as $propuesta) {
			guarani::act('act_periodos_de_inscripcion')->insertar_aplanado($periodo_inscripcion_fechas['periodo_insc'],null,$propuesta['propuesta']);
		}
	}
}

?>