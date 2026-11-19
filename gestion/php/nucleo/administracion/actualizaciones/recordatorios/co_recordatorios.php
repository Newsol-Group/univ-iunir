<?php
/**
 *
 *
 * @package Administracion
 * @subpackage Actualizaciones
 */
class co_recordatorios
{
	/**
        Obtiene una lista de recordatorios según el filtro recibido
    */
	function get_listado($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}
				
		$datos_persona = toba::consulta_php('co_personas')->get_persona_x_usuario(toba::usuario()->get_id());

		$sql = 'SELECT 		sga_usuarios_recordatorios.recordatorio,
							sga_usuarios_recordatorios.titulo,
							sga_usuarios_recordatorios.fecha_desde,
							sga_usuarios_recordatorios.fecha_hasta,'
							.guarani_sql::get_case('sga_usuarios_recordatorios', 'estado', 'estado', array(array('valor' => 'A', 'descr' => 'Activo'), array('valor' => 'C', 'descr' => 'Cumplido')))."
				FROM 		sga_usuarios_recordatorios 
				WHERE		sga_usuarios_recordatorios.persona = {$datos_persona['persona']}
				$where
				ORDER BY 	sga_usuarios_recordatorios.titulo
				";
		
		return guarani_db::consultar($sql);
	}	
						
}

?>