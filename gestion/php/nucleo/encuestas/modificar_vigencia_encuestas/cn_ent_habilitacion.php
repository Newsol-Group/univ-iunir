<?php
/**
 * 
 * @package Encuestas
 */
class cn_ent_habilitacion extends cn_entidad
{
	function get_datos($tabla=null)
	{
		return $this->tabla('habilitacion')->get();
	}

	function set_datos($datos, $tabla=null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('habilitacion')->set($datos);
	}

	//------------------------------------------------------------------------
	//-- Validaciones
	//------------------------------------------------------------------------

	function validar_datos($datos)
	{
		if (isset($datos['fecha_desde']) && guarani_fecha::es_menor($datos['fecha_desde'], guarani_fecha::get_hoy())) {
			$this->set_error('800_control_fechas', array('desde (de validez)', 'anterior', 'actual.'));
		}
		if (guarani_fecha::es_menor($datos['fecha_hasta'], guarani_fecha::get_hoy())) {
			$this->set_error('800_control_fechas', array('hasta (de validez)', 'anterior', 'actual.'));
		}
		if (isset($datos['fecha_desde']) && guarani_fecha::es_mayor_igual($datos['fecha_desde'], $datos['fecha_hasta'])) {
			$this->set_error('800_control_fechas', array('desde (de validez)', 'mayor o igual', 'hasta.'));
		}

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
}
?>