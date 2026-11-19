<?php 
/**
 *
 *
 * @package Matrícula
 * @subpackage Alumnos
 */
class cn_ent_motivo_movimiento_ha extends cn_entidad
{		
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('movimientos_ha_motivos')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('movimientos_ha_motivos')->get();
	}	
	
	function validar_datos($datos)
	{
		$datos['motivo_movimiento'] = $this->tabla('movimientos_ha_motivos')->get_columna('motivo_movimiento');
		$this->validar_nombre_motivo_movimiento_ha($datos['nombre'], $datos['motivo_movimiento']);
		
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	function validar_nombre_motivo_movimiento_ha($nombre, $motivo_movimiento)
	{
		if (!toba::consulta_php('co_motivos_movimiento_ha')->validar_nombre_motivo_movimiento_ha($nombre, $motivo_movimiento)) {
			$this->set_error('dato_duplicado', array('nombre', 'motivo de movimiento de la historia académica'));
		}
	}
	
}
?>