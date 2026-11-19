<?php
class cn_ent_administrar_turnos extends cn_entidad
{
	function pre_sincronizar()
	{
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function hay_turnos_asignados()
	{
		$datos = $this->get_datos();
		if (empty($datos['turno_config'])) {
			return false;
		} else {
			return toba::consulta_php('co_turnos_preinscripcion')->hay_turnos_asignados($datos['turno_config']);
		}
	}

	function get_datos_config()
	{
		$datos = [];
		$datos = $this->tabla('pre_turnos')->get();
		return $datos;
	}

	function set_datos_config($datos)
	{
		$this->validar_datos_config($datos);
		$this->tabla('pre_turnos')->set($datos);
	}

	function set_datos_agregar($datos)
	{
		$this->tabla('pre_turnos')->resetear();
		$this->validar_datos_config($datos);
		$this->tabla('pre_turnos')->nueva_fila($datos);
	}

	protected function fecha_no_habil($turno_config, $fecha)
	{
	}

	//--------------------------------------------------------------------------
	//---- Validaciones --------------------------------------------------------
	//--------------------------------------------------------------------------


	protected function validar_datos_config($datos)
	{
		if (empty($datos['turno_config'])) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una configuración'));
			$this->set_error($mje);
		} else { // Si no se seleccionó una configuración no tiene sentido validar las fechas.
			if (empty($datos['fecha'])) {
				$mje = guarani_mensajes::get('800_no_seleccion', array('una fecha'));
				$this->set_error($mje);
			} else {
				$this->validar_fecha($datos['turno_config'], $datos['fecha']);
			}
		}
		if (!is_numeric($datos['operador'])) {
			$mje = guarani_mensajes::get('800_error_valor_numerico', array($datos['operador']));
			$this->set_error($mje);
		}
	}

	protected function validar_fecha($turno_config, $fecha)
	{
		// La fecha de inicio no puede ser anterior a la fecha actual.
		if (guarani_fecha::es_menor_a_fecha_actual($fecha)) {
			$this->set_error('800_control_fechas', array('ingresada', 'menor', 'actual'));
		}
		$ubicaciones = toba::consulta_php('co_turnos_preinscripcion')->get_pre_turnos_config_ubicacion($turno_config);
		$es_dia_no_laborable = empty($ubicaciones) ? toba::consulta_php('co_dias_no_laborables')->es_dia_no_laborable($fecha) : toba::consulta_php('co_dias_no_laborables')->es_dia_no_laborable_ubicacion($fecha, $ubicaciones);
		if ($es_dia_no_laborable) {
			$mje = guarani_mensajes::get('800_dia_no_laboral');
			$this->set_error($mje);
		}
	}
}