<?php
class act_procesos_bk_nucleo
{
	/**
	 * Inserta un nuevo registro de log de proceso.
	 */
	function insertar_log_proceso($nombre_proceso, $carpeta_logs)
	{
		$nombre_proceso = toba::db()->quote($nombre_proceso);
		$carpeta_logs = toba::db()->quote($carpeta_logs);
		$usuario = toba::db()->quote(toba::usuario()->get_id());

		$sql = "INSERT INTO proc_logs
				(
					nombre_proceso,
					carpeta_logs,
					usuario
				) 
				VALUES 
				(
					$nombre_proceso,
					$carpeta_logs,
					$usuario
				);";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó registro de log de proceso instanciado (tabla: proc_logs) ************');
		return toba::db()->recuperar_secuencia('proc_logs_seq');
	}
	
	/**
	 * Elimina de la base registros de log de procesos corridos
	 */
	function eliminar_logs($eliminar)
	{
		$logs_a_eliminar = guarani_sql::armar_condicion_in($eliminar, 'proc_logs', 'log_proceso');
		$sql = "DELETE FROM proc_logs WHERE $logs_a_eliminar;";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron registros de log de proceso instanciado (tabla: proc_logs) ************');
	}
}
?>
