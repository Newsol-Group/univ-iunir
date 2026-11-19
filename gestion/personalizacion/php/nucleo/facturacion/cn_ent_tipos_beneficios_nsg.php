<?php 
/**
 *	Entidad Convenio
 *
 * @package Unidad de Gestión
 * @subpackage Convenios
 */
class cn_ent_tipos_beneficios_nsg extends cn_entidad
{

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('tipo_beneficio')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('tipo_beneficio')->get();
	}

	
	//-------------------------------------------------------------------

	function validar_datos($datos)
	{
		$datos['tipo_beneficio'] = $this->tabla('tipo_beneficio')->get_columna('tipo_beneficio');
		$this->validar_nombre_tipo_beneficio($datos['descripcion'], $datos['tipo_beneficio']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	// Determinar si el nombre del Tipo de beneficio ya existe
	function validar_nombre_tipo_beneficio($nombre, $tipo_beneficio) 
	{
		if (! toba::consulta_php('co_facturacion')->validar_nombre_tipo_beneficio($nombre, $tipo_beneficio)) {
			$this->set_error('dato_duplicado', array('nombre', 'tipo de beneficio'));
		}			
	}		
	
}
?>