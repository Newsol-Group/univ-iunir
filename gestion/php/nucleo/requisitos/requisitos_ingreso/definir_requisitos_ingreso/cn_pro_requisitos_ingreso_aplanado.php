<?php
/**
 * Proceso que genera el aplanado de requisitos de ingreso
 *
 *
 * @package Propuestas
 * @subpackage Requisitos/Requisitos de Ingreso
 */
class cn_pro_requisitos_ingreso_aplanado extends cn_proceso
{
	/**
	 * Se define un método de procesamiento particular porque no se quiere abrir una nueva transacción.
	 */
	function procesar($parametros = null, $transaccionar = true, $limpiar_memoria = true)
	{
		$this->procesar_especifico($parametros);
	}

	function procesar_especifico($parametros=null)
	{
		$requisito_propuesta = isset($parametros['requisito_propuesta']) ? $parametros['requisito_propuesta'] : null;
		$requisito_para = isset($parametros['requisito_para']) ? $parametros['requisito_para'] : null;
		// Se obtienen los datos del requisito de ingreso.
		$requisitos_ingreso = toba::consulta_php('co_requisitos_ingreso')->get_datos_requisitos_ingreso($requisito_propuesta, $requisito_para);
		foreach ($requisitos_ingreso as $requisito_ingreso) {
			$this->aplanar($requisito_ingreso);
		}
	}

	function aplanar($requisito_ingreso)
	{
		// El primer paso es borrar todos los registros existentes en sga_requisitos_propuestas_aplanado para el rango de fechas.
		guarani::act('act_requisitos_ingreso')->eliminar_aplanado($requisito_ingreso['requisito_propuesta']);

		$subtipos = toba::consulta_php('co_requisitos_ingreso')->get_subtipos($requisito_ingreso['requisito_propuesta']);
		$entidades = toba::consulta_php('co_requisitos_ingreso')->get_entidades($requisito_ingreso['requisito_propuesta']);

		switch ($requisito_ingreso['entidad_tipo']) {
			case g3entidad::tipo_institucion:
				// Para el caso de instituciones, existe siempre una única entidad: aquella institución con gestión académica (la institución de instalación).
				$entidad = current($entidades);
				$planes_versiones = toba::consulta_php('co_planes')->get_planes_versiones_por_institucion($entidad['entidad']);
				break;

			case g3entidad::tipo_responsable_academica:
				// Si se seleccionó la opción 'Algunas Responsables Académicas', en $entidades estarán aquellas entidades correspondientes a las responsables académicas que fueron elegidas.
				$planes_versiones = toba::consulta_php('co_planes')->get_planes_versiones_por_ra($entidades);
				break;

			case g3entidad::tipo_propuesta:
				// Si se seleccionó la opción 'Algunos Tipos de Propuestas', se deben recuperar aquellas propuestas de los subtipos (grado, posgrado, etc) seleccionados.
				// Si se seleccionó la opción 'Algunas Propuestas', en $entidades estarán aquellas entidades correspondientes a las propuestas que fueron elegidas.
				$planes_versiones = toba::consulta_php('co_planes')->get_planes_versiones_por_propuesta($subtipos, $entidades);
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
			guarani::act('act_requisitos_ingreso')->insertar_aplanado($requisito_ingreso['requisito_propuesta'],$plan_version['plan_version']);
		}
	}
}

?>