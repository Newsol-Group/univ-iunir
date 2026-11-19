<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Regularidad
 */
class cn_ent_readmision extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('readmisiones')->set($datos);
	}

	function get_datos($tabla=null)
	{
		return $this->tabla('readmisiones')->get();
	}

	function get_cursor_readmision()
	{
		return $this->tabla('readmisiones')->get_cursor();
	}
	
	function get_cambios_fila($datos)
	{
		return $this->tabla('readmisiones')->get_cambios_fila($this->get_cursor_readmision(), $datos);
	}
	
	function agregar_vencimiento($datos)
	{
		$this->tabla('readmisiones_vencimientos')->nueva_fila($datos);
	}
	
	function get_datos_vencimientos()
	{
		return $this->tabla('readmisiones_vencimientos')->get_filas();
	}
	
	function validar_datos($datos)
	{
		$this->validar_fechas($datos);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	function validar_fechas($datos)
	{
		$fecha_perdida_reg = toba::consulta_php('co_readmisiones')->get_fecha_perdida_regularidad($datos['perdida_regularidad']);
		
		if (guarani_fecha::es_menor($datos['fecha'], $fecha_perdida_reg)) {
			$this->set_error('800_control_fechas', array('de readmisión', 'menor', 'de pérdida de regularidad'));
		}
		
		if (isset($datos['fecha_vencimiento'])) {
			if (guarani_fecha::es_mayor($datos['fecha'], $datos['fecha_vencimiento'])) {
				$this->set_error('800_control_fechas', array('Readmisión', 'mayor', 'Vencimiento readmisión'));
			}
		}
	}
	
}
?>