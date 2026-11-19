<?php
class cn_ent_espacio_tipo extends cn_entidad
{
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('espacios_tipos')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('espacios_tipos')->get();
	}	
	
	//------Validaciones------//
	
	function validar_datos($datos)
	{
		$datos['espacio_tipo'] = $this->tabla('espacios_tipos')->get_columna('espacio_tipo');
		$this->validar_nombre_espacio_tipo($datos['nombre'], $datos['espacio_tipo']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	function validar_nombre_espacio_tipo($nombre, $espacio_tipo)
	{
		if (! toba::consulta_php('co_espacios')->validar_nombre_espacio_tipo($nombre, $espacio_tipo)) {
			$this->set_error('dato_duplicado', array('nombre', 'tipo de espacio'));
		}
	}
	
}

?>