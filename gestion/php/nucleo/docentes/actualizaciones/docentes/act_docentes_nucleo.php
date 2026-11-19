<?php
/**
 * Ejecución de comandos SQL que producen cambios en la base de negocio.
 *
 *
 * @package 	Docentes
 * @subpackage 	Actualizaciones
 * 
 */
class act_docentes_nucleo
{
	function insertar_docente($persona, $legajo)
	{
		$persona = toba::db()->quote($persona);
		$legajo = toba::db()->quote($legajo);
		$sql = "INSERT INTO sga_docentes (persona, legajo) VALUES ($persona, $legajo)";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó docente con éxito. ************');
	}

	/**
	 * Actualiza el estado de la asignacion de un docente en las cátedras.
	 *
	 */
	function actualizar_docente_catedras($docente, $estado)
	{
		$docente = toba::db()->quote($docente);
		$estado = toba::db()->quote($estado);
		
		$sql = "UPDATE 	sga_catedras_docentes 
				SET		estado = $estado
				WHERE 	docente = $docente";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó estado en sga_catedras_docentes con éxito. ************');
	}
	
	/**
	 * Actualiza el estado de la asignacion de un docente en los tribunales.
	 *
	 */
	function actualizar_docente_tribulanes($docente, $estado)
	{
		$docente = toba::db()->quote($docente);
		$estado = toba::db()->quote($estado);
		
		$sql = "UPDATE 	sga_tribunal_docentes 
				SET 	estado = $estado
				WHERE 	docente = $docente";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó estado en sga_tribunal_docentes con éxito. ************');
	}
}
?>