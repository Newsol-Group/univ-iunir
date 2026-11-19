<?php
/**
 *	Representa un Tipo de Sanción
 *
 * @package Matrícula
 * @subpackage Actualizaciones
 */
class cn_ent_tipo_de_sancion extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('tipos_sanciones')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('tipos_sanciones')->get();
	}

	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$datos['tipo_sancion'] = $this->tabla('tipos_sanciones')->get_columna('tipo_sancion');
		$this->validar_nombre_tipo_sancion($datos['nombre'], $datos['tipo_sancion']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	function validar_nombre_tipo_sancion($nombre, $tipo_sancion)
	{
		if (! toba::consulta_php('co_sanciones')->validar_nombre_tipo_sancion($nombre, $tipo_sancion)) {
			$this->set_error('dato_duplicado', array('nombre del tipo de sanción'));
		}
	}
	
}
?>