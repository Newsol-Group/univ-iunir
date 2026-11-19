<?php 
/**
 *
 *
 * @package Responsables_Academicas
 * @subpackage Aulas
 */
class cn_ent_aula extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla('aulas')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('aulas')->get();
	}
}
?>