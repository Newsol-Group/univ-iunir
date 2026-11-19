<?php
class cn_ent_asignacion_comisiones extends cn_entidad
{
/*	function set_datos_ini($datos)
	{
		$this->tabla("res_com")->cargar_con_datos($datos);
	}*/
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla("res_com")->procesar_filas($datos);
	}
	
	function get_resultados()
	{
		return $this->tabla('res_com')->get_filas();
	}
	
	function get_elemento()
	{
		return $this->tabla('com')->get();
	}
	
	function sincronizar()
	{
		$this->tabla("res_com")->sincronizar();
	}
	
	function asignar($resultado)
	{
		$this->tabla("res_com")->nueva_fila($resultado);
	}
	
	function eliminar_fila($resultado)
	{
		$this->tabla("res_com")->eliminar_fila($resultado);
	}
}

?>