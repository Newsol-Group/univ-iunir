<?php
/**
 *	Representa un Motivo de Cambio de Calidad de Planes de Estudios
 *
 * @package 	Matricula
 * @subpackage 	Actualizaciones
 * 
 */
class cn_ent_motivo_cambio_plan_estudios extends cn_entidad
{
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('motivos_plan')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('motivos_plan')->get();
	}

	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{		
		$datos['motivo_plan'] = $this->tabla('motivos_plan')->get_columna('motivo_plan');
		$this->validar_nombre_motivo_plan($datos['nombre'], $datos['motivo_plan']);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	function validar_nombre_motivo_plan($nombre, $motivo)
	{
		if (! toba::consulta_php('co_motivos_cambio_plan_estudios')->validar_nombre_motivo_plan($nombre, $motivo)) {
			$this->set_error('dato_duplicado', array('nombre', 'Motivo de cambio de calidad de planes de estudio'));
		}
	}

}
?>