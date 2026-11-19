<?php
/**
 * 
 *
 * @package		Matrícula
 * @subpackage 	Alumnos
 */
class pro_cambiar_masivamente_plan_alumnos_nucleo extends guarani_procesos_bk
{
	protected $alumnos = array();
	protected $destino;
	protected $documento;
	protected $observaciones;
	protected $plan_version_destino;
	protected $plan_destino_nombre;
	protected $version_destino_nombre;

	protected $msj_sin_plan_anterior = '800MAT_alumno_sin_plan_anterior';
	protected $msj_plan_baja = '800MAT_alumno_plan_baja';
	
	const nombre = 'pro_cambiar_masivamente_plan_alumnos';
	const reporte_cant_modificados = 1;
	const reporte_cant_no_modificados = 2;
	const reporte_equiv_otorgadas_alumno = 3;
	const reporte_equiv_otorgadas_actividad = 4;
	const reporte_resultados = 5;

	function set_datos($datos=array())
	{
		$this->alumnos = $datos['alumnos'];
		$this->destino = $datos['destino_clasificacion'];
		if (isset($datos['documento'])) {
			$this->documento = $datos['documento'];
		
		}
		if (isset($datos['observaciones'])) {
			$this->observaciones = $datos['observaciones'];
		}
		if (isset($datos['plan_version_destino'])) {
			$this->plan_version_destino = $datos['plan_version_destino'];
			$this->plan_destino_nombre = $datos['plan_destino_nombre'];
			$this->version_destino_nombre = $datos['version_destino_nombre'];
		}
	}

	function procesar()
	{
		if (!empty($this->alumnos)) {
			$paso = 100/guarani_arreglos::count($this->alumnos);
		} else {
			$this->log_estado->incrementar_progreso(100);
		}

		$cant_alumnos_cambiados = 0;
		$cant_alumnos_no_cambiados = 0;
		
		foreach ($this->alumnos as $alumno) {
            $estado = $this->get_estado();
            if ((isset($estado['abortar'])) && ($estado['abortar'])) {
                $this->abortar = true;
                break;
            }
			$modificado = true;
			try {
				$dato_reporte = array();
				$dato_reporte['alumno'] 			= $alumno['alumno'];
				$dato_reporte['identificacion'] 	= $alumno['nro_identificacion'];
				$dato_reporte['apellido_y_nombre'] 	= $alumno['apellido_y_nombres'];
				
				switch ($this->destino) {
					case alumno::dest_planes_activos_vigentes:
						$this->cambiar_alumno_plan($alumno['alumno'], $this->plan_version_destino, alumno::motivo_plan_cambio, alumno::cambio_plan_tipo_forzado);
						$dato_reporte['plan_version_destino'] = $this->plan_version_destino;
						$dato_reporte['plan_destino_nombre'] = $this->plan_destino_nombre;
						$dato_reporte['version_destino_nombre'] = $this->version_destino_nombre;
						break;
					case alumno::dest_plan_anterior:
						$plan_version_anterior_alumno = current(toba::consulta_php('co_alumnos')->get_plan_version_anterior_alumno($alumno['alumno'], 'PLAN'));
						if (!empty($plan_version_anterior_alumno)) {
							// Si el alumno tiene un plan versión anterior y está activo, se cambia. En caso contrario se muestra el msj de error.
							$datos_plan_version = current(toba::consulta_php('co_planes')->get_datos_plan_version($plan_version_anterior_alumno));
							if ($datos_plan_version['estado'] <> planes::estado_baja) {
								$this->cambiar_alumno_plan($alumno['alumno'], $plan_version_anterior_alumno, alumno::motivo_plan_cambio, alumno::cambio_plan_tipo_forzado);
								$dato_reporte['plan_version_destino'] = $datos_plan_version['plan_version'];
								$dato_reporte['plan_destino_nombre'] = $datos_plan_version['plan_nombre'];
								$dato_reporte['version_destino_nombre'] = $datos_plan_version['version_nombre'];
							} else {
								// El plan anterior del Alumno está dado de Baja.
								$dato_reporte['motivo'] = guarani_mensajes::get($this->msj_plan_baja);
								$modificado = false;
							}
						} else {
							// Si el alumno NO tiene un plan anterior al actual (en el histórico de cambios de plan).
							$dato_reporte['motivo'] = guarani_mensajes::get($this->msj_sin_plan_anterior);
							$modificado = false;
						}
						break;
					case alumno::dest_ultima_version:
						// Se cambia a la última versión vigente del plan origen de los alumnos, pasada como parámetro al proceso.
						$this->cambiar_alumno_plan($alumno['alumno'], $this->plan_version_destino, alumno::motivo_plan_cambio_version, alumno::cambio_plan_version_tipo_forzado);
						$dato_reporte['plan_version_destino'] = $this->plan_version_destino;
						$dato_reporte['plan_destino_nombre'] = $this->plan_destino_nombre;
						$dato_reporte['version_destino_nombre'] = $this->version_destino_nombre;
						break;
				}
			} catch(toba_error $e) {
				$dato_reporte['motivo'] = $e->get_mensaje();
				$modificado = false;
			}
			
			if ($modificado) {
				$this->log->set($dato_reporte, array(self::reporte_cant_modificados));
				$cant_alumnos_cambiados++;
			} else {
				$this->log_estado->set_error();
				$this->log->set($dato_reporte, array(self::reporte_cant_no_modificados));
				$cant_alumnos_no_cambiados++;
			}
			
			$this->log->guardar();
			$this->log_estado->incrementar_progreso($paso);
		}

		$mensaje_resultado = array();
		$mensaje_resultado['cant_alumnos'] = $cant_alumnos_cambiados + $cant_alumnos_no_cambiados;
		$mensaje_resultado['cant_modificados'] = $cant_alumnos_cambiados;
		$mensaje_resultado['cant_no_modificados'] = $cant_alumnos_no_cambiados;
		
		$reporte_equiv_alumnos = $this->obtener_equivalencias_otorgadas($this->alumnos, $this->destino);
		$total_equiv = 0;
		foreach ($reporte_equiv_alumnos as $alu_equiv) {
			$this->log->set($alu_equiv, array(self::reporte_equiv_otorgadas_alumno));
			$total_equiv += $alu_equiv['cant_totales'] + $alu_equiv['cant_parciales'] + $alu_equiv['cant_regularidad'];
		}
		$mensaje_resultado['cant_equiv_otorgadas'] = $total_equiv;
		
		$reporte_equiv_actividades = $this->obtener_equivalencias_otorgadas($this->alumnos, $this->destino, false);
		foreach ($reporte_equiv_actividades as $activ_equiv) {
			$this->log->set($activ_equiv, array(self::reporte_equiv_otorgadas_actividad));
		}
		
		$this->log->set($mensaje_resultado, array(self::reporte_resultados));
		$this->log->guardar();
	}
	
	//------------------------------------------------------------------------
	//-- Generación de Acta
	//------------------------------------------------------------------------

	/**
	 * Una vez recuperado el plan versión destino, si existe, se modifica el registro del alumno con el cambio de plan
	 * versión, como así también el hístórico de planes correspondiente.
	 */
	function cambiar_alumno_plan($alumno, $plan_version, $motivo, $tipo)
	{
		try {
			toba::db()->abrir_transaccion();

			guarani::act('act_alumnos')->actualizar_plan_alumno($alumno, $plan_version);
			guarani::act('act_alumnos')->insertar_cambio_plan($alumno, $plan_version, $motivo , 'now()', $tipo, $this->documento, $this->observaciones);

			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}
	
	/**
	 * Obtiene la cantidad de equivalencias otorgadas para los alumnos recientemente procesados
	 */
	function obtener_equivalencias_otorgadas($alumnos, $hacia, $por_alumno=true)
	{
		$filtro_alumnos = guarani_sql::armar_condicion_in($alumnos, 'sga_alumnos', 'alumno', 'alumno');
		if ($hacia == alumno::dest_plan_anterior || $hacia == alumno::dest_planes_activos_vigentes) {
			// Cambio de Plan
			$filtro_origen = "sga_equiv_tramite.origen = '" . equivalencia::origen_int_plan . "'";
		} else {
			// Cambio de Versión
			$filtro_origen = "sga_equiv_tramite.origen = '" . equivalencia::origen_int_version . "'";
		}
		$filtro = !empty($filtro_alumnos) ? $filtro_origen : $filtro_alumnos . ' AND ' . $filtro_origen;
		
		$filtro_fecha = "sga_equiv_tramite.fecha_generacion = " . guarani_sql::fecha_actual;
		$filtro .= ' AND ' . $filtro_fecha;
		
		if ($por_alumno) {
			return toba::consulta_php('co_equivalencias')->get_equivalencias_automaticas_otorgadas_por_alumno($filtro);
		} else {
			return toba::consulta_php('co_equivalencias')->get_equivalencias_automaticas_otorgadas_por_actividad($filtro);
		}
	}
}
?>