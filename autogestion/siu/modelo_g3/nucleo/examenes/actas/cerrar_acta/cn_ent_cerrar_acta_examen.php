<?php
/**
 *
 *
 * @package Exámenes
 * @subpackage Actas
 * 
 */
class cn_ent_cerrar_acta_examen extends cn_ent_acta_examen_basica
{
	protected $s__resultados_pto_control;

	function get_listado($filtro)
	{
		// Se listan los Llamados de Mesas de Examen que estén en Estado Activo (no Suspendido).
		$where = "vw_mesas_examen.llamado_mesa_estado = '" . mesa_de_examen::mesa_estado_activo . "'";
		$filtro = !empty($filtro) ? $filtro . ' AND ' . $where : $where;
		return toba::consulta_php('co_actas')->get_listado_actas_examen_con_sin_mesas($filtro);
	}

	function get_alumnos_acta_examen($id_acta = null, $estado = null)
	{
		$params_sistema = array('exa_orden_detalle_actas' => guarani::parametros()->get_valor('exa_orden_detalle_actas'));
		return toba::consulta_php('co_actas')->get_alumnos_acta_examen($id_acta, $estado, $params_sistema);
	}

	function cerrar_acta($id_acta, $mesa, $llamado_mesa, $alumnos, $libro_tomo = null)
	{
		$cierre_ok = false;
		$datos_acta = toba::consulta_php('co_actas')->get_datos_basicos_acta_examen($id_acta);	
		$utiliza_acta_digital = ($datos_acta['digital'] == comunes::si);
		$autoridad_firma_acta_digital = guarani::parametros()->get_valor('autoridad_firma_acta_digital');
		$ras = toba::consulta_php('co_mesas_de_examen')->get_ras_mesa_examen($mesa);

		/**
		 * @see https://redmine.siu.edu.ar/redmine/issues/30921 Ticket #30921
		 */
		if ($utiliza_acta_digital) {
			comunes::verificar_jasper_corriendo();
			arai_documentos::validar_cuenta_usuario_origen_arai_documentos();
			acta::validar_autorizantes_asociados_acta($id_acta, $autoridad_firma_acta_digital, $ras);
		}

		// Se obtienen los datos de la mesa
		$datos_mesa = toba::consulta_php('co_mesas_de_examen')->get_datos_mesa($mesa);
		$elemento = $datos_mesa['elemento'];

		// Se validan los datos del acta
		$this->validar_datos_acta($llamado_mesa, $alumnos, $datos_acta);
		$this->validar_puntos_de_control_alumnos($datos_mesa, $llamado_mesa, $alumnos, $datos_acta);
                
		try {
			//Se abre la transacción
			toba::db()->abrir_transaccion();

			// Si corresponde, se realiza la asignación de folios físicos
			if (!$utiliza_acta_digital) {
				$forma_asignacion_folio_fisico = guarani::parametros()->get_valor('exa_forma_asignacion_folio_fisico');
				if ($forma_asignacion_folio_fisico == acta::forma_asignacion_folio_fisico_cierre) {
					$carga_manual_libro = guarani::parametros()->get_valor('exa_carga_manual_libro_examen');
					$agrega_folios_fisicos_extra = guarani::parametros()->get_valor('exa_agrega_folios_fisicos_extra');
					$mensaje_error = acta::asignar_folio_fisico($datos_acta, guarani_arreglos::count($alumnos), $forma_asignacion_folio_fisico, $carga_manual_libro, $agrega_folios_fisicos_extra, $libro_tomo);
					if (!empty($mensaje_error)) {
						throw guarani::error(guarani_mensajes::get($mensaje_error));
					}
				}
			}

			/**
			 * Si se modificaron al Cargar Notas las Instancias de los detalles de Alumnos del Acta, agregando 
			 * alguna instancia asociada a la Mesa de Examen, que aún no se encuentra asociada al Acta, la misma 
			 * debe agregarse en sga_actas_instancias.
			 */
			if ($datos_acta['tipo_acta'] == acta::tipo_rectificativa) {
				$inst = toba::consulta_php('co_actas')->get_instancias_acta($id_acta);
				$acta_instancias = aplanar_matriz($inst, 'instancia');
			}
			foreach ($alumnos as $alumno) {
				// La fecha de vigencia sólo se completa para alumnos APROBADOS.
				if ($alumno['resultado'] == escalas_notas::resultado_aprobado) {
					// Se obtiene la fecha de vigencia del examen para el alumno (para casos de examenes con fecha de fin de vigencia)
					$alumno['fecha_vigencia'] = toba::consulta_php('co_actas')->get_fin_vigencia_examen($llamado_mesa, $alumno['fecha']);
				}
				// El plan-versión es 'sga_eval_detalle_examenes.plan_version'.
				$alumno['plan_version'] = $alumno['detalle_plan_version'];

				//Se realizan todas las actualizaciones en las tablas
				guarani::act('act_actas')->eliminar_eval_detalle_examen($alumno['evaluacion'], $alumno['alumno']);
				guarani::act('act_actas')->insertar_acta_detalle_examen($alumno);
				if ($datos_acta['tipo_acta'] == acta::tipo_rectificativa && !empty($datos_acta['acta_referencia'])) {
					guarani::act('act_actas')->rectificar_detalle($alumno['alumno'], $datos_acta['acta_referencia']);
					if (!in_array($alumno['instancia'], $acta_instancias)) {
						guarani::act('act_actas')->insertar_acta_instancia($id_acta, $alumno['instancia'], $alumno['escala_nota']);
					}
				}
				
				// ---------------------------------------------------------------------------------------------------------------------
				// Si es un acta de examen de reválida de cursada y el alumno aprobó el examen, actualizo la fecha de fin de vigencia
				// ----------------------------------------------------------------------------------------------------------------------
				if (($alumno['resultado'] == escalas_notas::resultado_aprobado) && ($alumno['instancia'] == comision::inst_exam_revalida)) {
					
					// Obtengo la ultima cursada/equiv de regularidad aprobada vencida y voy actualizar la fecha de vigencia de la misma					
					$regularidad_vencida_a_revalidar = toba::consulta_php('co_alumnos')->get_id_ultima_regularidad_vencida($alumno['alumno'], $elemento, $datos_acta['fecha'], false);
					
					if (!empty($regularidad_vencida_a_revalidar)) {
						$meses = 12; // Valor por defecto
						$fecha_vigencia_vencida = $regularidad_vencida_a_revalidar['fecha_vigencia'];
						
						// origen: C = Equivalencia de Regularidad /  R = Acta de Cursada
						$origen_regularidad = $regularidad_vencida_a_revalidar['origen']; 

						// Cantidad de meses que extiendo la vigencia. Es la misma si revalido una cursada o una equivalencia de regularidad
						// ya que esta es interna que corresponde a una cursada
						$meses = guarani::parametros()->get_valor('exa_cant_meses_vigencia_regularidad_revalida');
						
						// ---------------------------------------------------------------------------------------------
						// Tengo que verificar si la actividad en el plan de estudios posee una excepcion de vigencia de revalida.
						// Si posee tengo que tomar esos meses como vigencia de regularidad
						// ---------------------------------------------------------------------------------------------
						$filtro = " sga_excep_regularidad.elemento = {$datos_mesa['elemento']} AND
									sga_excep_regularidad_planes.plan = {$alumno['plan_inscripcion']}";
						$excep_vigencia = current(guarani::co('co_elementos')->get_planes_excepcion_regularidad($filtro));						
						if (!empty($excep_vigencia) && isset($excep_vigencia['vigencia_revalida'])) {
							$meses = $excep_vigencia['vigencia_revalida'];
						}

						// Nueva fecha de vigencia a partir de la fecha del examen de reválida o la fecha de vigencia vencida y los meses a agregar.
						$origen_vigencia = guarani::parametros()->get_valor('exa_origen_calculo_vigencia_revalida_regularidad');
						$fecha_desde = ($origen_vigencia == 'Examen') ? $datos_acta['fecha'] : $fecha_vigencia_vencida;
						$nueva_fecha_vigencia = guarani_fecha::get_fecha_desplazada($fecha_desde, $meses, 'mes');

						// ----------------------------------------------------------------------------------------------------------------------
						// 1. Actualizo la fecha de vigencia de la regularidad que se esta revalidando (acta de cursada o equiv de regularidad)
						// 2. Se genera un nuevo registro en la tabla de historial de cambios de vencimiento de regularidad  (Tabla sga_regularidades_venc) 
						//    El acta de examen de revalida se registra en "id_acta_revalida", el acta de cursada revalidado en "id_acta" o la equivalencia 
						//    revalidada en "equivalencia", ademas la fecha de fin de vigencia vencida y la nueva fecha de fin de vigencia
						// ----------------------------------------------------------------------------------------------------------------------
						$formateo = new toba_formateo('html');
						$fecha = $formateo->formato_fecha($alumno['fecha']);
						$observaciones = 'Se cambia la fecha de vencimiento de regularidad por haber aprobado un exámen de reválida con fecha ' . $fecha ;
						if ($origen_regularidad == acta::origen_regulares) {
							// Acta de regularidad
							guarani::act('act_actas')->actualizar_fecha_vigencia_detalle($regularidad_vencida_a_revalidar['id_acta'], $regularidad_vencida_a_revalidar['alumno2'], $nueva_fecha_vigencia);
							guarani::act('act_vencimientos_regularidad')->insertar_modificatoria($regularidad_vencida_a_revalidar['id_acta'], $regularidad_vencida_a_revalidar['alumno2'], null, null, $fecha_vigencia_vencida, $nueva_fecha_vigencia, $observaciones, $id_acta);
						} else {
							// Equivalencia de regularidad
							guarani::act('act_equivalencias')->actualizar_fecha_vigencia_equivalencia($regularidad_vencida_a_revalidar['equivalencia'], $nueva_fecha_vigencia);
							guarani::act('act_vencimientos_regularidad')->insertar_modificatoria(null, $regularidad_vencida_a_revalidar['alumno2'], $regularidad_vencida_a_revalidar['equivalencia'], null, $fecha_vigencia_vencida, $nueva_fecha_vigencia, $observaciones, $id_acta);
						}	
					}
				} // ---- Fin Acta de Revalida de Cursada -----
			} // ---- Fin Alumnos del acta ----

			guarani::act('act_actas')->cerrar_acta_examen($id_acta);

			/** El vencimiento de regularidades para los alumnos que han desaprobado, debe verificarse y actualizarse luego
			 *  de haber cerrado el acta, ya que la función f_examenes_vencimiento_regularidad_alumno consulta las vistas
			 *  de regularidades y la cantidad de veces que se rindió el examen, considerando actas ya cerradas.
			 *  Los parámetros de vencer regularidad y cantidad de veces para rendir examen, deben consultarse dentro de un
			 *  foreach por alumno, ya que se definen a nivel de plan versión.
			 */
			foreach ($alumnos as $alumno) {
				if (($alumno['resultado'] == escalas_notas::resultado_reprobado) && ($alumno['instancia'] == comision::inst_exam_regular)) {
					// Si el alumno está reprobado y está inscripto como regular, se verifica si corresponde,
					// a partir de la parametrización, ejecutar el proceso de vencimiento de regularidad en 
					// función de la cantidad de veces que haya desaprobado
					$atributos = array($alumno['plan_inscripcion']);
					$vencer_regularidad = guarani::parametros()->get_valor('exa_vencer_regularidad', $atributos);

					if ($vencer_regularidad == comunes::si) {
						$cant_veces_rendir_examen_regular = guarani::parametros()->get_valor('exa_cant_veces_rendir_examen_regular', $atributos);
						$cant_veces_rendir_examen_regular_equiv = guarani::parametros()->get_valor('exa_cant_veces_rendir_examen_regular_equiv', $atributos);
						$fecha_detalle = $alumno['fecha'];
						guarani::act('act_actas')->vencer_regularidades($id_acta, $elemento, $alumno['alumno'], $fecha_detalle, $cant_veces_rendir_examen_regular, $cant_veces_rendir_examen_regular_equiv);
					}
				}
			}

			if (!$utiliza_acta_digital && $forma_asignacion_folio_fisico == acta::forma_asignacion_folio_fisico_manual) {
				guarani::act('act_actas')->generar_foliado_fisico($id_acta);
			}
            
            $parametros = [];
            $parametros['id_acta'] = $id_acta;
            guarani::notificador()->enviar(notificador::cerrar_acta_examenes, $parametros, false);
                        
			//Se cierra la transacción
			toba::db()->cerrar_transaccion();
			$cierre_ok = true;
		} catch (toba_error_db $e) {
			$cierre_ok = false;
			toba::db()->abortar_transaccion();
			if ($e->get_sqlstate() == 'db_23505') {
				$mensaje = guarani_mensajes::get('800_cerrar_acta_error_ff');
				throw guarani::error($mensaje);
			} else {
				throw $e;
			}
		} catch (toba_error $e) {
			$cierre_ok = false;
			toba::db()->abortar_transaccion();
			throw $e;
		}
		
		if ($cierre_ok) {
			$parametros = [];
			$parametros['utiliza_acta_digital'] = $datos_acta['digital'];
			$parametros['docentes_nivel_autorizacion'] = guarani::parametros()->get_valor('docentes_nivel_autorizacion');
			$parametros['autoridad_firma_acta_digital'] = $autoridad_firma_acta_digital;
			$parametros['exa_actas_examen_cerradas_cnt_ejemplares'] = guarani::parametros()->get_valor('exa_actas_examen_cerradas_cnt_ejemplares');
			$parametros['exa_actas_examen_abiertas_cnt_ejemplares'] = guarani::parametros()->get_valor('exa_actas_examen_abiertas_cnt_ejemplares');
			$parametros['exa_orden_detalle_actas'] = guarani::parametros()->get_valor('exa_orden_detalle_actas');
		   /**
			* Envía un acta a SIU-Araí Documentos.
			* @see https://redmine.siu.edu.ar/redmine/issues/30921 Ticket #30921
			*/
			acta::enviar_acta_arai_documentos($id_acta, $parametros, $ras);
		}
	}

	function validar_datos_acta($llamado_mesa, $alumnos, $datos_acta)
	{
		$this->validar_acta_vacia($alumnos);
		$this->validar_campos_completados($alumnos);
		$this->validar_notas_cargadas($alumnos);
		if ($datos_acta['tipo_acta'] == acta::tipo_rectificativa) {
			$detalles_acta_original = toba::consulta_php('co_actas')->get_detalles_ultima_rectificacion_acta($datos_acta['acta_referencia']);
			$this->validar_cambios_rectificativa($alumnos, $detalles_acta_original);
		}
		$this->validar_inscripciones_pendientes($llamado_mesa);
		$this->validar_alumnos_acta($llamado_mesa);
		$this->validar_acta_impresa($datos_acta);		
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	/**
	 * Valida que el acta tenga al menos un registro
	 */
	function validar_acta_vacia($datos)
	{
		if (guarani_arreglos::count($datos) == 0) {
			$this->set_error('800_acta_vacia');
		}
	}

	/**
	 * Verifica que todos los campos de los alumnos asociados al acta hayan sido completados: Fecha y Resultado.
	 */
	function validar_campos_completados($alumnos)
	{
		foreach ($alumnos as $alumno) {
			if (empty($alumno['fecha']) || empty($alumno['resultado'])) {
				$this->set_error('800_acta_incompleta');
				break;
			}
		}
	}

	/**
	 * Verifica que se hayan cargado las notas en los detalles del acta.
	 */
	function validar_notas_cargadas($alumnos)
	{
		$resultado_aprobado = escalas_notas::resultado_aprobado;
		$resultado_reprobado = escalas_notas::resultado_reprobado;

		foreach ($alumnos as $alumno) {
			if (($alumno['resultado'] == $resultado_aprobado || $alumno['resultado'] == $resultado_reprobado) && !isset($alumno['nota'])) {
				$this->set_error('800_acta_sin_notas');
				break;
			}
		}
	}

	/**
	 * Valida que el acta haya sido impresa por lo menos en una ocasión
	 */
	function validar_acta_impresa($datos_acta)
	{
		if ($datos_acta['digital'] == comunes::no && $datos_acta['version'] != $datos_acta['version_impresa']) {
			$this->set_error('800_imprimir_ultima_version_acta');
		}
	}

	/**
	 * Valida que la mesa no tenga inscripciones en estado pendiente.
	 */
	function validar_inscripciones_pendientes($llamado_mesa)
	{
		$multiples_actas_examen = guarani::parametros()->get_valor('exa_multiples_actas_examen');

		if ($multiples_actas_examen == comunes::no) {
			$filtro = " sga_insc_examen.estado = '" . inscripcion_examen::estado_pendiente . "'";
			$cant_inscripciones_pendientes = toba::consulta_php('co_inscripciones_examenes')->get_cant_inscripciones_para_un_llamado_mesa($llamado_mesa, $filtro);

			if ($cant_inscripciones_pendientes > 0) {
				$this->set_error('800_inscripciones_pendientes');
			}
		}
	}

	/**
	 * Valida que todos los alumnos inscriptos a la mesa estén incluidos en el acta
	 * cuando solo se usa un acta por mesa de examen.
	 * @param type $llamado_mesa
	 */
	function validar_alumnos_acta($llamado_mesa)
	{
		$multiples_actas_examen = guarani::parametros()->get_valor('exa_multiples_actas_examen');
		if ($multiples_actas_examen == comunes::no) {			
			$cant = guarani_arreglos::count(guarani::co('co_actas')->get_alumnos_una_mesa_examen_no_estan_en_acta($llamado_mesa));
			if ($cant > 0) {
				$this->set_error('800_alumnos_acta');
			}
		}
	}
	
	/**
	 * Si el acta cerrada es de tipo rectificativa se verifica que cada alumno tenga al menos un dato modificado 
	 * respecto del acta original, o la última rectificación de la misma (instancia, nota, resultado ó fecha).
	 */
	function validar_cambios_rectificativa($alumnos, $detalles_acta_original)
	{
		$valido = true;
		foreach ($alumnos as $alumno) {
			$detalle_original = $this->get_detalle_original_alumno($detalles_acta_original, $alumno);
			//Si existe un detalle anterior para el alumno lo compara con el rectificado.
			if (!empty($detalle_original)) {
				if (($detalle_original['nota'] == $alumno['nota']) && ($detalle_original['resultado'] == $alumno['resultado']) && ($detalle_original['fecha'] == $alumno['fecha']) && ($detalle_original['instancia'] == $alumno['instancia'])) {
					$valido = false;
					break;
				}
			}
		}

		if (!$valido) {
			$this->set_error('800_rectif_sin_modif');
		}
	}

	/**
	 * Ejecuta los controles configurables al conjunto de alumnos.
	 */
	function validar_puntos_de_control_alumnos($datos_mesa, $llamado_mesa, $alumnos, $datos_acta)
	{
		$puntos_de_control = [
			guarani_punto_de_control::alumno_entidad,
			guarani_punto_de_control::persona_operacion,
			guarani_punto_de_control::alumno_operacion,
			guarani_punto_de_control::elegir_actividad_examen,
			guarani_punto_de_control::alumno_mesa_examen,
			guarani_punto_de_control::persona_propuesta,
			guarani_punto_de_control::cerrar_acta_examen
		];
		
		$puntos_de_control_instancia = toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancias($alumnos);
		
		foreach ($alumnos as $alumno) {
			// No corre controles sobre alumnos ausentes
			if ($alumno['resultado'] != escalas_notas::resultado_ausente) {
				$parametros = [
					'fecha' => guarani_fecha::get_hoy(),
					'persona' => $alumno['id_persona'],
					'alumno' => $alumno['alumno'],
					'plan_version' => $alumno['plan_version'],
					'elemento' => $datos_mesa['elemento'],
					'llamado_mesa' => $llamado_mesa,
					'propuesta' => $alumno['propuesta']
				];
				
				if ($datos_acta['tipo_acta'] == acta::tipo_rectificativa) {
					$parametros['id_acta_original'] = $datos_acta['acta_referencia'];
				}
				
				$ptos_control_a_validar = isset($puntos_de_control_instancia[$alumno['instancia']]) ? array_merge($puntos_de_control, $puntos_de_control_instancia[$alumno['instancia']]) : $puntos_de_control;
				$retorno = guarani::validador_puntos_de_control($ptos_control_a_validar, $parametros)->controlar();
				guarani::validador_puntos_de_control()->notificar();
				if ($retorno == guarani_punto_de_control::rechazado) {
					throw new guarani_error(null, null, guarani_mensajes::get('800_error_control_cierre_acta'));
				}
			}
		}
	}

	// Obtiene del arreglo de detalles originales el correspondiente al alumno dado si es que existe.
	function get_detalle_original_alumno($detalles, $alumno)
	{
		foreach ($detalles as $detalle_original) {
			if ($detalle_original['alumno'] == $alumno['alumno']) {
				return $detalle_original;
			}
		}
		return array();
	}

}