<?php 
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Instituciones
 */


class cn_ent_tipo_institucion extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('tipos_instituciones')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('tipos_instituciones')->get();
	}	

	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$datos['tipo_institucion'] = $this->tabla('tipos_instituciones')->get_columna('tipo_institucion');
		$this->validar_nombre_tipo_institucion($datos['nombre'], $datos['tipo_institucion']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	// Determinar si el nombre del Tipo de Institucion ya existe.
	
	function validar_nombre_tipo_institucion($nombre, $tipo_institucion) 
	{
		if (! toba::consulta_php('co_instituciones')->validar_nombre_tipo_institucion($nombre, $tipo_institucion)) {
			$this->set_error('dato_existente', array('nombre','Tipo de institución'));
		}
	}	
}
?>