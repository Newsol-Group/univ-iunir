<?php
class co_procesos_bk
{
	function get_listado($where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		
		$sql = "SELECT 
					proc_logs.log_proceso,
					proc_logs.nombre_proceso,
					proc_logs.carpeta_logs,
					proc_logs.usuario,
					COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ', ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) || ' (' || proc_logs.usuario || ')' as usuario_nombre,
					proc_logs.fecha
				FROM 
					proc_logs,
					mdp_personas
				WHERE 
					proc_logs.usuario = mdp_personas.usuario
					$where
				ORDER BY
					proc_logs.fecha DESC";
		
		$datos = guarani_db::consultar($sql);
		
		$salida = array();
		$eliminar = array();
		foreach ($datos as $key => $dato) {
			$carpeta_logs = guarani_logs::get_carpeta_logs() . $dato['carpeta_logs'];
			$ruta_archivo_estado = $carpeta_logs . '/' . guarani_procesos_bk::archivo_estado . '.xml';
			if (!file_exists($ruta_archivo_estado)) {
				// Eliminar registro de la base (no existe más la carpeta de log)
				$eliminar[] = $dato['log_proceso'];
			} else {
				$gl = new guarani_logs_estados('dummy');
				$mensajes = $gl->get(null, $ruta_archivo_estado);
				$dato['estado'] = $mensajes['progreso'];
				$salida[] = $dato;
			}
		}
		
		if (!empty($eliminar)) {
			guarani::act('act_procesos_bk')->eliminar_logs($eliminar);
		}
		
		return $salida;
	}
	
	function get_log_proceso($log_proceso)
	{
		$log_proceso = toba::db()->quote($log_proceso);
		
		$sql = "SELECT 
					proc_logs.log_proceso,
					proc_logs.nombre_proceso,
					proc_logs.carpeta_logs,
					proc_logs.usuario,
					proc_logs.fecha
				FROM 
					proc_logs,
					mdp_personas
				WHERE 
					proc_logs.log_proceso = $log_proceso
				";
		
		return guarani_db::consultar_fila($sql);
	}
}
?>