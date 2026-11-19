<?php
class cn_ent_asignacion_certificados extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla('res_cert')->procesar_filas($datos);
	}
	
	function sincronizar()
	{
		$this->tabla('res_cert')->sincronizar();
	}
	
	function get_resultados()
	{
		return $this->tabla('res_cert')->get_filas();
	}
	
	function get_certificado()
	{
		return $this->tabla('cert')->get();
	}
	
	function asignar($resultado)
	{
		$this->tabla('res_cert')->nueva_fila($resultado);
	}
	
	function eliminar_fila($resultado)
	{
		$this->tabla('res_cert')->eliminar_fila($resultado);
	}
}

?>