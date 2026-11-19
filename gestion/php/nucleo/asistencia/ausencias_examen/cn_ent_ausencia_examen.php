<?php 
/**
 *
 *
 * @package Asistencias
 * @subpackage -
 */
class cn_ent_ausencia_examen extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla('ausencias_examen')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('ausencias_examen')->get();
	}
}
?>