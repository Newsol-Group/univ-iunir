<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Equivalencias
 */
class cn_man_copiar_matriz_equivalencia extends cn_manejador_entidad
{
	function get_propuestas_planes_versiones_desde($filtro)
	{
		return toba::consulta_php('co_matrices_de_equivalencia')->get_propuestas_planes_versiones_con_matriz_equivalencia($filtro);
	}

	function get_propuestas_planes_versiones_hacia($propuesta_desde, $plan_version_desde)
	{
		$filtro = "sga_propuestas.propuesta = $propuesta_desde";
		$filtro .= " AND sga_planes_versiones.plan_version <> $plan_version_desde";
		return toba::consulta_php('co_matrices_de_equivalencia')->get_propuestas_planes_versiones_activos_matrices_equivalencia($filtro);
	}

	function get_matrices_equivalencia($plan_version=null, $filtro='')
	{
		$where = 'true';
		if (!empty($plan_version)) {
			$where .= " AND sga_equiv_matrices.plan_version_destino = $plan_version";
		}
		if (!empty($filtro)) {
			$where .= " AND $filtro";
		}
		return toba::consulta_php('co_matrices_de_equivalencia')->get_listado($where);
	}

	function get_datos_prop_plan_version($prop_plan_version)
	{
		return current(toba::consulta_php('co_planes')->get_datos_plan_version($prop_plan_version));
	}

	/**
	 * Copia las Matrices de Equivalencia desde un Plan Versión hacia otro Plan Versión dados como parámetro.
	 *
	 * @param 	integer $plan_version_desde
	 * @param 	integer $plan_version_hacia
	 * @param 	array	$matrices
	 */
	function copiar_matrices_equivalencia($plan_version_desde, $plan_version_hacia, $matrices)
	{
		$matrices_no_copiadas = array();
		foreach ($matrices as $matriz) {
			try {
				guarani::act('act_equivalencias')->copiar_matriz_equivalencia($plan_version_desde, $plan_version_hacia, $matriz['matriz']);
			} catch (toba_error $e) {
				$matrices_no_copiadas[] = $matriz;
				toba::db()->abortar_transaccion();
			}
		}
		return $matrices_no_copiadas;
	}
}
?>