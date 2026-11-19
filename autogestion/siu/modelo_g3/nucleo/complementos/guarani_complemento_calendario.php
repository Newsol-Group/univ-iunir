<?php

/**
 * Clase que implementa el comportamiento del Complemento Calendario
 */

class guarani_complemento_calendario extends guarani_complemento 
{
	protected $datos_extra; // Datos extra para la implementacion del complemento

	/**
	 * Parametros y Datos extras de configuracion del complemento
	 * @return array Datos extras para la configuracion del complemento
	 */
	function get_datos_extra()
	{
		$persona = persona::get_id();
		
		$sql = "SELECT 
						sga_usuarios_recordatorios.recordatorio,
						sga_usuarios_recordatorios.titulo,
						sga_usuarios_recordatorios.descripcion,
						sga_usuarios_recordatorios.fecha_desde,
						sga_usuarios_recordatorios.fecha_hasta
				FROM
						sga_usuarios_recordatorios
				WHERE 
						sga_usuarios_recordatorios.persona = $persona AND
						sga_usuarios_recordatorios.estado = 'A';
		";
		$this->datos_extra = guarani_db::consultar($sql);
		
		return $this->datos_extra;
	}

	function get_html()
	{
		
	}
	
}
?>