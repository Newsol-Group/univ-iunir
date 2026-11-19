<?php
/**
 *
 *
 * @package 	Propuestas
 * @subpackage 	Certificados
 * 
 */
class cn_ent_asignacion_comp_a_alcances extends cn_entidad
{
	function get_asignaciones($condiciones=null)
	{
		return $this->tabla('comp_x_alcance')->get_filas($condiciones);
	}
	
	function asignar($resultado)
	{
		$this->tabla('comp_x_alcance')->nueva_fila($resultado);
	}
	
	function eliminar_fila($seleccion)
	{
		$fila = current($this->get_asignaciones($seleccion));
		if (!empty($fila)) {
			$this->tabla('comp_x_alcance')->eliminar_fila($fila['x_dbr_clave']);	
		}
	}
}

?>