<?php
/**
 *
 *
 * @package Entidad G3
 * @subpackage Entidad G3
 */
class cn_ent_g3entidad extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla('g3entidad')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('g3entidad')->get();
	}
	
}

?>