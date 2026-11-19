<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class cn_ent_distribuir_comisiones extends cn_procesos_bk
{
	// Proceso background
	protected $s__proceso;	
	
	protected $hay_errores = false;
	protected $errores;

	function cargar_atributos_parametros($inscripciones)
	{
		$operacion = guarani::get_id_operacion();

		// Se obtienen las propuestas de los alumnos seleccionados
		$propuestas = toba::consulta_php('co_inscripciones_cursadas')->get_propuestas_inscripciones($inscripciones);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);

		// Se obtienen los planes de las inscripciones seleccionadas.
		$planes = toba::consulta_php('co_inscripciones_cursadas')->get_planes_inscripciones($inscripciones);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_plan, $planes);
	}
	
	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_distribuir_comisiones::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}

	function validar_alumnos($inscripciones, $comision_destino, $distribucion)
	{
		if ($distribucion != comision::dist_com_subcom) {
			$this->validar_destino($inscripciones, $comision_destino);
		}
		$this->validar_division_comisiones($inscripciones, $comision_destino);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	/**
	 * Valida que las comisiones orígenes de los alumnos seleccionados para distribuir no coincidan con el destino.
	 */
	function validar_destino($inscripciones, $comision_destino)
	{
		// PARA EL CASO DE DISTRIBUCIÓN ENTRE COMISIONES.
		// Obtiene los datos correspondientes a las inscripciones seleccionadas para distribuir.
		$cond_in = guarani_sql::armar_condicion_in($inscripciones, 'vw_insc_cursada', 'inscripcion', 'inscripcion');
		$datos_inscripciones = toba::consulta_php('co_inscripciones_cursadas')->get_datos_basicos_inscripciones($cond_in);
		foreach ($datos_inscripciones as $inscripcion) {
			if ($inscripcion['comision'] == $comision_destino) {
				$this->set_error('800CUR_error_origen_alumno', array('la Comisión', $inscripcion['comision_nombre'], $inscripcion['apellido_nombres']));
			}
		}
	}

	/**
	 * Valida que si el parámetro de División es estricto, la división del alumno sea igual a la de la comisión destino.
	 */
	function validar_division_comisiones($inscripciones, $comision_destino)
	{
		// Aplica tanto a la Distribución entre Comisiones, como a Subcomisiones.
		if (guarani::parametros()->get_valor('cur_division_estricta') == comunes::si) {
			$datos_comision_destino = current(toba::consulta_php('co_comisiones')->get_datos_comision($comision_destino));
			if (isset($datos_comision_destino['division'])) {
				$cond_in = guarani_sql::armar_condicion_in($inscripciones, 'vw_insc_cursada', 'inscripcion', 'inscripcion');
				$datos_inscripciones = toba::consulta_php('co_inscripciones_cursadas')->get_datos_basicos_inscripciones($cond_in);
				foreach ($datos_inscripciones as $alumno) {
					if (isset($alumno['alumno_division']) && $alumno['alumno_division'] != $datos_comision_destino['division']) {
						$this->set_error('800CUR_error_alumno_distinta_division', array($alumno['apellido_nombres'], $alumno['division_nombre'], $datos_comision_destino['comision_nombre'], $datos_comision_destino['division_nombre']));
					}
				}
			}
		}
	}

	/**
	 * Se controla que no se supere el cupo de las subcomisiones.
	 */
	function validar_cupo_comisiones($inscripciones, $tipo_dist, $alumnos_en_distribucion, $control_cupo_por, $comision_destino)
	{
		$cant_alumnos = guarani_arreglos::count($inscripciones);
		if (!empty($alumnos_en_distribucion)) {
			$cant_alu_en_distribucion = $this->get_alumnos_en_actual_distribucion_destino($alumnos_en_distribucion, $tipo_dist, $comision_destino);
			$cant_alumnos = $cant_alumnos + $cant_alu_en_distribucion;
		}
		// La cantidad de inscriptos se calcula a partir de la comisión, o partir de todos los inscriptos en las
		// comisiones que comparten la misma banda horaria, según si el parámetro de control de cupo es por
		// comisión o por aula respectivamente.
		if ($control_cupo_por == comision::cupo_por_comision) {
			$cant_inscriptos = ", sga_comisiones_cupo.cant_inscriptos AS cant_inscriptos";
		} else {
			$cant_inscriptos = ', (SELECT	COALESCE(SUM(sga_comisiones_cupo.cant_inscriptos) ,0)
											FROM sga_comisiones_cupo 
											WHERE sga_comisiones_cupo.comision IN (
												  SELECT	DISTINCT bh2.comision 
															FROM sga_comisiones_bh,
																 sga_comisiones_bh as bh2
															WHERE sga_comisiones_bh.comision = sga_comisiones.comision
																  AND bh2.asignacion = sga_comisiones_bh.asignacion
												)
									) AS cant_inscriptos';
		}
		$cupo_com = current(toba::consulta_php('co_comisiones')->get_datos_comision($comision_destino, '', $cant_inscriptos));
		if (($cupo_com['cupo']) && (($cupo_com['cupo'] - ($cupo_com['cant_inscriptos'] + ($cant_alumnos))) < 0)) {
			$msj_alerta = guarani_mensajes::get('800SIU_insc_cursada_sin_cupo_alerta', array('Comisión', $cupo_com['comision_nombre'], $cupo_com['cupo']));
			return $msj_alerta;
		}
	}

	/**
	 * Se controla que no se supere el cupo de las subcomisiones.
	 */
	function validar_cupo_subcomisiones($inscripciones, $tipo_dist, $alumnos_en_distribucion, $control_cupo_por, $subcos_dest)
	{
		if (!empty($subcos_dest)) {
			$cant_alumnos = guarani_arreglos::count($inscripciones);
			// La cantidad de inscriptos se calcula a partir de la subcomisión, o partir de todos los inscriptos en las
			// subcomisiones que comparten la misma banda horaria, según si el parámetro de control de cupo es por
			// comisión o por aula respectivamente.
			if ($control_cupo_por == comision::cupo_por_comision) {
				$cant_inscriptos = ", sga_subcomisiones_cupo.cant_inscriptos AS cant_inscriptos";
			} elseif ($control_cupo_por == comision::cupo_por_aula) {
				$cant_inscriptos = ",(SELECT COALESCE(SUM(cupo.cant_inscriptos),0) 
									 FROM	sga_subcomisiones_cupo as cupo
									 WHERE	cupo.subcomision IN ( 
																SELECT	DISTINCT sga_subcomisiones_bh.subcomision
																FROM	sga_comisiones_bh,
																		sga_subcomisiones_bh
																WHERE	sga_subcomisiones_bh.banda_horaria = sga_comisiones_bh.banda_horaria
																		AND sga_comisiones_bh.asignacion IN (
																				SELECT	sga_comisiones_bh.asignacion
																				FROM	sga_subcomisiones_bh, 
																						sga_comisiones_bh 
																				WHERE	sga_subcomisiones_bh.subcomision = sga_subcomisiones.subcomision
																						AND sga_comisiones_bh.banda_horaria = sga_subcomisiones_bh.banda_horaria
																			)
											)	
									) AS cant_inscriptos";
			}

			$filtro = 'sga_subcomisiones.subcomision IN (' . implode(',', toba::db()->quote($subcos_dest)) . ') ';
			$subcos_cupos = toba::consulta_php('co_comisiones')->get_subcomisiones_cupo($filtro, $cant_inscriptos);
			$nombres_sub = '';
			$cupos_sub = '';
			$cant = 0;
			foreach ($subcos_cupos as $subcom) {
				if (!empty($alumnos_en_distribucion)) {
					$cant_alu_en_distribucion = $this->get_alumnos_en_actual_distribucion_destino($alumnos_en_distribucion, $tipo_dist, $subcom['subcomision']);
					$cant_alumnos = $cant_alumnos + $cant_alu_en_distribucion;
				}
				if (($subcom['cupo']) && (($subcom['cupo'] - ($subcom['cant_inscriptos'] + ($cant_alumnos))) < 0)) {
					$nombres_sub .= $subcom['subcomision_nombre'] . ' ';
					$cupos_sub .= $subcom['cupo'] . ' ';
					$cant++;
				}
			}
			if ($cant > 0) {
				if ($cant > 1) {
					$descr = 'Subcomisión';
				} else {
					$descr = 'Subcomisiones';
				}
				$msj_alerta = guarani_mensajes::get('800SIU_insc_cursada_sin_cupo_alerta', array($descr, $nombres_sub, $cupos_sub));
				return $msj_alerta;
			}
		}
	}

	/**
	 * Recupera la cantidad de alumnos que están actualmente distribuidos al destino dado (Comisión o Subcomisión) en 
	 * memoria temporal, durante la misma ejecución de la operación, sin haberse confirmado y guardado en la base.
	 */
	function get_alumnos_en_actual_distribucion_destino($alumnos_en_distribucion, $tipo_dist, $destino)
	{
		$cant = 0;
		if ($tipo_dist == comision::dist_entre_com) {
			// Para un destino (distribución a comisión).
			foreach ($alumnos_en_distribucion as $alu) {
				if ($alu['destino'] == $destino) {
					$cant++;
				}
			}
		} elseif ($tipo_dist == comision::dist_com_subcom_otra_com) {
			// Para varios destinos (distribución a subcomisiones).
			foreach ($alumnos_en_distribucion as $alu) {
				$comision = $alu['destino'][0];
				$subcos = $alu['destino'][1];
				if (in_array($destino, $subcos)) {
					$cant++;
				}
			}
		}
		return $cant;
	}

	function get_errores()
	{
		return $this->errores;
	}

	function hay_errores()
	{
		return $this->hay_errores;
	}

	/**
	 * Acumula mensajes a enviar a la pantalla
	 */
	function set_error($descripcion_corta, $parms = null, $tipo = null)
	{
		$mensaje = guarani_mensajes::get_msg_info($descripcion_corta, $parms);
		// Si no está el mensaje definido en la base
		if (!$mensaje) {
			$mensaje = array('mensaje' => $descripcion_corta, 'tipo' => comunes::error_critico);
		}
		// Si hay definido un tratamiento para el mensaje
		if ($tipo) {
			$mensaje['tipo'] = $tipo;
		}
		$this->hay_errores = true;
		$this->errores[] = $mensaje;
	}

}