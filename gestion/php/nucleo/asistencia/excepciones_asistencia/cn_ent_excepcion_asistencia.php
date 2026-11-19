<?php 
/**
 *
 *
 * @package Asistencias
 * @subpackage 
 */
class cn_ent_excepcion_asistencia extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla('alumnos_excep_asistencia')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('alumnos_excep_asistencia')->get();
	}
}
?>