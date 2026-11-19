<?php 
/**
 *	Entidad Convenio
 *
 * @package Unidad de Gestión
 * @subpackage Convenios
 */
class cn_ent_tipos_bonif_nsg extends cn_entidad
{

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('tipo_bonif')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('tipo_bonif')->get();
	}

	
	//-------------------------------------------------------------------

	function validar_datos($datos)
	{
		$datos['tipo_bonif'] = $this->tabla('tipo_bonif')->get_columna('tipo_bonif');
		$this->validar_nombre_tipo_bonif($datos['descripcion'], $datos['tipo_bonif']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	// Determinar si el nombre del Tipo de Bonificación ya existe
	function validar_nombre_tipo_bonif($nombre, $tipo_bonif) 
	{
		if (! toba::consulta_php('co_facturacion')->validar_nombre_tipo_bonif($nombre, $tipo_bonif)) {
			$this->set_error('dato_duplicado', array('nombre', 'tipo de bonif'));
		}			
	}		
	
}
?>