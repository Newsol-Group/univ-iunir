<?php 
/**
 *
 *
 * @package Responsables_Academicas
 * @subpackage Tipos de Aulas
 */
class cn_ent_tipo_aula extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla('tipos_aulas')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('tipos_aulas')->get();
	}
}
?>