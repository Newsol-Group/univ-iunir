<?php
/**
 * Constantes para Inscripciones a Cursadas
 *
 * @package Cursadas
 * @subpackage Inscripciones
 */
class inscripcion_cursada_nucleo
{
	// Tipos de inscripción
	const tipo_preinscripcion = 'P'; //Con alternativas o prioridades
	const tipo_inscripcion = 'I';  //Sin alternativas, prioridades - Inscripción directa

	// Estados
	const estado_aceptada = 'A';
	const estado_aceptada_descr = 'Aceptada';
	const estado_pendiente = 'P';
	const estado_pendiente_descr = 'Pendiente';
	
	const operacion_rechazo = 'R';
	const operacion_baja = 'B';
	const operacion_cambio_comision = 'C';

	const operacion_rechazo_desc = 'Rechazada';
	const operacion_baja_desc = 'Baja';
	const operacion_cambio_comision_desc = 'Baja por cambio de comisión';
	
	// Estados de las preinscripciones (sga_insc_cursada_preinsc_estados)
	const preinsc_aceptada = 'A';
	const preinsc_pendiente = 'P';
	const preinsc_rechazada = 'R';
        
        // Parametros
        const cur_notificar_estado_inscripcion = 'cur_notificar_estado_inscripcion';

	/*
	 * Determina si en la inscricpión a cursadas se ejecutan o no los controles en los puntos de control.
	 */
	static function ejecutar_controles($propuestas)
	{
		return (guarani::parametros()->get_valor('cur_ejecuta_controles', $propuestas) == comunes::si);
	}

	/*
	 * Devuelve el estado correspondiente al retorno de validación de puntos de control recibido.
	 */
	static function get_estado_equivalente($retorno_prunto_de_control)
	{
		switch ($retorno_prunto_de_control) {
			case guarani_punto_de_control::autorizado:
				return self::estado_aceptada;
			case guarani_punto_de_control::advertido:
				return self::estado_pendiente;
			case guarani_punto_de_control::rechazado:
				$msj = guarani_mensajes::get('800_inscripcion_fallo_ctrl');
				throw guarani::error($msj);
		}
	}

	/*
	 * Devuelve la descripción del estado recibido.
	 */
	static function get_estado_descripcion($estado)
	{
		switch ($estado) {
			case self::estado_aceptada:
				return 'Aceptada';
			case self::estado_pendiente:
				return 'Pendiente';
		}
	}

	/**
	 * Efectúa una inscripción de un alumno en una comisión que administra un curso
	 * 
	 * @param int $alumno
	 * @param int $propuesta
	 * @param int $plan_version
	 * @param int $comision
	 * @param int $interfaz
	 * @param array $params_sistema
	 * @return array
	 */
	static function inscripcion_curso($alumno, $propuesta, $plan_version, $comision, $interfaz = comunes::interfaz_gestion, $params_sistema = array())
	{
		$nro_transaccion = toba::consulta_php('co_inscripciones_cursadas')->get_nuevo_nro_transaccion();

		// Se guarda inscripción
		$datos_inscripcion = array();
		$datos_inscripcion['comision'] = $comision['comision'];
		$datos_inscripcion['periodo_lectivo'] = $comision['periodo_lectivo'];
		$datos_inscripcion['alumno'] = $alumno;
		$datos_inscripcion['prioridad'] = 1;
		$datos_inscripcion['tipo'] = inscripcion_cursada_nucleo::tipo_inscripcion;
		$datos_inscripcion['plan_version'] = $plan_version;
		$datos_inscripcion['estado'] = $comision['estado_inscripcion'];
		$datos_inscripcion['fuera_de_termino'] = comunes::no;
		$datos_inscripcion['autorizacion'] = null;
		$datos_inscripcion['propuesta'] = $propuesta;

		$dejar_pendiente = false;
		// Si la comisión es cobrable y se usa SQ, se forzará el estado pendiente en la inscripción
		if ($comision['cobrable'] == comunes::si) {
			$usa_sq = $params_sistema['sq_usa_sanaviron'];
			if ($usa_sq == comunes::si) {
				$dejar_pendiente = true;
			}
		}

		$instancias = aplanar_matriz($comision['instancias_a_inscribir'], 'instancia');
		$subcomisiones = array();

		return guarani::act('act_inscripcion_cursadas')->guardar_inscripcion_a_comision_func(
				$nro_transaccion, $datos_inscripcion, $instancias, $subcomisiones, $dejar_pendiente, $interfaz, $params_sistema
		);
	}

	/**
	 * Si la comisión es cobrable y se utiliza Sanavirón-Quilmes, se le informa 
	 * al sistema de cobros que se ha registrado la baja de la inscripción a curso
	 * 
	 * @param int		$nro_transaccion
	 * @param char		$modo_devolucion
	 */
	static function baja_inscripcion_sq($nro_transaccion, $modo_devolucion)
	{
		$body = array(
			'id' => $nro_transaccion,
			'tipo_novedad' => cobro::TIPO_NOVEDAD_BAJA,
			'observaciones' => "Baja inscripción. Nro. transacción: $nro_transaccion",
			'modo_devolucion' => $modo_devolucion
		);
		
		try {

			return cobro::informar_suscripcion_novedad($body);
			
		} catch (Exception $e) {

			switch ($e->getCode()) {
				case cobro::ESTADO_GET_VACIO:
					$mensaje = guarani_mensajes::get('800_sq_error_no_suscripcion');
					break;
				case cobro::ERROR_POST_SERV_VERS_SIN_SERVICIO:
					$mensaje = guarani_mensajes::get('800_sq_error_pago_avanzado_baja');
					break;
				default:
					$mensaje = guarani_mensajes::get('800_error_SQ');
			}

			throw new toba_error($mensaje);
			
		}
	}

}
?>