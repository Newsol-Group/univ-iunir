<?php
/**
 * Proceso de activación de una versión de un Plan de Estudios
 *
 *
 * @package Propuestas
 * @subpackage Plan
 */
class cn_pro_plan_activacion extends cn_proceso
{
	function set_plan() {}
	
	function procesar_especifico($id_plan_version)
	{
		$man = toba::cn('manejador_plan');	
		$man->desactivar_transacciones_anidadas();
		$plan_a_activar = $man->entidad();
		
		$id_plan_version_vigente = $plan_a_activar->get_version_vigente();
        
        
        if ($id_plan_version_vigente) {
			if ($id_plan_version == $id_plan_version_vigente) {
				throw new guarani_error('La versión no puede activarse porque figura en el plan de estudios como version actual del plan.');
			}
		}
		$plan_a_activar->activar_version();
		if ($id_plan_version_vigente) {
			$plan_vigente = $man->cargar($id_plan_version_vigente);	
			$plan_vigente->set_version_no_vigente(); 
			$plan_vigente->desactivar_control_bloqueo(); 			
		}
		$plan_a_activar->desactivar_control_bloqueo();

		$man->guardar();
		$plan_a_activar->activar_control_bloqueo();
		$man->activar_transacciones_anidadas();
		//Se eliminan los elementos huerfanos de la tabla sga_elementos producto de edicion de planes.
		$man->purgar_elementos_huerfanos();
	}

}

?>