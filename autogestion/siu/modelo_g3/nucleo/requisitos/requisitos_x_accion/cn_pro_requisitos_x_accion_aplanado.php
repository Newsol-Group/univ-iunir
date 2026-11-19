<?php
/**
 * Proceso que genera el aplanado de Requisitos por Acción
 *
 *
 * @package Propuestas
 * @subpackage Requisitos/Requisitos por Acción
 */

class cn_pro_requisitos_x_accion_aplanado extends cn_proceso
{
	/**
	 * Se define un método de procesamiento particular porque no se quiere abrir una nueva transacción.
	 */
	function procesar($parametros = NULL, $transaccionar = true, $limpiar_memoria = true)
	{
		$this->procesar_especifico($parametros);
	}

	function procesar_especifico($requisitos_x_accion)
	{
		// Se obtienen los datos de los requisitos por acción.
		$filtro = empty($requisitos_x_accion) ? '' : guarani_sql::armar_condicion_in($requisitos_x_accion, 'sga_requisitos_x_accion', 'requisito_accion', 'requisito_accion');
		$requisitos_x_accion = toba::consulta_php('co_requisitos_x_accion')->get_requisitos_x_accion($filtro);
		foreach ($requisitos_x_accion as $requisito_x_accion) {
			$this->aplanar($requisito_x_accion);
		}
	}

	function aplanar($requisito_x_accion)
	{
		// El primer paso es borrar todos los registros existentes en sga_periodos_inscripcion_aplanado para el rango de fechas.
		guarani::act('act_requisitos_x_accion')->eliminar_aplanado($requisito_x_accion['requisito_accion']);

		switch ($requisito_x_accion['entidad_tipo']) {
			case g3entidad::tipo_institucion:
				$planes_versiones = toba::consulta_php('co_planes')->get_planes_versiones_por_institucion($requisito_x_accion['entidad']);
				break;

			case g3entidad::tipo_responsable_academica:
				$planes_versiones = toba::consulta_php('co_planes')->get_planes_versiones_por_ra($requisito_x_accion['entidad']);
				break;

			case g3entidad::tipo_propuesta:
				$planes_versiones = toba::consulta_php('co_planes')->get_planes_versiones_por_propuesta($requisito_x_accion['entidad_subtipo'], $requisito_x_accion['entidad']);
				break;

			case g3entidad::tipo_plan_estudio:
				$planes_versiones = toba::consulta_php('co_planes')->get_planes_versiones_por_plan($requisito_x_accion['entidad']);
				break;

			case g3entidad::tipo_plan_estudio_version:
				$planes_versiones = toba::consulta_php('co_planes')->get_planes_versiones($requisito_x_accion['entidad']);
				break;
		}

		// Se insertan los registros correspondientes en la tabla de aplanado.
		foreach ($planes_versiones as $plan_version) {
			guarani::act('act_requisitos_x_accion')->insertar_aplanado($requisito_x_accion['requisito_accion'],$plan_version['plan_version']);
		}
	}
}

?>