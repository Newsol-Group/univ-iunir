<?php 
/**
 *	Entidad Convenio
 *
 * @package Unidad de Gestión
 * @subpackage Convenios
 */
class cn_ent_convenios_tipo extends cn_entidad
{

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('convenios_tipos')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('convenios_tipos')->get();
	}

	
	//-------------------------------------------------------------------

	function validar_datos($datos)
	{
		$datos['convenio_tipo'] = $this->tabla('convenios_tipos')->get_columna('convenio_tipo');
		$this->validar_nombre_convenio_tipo($datos['nombre'], $datos['convenio_tipo']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	// Determinar si el nombre del Tipo de Convenio ya existe
	function validar_nombre_convenio_tipo($nombre, $convenio) 
	{
		if (! toba::consulta_php('co_convenios')->validar_nombre_convenio_tipo($nombre, $convenio)) {
			$this->set_error('dato_duplicado', array('nombre', 'tipo de convenio'));
		}			
	}		
	
}
?>