<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Formularios
 * 
 */
class cn_ent_formulario_constancia extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos_formulario($datos);
		$this->tabla('formulario')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('formulario')->get();
	}
	
	//-------------------------------------------------------------------
	
	function validar_datos_formulario($datos)
	{
		$datos['objeto'] = $this->tabla('formulario')->get_columna('objeto');
		$this->validar_nombre_formulario($datos['nombre'], $datos['objeto']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	// Determinar si el nombre del Formulario de Constancia ya existe.
	function validar_nombre_formulario($nombre, $objeto) 
	{
		if (!toba::consulta_php('co_constancias')->validar_nombre_formulario($nombre, $objeto)) {
			$this->set_error('dato_existente', array('nombre', 'Formulario de Constancia'));
		}
	}	
}
?>