<?php

namespace siu\operaciones\cursada;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\mail;
use siu\errores\error_guarani_seguridad;
use siu\guarani;
use siu\extension_kernel\controlador_errores_control;
use SIU\Chulupi\util\validador;
use siu\operaciones\cursada\pagelet_info_materia;
use siu\operaciones\cursada\generador_comprobantes_cursada;
use siu\errores\error_guarani;
use siu\modelo\transacciones\transaccion;

class controlador extends controlador_errores_control
{
	protected $id_cert_baja;
	/**
	 *
	 * @var generador_comprobantes_cursada
	 */
	protected $generador_comp;

	/**
	 * @return \siu\modelo\transacciones\inscripcion_cursada
	 */
	function modelo()
	{
		return guarani::cursada();
	}

	function ini()
	{
		$this->generador_comp = kernel::localizador()->instanciar('operaciones\cursada\generador_comprobantes_cursada');
	}

	function accion__index()
	{
		$mensajes = $this->modelo()->control__inicial();
		$this->acum_mensajes($mensajes);
		$this->vista()->set_hay_mensajes($this->hay_var('mensajes'));
	}

	function accion__elegir_materia()
	{
		$materia = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
		$pagelet = $this->vista()->pagelet('info_materia');

		$this->elegir_materia($materia);

		$this->add_var('materia', $materia);

		$msg_confirma_desea_inscribirse_a_cursar = kernel::traductor()->trans('800CUR_confirmar_alta_insc', array(
			'%1%' => $pagelet->get_materia_codigo(),
			'%2%' => $pagelet->get_materia_nombre()
		));
		kernel::renderer()->add_to_ajax_response('msg_confirma_desea_inscribirse_a_cursar', $msg_confirma_desea_inscribirse_a_cursar);
		kernel::renderer()->add($pagelet);
	}

	protected function elegir_materia($materia, $mensajes_anteriores = array())
	{
		$pagelet = $this->vista()->pagelet('info_materia');
		try {
			$mensajes = $this->modelo()->control__seleccion_materia($materia);
			$pagelet->set_estado_info(pagelet_info_materia::MATERIA_SELECCIONADA);
			$this->add_var('mensajes', array_merge($mensajes_anteriores, $mensajes));
		} catch (error_guarani $e) {
			$pagelet->set_estado_info(pagelet_info_materia::ERROR_MATERIA_SELECCIONADA);
			$this->add_var('mensajes', array_merge($mensajes_anteriores, $e->get_mensajes_usuario()));
		}
	}

	protected function validar_comisiones_prioridad($comisiones)
	{
		if (\guarani_arreglos::count($comisiones) <= 0) {
			throw new error_guarani_seguridad("Se intenta llegar a la acción inscribir_prioridad sin comisiones");
		}

		foreach ($comisiones as $key => $comision) {
			$this->validate_value($comision['materia'], validador::TIPO_ALPHANUM, array());
			$this->validate_value($comision['comision'], validador::TIPO_ALPHANUM, array());
			if (isset($comision['subcomisiones'])) {
				$this->validate_value($comision['subcomisiones'], validador::TIPO_ARRAY_ALPHANUM, array());
			} else {
				$comisiones[$key]['subcomisiones'] = array();
			}
		}

		return $comisiones;
	}

	function accion__inscribir_prioridad()
	{
		try {

                        $simultaneidad_operaciones_bloqueada = false;
                        try {
                            // Bloquea acceso simultáneo
                            $this->modelo()->bloquear_simultaneidad_operaciones(transaccion::OP_INSCRIPCION_CURSADA);
                        } catch (\Exception $e) {
                                // Si fue bloqueado
                                $simultaneidad_operaciones_bloqueada = true;
				throw $e;
			}

			try {
				// Chequeo los parámetros
				$url_actividad = $this->validate_param('materia', 'post', validador::TIPO_ALPHANUM);
				$comisiones = $this->validar_comisiones_prioridad(kernel::request()->getPost('comisiones'));
			} catch (\Exception $e) {
				$e = new error_guarani('');
				$e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, kernel::traductor()->trans('error_parametros')));
				throw $e;
			}
			
			try {
				// Chequeo de CSRF
				$this->check_csrf();
			} catch (\Exception $e) {
				$e = new error_guarani('');
				$e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, kernel::traductor()->trans('error_csrf')));
				throw $e;
			}
			
			$resultado = $this->modelo()->evt__inscripcion_prioridad($comisiones);
			kernel::renderer()->add_to_ajax_response('mensaje_inscripcion', $resultado['mensaje_inscripcion']);
			$pagelet = $this->vista()->pagelet('info_materia');
			$pagelet->set_estado_info(pagelet_info_materia::MATERIA_SELECCIONADA);
			$this->add_pagelet_vars($url_actividad, '');
			kernel::renderer()->add($this->vista()->pagelet('info_materia'));
		} catch (error_guarani $e) {
			// Se genera un nuevo csrf porque el anterior ya se consumió
			kernel::renderer()->add_csrf($this->generar_csrf());
			$this->finalizar_request_con_notificaciones(kernel::traductor()->trans('inscripcion_a_cursadas.insc_cursada_error'), $e->get_mensajes_usuario());
		} finally {

                        // Si no fue bloqueado le libero el acceso simultáneo, sino dejo que espere el tiempo determinado
                        if (!$simultaneidad_operaciones_bloqueada) {
                            // Desbloquea acceso simultáneo
                            $this->modelo()->desbloquear_simultaneidad_operaciones(transaccion::OP_INSCRIPCION_CURSADA);
                        }

		}
	}
        
	function accion__inscribir()
	{
		try {

                        $simultaneidad_operaciones_bloqueada = false;
                        try {
                            // Bloquea acceso simultáneo
                            $this->modelo()->bloquear_simultaneidad_operaciones(transaccion::OP_INSCRIPCION_CURSADA);
                        } catch (\Exception $e) {
                                // Si fue bloqueado
                                $simultaneidad_operaciones_bloqueada = true;
				throw $e;
			}

			try {
				// Chequeo los parámetros
				$url_actividad = $this->validate_param('materia', 'post', validador::TIPO_ALPHANUM);
				$url_comision = $this->validate_param('comision', 'post', validador::TIPO_ALPHANUM);
				$url_subcomisiones = $this->get_param('subcomisiones', 'post', 'string_array');
				$codigo_inscripcion_cerrada_recibido = $this->validate_param('codigo_inscripcion_cerrada', 'post', validador::TIPO_TEXTO, array(
					'allowempty' => true
				));
			} catch (\Exception $e) {
				$e = new error_guarani('');
				$e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, kernel::traductor()->trans('error_parametros')));
				throw $e;
			}
			
			try {
				// Chequeo de CSRF
				$this->check_csrf();
			} catch (\Exception $e) {
				$e = new error_guarani('');
				$e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, kernel::traductor()->trans('error_csrf')));
				throw $e;
			}
			
			$resultado = $this->modelo()->evt__inscripcion($url_actividad, $url_comision, $url_subcomisiones, $codigo_inscripcion_cerrada_recibido);
			kernel::renderer()->add_to_ajax_response('mensaje_inscripcion', $resultado['mensaje_inscripcion']);
			$pagelet = $this->vista()->pagelet('info_materia');
			$pagelet->set_estado_info(pagelet_info_materia::MATERIA_SELECCIONADA);
			$this->add_var('mensajes', $resultado['mensajes_controles']);
			$this->add_pagelet_vars($url_actividad, $url_comision);
			$this->add_var('ignorar_orden_comision', $url_comision);
			kernel::renderer()->add($this->vista()->pagelet('info_materia'));
		} catch (error_guarani $e) {
			// Se genera un nuevo csrf xq el anterior ya se consumió
			kernel::renderer()->add_csrf($this->generar_csrf());
			$this->finalizar_request_con_notificaciones(kernel::traductor()->trans('inscripcion_a_cursadas.insc_cursada_error'), $e->get_mensajes_usuario());
		} finally {

                        // Si no fue bloqueado le libero el acceso simultáneo, sino dejo que espere el tiempo determinado
                        if (!$simultaneidad_operaciones_bloqueada) {
                            // Desbloquea acceso simultáneo
                            $this->modelo()->desbloquear_simultaneidad_operaciones(transaccion::OP_INSCRIPCION_CURSADA);
                        }
                        
		}
	}

	function accion__baja()
	{
		try {
			
			try {
				// Chequeo los parámetros
				$url_actividad = $this->validate_param('materia', 'post', validador::TIPO_ALPHANUM, array('allowempty' => true));
				$url_inscripcion = $this->validate_param('inscripcion', 'post', validador::TIPO_ALPHANUM);
			} catch (\Exception $e) {
				$e = new error_guarani('');
				$e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, kernel::traductor()->trans('error_parametros')));
				throw $e;
			}
			
			try {
				// Chequeo de CSRF
				$this->check_csrf();
			} catch (\Exception $e) {
				$e = new error_guarani('');
				$e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, kernel::traductor()->trans('error_csrf')));
				throw $e;
			}
			
			$resultado = $this->modelo()->evt__eliminar_inscripcion($url_actividad, $url_inscripcion);
			$this->guardar_id_cert_baja($url_inscripcion, $resultado[2]);
			$href = kernel::vinculador()->crear('cursada', 'comprobante_baja', array('baja' => $url_inscripcion));
			$link = kernel::traductor()->trans('link_baja_cursada', array('%1%' => $href));
			kernel::renderer()->add_to_ajax_response('mensaje_cert_baja', $resultado[0] . " " . $link);
			$pagelet = $this->vista()->pagelet('info_materia');
			$pagelet->set_estado_info(pagelet_info_materia::MATERIA_SELECCIONADA);
			$this->add_var('mensajes', $resultado['advertencias']);
			$this->add_pagelet_vars($url_actividad, $url_inscripcion);
			kernel::renderer()->add($this->vista()->pagelet('info_materia'));
		} catch (error_guarani $e) {
			kernel::renderer()->add_csrf($this->generar_csrf());
			$this->finalizar_request_con_notificaciones(kernel::traductor()->trans('error_baja_cursada'), $e->get_mensajes_usuario());
		}
	}

	/**
	 * Acción baja que se ejecuta desde el estado inicial del pagelet 
	 */
	function accion__baja_inicial()
	{
		// Se llama a un método especial en esta baja porque puede ser que se
		// esté dando de baja una materia con el período de alta cerrado (no aparece
		// en la lista de la izquierda). Se hace sólo en este lugar ya que este
		// es el único que lista TODAS las materias en las que se puede dar de baja
		try {
			
			try {
				// Chequeo los parámetros
				$url_inscripcion = $this->validate_param('inscripcion', 'post', validador::TIPO_ALPHANUM);
			} catch (\Exception $e) {
				$e = new error_guarani('');
				$e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, kernel::traductor()->trans('error_parametros')));
				throw $e;
			}
			
			try {
				// Chequeo de CSRF
				$this->check_csrf();
			} catch (\Exception $e) {
				$e = new error_guarani('');
				$e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, kernel::traductor()->trans('error_csrf')));
				throw $e;
			}
			
			$resultado = $this->modelo()->evt__eliminar_inscripcion_periodo_alta_cerrado($url_inscripcion);
			$this->guardar_id_cert_baja($url_inscripcion, $resultado[2]);
			$href = kernel::vinculador()->crear('cursada', 'comprobante_baja', array('baja' => $url_inscripcion));
			$link = kernel::traductor()->trans('link_baja', array('%1%' => $href));
			kernel::renderer()->add_to_ajax_response('mensaje_cert_baja', $resultado[0] . " " . $link);
			$this->add_var('mensajes', $resultado['advertencias']);
			kernel::renderer()->add($this->vista()->pagelet('info_materia'));
		} catch (error_guarani $e) {
			kernel::renderer()->add_csrf($this->generar_csrf());
			$this->finalizar_request_con_notificaciones(kernel::traductor()->trans('error_baja_cursada'), $e->get_mensajes_usuario());
		}
	}

	function accion__info_notificaciones()
	{
		$data = guarani::persona()->cursadas()->info__asignaciones();
		$this->render_raw_json($data);
	}

	function accion__enviar_comprobante()
	{
		$dir_mail = kernel::persona()->get_mail();
		if (empty($dir_mail)) {
			$this->finalizar_request();
		}
		$url_materia = $this->validate_param('materia', 'get', validador::TIPO_ALPHANUM, array('default' => null));
		$url_inscripcion = $this->validate_param('inscripcion', 'get', validador::TIPO_ALPHANUM);
		$datos = $this->modelo()->info__comprobante($url_materia, $url_inscripcion);

		$asunto = kernel::traductor()->trans('asunto_mail_alta_certificado_cursada', array(
			'%1%' => $datos['fecha_inscripcion'],
			'%2%' => $datos['actividad_nombre']
		));
		$tpl = kernel::load_template('info_materia/mail_alta_certificado.twig');
		$cuerpo = $tpl->render(array());
		$path_archivo = kernel::proyecto()->get_dir_temp() . '/' . uniqid() . '.png';
		$this->generador_comp->set_tipo_generacion(generador_comprobantes_cursada::TIPO_GENERACION_ARCHIVO);
		$this->generador_comp->set_path_destino($path_archivo);
		$this->generador_comp->generar_comp_alta($datos);
		$mail = new mail($dir_mail, $asunto, $cuerpo);
		$mail->agregar_adjunto('comprobante.png', $path_archivo);
		$mail->set_html(true);

		$mail->enviar();
		//Una vez enviado el email elimino el comprobante.
		unlink($path_archivo);
		$this->render_ajax();
	}

	function accion__generar_comprobante()
	{
		$url_materia = $this->validate_param('materia', 'get', validador::TIPO_ALPHANUM, array('default' => null));
		$url_inscripcion = $this->validate_param('inscripcion', 'get', validador::TIPO_ALPHANUM);
		$imprimir = $this->validate_param('i', 'get', validador::TIPO_INT, array(
			'allowempty' => true,
			'default' => 0
		));

		$datos = $this->modelo()->info__comprobante($url_materia, $url_inscripcion);
		$comisiones_por_prioridad = $this->modelo()->info__comisiones_por_inscripcion_prioridad($datos['alumno'], $datos['nro_transaccion']);

		if ($imprimir == 1) {
			$this->generador_comp->set_tipo_generacion(generador_comprobantes_cursada::TIPO_GENERACION_STREAM);
		} else {
			$this->generador_comp->set_tipo_generacion(generador_comprobantes_cursada::TIPO_GENERACION_DESCARGA);
		}
		$this->generador_comp->generar_comp_alta($datos, $comisiones_por_prioridad);
		$this->finalizar_request();
	}

	function accion__comprobante_baja()
	{
		$url_inscripcion = $this->validate_param('baja', 'get', validador::TIPO_ALPHANUM);
		$datos = $this->modelo()->info__comprobante_baja($url_inscripcion);
		$this->generador_comp->set_tipo_generacion(generador_comprobantes_cursada::TIPO_GENERACION_DESCARGA);
		$this->generador_comp->generar_comp_baja($datos);
		$this->finalizar_request();
	}

	protected function add_pagelet_vars($materia, $comision)
	{
		$this->add_var('materia', $materia);
		$this->add_var('comision', $comision);
	}

	protected function guardar_id_cert_baja($key, $value)
	{
		kernel::sesion()->set("id_cert_baja_$key", $value);
		$mensaje = kernel::traductor()->trans('mensaje_baja_comprobante', array(
			'%1%' => kernel::vinculador()->crear('cursada', 'comprobante_baja', array(
				'baja' => $key
			))
		));
		kernel::renderer()->add_to_ajax_response('mensaje_cert_baja', $mensaje);
	}

}
