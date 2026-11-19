<?php
/**
 * 
 * 
 * @package 	Calendario Académico
 * @subpackage 	Planificación
 *
 */
class cn_ent_temas_planificados_clase extends cn_entidad
{
	function get_datos($tabla=null)
	{
		return $this->tabla('clases')->get();
	}
		
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla('clases')->set($datos);	
	}
}
?>