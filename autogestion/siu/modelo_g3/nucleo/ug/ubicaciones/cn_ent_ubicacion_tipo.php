<?php 
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Ubicaciones
 */


class cn_ent_ubicacion_tipo extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('ubicaciones_tipos')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('ubicaciones_tipos')->get();
	}	

	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$datos['ubicacion_tipo'] = $this->tabla('ubicaciones_tipos')->get_columna('ubicacion_tipo');
		$this->validar_nombre_tipo_ubicacion($datos['nombre'], $datos['ubicacion_tipo']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	// Determinar si el nombre del Tipo de Ubicacion ya existe.
	
	function validar_nombre_tipo_ubicacion($nombre, $ubicacion_tipo) 
	{
		if (! toba::consulta_php('co_ubicaciones')->validar_nombre_tipo_ubicacion($nombre, $ubicacion_tipo)) {
			$this->set_error('dato_existente', array('nombre', 'Tipo de Ubicación'));
		}
	}	
}
?>