<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Preinscripciones
 */
class cn_ent_titulos_secundarios extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla('titulos')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('titulos')->get();
	}
}