<?php
class cn_ent_generar_turnos extends cn_entidad
{
	function pre_sincronizar()
	{
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
		$this->generar_turnos();
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

	//--------------------------------------------------------------------------
	//---- TABLA: pre_turnos_config
	//--------------------------------------------------------------------------

	function get_datos_config()
	{
		return $this->tabla('pre_turnos_config')->get();
	}

	function set_datos_config($datos)
	{
		$this->validar_datos_config($datos);
		$this->tabla('pre_turnos_config')->set($datos);
	}

	//--------------------------------------------------------------------------
	//---- TABLA: pre_turnos_config_intervalos
	//--------------------------------------------------------------------------

	function get_datos_config_intervalos()
	{
		return $this->tabla('pre_turnos_config_intervalos')->get_filas();
	}

	function set_datos_config_intervalos($datos)
	{
		$this->validar_datos_config_intervalos($datos);
		$this->tabla('pre_turnos_config_intervalos')->procesar_filas($datos);
	}

	//--------------------------------------------------------------------------
	//---- TABLA: pre_turnos_config_ra
	//--------------------------------------------------------------------------

	function get_datos_config_ra()
	{
		$datos = $this->tabla('pre_turnos_config_ra')->get_filas();
		$salida = array();
		$salida['responsables_academicas'] = array();
		foreach ($datos as $dato) {
			$salida['responsables_academicas'][] = $dato['responsable_academica'];
		}
		return $salida;
	}

	function set_datos_config_ra($datos)
	{
		$this->validar_datos_config_ra($datos);
		$this->tabla('pre_turnos_config_ra')->resetear();
		$datos_ras = $datos['responsables_academicas'];
		foreach ($datos_ras as $ra) {
			$this->tabla('pre_turnos_config_ra')->nueva_fila(array('responsable_academica' => $ra));
		}
	}

	//--------------------------------------------------------------------------
	//---- TABLA: pre_turnos_config_ubicacion
	//--------------------------------------------------------------------------

	function get_datos_config_ubicacion()
	{
		$datos = $this->tabla('pre_turnos_config_ubicacion')->get_filas();
		$this->validar_datos_config_ra($datos);
		$salida = array();
		$salida['ubicacion'] = array();
		foreach ($datos as $dato) {
			$salida['ubicacion'][] = $dato['ubicacion'];
		}
		return $salida;
	}

	function set_datos_config_ubicacion($datos)
	{
		$this->tabla('pre_turnos_config_ubicacion')->resetear();
		$datos_ras = $datos['ubicacion'];
		foreach ($datos_ras as $ra) {
			$this->tabla('pre_turnos_config_ubicacion')->nueva_fila(array('ubicacion' => $ra));
		}
	}

	//--------------------------------------------------------------------------
	//---- TABLA: pre_turnos_config_modalidad
	//--------------------------------------------------------------------------

	function get_datos_config_modalidad()
	{
		$datos = $this->tabla('pre_turnos_config_modalidad')->get_filas();
		$this->validar_datos_config_modalidad($datos);
		$salida = array();
		$salida['modalidad'] = array();
		foreach ($datos as $dato) {
			$salida['modalidad'][] = $dato['modalidad'];
		}
		return $salida;
	}

	function set_datos_config_modalidad($datos)
	{
		$this->tabla('pre_turnos_config_modalidad')->resetear();
		$this->validar_datos_config_modalidad($datos);
		$datos_mod = $datos['modalidad'];
		foreach ($datos_mod as $m) {
			$this->tabla('pre_turnos_config_modalidad')->nueva_fila(array('modalidad' => $m));
		}
	}

	//--------------------------------------------------------------------------
	//---- TABLA: pre_turnos
	//--------------------------------------------------------------------------

	protected function generar_turnos()
	{
		$this->tabla('pre_turnos')->resetear();

		$datos = $this->get_datos_config();
		$fecha = $datos['fecha_inicio'];
		$fecha_fin = $datos['fecha_fin'];
		$dias_atencion = $this->get_dias_turnos($datos);

		$intervalos = $this->get_datos_config_intervalos();

		while (guarani_fecha::es_menor_igual($fecha, $fecha_fin, true)) {
			if ($this->generar_turnos_en_fecha($fecha, $dias_atencion, $datos['atiende_feriados'])) {
				foreach ($intervalos as $intervalo) {
					$hora = $intervalo['hora_inicio'];
					while (guarani_hora::es_menor($hora, $intervalo['hora_fin'])) {
						// Operadores
						for ($i = 1; $i <= $datos['cant_operadores']; $i++) {
							$turno = array(
								'fecha' => $fecha,
								'hora' => $hora,
								'operador' => $i
							);
							// Se agrega el turno
							$this->tabla('pre_turnos')->nueva_fila($turno);
						}
						$hora = guarani_hora::sumar_minutos_a_hora($hora, $datos['minutos_intervalo']);
					}
				}
			}
			$fecha = guarani_fecha::get_fecha_desplazada($fecha, 1, 'dia');
		}
	}

	protected function get_dias_turnos($datos)
	{
		$salida = array();
		if ($datos['atiende_domingo']) {
			$salida[] = 0;
		}
		if ($datos['atiende_lunes']) {
			$salida[] = 1;
		}
		if ($datos['atiende_martes']) {
			$salida[] = 2;
		}
		if ($datos['atiende_miercoles']) {
			$salida[] = 3;
		}
		if ($datos['atiende_jueves']) {
			$salida[] = 4;
		}
		if ($datos['atiende_viernes']) {
			$salida[] = 5;
		}
		if ($datos['atiende_sabado']) {
			$salida[] = 6;
		}
		return $salida;
	}

	protected function generar_turnos_en_fecha($fecha, $dias_atencion, $atiende_feriados)
	{
		return in_array(guarani_fecha::dia_de_la_semana($fecha), $dias_atencion) && ($atiende_feriados || !$this->fecha_no_habil($fecha));
	}

	protected function fecha_no_habil($fecha)
	{
		$ubicaciones = $this->get_datos_config_ubicacion();
		return empty($ubicaciones['ubicacion']) ? toba::consulta_php('co_dias_no_laborables')->es_dia_no_laborable($fecha) : toba::consulta_php('co_dias_no_laborables')->es_dia_no_laborable_ubicacion($fecha, $ubicaciones['ubicacion']);
	}

	//--------------------------------------------------------------------------
	//---- Validaciones --------------------------------------------------------
	//--------------------------------------------------------------------------

	protected function validar_datos_config_intervalos($datos)
	{
		if (empty($datos)) {
			$mje = 'Debe ingresar al menos un intervalo de atención para poder continuar';
			$this->set_error($mje);
		} else {
			$this->validar_horas($datos);
		}
	}

	protected function validar_horas($datos)
	{
		foreach ($datos as $dato) {
			$hora_inicio = $dato['hora_inicio'];
			$hora_fin = $dato['hora_fin'];
			// La hora hasta no puede ser anterior a la hora desde
			if (guarani_hora::es_menor($hora_fin, $hora_inicio)) {
				$mensaje = "La hora fin no puede ser mayor a la hora de inicio en el intervalo [$hora_inicio - $hora_fin)";
				$this->set_error($mensaje);
			}
		}
	}

	protected function validar_datos_config_ra($datos)
	{
		if (empty($datos['responsables_academicas'])) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una Responsable Académica'));
			$this->set_error($mje);
		}
	}

	protected function validar_datos_config_ubicacion($datos)
	{
		if (empty($datos['ubicacion'])) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una Ubicación'));
			$this->set_error($mje);
		}
	}

	protected function validar_datos_config_modalidad($datos)
	{
		if (empty($datos['modalidad'])) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una Modalidad'));
			$this->set_error($mje);
		}
	}

	protected function validar_datos_config($datos)
	{
		$this->validar_fechas($datos);
		$this->validar_dias_atencion($datos);
	}

	protected function validar_fechas($datos)
	{
		$fecha_inicio = $datos['fecha_inicio'];
		$fecha_fin = $datos['fecha_fin'];

		// La fecha de inicio no puede ser anterior a la fecha actual.
		if (guarani_fecha::es_menor_a_fecha_actual($fecha_inicio)) {
			$this->set_error('800_control_fechas', array('de inicio', 'anterior', 'actual'));
		}

		// La fecha fin no puede ser anterior a la fecha desde.
		if (guarani_fecha::es_menor($fecha_fin, $fecha_inicio)) {
			$this->set_error('800_control_fechas', array('de inicio', 'mayor', 'de finalización'));
		}
	}

	protected function validar_dias_atencion($datos)
	{
		$cont = $datos['atiende_lunes'] + $datos['atiende_martes'] + $datos['atiende_miercoles'] + $datos['atiende_jueves'] + $datos['atiende_viernes'] + $datos['atiende_sabado'] + $datos['atiende_domingo'];
		if ($cont == 0) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('un día de atención'));
			$this->set_error($mje);
		}
	}

}