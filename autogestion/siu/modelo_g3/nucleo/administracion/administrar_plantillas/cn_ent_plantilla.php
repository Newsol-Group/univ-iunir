<?php
/**
 *
 *
 * @package 	Administración
 * @subpackage 	Administrar Plantillas
 * 
 */
class cn_ent_plantilla extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos_plantilla($datos);
		$this->tabla('plantilla')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('plantilla')->get();
	}
	
	//-------------------------------------------------------------------
	
	function validar_datos_plantilla($datos)
	{
		$datos['plantilla'] = $this->tabla('plantilla')->get_columna('plantilla');
		$this->validar_nombre_plantilla($datos['nombre'], $datos['plantilla']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	// Determinar si el nombre de la Plantilla de Constancia ya existe.
	function validar_nombre_plantilla($nombre, $plantilla) 
	{
		if (!toba::consulta_php('co_constancias')->validar_nombre_plantilla($nombre, $plantilla)) {
			$this->set_error('dato_existente', array('nombre', 'Plantilla de Constancia'));
		}
	}	
}
?>