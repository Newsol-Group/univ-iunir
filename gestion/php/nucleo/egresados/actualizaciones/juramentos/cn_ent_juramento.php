<?php
/**
 *	Representa una juramento
 *
 * @package Egresados
 * @subpackage Juramentos
 */
class cn_ent_juramento extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('juramentos')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('juramentos')->get();
	}
	
	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$datos['juramento'] = $this->tabla('juramentos')->get_columna('juramento');
		$this->validar_nombre_juramento($datos['descripcion_abreviada'], $datos['juramento']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	function validar_nombre_juramento($descripcion_abreviada, $juramento)
	{
		if (! toba::consulta_php('co_juramentos')->validar_nombre_juramento($descripcion_abreviada, $juramento)) {
			$this->set_error('dato_duplicado', array('nombre del juramento'));
		}
	}

}
?>