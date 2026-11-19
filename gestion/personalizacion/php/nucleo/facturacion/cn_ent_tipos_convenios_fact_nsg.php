<?php 
/**
 *	Entidad Convenio
 *
 * @package Unidad de Gestión
 * @subpackage Convenios
 */
class cn_ent_tipos_convenios_fact_nsg extends cn_entidad
{

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('tipo_convenio_fact')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('tipo_convenio_fact')->get();
	}

	
	//-------------------------------------------------------------------

	function validar_datos($datos)
	{
		$datos['tipo_convenio_fact'] = $this->tabla('tipo_convenio_fact')->get_columna('tipo_convenio_fact');
		$this->validar_nombre_tipo_convenio_fact($datos['descripcion'], $datos['tipo_convenio_fact']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	// Determinar si el nombre del Tipo de Convenio ya existe
	function validar_nombre_tipo_convenio_fact($nombre, $tipo_convenio_fact) 
	{
		if (! toba::consulta_php('co_facturacion')->validar_nombre_tipo_convenio_fact($nombre, $tipo_convenio_fact)) {
			$this->set_error('dato_duplicado', array('nombre', 'tipo de convenio fact'));
		}			
	}		
	
}
?>