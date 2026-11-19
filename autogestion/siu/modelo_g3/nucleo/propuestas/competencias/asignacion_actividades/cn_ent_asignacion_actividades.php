<?php
class cn_ent_asignacion_actividades extends cn_entidad
{
/*	function set_datos_ini($datos)
	{
		$this->tabla("res_act")->cargar_con_datos($datos);
	}*/
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla("res_act")->procesar_filas($datos);
	}
	
	function get_resultados()
	{
		return $this->tabla('res_act')->get_filas();
	}
	
	function get_elemento()
	{
		return $this->tabla('elem')->get();
	}
	
	function sincronizar()
	{
		$this->tabla("res_act")->sincronizar();
	}
	
	function asignar($resultado)
	{
		$this->tabla("res_act")->nueva_fila($resultado);
	}
	
	function eliminar_fila($resultado)
	{
		$this->tabla("res_act")->eliminar_fila($resultado);
	}
}

?>