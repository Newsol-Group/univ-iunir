<?php
/**
 *	Representa un Motivo de Cambio de Calidad de Planes de Estudios
 *
 * @package Matricula
 * @subpackage Actualizaciones
 */
class cn_ent_motivo_cambio_ubicacion extends cn_entidad
{
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('motivos_ubicacion')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('motivos_ubicacion')->get();
	}

	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{		
		$datos['motivo_ubicacion'] = $this->tabla('motivos_ubicacion')->get_columna('motivo_ubicacion');
		$this->validar_nombre_motivo_cambio_ubicacion($datos['nombre'], $datos['motivo_ubicacion']);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	function validar_nombre_motivo_cambio_ubicacion($nombre, $motivo)
	{
		if (! toba::consulta_php('co_motivos_cambio_ubicacion')->validar_nombre_motivo_cambio_ubicacion($nombre, $motivo)) {
			$this->set_error('dato_duplicado', array('nombre', 'Motivo de cambio de ubicación'));
		}
	}
}
?>