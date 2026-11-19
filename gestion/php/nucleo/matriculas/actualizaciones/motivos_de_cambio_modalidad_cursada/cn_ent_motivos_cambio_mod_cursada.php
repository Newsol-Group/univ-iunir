<?php

/**
 *	Representa Motivos de cambio de Modalidad de Cursada
 *
 * @package Matrícula
 * @subpackage Actualizaciones
 */
class cn_ent_motivos_cambio_mod_cursada extends cn_entidad
{
		
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('motivos_modalidad')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('motivos_modalidad')->get();
	}
	
	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$datos['motivo_modalidad'] = $this->tabla('motivos_modalidad')->get_columna('motivo_modalidad');
		
		$this->validar_nombre_motivo_de_cambio_de_modalidad($datos['nombre'], $datos['motivo_modalidad']);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	function validar_nombre_motivo_de_cambio_de_modalidad($nombre, $motivo)
	{
		if (! toba::consulta_php('co_motivos_cambio_mod_cursada')->validar_nombre_motivo_de_cambio_de_modalidad($nombre, $motivo)) {
			$this->set_error('dato_duplicado', array('nombre', 'Motivo de Cambio de Modalidad'));
		}
	}
	
}

?>