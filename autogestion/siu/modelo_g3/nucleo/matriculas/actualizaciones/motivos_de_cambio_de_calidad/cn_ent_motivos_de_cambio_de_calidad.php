<?php
/**
 *	Representa un motivo de cambio de calidad
 *
 * @package Matricula
 * @subpackage Actualizaciones
 */
class cn_ent_motivos_de_cambio_de_calidad extends cn_entidad
{
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('motiv_cambio_calidad')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('motiv_cambio_calidad')->get();
	}

	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$datos['motivo_calidad'] = $this->tabla('motiv_cambio_calidad')->get_columna('motivo_calidad');
		$this->validar_nombre_motivo_de_cambio_de_calidad($datos['nombre'], $datos['motivo_calidad']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	function validar_nombre_motivo_de_cambio_de_calidad($nombre, $motivo)
	{
		if (! toba::consulta_php('co_motivos_de_cambio_de_calidad')->validar_nombre_motivo_de_cambio_de_calidad($nombre, $motivo)) {
			$this->set_error('dato_duplicado', array('nombre', 'Motivo de cambio de calidad'));
		}
	}

}
?>