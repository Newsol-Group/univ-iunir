<?php
/**
 *	Representa un día no laborable
 *
 * @package Calendario
 * @subpackage Días No Laborables
 */
class cn_ent_dia_no_laborable extends cn_entidad
{
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla('dias_no_laborables')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('dias_no_laborables')->get();
	}
	
}

?>