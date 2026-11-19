<?php
/**
 *	Representa un Área Temática
 *
 * @package Propuestas
 * @subpackage Resultados
 */
class cn_ent_area_tematica extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('campo_disciplinar')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('campo_disciplinar')->get();
	}
	
	//------Validaciones------//
	
	function validar_datos($datos)
	{
		$datos['campo_disciplinar'] = $this->tabla('campo_disciplinar')->get_columna('campo_disciplinar');
		$this->validar_nombre_area_tematica($datos['nombre'], $datos['campo_disciplinar']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	function validar_nombre_area_tematica($nombre, $campo_disciplinar)
	{
		if (! toba::consulta_php('co_areas_tematicas')->validar_nombre_area_tematica($nombre, $campo_disciplinar)) {
			$this->set_error('dato_duplicado', array('nombre', 'campo disciplinar'));
		}
	}

}
?>