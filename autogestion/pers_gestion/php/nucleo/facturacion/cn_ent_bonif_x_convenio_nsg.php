<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Instituciones
 */

class cn_ent_bonif_x_convenio_nsg extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('bonif_x_convenio')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('bonif_x_convenio')->get();
	}

	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$datos['bonif_x_convenio'] = $this->tabla('bonif_x_convenio')->get_columna('bonif_x_convenio');
		$this->validar_nombre_bonif_x_convenio($datos['tipo_convenio_fact'], $datos['tipo_bonif']);
		if ($this->hay_errores()){
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	// Determinar si el nombre de la Ubicacion ya existe.
	function validar_nombre_bonif_x_convenio($tipo_convenio, $tipo_bonif) 
	{
		if (! toba::consulta_php('co_facturacion')->validar_nombre_bonif_x_convenio($tipo_convenio, $tipo_bonif)){
			$this->set_error('dato_existente',array('nombre', 'Bonif x Convenio'));
		}
	}	
}
?>