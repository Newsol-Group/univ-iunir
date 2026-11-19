<?php 
/**
 *	Entidad Convenio
 *
 * @package Unidad de Gestión
 * @subpackage Convenios
 */
class cn_ent_morosos_nsg extends cn_entidad
{

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$datos['morosos'] = $this->tabla('morosos')->get_columna('moroso');
		$this->tabla('morosos')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('morosos')->get();
	}

	
	//-------------------------------------------------------------------
	
}
?>