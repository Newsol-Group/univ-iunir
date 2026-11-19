<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Instituciones
 */

class cn_ent_formas_pago_x_propuesta_nsg extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('forma_pago_x_propuesta')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('forma_pago_x_propuesta')->get();
	}

	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$datos['forma_pago_x_propuesta'] = $this->tabla('forma_pago_x_propuesta')->get_columna('forma_pago_x_propuesta');
		$this->validar_nombre_formas_pago_x_propuesta($datos['propuesta'], $datos['forma_pago'], $datos['corresponde_arancel_inscrp']);
		if ($this->hay_errores()){
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	// Determinar si el nombre de la Ubicacion ya existe.
	function validar_nombre_formas_pago_x_propuesta($propuesta, $forma_pago, $corresp_arancel) 
	{
		if (! toba::consulta_php('co_facturacion')->validar_nombre_formas_pago_x_propuesta($propuesta, $forma_pago, $corresp_arancel)){
			$this->set_error('dato_existente',array('nombre', 'Formas de Pago x Propuesta'));
		}
	}	
}
?>