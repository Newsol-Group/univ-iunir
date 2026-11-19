<?php 
/**
 *	Entidad Convenio
 *
 * @package Unidad de Gestión
 * @subpackage Convenios
 */
class cn_ent_medios_pago_nsg extends cn_entidad
{

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('medio_pago')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('medio_pago')->get();
	}

	
	//-------------------------------------------------------------------

	function validar_datos($datos)
	{
		$datos['medio_pago'] = $this->tabla('medio_pago')->get_columna('medio_pago');
		$this->validar_nombre_medios_pago($datos['descripcion'], $datos['medio_pago']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	// Determinar si el nombre del Medio de Pago ya existe
	function validar_nombre_medios_pago($nombre, $medio_pago) 
	{
		if (! toba::consulta_php('co_facturacion')->validar_nombre_medios_pago($nombre, $medio_pago)) {
			$this->set_error('dato_duplicado', array('nombre', 'medios de pago'));
		}			
	}		
	
}
?>