<?php
/**
 * 
 * 
 * @package 	Exámenes
 * @subpackage 	Inscripciones
 * 
 */
class cn_man_excepciones_insc_examenes_pendientes extends cn_manejador_entidad
{
	// Listados que discriminan las inscripciones que superaron o no los controles configurables.
	protected $s__exitosas;
	protected $s__no_exitosas;
	protected $s__mensajes_error;

	function etiquetas($dep = null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_insc_examen');
		} else {
			parent::etiquetas($dep);
		}
	}

	/**
	 * Retorna el listado de inscripciones a examen pendientes
	 *
	 * @param string $filtro
	 * @return array
	 */
	function get_listado($filtro)
	{
		if ($filtro) {
			$filtro .= ' AND ';
		}
		$filtro .= "sga_insc_examen.estado = '" . inscripcion_examen::estado_pendiente . "'";
		return guarani::co('co_inscripciones_examenes')->get_listado_inscripciones($filtro);
	}

	/**
	 * Retorna el conjunto de inscripciones que superaron todos los controles (Autorizado).
	 *
	 * @return array
	 */
	function get_insc_exitosas()
	{
		return $this->s__exitosas;
	}

	/**
	 * Retorna el conjunto de inscripciones que no superaron algún control (Advertido o Rechazado).
	 *
	 * @return array
	 */
	function get_insc_no_exitosas()
	{
		return $this->s__no_exitosas;
	}

	/**
	 * Retorna los mensajes de error recolectados para una inscripción dada.
	 *
	 * @param int $inscripcion
	 * @return array
	 */
	function get_mensajes_error($inscripcion)
	{
		return $this->s__mensajes_error[$inscripcion];
	}

	function resetear()
	{
		unset($this->s__exitosas);
		unset($this->s__no_exitosas);
		unset($this->s__mensajes_error);

		parent::resetear();
	}

	function exceptuar_inscripciones($inscripciones)
	{
		try {
			toba::db()->abrir_transaccion();
			foreach ($inscripciones as $i) {
				guarani::act('act_inscripcion_examenes')->exceptuar_inscripcion($i['inscripcion'], $i['motivo_excepcion']);
			}
			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Controles --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Ejecuta controles configurables sobre un conjunto de inscripciones, discriminándolas entre exitosas y no exitosas.
	 *
	 * @param array $inscripciones
	 */
	function controlar_inscripciones($inscripciones)
	{
		$this->s__insc_exitosas = array();
		$this->s__insc_no_exitosas = array();
		$this->s__mensajes_error = array();

		foreach ($inscripciones as $inscripcion) {
			$retorno = $this->validar_puntos_de_control($inscripcion);

			if ($retorno == guarani_punto_de_control::autorizado) {
				$this->s__exitosas[] = $inscripcion['inscripcion'];
			} else {
				$this->s__no_exitosas[] = $inscripcion['inscripcion'];
			}
		}

		// Las inscripciones que superaron los controles quedan automáticamente aceptadas.
		try {
			if (guarani_arreglos::count($this->s__exitosas) > 0) {
				guarani::act('act_inscripcion_examenes')->aceptar_inscripciones($this->s__exitosas);
                                if( guarani::parametros()->get_valor(inscripcion_examen::exa_notificar_estado_inscripcion) == comunes::si ) {
                                    foreach ( $this->s__exitosas as $i ) {
                                        $datos_inscripcion = toba::consulta_php('co_inscripciones_examenes')->get_insc_examen('inscripcion = ' . $i);
                                        $datos_mesa = toba::consulta_php('co_mesas_de_examen')->get_datos_llamado_mesa($datos_inscripcion[0]['llamado_mesa']);
                                        $datos_alumno = toba::consulta_php('co_alumnos')->get_datos_alumno($datos_inscripcion[0]['alumno']);
                                        $parametros['estado'] = $datos_inscripcion[0]['estado'];
                                        $parametros['nombre_actividad'] = $datos_mesa['actividad_nombre'];
                                        $parametros['fecha_mesa'] = $datos_mesa['fecha_formato_visual'];
                                        $parametros['persona'] = $datos_alumno[0]['persona'];                            
                                        $parametros['email'] = toba::consulta_php('co_personas')->get_emails_personas( Array($datos_alumno[0]['persona']) ); 
                                        $parametros['nombre_alumno'] = $datos_alumno[0]['nombres'];
                                        guarani::notificador()->enviar(notificador::inscribir_a_examenes, $parametros);
                                    }
                                }
			}
		} catch (guarani_error $e) {
			unset($this->s__exitosas);
			throw $e;
		}
	}

	function validar_puntos_de_control($datos_inscripcion)
	{
		$puntos_de_control_instancia = toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($datos_inscripcion['instancia']);
		$puntos_de_control = array(
			guarani_punto_de_control::alumno_entidad,
			guarani_punto_de_control::persona_operacion,
			guarani_punto_de_control::alumno_operacion,
			guarani_punto_de_control::elegir_actividad_examen,
			guarani_punto_de_control::alumno_mesa_examen,
			guarani_punto_de_control::persona_propuesta
		);
		$puntos_de_control = array_merge($puntos_de_control, $puntos_de_control_instancia);

		$parametros = array(
			'fecha' => guarani_fecha::get_hoy(),
			'persona' => $datos_inscripcion['persona'],
			'alumno' => $datos_inscripcion['alumno'],
			'plan_version' => $datos_inscripcion['plan_version'],
			'elemento' => $datos_inscripcion['elemento'],
			'llamado_mesa' => $datos_inscripcion['llamado_mesa'],
			'propuesta' => $datos_inscripcion['propuesta']
		);

		$retorno = guarani::validador_puntos_de_control($puntos_de_control, $parametros)->controlar();

		if ($retorno != guarani_punto_de_control::autorizado) {
			$this->s__mensajes_error[$datos_inscripcion['inscripcion']] = guarani::validador_puntos_de_control()->get_mensajes();
		}

		return $retorno;
	}

}
?>