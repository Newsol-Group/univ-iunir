<?php
/**
 *
 *  
 * @package 	Cursadas
 * @subpackage 	Inscripciones
 * 
 */
class pro_reprocesar_inscripciones_actividades extends guarani_procesos_bk
{
	protected $inscripciones;
	
	// Código interno de la operación que invocó el proceso (necesario para recuperar los controles de cada punto).
	protected $operacion;

	const reporte_modificadas = 1;
	const reporte_a_rechazar = 2;
	const reporte_resultados = 4;
	
	const nombre = 'pro_reprocesar_inscripciones_actividades';

	function set_datos($datos = array())
	{
		$this->inscripciones = $datos['inscripciones'];
		$this->operacion = $datos['operacion'];
	}

	function procesar()
	{
		$paso = 100 / guarani_arreglos::count($this->inscripciones);

		$cant_modificadas = 0;
		$cant_a_rechazar = 0;

		$filtro = guarani_sql::armar_condicion_in($this->inscripciones, 'sga_insc_cursada', 'inscripcion');
		$datos_inscripciones = guarani::co('co_inscripciones_cursadas')->get_listado($filtro);
		$notificar_estado_inscripcion = (guarani::parametros()->get_valor(inscripcion_cursada::cur_notificar_estado_inscripcion) == comunes::si);

		foreach ($datos_inscripciones as $clave => $inscripcion) {
			$estado = $this->get_estado();
			if ((isset($estado['abortar'])) && ($estado['abortar'])) {
				$this->abortar = true;
				break;
			}
			try {
				toba::db()->abrir_transaccion();

				$retorno = $this->validar_puntos_de_control_alumno($inscripcion);
				$mensajes = $retorno['mensajes'];
				$requisitos = $retorno['requisitos'];
				$retorno = $retorno['retorno'];
				
				$instancia_a_agregar = null;
				$instancia_a_rechazar = null;
				if ($retorno != guarani_punto_de_control::rechazado) {
					$instancias = guarani::co('co_comisiones')->get_instancias_comision($inscripcion['id_comision']);
					$instancia_mandatoria = guarani::parametros()->get_valor('cur_instancia_mandatoria', array($inscripcion['propuesta']));
					$todas_las_instancias_rechazadas = true;
					foreach ($instancias as $instancia) {
						$retorno_instancia = $this->validar_puntos_de_control_instancia($inscripcion, $instancia['instancia']);
						if ($retorno_instancia['retorno'] == guarani_punto_de_control::advertido) {
							$retorno = $retorno_instancia['retorno'];
						}
						$requisitos = empty($requisitos) ? $retorno_instancia['requisitos'] : "$requisitos||{$retorno_instancia['requisitos']}";
						$mensajes = empty($mensajes) ? $retorno_instancia['mensajes'] : "$mensajes||{$retorno_instancia['mensajes']}";

						if ($retorno_instancia['retorno'] == guarani_punto_de_control::autorizado ||
							$retorno_instancia['retorno'] == guarani_punto_de_control::advertido) {
							$todas_las_instancias_rechazadas = false;
							// Hay que asociar una instancia de la inscripción?
							if (!toba::consulta_php('co_inscripciones_cursadas')->existe_instancia_para_inscripcion($instancia['instancia'], $inscripcion['inscripcion'])) {
								$instancia_a_agregar = $instancia['instancia'];
							}
						} else {
							// Rechazar la instancia mandatoria implica rechazar la inscripción completa.
							if ($instancia['instancia'] == $instancia_mandatoria) {
								$todas_las_instancias_rechazadas = true;
								break;
							}
							// Hay que desasociar una instancia de la inscripción?
							if (toba::consulta_php('co_inscripciones_cursadas')->existe_instancia_para_inscripcion($instancia['instancia'], $inscripcion['inscripcion'])) {
								$instancia_a_rechazar = $instancia['instancia'];
							}
						}
					}
					if ($todas_las_instancias_rechazadas) {
						$retorno = guarani_punto_de_control::rechazado;
					}
				}

				if ($retorno == guarani_punto_de_control::rechazado) {
					$inscripcion['observaciones'] = $mensajes;
					$inscripcion['requisitos'] = $requisitos;
					$this->log->set($inscripcion, array(self::reporte_a_rechazar));
					$cant_a_rechazar++;
				} else {
					$inscripcion_modificada = false;
					if (!empty($instancia_a_agregar)) {
						guarani::act('act_inscripcion_cursadas')->insertar_insc_cursada_instancia($inscripcion['inscripcion'], $instancia_a_agregar);
						$inscripcion['instancias'] = toba::consulta_php('co_inscripciones_cursadas')->get_instancias_nombre_inscripcion($inscripcion['inscripcion']);
						$inscripcion_modificada = true;
					}
					if (!empty($instancia_a_rechazar)) {
						guarani::act('act_inscripcion_cursadas')->eliminar_insc_cursada_instancia($inscripcion['inscripcion'], $instancia_a_rechazar);
						$inscripcion['instancias'] = toba::consulta_php('co_inscripciones_cursadas')->get_instancias_nombre_inscripcion($inscripcion['inscripcion']);
						$inscripcion_modificada = true;
					}
					if ($retorno != $inscripcion['estado']) {
						guarani::act('act_inscripcion_cursadas')->cambiar_estado_inscripcion($inscripcion['inscripcion'], $retorno);
						$inscripcion['estado'] = $retorno;
						$inscripcion['estado_inscr'] = inscripcion_cursada::get_estado_descripcion($retorno);
						$inscripcion_modificada = true;
						if ($notificar_estado_inscripcion) {
							$parametros['estado'] = $inscripcion['estado'];
							$parametros['nombre_actividad'] = $inscripcion['actividad'];
							$parametros['nombre_comision'] = $inscripcion['comision'];
							$parametros['persona'] = $inscripcion['persona'];
							$parametros['email'] = toba::consulta_php('co_personas')->get_emails_personas( Array($inscripcion['persona']) ); 
							$datos_alumno = toba::consulta_php('co_alumnos')->get_datos_alumno($inscripcion['alumno']); 
							$parametros['nombre_alumno'] = $datos_alumno[0]['nombres']; 
							guarani::notificador()->enviar(notificador::inscribir_a_actividad, $parametros);
						}
					}
					if ($inscripcion_modificada) {
						$this->log->set($inscripcion, array(self::reporte_modificadas));
						$cant_modificadas++;
					}
				}

				toba::db()->cerrar_transaccion();
			} catch (toba_error $e) {
				toba::db()->abortar_transaccion();
				$this->log_estado->set_error();
				throw $e;
			}
			$this->log->guardar();
			$this->log_estado->incrementar_progreso($paso);
		}

		$mensaje_resultado['modificadas'] = $cant_modificadas;
		$mensaje_resultado['a_rechazar'] = $cant_a_rechazar;
		$this->log->set($mensaje_resultado, array(self::reporte_resultados));
		$this->log->guardar();
	}

	function validar_puntos_de_control_alumno($datos_inscripcion)
	{
		toba::solicitud()->set_datos_item(array('item_parametro_a' => $this->operacion));
		$params = array(
			'fecha' => guarani_fecha::get_hoy(),
			'persona' => $datos_inscripcion['persona'],
			'alumno' => $datos_inscripcion['alumno'],
			'plan_version' => $datos_inscripcion['plan_version'],
			'elemento' => $datos_inscripcion['elemento'],
			'comision' => $datos_inscripcion['id_comision'],
			'propuesta' => $datos_inscripcion['propuesta']
		);
		$ptos_control_a_validar = array(
			guarani_punto_de_control::alumno_entidad,
			guarani_punto_de_control::persona_operacion,
			guarani_punto_de_control::alumno_operacion,
			guarani_punto_de_control::alumno_actividad,
			guarani_punto_de_control::alumno_comision,
			guarani_punto_de_control::persona_propuesta
		);
		$retorno = guarani::validador_puntos_de_control($ptos_control_a_validar, $params)->controlar();
		$mensajes = guarani::validador_puntos_de_control()->get_mensajes(comunes::interfaz_gestion);

		$subcomisiones = toba::consulta_php('co_inscripciones_cursadas')->get_subcomisiones_por_inscripcion($datos_inscripcion['inscripcion']);
		if (!empty($subcomisiones)) {
			foreach ($subcomisiones as $subcomision) {
				$params['subcomision'] = $subcomision['subcomision'];
				$retorno_subcomision = guarani::validador_puntos_de_control(guarani_punto_de_control::alumno_subcomision, $params)->controlar();
				if ($retorno == guarani_punto_de_control::autorizado ||
					$retorno == guarani_punto_de_control::advertido && $retorno_subcomision == guarani_punto_de_control::rechazado) {
					$retorno = $retorno_subcomision;
				}
				$mensajes[] = current(guarani::validador_puntos_de_control()->get_mensajes(comunes::interfaz_gestion));
			}
		}

		$requisitos = implode('||', aplanar_matriz($mensajes, 'requisito'));
		$mensajes = implode('||', aplanar_matriz($mensajes, 'mensaje'));
		return array('retorno' => $retorno, 'mensajes' => $mensajes, 'requisitos' => $requisitos);
	}

	function validar_puntos_de_control_instancia($datos_inscripcion, $instancia)
	{
		$ptos_control_a_validar = toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($instancia);
		$params = array(
			'alumno' => $datos_inscripcion['alumno'],
			'elemento' => $datos_inscripcion['elemento'],
			'fecha' => guarani_fecha::get_hoy(),
			'plan_version' => $datos_inscripcion['plan_version']
		);
		$retorno = guarani::validador_puntos_de_control($ptos_control_a_validar, $params)->controlar();
		$mensajes = guarani::validador_puntos_de_control()->get_mensajes(comunes::interfaz_gestion);

		$requisitos = implode('||', aplanar_matriz($mensajes, 'requisito'));
		$mensajes = implode('||', aplanar_matriz($mensajes, 'mensaje'));
		
		return array('retorno' => $retorno, 'mensajes' => $mensajes, 'requisitos'=>$requisitos);
	}

}
?>