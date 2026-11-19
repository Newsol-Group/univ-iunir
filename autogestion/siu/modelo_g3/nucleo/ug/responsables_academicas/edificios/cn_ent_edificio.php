<?php 
/**
 *
 *
 * @package Responsables_Academicas
 * @subpackage Edificios
 */
class cn_ent_edificio extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla('edificios')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('edificios')->get();
	}
}
?>