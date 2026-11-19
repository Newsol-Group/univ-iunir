<?php
namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use siu\errores\error_guarani;
use siu\guarani;
use siu\modelo\controles\proceso_control;
use siu\modelo\datos\catalogo;
use siu\modelo\datos\util;
use siu\modelo\entidades\parametro;
use siu\modelo_g3\cobro;

class cursos extends transaccion
{
	protected $operacion = '800SIUCUR051';
	protected $operacion_baja = '800SIUCUR005';
	
	protected $info_curso;
	protected $propuesta;
	protected $plan;
	protected $plan_version;
	protected $comision;
	protected $alumno;
	protected $comision_datos;
	
	static protected $ejecuta_controles;

	function info__propuestas_administran_cursos()
	{
		return catalogo::consultar('cursos', 'propuestas_administran_cursos');
	}

	function info__actividades_propuesta_curso_por_persona($propuesta_curso)
	{
		$actividades_propuesta_curso = catalogo::consultar('cursos', 'actividades_propuestas_administran_cursos_con_comisiones_vigentes', [
			'propuesta_curso' => $propuesta_curso
		]);
		
		// AP = Aceptadas y Pendientes
		$estado = \inscripcion_cursada::estado_aceptada.\inscripcion_cursada::estado_pendiente;
		$inscripciones_cursada_persona = catalogo::consultar('alumno', 'inscripciones_cursadas', [
			'persona' => $this->persona,
			'estado' => $estado,
			'vigentes' => \comunes::si,
			'cursos' => true
		]);


		if ($inscripciones_cursada_persona) {
			// Si la persona tiene inscripciones a cursada
			foreach ($actividades_propuesta_curso as $key => $actividad_propuesta_curso) {
				$plan_version	= $actividad_propuesta_curso['plan_version'];
				$actividad		= $actividad_propuesta_curso['elemento'];

				$inscripciones_cursada_persona_actividad = array_filter($inscripciones_cursada_persona, function($inscripcion) use ($plan_version, $actividad) {
					return	($inscripcion['plan_version'] == $plan_version) &&
							($inscripcion['actividad'] == $actividad);
				});

				if (\guarani_arreglos::count($inscripciones_cursada_persona_actividad) > 0) {
					$actividades_propuesta_curso[$key]['inscripto'] = true;
				} else {
					$actividades_propuesta_curso[$key]['inscripto'] = false;
				}
			}
		}

		return $actividades_propuesta_curso;
	}

	function info__comisiones_curso_usando_co_comisiones($propuesta, $plan, $plan_version, $curso)
	{
		$ras = \toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuesta);
		$sq_usa_sanaviron = parametro::get_valor('sq_usa_sanaviron', $ras);

		$where = "sga_comisiones.elemento = " . kernel::db()->quote($curso);
		$params_sistema['sq_usa_sanaviron'] = $sq_usa_sanaviron;
		$datos = \toba::consulta_php('co_comisiones')->get_cursos($propuesta, $plan, $plan_version, $where, true, false, 'comision', true, $params_sistema, \comunes::interfaz_autogestion);

		foreach ($datos as $key => $dato) {
			$datos[$key][catalogo::id] = catalogo::generar_id($propuesta . $plan . $plan_version . $curso . $dato['comision']);

			// Se cambia el formato a los horarios-aulas
			$cadena = $dato['horario_aula_descr'];
			$patron = '/(\w{3} \d{2}:\d{2} a \d{2}:\d{2}) \(([^\)]+)\)(\s\/)?/i';
			$sustitucion = '${1} - ${2}</br>';
			$datos[$key]['horario_aula_descr'] = preg_replace($patron, $sustitucion, $cadena);

			// Se cambia el formato a los docentes
			$cadena = $dato['docentes_responsabilidad'];
			$patron = '/([^\(]+ \([^\)]+\))(,\s)?/i';
			$sustitucion = '${1}</br>';
			$datos[$key]['docentes_responsabilidad'] = preg_replace($patron, $sustitucion, $cadena);

			$datos[$key]['permite_inscripcion'] = true;

			// Si usa Sanaviron y el curso es cobrable se obtiene el costo
			if (($sq_usa_sanaviron == \comunes::si) && ($dato['cobrable'] == \comunes::si)) {
				$id_externo_SQ = "{$propuesta}|{$dato['elemento']}|{$dato['comision']}";
				$fallo_comunicacion_SQ = false;

				try {
					$info_valorizaciones_SQ = cobro::get_servicios_instancias_valorizaciones($id_externo_SQ);
				} catch (\Exception $e) {
					$fallo_comunicacion_SQ = true;
					klog("Ocurrio un error al invocar el servicio REST 'GET /servicios-instancias-valorizaciones' de SQ.");
					klog2("Error GET /servicios-instancias-valorizaciones", $e->getMessage());
				}

				if ($fallo_comunicacion_SQ) {
					$costo = kernel::traductor()->trans('inscripcion_cursos.no_se_pudo_obtener');
					$label_costo = "label-important";
					$datos[$key]['msj_costo'] = kernel::traductor()->trans('inscripcion_cursos.msj_no_se_pudo_obtener_costo');
					$datos[$key]['tipo_msj_costo'] = "alert-error";
					$datos[$key]['permite_inscripcion'] = false;
				} elseif (empty($info_valorizaciones_SQ)) {
					$costo = kernel::traductor()->trans('inscripcion_cursos.sin_definir');
					$label_costo = "label-warning";
					$datos[$key]['msj_costo'] = kernel::traductor()->trans('inscripcion_cursos.msj_costo_sin_definir');
					$datos[$key]['tipo_msj_costo'] = "";
					$datos[$key]['permite_inscripcion'] = false;
				} else {
					$info_valorizacion_SQ = current($info_valorizaciones_SQ);
					if ($info_valorizacion_SQ['id_moneda'] == cobro::MONEDA_PESOS) {
						$moneda_simbolo = cobro::MONEDA_PESOS_SIMBOLO;
					} elseif ($info_valorizacion_SQ['id_moneda'] == cobro::MONEDA_DOLAR) {
						$moneda_simbolo = cobro::MONEDA_DOLAR_SIMBOLO;
					} elseif ($info_valorizacion_SQ['id_moneda'] == cobro::MONEDA_EURO) {
						$moneda_simbolo = cobro::MONEDA_EURO_SIMBOLO;
					}

					$costo = $moneda_simbolo . round($info_valorizacion_SQ['importe'], 2);
					$label_costo = "label-info";
				}

				$datos[$key]['costo'] = $costo;
				$datos[$key]['label_costo'] = $label_costo;
			}
		}

		return $datos;
	}

	function info__comisiones_curso($curso)
	{
		return catalogo::consultar('cursos', 'get_info_comisiones_curso', array('curso' => $curso));
	}

	function info__inscripciones($curso = null)
	{
		// Se obtiene la propuesta seleccionada de sesion
		$propuesta_seleccionada = null;
		if (kernel::sesion()->esta_seteada('__propuesta')) {
			$propuesta_seleccionada = kernel::sesion()->get('__propuesta');
		}
		$datos = guarani::persona()->cursadas()->get_inscripciones($propuesta_seleccionada, $curso);
		return $datos;
	}

	function info__curso($curso)
	{
		if (!isset($this->info_curso)) {
			$this->info_curso = catalogo::consultar('cursos', 'get_info_curso', array('curso' => $curso));
		}
		return $this->info_curso;
	}

	function info__comision($propuesta, $plan, $plan_version, $comision, $es_para_baja = false)
	{
		$where = 'sga_comisiones.comision = ' . kernel::db()->quote($comision);
		$ras = \toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuesta);
		$sq_usa_sanaviron = parametro::get_valor('sq_usa_sanaviron', $ras);
		$params_sistema['sq_usa_sanaviron'] = $sq_usa_sanaviron;
		
		$es_para_inscripcion = true;
		if ($es_para_baja)	{	
			// Se listan comisiones con fecha tope de baja vigente
			$es_para_inscripcion = false;
		}		
			
		$datos = \toba::consulta_php('co_comisiones')->get_cursos($propuesta, $plan, $plan_version, $where, $es_para_inscripcion, false, 'comision', true, $params_sistema, \comunes::interfaz_autogestion, $es_para_baja);
		$datos_comision = current($datos);
		return $datos_comision;
	}

	/**
	 * Efectúa la inscripción a la comisión del curso seleccionado.
	 * Si es necesario, inscribe previamente a la persona en la propuesta que maneja cursos.
	 * Copiado de Gestión: php/nucleo/cursadas/cursos/inscripcion/cn_insc_cursos.php función inscribir
	 *
	 * @return array estado de la inscripción
	 */
	function evt__inscribir_curso($propuesta, $plan, $plan_version, $comision)
	{
		try {

			$this->propuesta = $propuesta;
			$this->plan = $plan;
			$this->plan_version = $plan_version;
			$this->comision = $comision;

            // Abro transacción
            kernel::db()->abrir_transaccion();

			$ras = \toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($this->propuesta);
			$sq_usa_sanaviron = parametro::get_valor('sq_usa_sanaviron', $ras);

			$alumno_datos = \toba::consulta_php('co_alumnos')->get_datos_alumno_por_persona_propuesta($this->persona, $this->propuesta);

			// Si es su primera inscripción a un curso de la propuesta englobadora,
			// se lo inscribe en primera instancia
			if (empty($alumno_datos)) {
                $params_sistema = array('sq_usa_sanaviron' => $sq_usa_sanaviron);
                \inscripcion::inscribir_propuesta_curso($this->persona, $this->propuesta, $this->comision, $params_sistema, \comunes::interfaz_autogestion);

				$alumno_datos = \toba::consulta_php('co_alumnos')->get_datos_alumno_por_persona_propuesta($this->persona, $this->propuesta);
			}

			// Se obtiene el ID de alumno para manejar la inscripción
			$this->alumno = $alumno_datos['alumno'];

			$this->comision_datos = $this->info__comision($this->propuesta, $this->plan, $this->plan_version, $this->comision);

			$mensajes_controles = $this->validar_inscripcion($this->propuesta);

			$params_sistema = array(
				'cur_controlar_cupo' => parametro::get_valor('cur_controlar_cupo', $propuesta),
				'cur_controlar_cupo_por' => parametro::get_valor('cur_controlar_cupo_por', $propuesta),
				'cur_insc_siempre_pendiente' => parametro::get_valor('cur_insc_siempre_pendiente', $plan),
				'sq_usa_sanaviron' => $sq_usa_sanaviron
			);

			$res = \inscripcion_cursada::inscripcion_curso($this->alumno, $this->propuesta, $this->plan_version, $this->comision_datos, \comunes::interfaz_autogestion, $params_sistema);

			if ($res['res'] == 1) {
				$nro_transaccion = $res['pars'][1];
				$datos_insc = current(\toba::consulta_php('co_inscripciones_cursadas')->get_datos_inscripcion($nro_transaccion));
				$nro_transaccion = $datos_insc['nro_transaccion'];
				$estado_nombre = $datos_insc['estado_nombre'];

				// Limpia la cache de inscripción a cursadas
				guarani::persona()->cursadas()->reset();

				$res['abrir_SQ_pagos'] = false;
				$res['hubo_error_SQ'] = false;

				// Si usa Sanaviron y el curso es cobrable, se obtiene el costo
				if (($sq_usa_sanaviron == \comunes::si) && ($this->comision_datos['cobrable'] == \comunes::si)) {
					$res['abrir_SQ_pagos'] = true;

					$body = array(
						"cliente" => array(
							"id_externo" => kernel::persona()->get_id(),
							"tipo_documento" => kernel::persona()->get_tipo_documento(),
							"numero_documento" => kernel::persona()->get_nro_documento(),
							"apellido" => kernel::persona()->get_solo_apellido(),
							"nombres" => kernel::persona()->get_solo_nombres()
						),
						"tipo_consumo" => cobro::TIPO_CONSUMO_SUSCRIPCION_ABREV,
						"id_consumo" => "{$this->propuesta}|{$this->comision_datos['elemento']}|{$this->comision_datos['comision']}",
						"id_tramite_ref" => $nro_transaccion,
						"callback_url" => "{$_SERVER['HTTP_REFERER']}?sq=1&transaccion_guarani={$nro_transaccion}"
					);

					$respuesta_SQ = cobro::generar_solicitud_consumo_externo($body);
					$token_SQ = $respuesta_SQ['token'];
					\guarani::act('act_inscripcion_cursadas')->actualizar_SQ_token($nro_transaccion, $token_SQ);
					$url_sq_pagos = cobro::get_url_consumo_SQ_pagos($token_SQ);
					$res['url_sq_pagos'] = $url_sq_pagos;
				}

				$res['mensaje_inscripcion'] = kernel::traductor()->trans('inscripcion_cursos.inscr_curso_ok', array(
					'%estado%' => $estado_nombre,
					'%transaccion%' => $nro_transaccion
				));

				$res['mensajes_controles'] = $mensajes_controles;

                // Si sale bien cierro transacción
                kernel::db()->cerrar_transaccion();

				return $res;
			} else {
				// hubo un error en la inscripción, se formatea para que se muestre como un error de control y además se lo
				// concatena con los posibles warnings e infos provenientes de control__inscripcion
				$this->lanzar_error_inscripcion(guarani::traducir_mensaje_BD($res['msg_indice'], $res['pars']), $mensajes_controles);
			}

		}
        catch (\guarani_error $e){

            // Si falla algo aborto transacción
            kernel::db()->abortar_transaccion();
            kernel::log()->add_debug('Falló la inscripción a la propuesta tipo curso', $e->get_mensaje());
            $this->lanzar_error_inscripcion($e->get_mensaje());
        }
        catch (error_guarani $e){

            // Si falla algo aborto transacción
            kernel::db()->abortar_transaccion();
            kernel::log()->add_debug('No se le permite inscribir al curso', $e->getMessage());
            throw $e;
        }
        catch (\Exception $e) {

            // Si falla algo aborto transacción
            kernel::db()->abortar_transaccion();

            $tipo_excepcion = get_class($e);
            $pattern = preg_quote("GuzzleHttp\Exception", '/');

            // Si falla el WS a SQ (devuelve una excepcion del tipo GuzzleHttp\Exception\*)
            if(preg_match("/^$pattern.*/", $tipo_excepcion)) {
                $res['hubo_error_SQ'] = true;
                kernel::log()->add_debug('Error petición REST: POST solicitudes-consumos-externos', $e->getMessage());
                $this->lanzar_error_inscripcion(kernel::traductor()->trans('inscripcion_cursos.error_comunicacion_sq'));
            }
            else {
                kernel::log()->add_debug('Error al inscribir al curso', $e->getMessage());
                $this->lanzar_error_inscripcion(kernel::traductor()->trans('inscripcion_cursos.insc_curso_error_descr'));
            }
        }

	}

	protected function lanzar_error_inscripcion($mensaje, $mensajes_controles = array())
	{
		$e = new error_guarani(kernel::traductor()->trans('inscripcion_cursos.insc_curso_error'));
		if (!empty($mensajes_controles)) {
			$e->add_mensajes_usuario($mensajes_controles);
		}
		$e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, $mensaje));
		throw $e;
	}

    function evt__eliminar_inscripcion($propuesta, $plan, $plan_version, $comision, $inscripcion, $nro_transaccion, $modo_devolucion = null)
    {
        $this->propuesta = $propuesta;
        $this->plan = $plan;
        $this->plan_version = $plan_version;
        $this->comision = $comision;

		$alumno_datos = \toba::consulta_php('co_alumnos')->get_datos_alumno_por_persona_propuesta($this->persona, $this->propuesta);
		$this->alumno = $alumno_datos['alumno'];

        return $this->eliminar_inscripcion($inscripcion, $nro_transaccion, $modo_devolucion);
    }

    protected function eliminar_inscripcion($inscripcion, $nro_transaccion, $modo_devolucion = null)
    {
		list($resultado_controles, $mensajes_controles) = $this->control__eliminar_inscripcion();

		try {
			// Se abre una transacción
			kernel::db()->abrir_transaccion();

			// Causa error genérica
			$resultado['causa_error'] = kernel::traductor()->trans('inscripcion_cursos.baja_insc_curso_error_descr');

			$es_para_baja = true;
			$this->comision_datos = $this->info__comision($this->propuesta, $this->plan, $this->plan_version, $this->comision, $es_para_baja);

			$ras = \toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($this->propuesta);
			$sq_usa_sanaviron = parametro::get_valor('sq_usa_sanaviron', $ras);
			
			// Si no está habilitada la baja
			if (!$this->comision_datos['baja_habilitada']) {
				$resultado['causa_error'] = kernel::traductor()->trans('inscripcion_cursos.baja_no_habilitada_error');
				throw new \Exception(kernel::traductor()->trans("inscripcion_cursos.baja_no_habilitada_error"));
			}

			$resultado = catalogo::consultar('inscripcion_cursada', 'eliminar_inscripcion_comision', array('inscripcion' => $inscripcion));

			$usa_sq = ($sq_usa_sanaviron == \comunes::si);
			$comision_cobrable = ($this->comision_datos['cobrable'] == \comunes::si);
			
			$existe_consumo_externo_sq = false;
			// Si se usa SQ y el curso es cobrable entonces consulto el estado del mismo en SQ
			if ($usa_sq && $comision_cobrable) {
				$estado_consumo_externo = cobro::get_estado_consumo_externo(cobro::TIPO_CONSUMO_SUSCRIPCION, $nro_transaccion);
				$existe_consumo_externo_sq = ($estado_consumo_externo['status_code'] != cobro::ESTADO_GET_VACIO);
			}
			
			// Si la baja de inscripción se procesa bien, se usa SQ, el curso es cobrable y existe el consumo externo en SQ, se hace un POST a suscripciones-novedades
			if (($resultado['resultado'] > 0) && $usa_sq && $comision_cobrable && $existe_consumo_externo_sq) {

				$body = array(
					"id" => $nro_transaccion,
					"tipo_novedad" => cobro::TIPO_NOVEDAD_BAJA,
					"observaciones" => "Baja de la inscripción a curso, Nro. transacción: {$nro_transaccion}",
					"modo_devolucion" => $modo_devolucion
				);

				try {

					cobro::informar_suscripcion_novedad($body);
					
				} catch (\Exception $e) {

					switch ($e->getCode()) {
						case cobro::ESTADO_GET_VACIO:
							$resultado['causa_error'] = kernel::traductor()->trans('inscripcion_cursos.error_no_suscripcion_sq');
							break;
						case cobro::ERROR_POST_SERV_VERS_SIN_SERVICIO:
							$resultado['causa_error'] = kernel::traductor()->trans('inscripcion_cursos.error_pago_avanzado_baja_sq');
							break;
						default:
							$resultado['causa_error'] = kernel::traductor()->trans('inscripcion_cursos.error_comunicacion_sq');
					}

					throw $e;
				}
			}

			// Se cierra la transacción
			kernel::db()->cerrar_transaccion();

			$parametros_mensaje = util::parsear_parametros_mensajes($resultado['mensaje_param']);
			$mensaje_baja = kernel::traductor()->trans($resultado['mensaje_indice'], $parametros_mensaje);
			
		} catch (\Exception $e) {

			// Se revierten los cambios
			kernel::db()->abortar_transaccion();

			// Se marca la baja como errónea
			$resultado['resultado'] = -1;

			kernel::log()->add_debug('Error baja curso', $e->getMessage());
		}

		if ($resultado['resultado'] > 0) {
			$transaccion = $resultado['nro_transaccion'];
			kernel::log()->add_debug('TRANSACCION', $transaccion);
			guarani::persona()->cursadas()->reset();
			return array($mensaje_baja, $transaccion, $inscripcion, 'advertencias' => $mensajes_controles);
		} else {
			$e = new error_guarani(kernel::traductor()->trans('inscripcion_cursos.baja_insc_curso_error'));
			$e->add_mensajes_usuario($mensajes_controles);
			$e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, $resultado['causa_error']));
			throw $e;
		}
	}

	function info__inscripcion($inscripcion)
	{
		$datos = catalogo::consultar('inscripcion_cursada', 'info_inscripcion', array('inscripcion' => $inscripcion));
		return $datos;
	}

	function info__existe_inscripcion($inscripcion)
	{
		$datos = catalogo::consultar('inscripcion_cursada', 'existe_inscripcion', array('inscripcion' => $inscripcion));
		return $datos['existe'];
	}

	//--------------------------------------------------------------------------
	//---- Validaciones
	//--------------------------------------------------------------------------

	protected function validar_inscripcion($propuesta)
	{
		$estado = \inscripcion_cursada::estado_pendiente;

		// Controles inscripcion
		list($resultado_controles, $mensajes_controles) = $this->control__inscripcion($propuesta);

		// Controles instancias
		list($resultado_controles_instancias, $mensajes_controles_instancia) = $this->controlar_instancias();

		$mensajes_controles = array_merge($mensajes_controles, $mensajes_controles_instancia);
		$hubo_advertencias = $resultado_controles == \guarani_punto_de_control::advertido || $resultado_controles_instancias == \guarani_punto_de_control::advertido;

		// Estado de la inscripción.
		if (static::ejecutar_controles($propuesta) && !$hubo_advertencias) {
			if (parametro::get_valor('cur_insc_siempre_pendiente', $propuesta) == \comunes::no) {
				$estado = \inscripcion_cursada::estado_aceptada;
			}
		}

		$this->comision_datos['estado_inscripcion'] = $estado;

		return $mensajes_controles;
	}

	protected function controlar_instancias()
	{
		// Validación de Instancias
		// Determina a qué instancias se inscribe y con qué estado
		// La validación de instancias se realiza independientemente del parámetro 'cur_ejecuta_controles'
		$this->comision_datos['instancias_a_inscribir'] = array();
		$instancias = \toba::consulta_php('co_comisiones')->get_instancias_comision($this->comision);
		$acumulacion_mensajes = array();
		$peor_resultado = \guarani_punto_de_control::autorizado;

		foreach ($instancias as $instancia) {

			$texto_a_agregar = "Instancia {$instancia['nombre']}:";

			$ptos_control_a_validar = \toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($instancia['instancia']);
			$params = array(
				'alumno' => $this->alumno,
				'elemento' => $this->comision_datos['elemento'],
				'fecha' => \guarani_fecha::get_hoy(),
				'plan_version' => $this->plan_version
			);

			$proceso = $this->nuevo_proceso_control($ptos_control_a_validar, $params);
			$resultado_controles = $proceso->ejecutar();
			$mensajes_controles = $proceso->get_mensajes();

			$acumulacion_mensajes = array_merge($acumulacion_mensajes, guarani::agregar_texto_inicio_mensajes($mensajes_controles, $texto_a_agregar));

			if ($resultado_controles != \guarani_punto_de_control::rechazado) {
				$this->comision_datos['instancias_a_inscribir'][] = $instancia;
			}
			// Se guarda el peor estado acumulado de las no rechazadas
			if ($resultado_controles == \guarani_punto_de_control::advertido) {
				$peor_resultado = $resultado_controles;
			}
		}

		return array($peor_resultado, $acumulacion_mensajes);
	}

	//--------------------------------------------------------------------------
	//----	CONTROLES
	//--------------------------------------------------------------------------
	
	/**
	 * Determina si en la inscricpión a cursada se ejecutan o no los controles en los puntos de control.
	 * @return boolean
	 */
	static function ejecutar_controles($propuesta)
	{
		if (!isset(self::$ejecuta_controles)) {
			self::$ejecuta_controles = (parametro::get_valor('cur_ejecuta_controles', $propuesta) == \comunes::si);
		}
		return self::$ejecuta_controles;
	}

	protected function control__inscripcion($propuesta)
	{
		// Si se deben ejecutar los controles configurables
		if (static::ejecutar_controles($propuesta)) {
			// Controles inscripcion a curso
			list($puntos_de_control, $parametros) = $this->agregar_puntos_control_inscripcion_curso();
			$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros);
			$resultado_controles_inscripcion_curso = $proceso->ejecutar();
			$mensajes_controles_inscripcion_curso = $proceso->get_mensajes();

			$resultado_controles = proceso_control::get_peor_resultado(array($resultado_controles_inscripcion_curso));

			return array($resultado_controles, $mensajes_controles_inscripcion_curso);
		}

		return array(\guarani_punto_de_control::autorizado, array());
	}

	function agregar_puntos_control_inscripcion_curso()
	{
		//https://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a0:Alumno-Entidad
		//https://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a1:Persona-Operacion
		//https://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a2:Alumno-Operación
		//https://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a3:ElegirActividadCursada
		//https://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a6:ElegirComisión
		//https://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a8:Alumno-AñoAcadémico

		$puntos_de_control = array(
			\guarani_punto_de_control::alumno_entidad,
			\guarani_punto_de_control::persona_operacion,
			\guarani_punto_de_control::alumno_operacion,
			\guarani_punto_de_control::alumno_actividad,
			\guarani_punto_de_control::alumno_comision,
			\guarani_punto_de_control::alumno_anio_academico,
		);

		$parametros = array(
			'persona' => $this->persona,
			'fecha' => \guarani_fecha::get_hoy(),
			'alumno' => $this->alumno,
			'propuesta' => $this->propuesta,
			'plan_version' => $this->plan_version,
			'elemento' => $this->comision_datos['elemento'],
			'anio_academico' => $this->comision_datos['anio_academico'],
			'comision' => $this->comision_datos['comision']
		);

		return array($puntos_de_control, $parametros);
	}

	function control__eliminar_inscripcion()
	{
		// Controles de baja a inscripcion a curso
		list($puntos_de_control, $parametros) = $this->agregar_puntos_control_baja();
		$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros, $this->operacion_baja);
		$resultado_controles_baja_inscripcion_curso = $proceso->ejecutar();
		$mensajes_controles_baja_inscripcion_curso = $proceso->get_mensajes();

		$resultado_controles = proceso_control::get_peor_resultado(array($resultado_controles_baja_inscripcion_curso));

		return array($resultado_controles, $mensajes_controles_baja_inscripcion_curso);
	}

	function agregar_puntos_control_baja()
	{
		//https://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a0:Alumno-Entidad
		//https://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a1:Persona-Operacion
		//https://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a2:Alumno-Operación

		$puntos_de_control = array(
			\guarani_punto_de_control::alumno_entidad,
			\guarani_punto_de_control::persona_operacion,
			\guarani_punto_de_control::alumno_operacion
		);

		$parametros = array(
			'fecha' => \guarani_fecha::get_hoy(),
			'persona' => $this->persona,
			'alumno' => $this->alumno,
			'plan_version' => $this->plan_version
		);

		return array($puntos_de_control, $parametros);
	}

	//--------------------------------------------------------------------------
	//---- INFO
	//--------------------------------------------------------------------------

	function info__comprobante($inscripcion)
	{
		return catalogo::consultar('inscripcion_cursada', 'info_comprobante_inscripcion', array('inscripcion' => $inscripcion));
	}

	function info__comprobante_baja($inscripcion)
	{
		return catalogo::consultar('inscripcion_cursada', 'info_comprobante_baja', array('inscripcion' => $inscripcion));
	}

	function info__cupo_comisiones($comisiones)
	{
		$comisiones_str = implode(",", $comisiones);
		$datos = catalogo::consultar('inscripcion_cursada', 'info_cupo_comisiones', array(
			'persona' => $this->persona,
			'comisiones_str' => $comisiones_str,
			'param_cur_controla_cupo_por' => 'comision'
		));

		return $datos;
	}

	function info__dias_semana()
	{
		return catalogo::consultar('sistema', 'get_dias_semana');
	}
	
	function info__ubicaciones()
	{
		return catalogo::consultar('sistema', 'get_ubicaciones');
	}

}