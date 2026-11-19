<?php
namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\VistaJasperreports;
use SIU\Chulupi\util\encoding;
use SIU\Chulupi\ErrorKernelDb;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\parametro;
use siu\errores\error_guarani;
use siu\modelo\entidades\rs_consulta;
use siu\modelo_g3\arai_documentos;
use siu\modelo_g3\acta;
use toba;

class impresion_actas_examen extends transaccion
{
	protected $operacion = '800SIUEXA011';
	protected $cache_request_acta_cabecera = array();
	
	// Manejo de errores
	protected $hay_errores = false;
	protected $errores;
	protected $hay_notificaciones = false;
	protected $notificaciones;

	function iniciar()
	{
		
	}

	//----------------------------------------------------------------------------------------
	//---- Manejo de errores y notificaciones ------------------------------------------------
	//----------------------------------------------------------------------------------------	
	
	/**
	 * Acumulación de mensajes.
	 *
	 * @param string $descripcion_corta
	 * @param array $params
	 * @param string $tipo
	 */
	function set_error($descripcion_corta, $params = array(), $tipo = \comunes::error_critico)
	{
		$this->hay_errores = true;
		$this->errores[] = array('descripcion_corta' => $descripcion_corta, 'params' => $params, 'tipo' => $tipo);
	}

	function set_notificacion($descripcion_corta, $params = null, $tipo = \comunes::error_advertencia)
	{
		$this->hay_notificaciones = true;
		$this->notificaciones[] = array('descripcion_corta' => $descripcion_corta, 'params' => $params, 'tipo' => $tipo);
	}

	function get_errores()
	{
		return $this->errores;
	}

	function get_notificaciones()
	{
		return $this->notificaciones;
	}

	function hay_errores()
	{
		return $this->hay_errores;
	}

	function hay_notificaciones()
	{
		return $this->hay_notificaciones;
	}

	//--------------------------------------------------------------------------
	//---- INFO
	//--------------------------------------------------------------------------

	function info__actas($seleccion)
	{
		$parametros = array('persona' => $this->persona, 'llamado_mesa' => $seleccion['llamado_mesa']);
		$rs = new rs_consulta('impresion_actas_examen', 'lista_actas_docente', $parametros);
		$rs->set_columna_id('id_acta');
		$rs->agregar_entidad('llamado_mesa');
		return $rs;
	}

	function info__actas_instancias($datos)
	{
		$parametros = array('llamado_mesa' => $datos['llamado_mesa']);
		$rs = catalogo::consultar('acta', 'get_actas_llamado_mesa', $parametros);
		return $rs;
	}

	function info__acta_cabecera($datos)
	{
		$parametros = array('acta' => $datos['id_acta']);
		return catalogo::consultar('impresion_actas_examen', 'acta_cabecera', $parametros);
	}

	//--------------------------------------------------------------------------
	//---- CIERRE DE ACTA
	//--------------------------------------------------------------------------

	function evt__cerrar_acta($acta, $libro = null)
	{
		$cierre_ok = false;
		$id_acta = $acta['id_acta'];
		$llamado_mesa = $acta['llamado_mesa'];
		$mesa = $acta['mesa_examen'];

		// Se obtienen los datos del acta
		$datos_acta = \toba::consulta_php('co_actas')->get_datos_basicos_acta_examen($id_acta);

		// Se obtienen los datos de la mesa
		$datos_mesa = \toba::consulta_php('co_mesas_de_examen')->get_datos_mesa($mesa);
		$elemento = $datos_mesa['elemento'];

		// Atributos para la resolución de parámetros
		$propuestas = catalogo::consultar('atributos_params', 'get_propuestas_llamado_mesa', array('llamado_mesa' => $llamado_mesa));
		$ras = catalogo::consultar('atributos_params', 'get_propuestas_ras', array('propuestas' => $propuestas));

		$utiliza_acta_digital = ($datos_acta['digital'] == \comunes::si);
		$autoridad_firma_acta_digital = parametro::get_valor('autoridad_firma_acta_digital', $ras);

                /**
                 * @see https://redmine.siu.edu.ar/redmine/issues/30925 Ticket #30925
                 */
                if ($utiliza_acta_digital) {
                    try {
                        \comunes::verificar_jasper_corriendo();
                        arai_documentos::validar_cuenta_usuario_origen_arai_documentos();
                        acta::validar_autorizantes_asociados_acta($id_acta, $autoridad_firma_acta_digital, $ras);
                    } catch (\Exception $e) {
                        throw new error_guarani($e->getMessage());
                    }
                }
                        
		$params_sistema = array('exa_orden_detalle_actas' => parametro::get_valor('exa_orden_detalle_actas', $ras));
		// Se obtienen los alumnos del acta
		$alumnos = \toba::consulta_php('co_actas')->get_alumnos_acta_examen($id_acta, $datos_acta['estado'], $params_sistema);
		// Se validan los datos del acta
		$params_sistema = array(
			'exa_imprimir_ultima_version_acta_web' => parametro::get_valor('exa_imprimir_ultima_version_acta_web', $propuestas),
			'exa_multiples_actas_examen' => parametro::get_valor('exa_multiples_actas_examen', $propuestas)
		);
		$this->validar_datos_acta($mesa, $alumnos, $datos_acta, $params_sistema);
		$this->validar_puntos_de_control_alumnos($datos_mesa, $llamado_mesa, $alumnos, $datos_acta);

		try {
			//Se abre la transacción
			kernel::db()->abrir_transaccion();

                        /**
                         * Si corresponde, se realiza la asignación de folios físicos
                         * @see https://redmine.siu.edu.ar/redmine/issues/30925 Ticket #30925
                         */
			if (!$utiliza_acta_digital) {
                            $forma_asignacion_folio_fisico = parametro::get_valor('exa_forma_asignacion_folio_fisico', $propuestas);
                            if ($forma_asignacion_folio_fisico == \acta::forma_asignacion_folio_fisico_cierre) {
                                    $carga_manual_libro = parametro::get_valor('exa_carga_manual_libro_examen', $propuestas);
                                    $agrega_folios_fisicos_extra = parametro::get_valor('exa_agrega_folios_fisicos_extra', $propuestas);
                                    $mensaje_error = \acta::asignar_folio_fisico($datos_acta, \guarani_arreglos::count($alumnos), $forma_asignacion_folio_fisico, $carga_manual_libro, $agrega_folios_fisicos_extra);
                                    if (!empty($mensaje_error)) {
                                            throw new error_guarani(kernel::traductor()->trans($mensaje_error));
                                    }
                            }
                        }
			
			// Si se modificaron al Cargar Notas las Instancias de los detalles de Alumnos del Acta, agregando 
			// alguna instancia asociada a la Mesa de Examen, que aún no se encuentra asociada al Acta, la misma 
			// debe agregarse en sga_actas_instancias.
			if ($datos_acta['tipo_acta'] == \acta::tipo_rectificativa) {
				$inst = \toba::consulta_php('co_actas')->get_instancias_acta($id_acta);
				$acta_instancias = aplanar_matriz($inst, 'instancia');
			}
                        
			foreach ($alumnos as $alumno) {     
				// Se obtiene la fecha de vigencia para el alumno
				$alumno['fecha_vigencia'] = \toba::consulta_php('co_actas')->get_fin_vigencia_examen($llamado_mesa, $alumno['fecha']);
				// El plan-versión es 'sga_eval_detalle_examenes.plan_version'.
				$alumno['plan_version'] = $alumno['detalle_plan_version'];

				// Se realizan todas las actualizaciones en las tablas
				\guarani::act('act_actas')->eliminar_eval_detalle_examen($alumno['evaluacion'], $alumno['alumno']);
				\guarani::act('act_actas')->insertar_acta_detalle_examen($alumno);
				if ($datos_acta['tipo_acta'] == \acta::tipo_rectificativa && !empty($datos_acta['acta_referencia'])) {
					\guarani::act('act_actas')->rectificar_detalle($alumno['alumno'], $datos_acta['acta_referencia']);
					if (!in_array($alumno['instancia'], $acta_instancias)) {
						\guarani::act('act_actas')->insertar_acta_instancia($id_acta, $alumno['instancia'], $alumno['escala_nota']);
					}
				}

				if ($alumno['resultado'] == \escalas_notas::resultado_aprobado && $alumno['instancia'] == \comision::inst_exam_revalida) {
					// Se obtiene la última cursada/equivalencia de regularidad aprobada vencida.
					$regularidad_vencida_a_revalidar = \toba::consulta_php('co_alumnos')->get_id_ultima_regularidad_vencida($alumno['alumno'], $elemento, $datos_acta['fecha'], false);
					if (!empty($regularidad_vencida_a_revalidar)) {
						$meses = parametro::get_valor('exa_cant_meses_vigencia_regularidad_revalida', $propuestas);

						// Se verifica si la actividad en el plan de estudios posee una excepcion de vigencia de reválida.
						$filtro = " sga_excep_regularidad.elemento = {$datos_mesa['elemento']} AND
									sga_excep_regularidad_planes.plan = {$alumno['plan_inscripcion']}";
						$excep_vigencia = current(\toba::consulta_php('co_elementos')->get_planes_excepcion_regularidad($filtro));
						if (!empty($excep_vigencia) && isset($excep_vigencia['vigencia_revalida'])) {
							$meses = $excep_vigencia['vigencia_revalida'];						
						}

						// Nueva fecha de vigencia a partir de la fecha del examen de reválida o la fecha de vigencia vencida y los meses a agregar.
						$origen_vigencia = parametro::get_valor('exa_origen_calculo_vigencia_revalida_regularidad', $propuestas);
						$fecha_desde = ($origen_vigencia == 'Examen') ? $datos_acta['fecha'] : $regularidad_vencida_a_revalidar['fecha_vigencia'];
						$nueva_fecha_vigencia = \guarani_fecha::get_fecha_desplazada($fecha_desde, $meses, 'mes');

						// Se actualiza la fecha de vigencia de la regularidad que se está revalidando (acta de cursada o equivalencia de regularidad).
						// Se genera un nuevo registro en el historial de cambios de vencimiento de regularidad (sga_regularidades_venc). 
						$fecha = convertir_fecha_formato_frontend($alumno['fecha']);
						$observaciones = kernel::traductor()->trans('actex.msj_obs_venc_reg') . $fecha;
						if ($regularidad_vencida_a_revalidar['origen'] == \acta::origen_regulares) {
							\guarani::act('act_actas')->actualizar_fecha_vigencia_detalle($regularidad_vencida_a_revalidar['id_acta'], $regularidad_vencida_a_revalidar['alumno2'], $nueva_fecha_vigencia);
							\guarani::act('act_vencimientos_regularidad')->insertar_modificatoria($regularidad_vencida_a_revalidar['id_acta'], $regularidad_vencida_a_revalidar['alumno2'], null, null, $regularidad_vencida_a_revalidar['fecha_vigencia'], $nueva_fecha_vigencia, $observaciones, $id_acta);
						} else {
							\guarani::act('act_equivalencias')->actualizar_fecha_vigencia_equivalencia($regularidad_vencida_a_revalidar['equivalencia'], $nueva_fecha_vigencia);
							\guarani::act('act_vencimientos_regularidad')->insertar_modificatoria(null, $regularidad_vencida_a_revalidar['alumno2'], $regularidad_vencida_a_revalidar['equivalencia'], null, $regularidad_vencida_a_revalidar['fecha_vigencia'], $nueva_fecha_vigencia, $observaciones, $id_acta);
						}
					}
				}
			}

			\guarani::act('act_actas')->cerrar_acta_examen($id_acta);

			// El vencimiento de regularidades para los alumnos que han desaprobado, debe verificarse y actualizarse luego
			// de haber cerrado el acta, ya que la función f_examenes_vencimiento_regularidad_alumno consulta las vistas
			// de regularidades y la cantidad de veces que se rindió el examen, considerando actas ya cerradas.
			// Los parámetros de vencer regularidad y cantidad de veces para rendir examen, deben consultarse dentro de un
			// foreach por alumno, ya que se definen a nivel de plan versión.
			foreach ($alumnos as $alumno) {
				if (($alumno['resultado'] == \escalas_notas::resultado_reprobado) && ($alumno['instancia'] == \comision::inst_exam_regular)) {
					// Si el alumno está reprobado y está inscripto como regular, se verifica si corresponde,
					// a partir de la parametrización, ejecutar el proceso de vencimiento de regularidad en 
					// función de la cantidad de veces que haya desaprobado
					$atributos = array($alumno['plan_inscripcion']);
					$vencer_regularidad = parametro::get_valor('exa_vencer_regularidad', $atributos);
					if ($vencer_regularidad == \comunes::si) {
						$cant_veces_rendir_examen_regular = parametro::get_valor('exa_cant_veces_rendir_examen_regular', $atributos);
						$cant_veces_rendir_examen_regular_equiv = parametro::get_valor('exa_cant_veces_rendir_examen_regular_equiv', $atributos);
						$fecha_detalle = $alumno['fecha'];
						\guarani::act('act_actas')->vencer_regularidades($id_acta, $elemento, $alumno['alumno'], $fecha_detalle, $cant_veces_rendir_examen_regular, $cant_veces_rendir_examen_regular_equiv);
					}
				}
			}

			/* Si no es un acta digital y el parámetro 'exa_imprimir_ultima_version_acta_web' == 'N' 
			 * se debe actualizar el campo sga_actas.cerrada_por_docente = S. 
                         * @see https://redmine.siu.edu.ar/redmine/issues/30925 Ticket #30925
                         */
			$param_exa_imprimir_ultima_version_acta_web = parametro::get_valor('exa_imprimir_ultima_version_acta_web', $propuestas);
			if (!$utiliza_acta_digital && ($param_exa_imprimir_ultima_version_acta_web == \comunes::no)) {
				catalogo::consultar('impresion_actas_examen', 'marcar_cerrada_por_docente', array('id_acta' => $id_acta));
			}
			
                        /**
                         * @see https://redmine.siu.edu.ar/redmine/issues/30925 Ticket #30925
                         */
			if (!$utiliza_acta_digital && $forma_asignacion_folio_fisico == \acta::forma_asignacion_folio_fisico_manual) {
				\guarani::act('act_actas')->generar_foliado_fisico($id_acta);
			}
                        
			// Se cierra la transacción
			kernel::db()->cerrar_transaccion();
			$cierre_ok = true;
		} catch (error_guarani $e) {
			$cierre_ok = false;
			kernel::db()->abortar_transaccion();
			kernel::log()->add_error($e);
			throw $e;
		} catch (\guarani_error $e) {
			$cierre_ok = false;
			kernel::db()->abortar_transaccion();
			kernel::log()->add_error($e);
			throw new error_guarani($e->getMessage());
		} catch (ErrorKernelDb $e) {
			$cierre_ok = false;
			kernel::db()->abortar_transaccion();
			if ($e->get_sqlstate() == 'db_23505') {
				$mensaje = kernel::traductor()->trans('800_cerrar_acta_error_ff');
				throw new error_guarani($mensaje);
			} else {
				throw $e;
			}
		}
		
		if ($cierre_ok) {
			try {
				$parametros = [];
				$parametros['utiliza_acta_digital'] = $datos_acta['digital'];
				$parametros['docentes_nivel_autorizacion'] = parametro::get_valor('docentes_nivel_autorizacion', $ras);
				$parametros['autoridad_firma_acta_digital'] = $autoridad_firma_acta_digital;
				$parametros['exa_actas_examen_cerradas_cnt_ejemplares'] = parametro::get_valor('exa_actas_examen_cerradas_cnt_ejemplares', $propuestas);
				$parametros['exa_actas_examen_abiertas_cnt_ejemplares'] = parametro::get_valor('exa_actas_examen_abiertas_cnt_ejemplares', $propuestas);
				$parametros['exa_orden_detalle_actas'] = parametro::get_valor('exa_orden_detalle_actas', $ras);
			   /**
				* Envía un acta a Araí-Documentos.
				* @see https://redmine.siu.edu.ar/redmine/issues/30925 Ticket #30925
				*/
				acta::enviar_acta_arai_documentos($id_acta, $parametros, $ras);
			} catch (\Exception $e) {
				kernel::log()->add_error($e);
				throw new error_guarani($e->getMessage());
			}
		}
		return $this->get_notificaciones();
	}

	function validar_datos_acta($mesa_examen, $alumnos, $datos_acta, $params_sistema)
	{
		$this->validar_acta_vacia($alumnos);
		$this->validar_campos_completados($alumnos);
		$this->validar_notas_cargadas($alumnos);
		if ($datos_acta['tipo_acta'] == \acta::tipo_rectificativa) {
			$detalles_acta_original = \toba::consulta_php('co_actas')->get_detalles_ultima_rectificacion_acta($datos_acta['acta_referencia']);
			$this->validar_cambios_rectificativa($alumnos, $detalles_acta_original);
		}
		$this->validar_inscripciones_pendientes($datos_acta['llamado_mesa'], $params_sistema['exa_multiples_actas_examen']);
		$this->validar_alumnos_acta($datos_acta['llamado_mesa'], $params_sistema['exa_multiples_actas_examen']);

		/*
                 * @see https://redmine.siu.edu.ar/redmine/issues/30925 Ticket #30925
                 * Se valida que la versión impresa coincida con la ultima versión del acta solo si 
                   no es un acta digital y el parámetro exa_imprimir_ultima_version_acta_web = S
                 */
		if ($datos_acta['digital'] == \comunes::no && $params_sistema['exa_imprimir_ultima_version_acta_web'] == \comunes::si) {
			$this->validar_acta_impresa($datos_acta);
		}

		if ($this->hay_errores()) {
			throw new error_guarani($this->get_mensajes($this->errores));
		}
	}

	function get_mensajes($notificaciones)
	{
		$mensajes_notificacion = array();
		if (!empty($notificaciones)) {
			foreach ($notificaciones as $notificacion) {
				$params = array();
				$i = 1;
				foreach ($notificacion['params'] as $param) {
					$params["%$i%"] = $param;
					$i++;
				}
				$mensajes_notificacion[] = kernel::traductor()->trans($notificacion['descripcion_corta'], $params);
			}
		}
		return implode('\n ', $mensajes_notificacion);
	}

	/**
	 * Valida que el acta tenga al menos un registro
	 */
	function validar_acta_vacia($datos)
	{
		if (\guarani_arreglos::count($datos) == 0) {
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
		$resultado_aprobado = \escalas_notas::resultado_aprobado;
		$resultado_reprobado = \escalas_notas::resultado_reprobado;

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
		if ($datos_acta['version'] != $datos_acta['version_impresa']) {
			$this->set_error('800_imprimir_ultima_version_acta');
		}
	}

	/**
	 * Valida que la mesa no tenga inscripciones en estado pendiente.
	 * @param int $llamado_mesa
	 * @param string $multiples_actas_examen
	 */
	function validar_inscripciones_pendientes($llamado_mesa, $multiples_actas_examen)
	{
		// Si el parámetro 'exa_multiples_actas_examen' es 'NO' valido
		if ($multiples_actas_examen == \comunes::no) {
			$filtro = " sga_insc_examen.estado = '" . \inscripcion_examen::estado_pendiente . "'";
			$cant_inscripciones_pendientes = \toba::consulta_php('co_inscripciones_examenes')->get_cant_inscripciones_para_un_llamado_mesa($llamado_mesa, $filtro);

			if ($cant_inscripciones_pendientes > 0) {
				$this->set_error('800_inscripciones_pendientes');
			}
		}
	}

	/**
	 * Valida que todos los alumnos inscriptos a la mesa están incluidos en el acta
	 * cuando solo se usa un acta por mesa de examen.
	 * @param int $llamado_mesa
	 * @param string $multiples_actas_examen
	 */
	function validar_alumnos_acta($llamado_mesa, $multiples_actas_examen)
	{
		// Si el parámetro 'exa_multiples_actas_examen' es 'NO' valido
		if ($multiples_actas_examen == \comunes::no) {			
			$cant = \guarani_arreglos::count(\toba::consulta_php('co_actas')->get_alumnos_una_mesa_examen_no_estan_en_acta($llamado_mesa));
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
			\guarani_punto_de_control::alumno_entidad,
			\guarani_punto_de_control::persona_operacion,
			\guarani_punto_de_control::alumno_operacion,
			\guarani_punto_de_control::elegir_actividad_examen,
			\guarani_punto_de_control::alumno_mesa_examen,
			\guarani_punto_de_control::persona_propuesta,
			\guarani_punto_de_control::cerrar_acta_examen
		];
		
		$instancias = toba::consulta_php('co_actas')->get_instancias_acta($datos_acta['id_acta']);
		$puntos_de_control_instancia = toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancias($instancias);		

		foreach ($alumnos as $alumno) {
			// No corre controles sobre alumnos ausentes
			if ($alumno['resultado'] != \escalas_notas::resultado_ausente) {
				$parametros = [
					'fecha' => \guarani_fecha::get_hoy(),
					'persona' => $alumno['id_persona'],
					'alumno' => $alumno['alumno'],
					'plan_version' => $alumno['plan_version'],
					'elemento' => $datos_mesa['elemento'],
					'llamado_mesa' => $llamado_mesa,
					'propuesta' => $alumno['propuesta']
				];
				
				if ($datos_acta['tipo_acta'] == \acta::tipo_rectificativa) {
					$parametros['id_acta_original'] = $datos_acta['acta_referencia'];
				}
				
				$puntos_de_control_alumno = isset($puntos_de_control_instancia[$alumno['instancia']]) ? array_merge($puntos_de_control, $puntos_de_control_instancia[$alumno['instancia']]) : $puntos_de_control;
				$proceso = $this->nuevo_proceso_control($puntos_de_control_alumno, $parametros);
				$resultado_controles = $proceso->ejecutar();
				$mensajes_controles = $proceso->get_mensajes();

				if ($resultado_controles != \guarani_punto_de_control::autorizado) {
					$e = new error_guarani(kernel::traductor()->trans('error_ejecutando_controles'));
					$e->add_mensajes_usuario($mensajes_controles);
					throw $e;
				}
			}
		}
	}

	/**
	 * Obtiene del arreglo de detalles originales el correspondiente al alumno dado si es que existe.
	 */
	function get_detalle_original_alumno($detalles, $alumno)
	{
		foreach ($detalles as $detalle_original) {
			if ($detalle_original['alumno'] == $alumno['alumno']) {
				return $detalle_original;
			}
		}
		return array();
	}

	function get_mensaje_error($errores)
	{
		$mensaje_error = '';
		foreach ($errores as $error) {
			$params = array();
			$i = 1;
			foreach ($error['params'] as $param) {
				$params["%$i%"] = $param;
				$i++;
			}
			$mensaje_error .= "\n" . kernel::traductor()->trans($error['descripcion_corta'], $params);
		}
		return $mensaje_error;
	}

	//--------------------------------------------------------------------------
	//---- COMPROBANTE
	//--------------------------------------------------------------------------

	// Si se modifica aquí también modificar la función 'generar_pdf_acta_examen' de la clase 'src/siu/modelo_g3/nucleo/actas/acta_nucleo.php'
	function generar_comprobante($acta)
	{
		$datos_acta = $this->info__acta_cabecera($acta);
		$propuestas = catalogo::consultar('atributos_params', 'get_propuestas_llamado_mesa', array('llamado_mesa' => $datos_acta['llamado_mesa']));
		$ras = catalogo::consultar('atributos_params', 'get_propuestas_ras', array('propuestas' => $propuestas));

		$utiliza_acta_digital = ($datos_acta['digital'] == \comunes::si);
		/**
		 * Descarga desde Araí-Documentos.
		 * @see https://redmine.siu.edu.ar/redmine/issues/30925 Ticket #30925
		 * @see https://redmine.siu.edu.ar/redmine/issues/32523 Ticket #32523
		 */
		if ($utiliza_acta_digital) { 
			$fallo = false;
			$msg_error = "";

			switch ($datos_acta['estado_documento_digital']) {
				// Si falló la creación del documento en SIU-Araí.
				case \arai_documentos::ESTADO_DOCUMENTO_DIGITAL_FALLO:
					$fallo = true;
					$msg_error = kernel::traductor()->trans('impresion_actas.fallo_creacion');
					break;
				// Si aún no se creo el documento en SIU-Araí.
				case \arai_documentos::ESTADO_DOCUMENTO_DIGITAL_PENDIENTE:
					$fallo = true;
					$msg_error = kernel::traductor()->trans('impresion_actas.pendiente_creacion');
					break;
				// Si la creación del documento en SIU-Araí fue exitosa (incluye autorizados).
				case \arai_documentos::ESTADO_DOCUMENTO_DIGITAL_EXITO:
				case \arai_documentos::ESTADO_DOCUMENTO_DIGITAL_AUTORIZADO:
					// Si no hay documento de SIU-Araí asociado
					if (is_null($datos_acta['id_documento_digital'])) {
						$fallo = true;
						$msg_error = kernel::traductor()->trans('impresion_actas.documento_no_asociado');
					} else {
						return ['path' => arai_documentos::descargarDocumentoToFilesystem($datos_acta['id_documento_digital']), 'nombre_descarga' => 'archivo.pdf'];
					}
					break;
			}

			if ($fallo) {
				throw new \Exception($msg_error);
			}
		}

		$params_sistema =  [ 'exa_orden_detalle_actas' => parametro::get_valor('exa_orden_detalle_actas', $ras),
                                    'nombre_persona_doc_oficiales' => parametro::get_valor('nombre_persona_doc_oficiales')];
		$datos_alumnos = \toba::consulta_php('co_actas')->get_alumnos_acta_examen($datos_acta['id_acta'], $datos_acta['estado'], $params_sistema);

		/**
		 * @see https://redmine.siu.edu.ar/redmine/issues/30925 Ticket #30925
		 */
		$param_exa_imprimir_ultima_version_acta_web = parametro::get_valor('exa_imprimir_ultima_version_acta_web', $propuestas);
		if (!$utiliza_acta_digital && ($param_exa_imprimir_ultima_version_acta_web == \comunes::si)) {
			// Si se imprime un acta abierta y el parámtro anterior está establecido en "S"
			// se debe actualizar la versión impresa
			if ($datos_acta['estado'] == \acta::estado_abierta && $datos_acta['version'] != $datos_acta['version_impresa']) {
				try {
					kernel::db()->abrir_transaccion();
					$forma_asignacion_folio_fisico = parametro::get_valor('exa_forma_asignacion_folio_fisico', $propuestas);
					// Si corresponde, se realiza la asignación de folios físicos.
					if (!$utiliza_acta_digital && ($forma_asignacion_folio_fisico == \acta::forma_asignacion_folio_fisico_impresion)) {
						$carga_manual_libro = parametro::get_valor('exa_carga_manual_libro_examen', $propuestas);
						$agrega_folios_fisicos_extra = parametro::get_valor('exa_agrega_folios_fisicos_extra', $propuestas);
						$mensaje_error = \acta::asignar_folio_fisico($datos_acta, \guarani_arreglos::count($datos_alumnos), $forma_asignacion_folio_fisico, $carga_manual_libro, $agrega_folios_fisicos_extra);
						if (!empty($mensaje_error)) {
							throw new error_guarani(kernel::traductor()->trans($mensaje_error));
						}
					}
                                        
                                        \guarani::act('act_actas')->actualizar_version_impresa($datos_acta['id_acta']);
					
                                        kernel::db()->cerrar_transaccion();
				} catch (error_guarani $e) {
					kernel::db()->abortar_transaccion();
					kernel::log()->add_error($e);
					throw $e;
				} catch (\guarani_error $e) {
					kernel::db()->abortar_transaccion();
					kernel::log()->add_error($e);
					throw new error_guarani($e->getMessage());
				} catch (ErrorKernelDb $e) {
					kernel::db()->abortar_transaccion();
					if ($e->get_sqlstate() == 'db_23505') {
						$mensaje = kernel::traductor()->trans('800_cerrar_acta_error_ff');
						throw new error_guarani($mensaje);
					} else {
						throw $e;
					}
				}
			}
		}

		$report = new VistaJasperreports();

		// Se setea la plantilla.
		$path = estatica('guarani', 'get_path_rep_jasper', [\acta::plantilla_examenes]);
		$report->set_path_reporte($path);

		// Cantidad de ejemplares a imprimir.
		$nombre_parametro = 'exa_actas_examen';
		$nombre_parametro .= ($datos_acta['estado'] == \acta::estado_cerrada) ? '_cerradas' : '_abiertas';
		$nombre_parametro .= '_cnt_ejemplares';

		$cnt_ejemplares = parametro::get_valor($nombre_parametro, $propuestas);

		$titulo = 'ACTA';
		if ($datos_acta['tipo_acta'] == \acta::tipo_rectificativa) {
			$titulo .= ' RECTIFICATIVA';
		}
		$titulo .= ' DE EXAMEN';

		$copia = ($datos_acta['version_impresa'] && $datos_acta['estado'] == \acta::estado_cerrada) ? 'ES COPIA' : '';
		$anulada = ($datos_acta['estado'] == \acta::estado_anulada) ? 'ANULADA' : '';
		$texto_aux = $copia . $anulada;
		//Institucion
		$institucion = current(\toba::consulta_php('co_instituciones')->get_institucion_gestionante());
		$actividad = $datos_acta['elemento'];
		$llamado = $datos_acta['llamado_mesa'];
		$datos = \toba::consulta_php('co_responsables_academicas')->get_ras_actividad_llamado($actividad, $llamado);

		$report->set_parametro('titulo', 'S', $titulo);
		$path_logo = kernel::localizador()->encontrar_path_archivo('www/img', 'logo_comprobante_sin_bajada_nuevo.png');
		$report->set_parametro('logo', 'S', $path_logo);
		$report->set_parametro('institucion', 'S', $institucion['nombre']);
		$report->set_parametro('ra_nombre', 'S', $datos['resp_acad_nombre']);
		$report->set_parametro('texto_aux', 'S', $texto_aux);
		$str = is_null($datos_acta['observaciones']) ? '' : $datos_acta['observaciones'];
		$report->set_parametro('observaciones', 'S', $str);
		$report->set_parametro('nro_acta', 'S', $datos_acta['nro_acta']);
		$report->set_parametro('fecha', 'S', $datos_acta['fecha_formato_visual']);
		
		$rectifica_a = "null";
		if ($datos_acta['tipo_acta'] == \acta::tipo_rectificativa) {
			$nro_acta_rectificada = \toba::consulta_php('co_actas')->get_nro_acta_rectificada($datos_acta['acta_referencia']);
			if (!empty($nro_acta_rectificada)) {
				$rectifica_a = $nro_acta_rectificada[0]['nro_acta_rectificada'];
			}
		}
		
		$report->set_parametro('rectifica_a', 'S', $rectifica_a);
		$report->set_parametro('version', 'S', $datos_acta['version']);
		$report->set_parametro('codigo_nombre_actividad', 'S', $datos_acta['cod_nombre_actividad']);
		$str = is_null($datos_acta['turno_nombre']) ? ' - ' : $datos_acta['turno_nombre'];
		$report->set_parametro('turno_examen', 'S', $str);
		$report->set_parametro('mesa', 'S', $datos_acta['mesa_nombre']);
		$str = is_null($datos_acta['llamado_nombre']) ? ' - ' : $datos_acta['llamado_nombre'];
		$report->set_parametro('nombre_llamado', 'S', $str);
		$report->set_parametro('anio_academico', 'E', $datos_acta['anio_academico']);
		$report->set_parametro('nombre_ubicacion', 'S', $datos_acta['ubicacion_nombre']);
		$report->set_parametro('acta_digital', 'S', $datos_acta['digital']);

		// Docentes y roles (máximo 3 por acta).
		$docentes_actas = \toba::consulta_php('co_mesas_de_examen')->get_docentes_mesa($datos_acta['llamado_mesa']);
                $docentes = "";
		for ($i = 0; $i < 3; $i++) {
			$rol = '';
			$docente_nombre = '';
			if (isset($docentes_actas[$i]['docente_nombre'])) {
				$docente_nombre = $docentes_actas[$i]['docente_nombre'];
				$rol = $docentes_actas[$i]['nombre'];
                                $docentes .= " {$docente_nombre} ({$rol}) ";
			}
			$par = 'docente_' . ($i + 1);
			$report->set_parametro($par, 'S', $docente_nombre);
			$par = 'rol_' . ($i + 1);
			$report->set_parametro($par, 'S', $rol);
		}

                $report->set_parametro('docentes', 'S', $docentes);
                
		$default_valor_libro_folio_folio = ($utiliza_acta_digital)? \acta::libro_no_aplica : 0;
		
		foreach ($datos_alumnos as $clave => $valor) {
			$datos_folio_acta = \toba::consulta_php('co_actas')->get_datos_folio_acta($datos_acta['id_acta'], $valor['folio']);
			$datos_alumnos[$clave]['folio_fisico'] = isset($datos_folio_acta['folio_fisico']) ? $datos_folio_acta['folio_fisico'] : $default_valor_libro_folio_folio;
			$datos_alumnos[$clave]['libro'] = isset($datos_folio_acta['nombre_libro']) ? $datos_folio_acta['nombre_libro'] : $default_valor_libro_folio_folio;
			$datos_alumnos[$clave]['tomo'] = isset($datos_folio_acta['nro_tomo']) ? $datos_folio_acta['nro_tomo'] : $default_valor_libro_folio_folio;
		}

		$columnas = array('renglon', 'persona', 'documento', 'legajo', 'instancia_nombre', 'fecha', 'nota', 'nota_descr', 'resultado_descr', 'folio_fisico', 'libro', 'tomo', 'folio');
		$xml = "<tabla break-after='page'><datos>";
		foreach ($datos_alumnos as $datos_alumno) {
			$xml .= '<fila>';
			foreach ($columnas as $clave) {
				$valor = isset($datos_alumno[$clave]) ? $datos_alumno[$clave] : '';
                                // Escape a caracteres especiales xml que generan error en el parser de jasper.
                                $valor = \guarani_texto::reemplazar_caracteres_especiales_xml($valor);
                                $valor = \guarani_texto::parsear_caracteres_xml($valor);
				$xml .= '<dato clave="' . $clave . '" valor="' . $valor . '"/>';
                        }
			$xml .= '</fila>';
		}
		$xml .= '</datos></tabla>';

		$report->set_xml(encoding::e_seguro($xml));

		// Si hay que generar más de una copia se replica el reporte.
		for ($i = 0; $i < $cnt_ejemplares; $i++) {
			$report->completar_con_datos();
		}

		$report->generar_salida();
		return ['path' => $report->get_nombre_archivo_generado(), 'nombre_descarga' => 'archivo.pdf'];
	}

}