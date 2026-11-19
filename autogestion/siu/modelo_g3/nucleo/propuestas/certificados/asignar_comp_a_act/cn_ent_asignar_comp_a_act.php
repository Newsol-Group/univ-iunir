<?php
/**
 *
 *
 * @package 	Propuestas
 * @subpackage 	Certificados
 * 
 */
class cn_ent_asignar_comp_a_act extends cn_entidad
{
	
	function get_asignaciones($condiciones=null)
	{
		return $this->tabla('comp_x_actividad')->get_filas($condiciones);
	}
	
	function asignar($resultado)
	{
		$this->tabla('comp_x_actividad')->nueva_fila($resultado);
	}
	
	function procesar_asignados($datos)
	{
		$this->tabla('comp_x_actividad')->procesar_filas($datos);
	}
	
	function eliminar_fila($resultado)
	{
		$this->tabla('comp_x_actividad')->eliminar_fila($resultado);
	}
	
	function sincronizar()
	{
		$this->tabla('comp_x_actividad')->sincronizar();
	}
	
}
?>