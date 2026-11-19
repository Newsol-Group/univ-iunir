<?php 
/**
 *	Entidad Convenio
 *
 * @package Unidad de Gestión
 * @subpackage Convenios
 */
class cn_ent_formas_pago_nsg extends cn_entidad
{

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('forma_pago')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('forma_pago')->get();
	}

	
	//-------------------------------------------------------------------

	function validar_datos($datos)
	{
		$datos['forma_pago'] = $this->tabla('forma_pago')->get_columna('forma_pago');
		$this->validar_nombre_formas_pago($datos['descripcion'], $datos['forma_pago']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	// Determinar si el nombre de la Forma de Pago ya existe
	function validar_nombre_formas_pago($nombre, $forma_pago) 
	{
		if (! toba::consulta_php('co_facturacion')->validar_nombre_formas_pago($nombre, $forma_pago)) {
			$this->set_error('dato_duplicado', array('nombre', 'formas de pago'));
		}			
	}		
	
}
?>