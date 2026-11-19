<?php 
/**
 *
 * @package Facturación
 * @subpackage Conceptos de Facturación
 */
class cn_ent_conceptos_fact_nsg extends cn_entidad
{

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('conceptos_fact')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('conceptos_fact')->get();
	}

	
	//-------------------------------------------------------------------

	function validar_datos($datos)
	{
		$datos['conceptos_fact'] = $this->tabla('conceptos_fact')->get_columna('concepto');
		$this->validar_nombre_concepto_fact($datos['descripcion'], $datos['conceptos_fact']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	// Determinar si el nombre del Concepto de Facturación ya existe
	function validar_nombre_concepto_fact($nombre, $conceptos_fact) 
	{
		if (! toba::consulta_php('co_facturacion')->validar_nombre_concepto_fact($nombre, $conceptos_fact)) {
			$this->set_error('dato_duplicado', array('nombre', 'conceptos de facturación'));
		}			
	}		
	
}
?>