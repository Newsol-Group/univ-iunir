<?php
/**
 *	Representa una Recordatorio
 *
 * @package Administracion
 * @subpackage Actualizaciones
 */
class cn_ent_recordatorio extends cn_entidad
{

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		
		$datos_persona = toba::consulta_php('co_personas')->get_persona_x_usuario(toba::usuario()->get_id());
		$datos['persona'] = $datos_persona['persona'];
		
		$this->tabla('recordatorios')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('recordatorios')->get();
	}
	
	//--------------------------------------------------------------------------
	//------ Validaciones ------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function validar_datos($datos)
	{	
		$this->validar_fechas($datos);
		
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	function validar_fechas($datos)
	{	
		$fecha_desde = $datos['fecha_desde'];
		$fecha_hasta = $datos['fecha_hasta'];
		
		if (isset($fecha_desde) && isset($fecha_hasta)) {
			if (guarani_fecha::es_mayor($fecha_desde, $fecha_hasta)) {
				$this->set_error('800_control_fechas', array('Desde', 'mayor', 'Hasta.'));
			}
		} 
	}

}
?>