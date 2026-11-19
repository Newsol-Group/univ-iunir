<?php
/**
 *
 * 
 * @package 	Matrícula
 * @subpackage 	Competencias
 * 
 */
class cn_ent_asig_comp_ingreso_propuesta extends cn_entidad
{
	
	function get_asignados()
	{
		$asignados = $this->tabla('competencias_ing_propuesta')->get_filas();	
		$asignados = rs_ordenar_por_columnas($asignados, array('tipo_nombre', 'nombre'), SORT_ASC);
		return $asignados;
	}
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla('competencias_ing_propuesta')->procesar_filas($datos);
	}
	
	function asignar($resultado)
	{
		$this->tabla('competencias_ing_propuesta')->nueva_fila($resultado);
	}
	
	function eliminar_fila($resultado)
	{
		$this->tabla('competencias_ing_propuesta')->eliminar_fila($resultado);
	}
	
	function sincronizar()
	{
		$this->tabla('competencias_ing_propuesta')->sincronizar();
	}
	
}
?>