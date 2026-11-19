<?php
/**
 *
 *
 * @package Responsables Académicas
 * @subpackage Sedes
 */
class cn_ent_sede extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla('sedes')->set($datos);
	}
  
	function get_datos($tabla=null)
	{
		return $this->tabla('sedes')->get();
	}
}
?>