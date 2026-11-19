<?php
/**
 * Proceso que versionar una versión de un Plan de Estudios
 *
 *
 * @package Propuestas
 * @subpackage Plan
 */
class cn_pro_plan_versionador extends cn_proceso
{
	function set_plan() {}
	
	function procesar_especifico($parametros)
	{
		$id_plan_version = $parametros['id_plan_version'];
				
		$this->set_mensaje_exito('Plan versionado con éxito.');
		$man = toba::cn('manejador_plan');	
		$man->cargar($id_plan_version);
		$man->desactivar_transacciones_anidadas();		
		$plan_a_versionar = $man->entidad();
		
		if ($plan_a_versionar->es_nueva_version()) {
			throw new guarani_error('No se puede versionar una versión nueva.');
		}
		
		if ($plan_a_versionar->existe_version_nueva()) {
			throw new guarani_error('Ya existe una versión nueva en el plan.');
		}
		
		$nuevo_id = $plan_a_versionar->versionar_version($parametros['version'], $parametros['nombre']);

		$man->resetear();
		
		return $nuevo_id;
	}

}

?>