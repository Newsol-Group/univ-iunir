<?php 
/**
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
class ci_rep_log_sincronizacion extends ci_rep_sin_listado_inicial
{

	function get_tabla_principal()
	{
		return 'gde_log_sync_kolla';
	}


	function get_datos($filtro)
	{	
		//	toba::logger()->debug("El filtro es ".$filtro);
			$datos = toba::consulta_php('co_encuestas')->get_listado_log_sincronizacion($filtro);
		//	toba::logger()->debug($datos);
			return $datos;
	}
		
}

?>