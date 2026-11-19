<?php
/**
 *
 * Representa un Motivo de Excepcion de Requisitos
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class cn_ent_motivo_de_excepcion_de_requisitos extends cn_entidad
{
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('motivos_excepcion_r')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('motivos_excepcion_r')->get();
	}
	
	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$datos['motivo_excepcion'] = $this->tabla('motivos_excepcion_r')->get_columna('motivo_excepcion');
		$this->validar_nombre_motivo_de_excepcion($datos['nombre'], $datos['motivo_excepcion']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	function validar_nombre_motivo_de_excepcion($nombre, $motivo_excepcion)
	{
		if (! toba::consulta_php('co_motivos_de_excepcion_de_requisitos')->validar_nombre_motivo_de_excepcion($nombre, $motivo_excepcion)) {
			$this->set_error('dato_duplicado', array('nombre del motivo de excepción de requisitos'));
		}
	}

}
?>