<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Inscripciones
 * 
 */
class cn_ent_modificar_insc_propuesta extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla('propuesta_aspira')->set($datos);
	}

	function get_datos($tabla=null)
	{
		return $this->tabla('propuesta_aspira')->get();
	}
}
?>