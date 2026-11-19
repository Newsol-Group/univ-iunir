<?php
/**
 * Ejecución de comandos SQL que producen cambios en la base de negocio.
 *
 *
 * @package 	Propuestas
 * @subpackage 	Planes
 * 
 */
class act_correlativas_nucleo
{
	/**
	 * Copia las correlatividades de una actividad en otra.
	 *
	 * @param int $elemento_origen
	 * @param int $plan_version_origen
	 * @param int $elemento_destino
	 * @param int $plan_version_destino
	 * @return int código de status de ejecución
	 */
	function copiar_correlativas($elemento_origen, $plan_version_origen, $elemento_destino, $plan_version_destino)
	{
		$elemento_origen = toba::db()->quote($elemento_origen);
		$plan_version_origen = toba::db()->quote($plan_version_origen);
		$elemento_destino = toba::db()->quote($elemento_destino);
		$plan_version_destino = toba::db()->quote($plan_version_destino);
		
		$ejecutar = "   SELECT  *
						FROM 	f_copiar_correlativa($elemento_origen, $plan_version_origen, $elemento_destino, $plan_version_destino);
					";
					
		$rs = current(guarani_db::ejecutar($ejecutar, true, true));
		toba::logger()->info('************ Se copiaron correlativas con éxito. ************');
		return $rs['f_copiar_correlativa'];
	}
	
}
?>