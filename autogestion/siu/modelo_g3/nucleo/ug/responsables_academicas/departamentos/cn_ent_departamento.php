<?php 
/**
 *
 *
 * @package Responsables_Academicas
 * @subpackage Departamentos
 */
class cn_ent_departamento extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla('departamentos')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('departamentos')->get();
	}
}
?>