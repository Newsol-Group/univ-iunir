<?php
namespace siu\operaciones\examen;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\mail;
use siu\guarani;
use siu\extension_kernel\controlador_errores_control;
use SIU\Chulupi\util\validador;
use siu\errores\error_guarani;
use siu\modelo\transacciones\transaccion;

class controlador extends controlador_errores_control
{
	protected $id_cert_baja;
	
	/**
	 * @var generador_comprobantes_cursada
	 */
	protected $generador_comp;

	/**
	 * 
	 * @return \siu\modelo\transacciones\inscripcion_examen
	 */
	function modelo()
	{
		return guarani::examen();
	}

	function ini()
	{
		$this->generador_comp = kernel::localizador()->instanciar('operaciones\examen\generador_comprobantes_examen');
	}

	function accion__index()
	{
		$this->exec_fn_modelo('control__inicial');
		$this->vista()->set_hay_mensajes($this->hay_var('mensajes'));
	}

	function accion__elegir_materia()
	{
		$materia = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
		$pagelet = $this->vista()->pagelet('info_materia_examen');

		$this->elegir_materia($materia);

		$this->add_var('materia', $materia);
		kernel::renderer()->add($pagelet);
	}

	protected function elegir_materia($materia, $mensajes_anteriores = array())
	{
		$pagelet = $this->vista()->pagelet('info_materia_examen');
		try {
			$mensajes = $this->modelo()->control__seleccion_materia($materia);
			$pagelet->set_estado_info(pagelet_info_materia_examen::MATERIA_SELECCIONADA);
			$this->add_var('mensajes', array_merge($mensajes_anteriores, $mensajes));
		} catch (error_guarani $e) {
			$pagelet->set_estado_info(pagelet_info_materia_examen::ERROR_MATERIA_SELECCIONADA);
			$this->add_var('mensajes', array_merge($mensajes_anteriores, $e->get_mensajes_usuario()));
		}
	}

	function accion__inscribir()
	{
		try {

                        $simultaneidad_operaciones_bloqueada = false;
                        try {
                            // Bloquea acceso simultáneo
                            $this->modelo()->bloquear_simultaneidad_operaciones(transaccion::OP_INSCRIPCION_EXAMEN);
                        } catch (\Exception $e) {
                                // Si fue bloqueado
                                $simultaneidad_operaciones_bloqueada = true;
				throw $e;
			}

			try {
				// Chequeo los parámetros
				$url_actividad = $this->validate_param('materia', 'post', validador::TIPO_ALPHANUM);
				$url_llamado_mesa = $this->validate_param('mesa', 'post', validador::TIPO_ALPHANUM);
				$instancia = null;

				if (!$this->modelo()->la_seleccion_de_instancia_es_automatica()) {
					$instancia = $this->validate_param('tipo_insc', 'post', validador::TIPO_INT);
				}
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
			
			$resultado = $this->modelo()->evt__inscripcion($url_actividad, $url_llamado_mesa, $instancia);
			kernel::renderer()->add_to_ajax_response('mensaje_inscripcion', $resultado['mensaje_inscripcion']);
			$pagelet = $this->vista()->pagelet('info_materia_examen');
			$pagelet->set_estado_info(pagelet_info_materia_examen::MATERIA_SELECCIONADA);
			$this->add_var('mensajes', $resultado['mensajes_controles']);
			$this->add_pagelet_vars($url_actividad, $url_llamado_mesa);
			kernel::renderer()->add($this->vista()->pagelet('info_materia_examen'));
		} catch (error_guarani $e) {
			// Se genera un nuevo csrf xq el anterior ya se consumió
			kernel::renderer()->add_csrf($this->generar_csrf());
			$this->finalizar_request_con_notificaciones(kernel::traductor()->trans('examen.insc_examen_error'), $e->get_mensajes_usuario());
		} finally {

                        // Si no fue bloqueado le libero el acceso simultáneo, sino dejo que espere el tiempo determinado
                        if (!$simultaneidad_operaciones_bloqueada) {
                            // Desbloquea acceso simultáneo
                            $this->modelo()->desbloquear_simultaneidad_operaciones(transaccion::OP_INSCRIPCION_EXAMEN);
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
			$this->guardar_id_cert_baja($url_inscripcion, $resultado['inscripcion']);
			$href = kernel::vinculador()->crear('examen', 'comprobante_baja', array('baja' => $url_inscripcion));
			$link = kernel::traductor()->trans('link_baja', array('%1%' => $href));
			kernel::renderer()->add_to_ajax_response('mensaje_cert_baja', $resultado['mensaje_baja'] . " " . $link);
			$pagelet = $this->vista()->pagelet('info_materia_examen');
			$pagelet->set_estado_info(pagelet_info_materia_examen::MATERIA_SELECCIONADA);
			$this->add_var('mensajes', $resultado['mensajes_controles']);
			$this->add_pagelet_vars($url_actividad, $url_inscripcion);
			kernel::renderer()->add($this->vista()->pagelet('info_materia_examen'));
		} catch (error_guarani $e) {
			kernel::renderer()->add_csrf($this->generar_csrf());
			$this->finalizar_request_con_notificaciones(kernel::traductor()->trans('error_baja_examen'), $e->get_mensajes_usuario());
		}
	}

	/**
	 * Acción baja que se ejecuta desde el estado inicial del pagelet 
	 */
	function accion__baja_inicial()
	{
		
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
		
			$resultado = $this->modelo()->evt__eliminar_inscripcion_inicial($url_inscripcion);
			$this->guardar_id_cert_baja($url_inscripcion, $resultado['inscripcion']);
			$href = kernel::vinculador()->crear('examen', 'comprobante_baja', array('baja' => $url_inscripcion));
			$link = kernel::traductor()->trans('link_baja', array('%1%' => $href));
			kernel::renderer()->add_to_ajax_response('mensaje_cert_baja', $resultado['mensaje_baja'] . " " . $link);
			$this->add_var('mensajes', $resultado['mensajes_controles']);
			kernel::renderer()->add($this->vista()->pagelet('info_materia_examen'));
		} catch (error_guarani $e) {
			kernel::renderer()->add_csrf($this->generar_csrf());
			$this->finalizar_request_con_notificaciones(kernel::traductor()->trans('error_baja_examen'), $e->get_mensajes_usuario());
		}
	}

	function accion__enviar_comprobante()
	{
		$dir_mail = kernel::persona()->get_mail();
		if (empty($dir_mail)) {
			$this->finalizar_request();
		}
		$url_actividad = $this->validate_param('actividad', 'get', validador::TIPO_ALPHANUM);
		$url_inscripcion = $this->validate_param('inscripcion', 'get', validador::TIPO_ALPHANUM);
		$datos = $this->modelo()->info__comprobante($url_actividad, $url_inscripcion);

		$asunto = kernel::traductor()->trans('asunto_mail_alta_certificado_examen', array(
			'%1%' => $datos['fecha_inscripcion'],
			'%2%' => $datos['actividad_nombre']
		));
		$tpl = kernel::load_template('info_materia_examen/mail_alta_certificado.twig');
		$cuerpo = $tpl->render(array());
		$path_archivo = kernel::proyecto()->get_dir_temp() . '/' . uniqid() . '.png';
		$this->generador_comp->set_tipo_generacion(generador_comprobantes_examen::TIPO_GENERACION_ARCHIVO);
		$this->generador_comp->set_path_destino($path_archivo);
		$this->generador_comp->generar_comp_alta($datos);

		$mail = new mail($dir_mail, $asunto, $cuerpo);
		$mail->agregar_adjunto('comprobante.png', $path_archivo);
		$mail->set_html(true);

		$mail->enviar();
		
		// Una vez enviado el email se elimina el comprobante.
		unlink($path_archivo);
		$this->render_ajax();
	}

	function accion__generar_comprobante()
	{
		$url_actividad = $this->validate_param('actividad', 'get', validador::TIPO_ALPHANUM);
		$url_inscripcion = $this->validate_param('inscripcion', 'get', validador::TIPO_ALPHANUM);
		$imprimir = $this->validate_param('i', 'get', validador::TIPO_INT, array(
			'allowempty' => true,
			'default' => 0
		));

		$datos = $this->modelo()->info__comprobante($url_actividad, $url_inscripcion);
		if ($imprimir == 1) {
			$this->generador_comp->set_tipo_generacion(generador_comprobantes_examen::TIPO_GENERACION_STREAM);
		} else {
			$this->generador_comp->set_tipo_generacion(generador_comprobantes_examen::TIPO_GENERACION_DESCARGA);
		}
		$this->generador_comp->generar_comp_alta($datos);
		$this->finalizar_request();
	}

	function accion__comprobante_baja()
	{
		$url_inscripcion = $this->validate_param('baja', 'get', validador::TIPO_ALPHANUM);
		$datos = $this->modelo()->info__comprobante_baja($url_inscripcion);
		$this->generador_comp->set_tipo_generacion(generador_comprobantes_examen::TIPO_GENERACION_DESCARGA);
		$this->generador_comp->generar_comp_baja($datos);
		$this->finalizar_request();
	}

	protected function add_pagelet_vars($materia, $mesa)
	{
		$this->add_var('materia', $materia);
		$this->add_var('mesa', $mesa);
	}

	protected function guardar_id_cert_baja($key, $value)
	{
		kernel::sesion()->set("id_cert_baja_$key", $value);
	}

	function accion__info_notificaciones()
	{
		$data = guarani::persona()->examenes()->get_inscripciones();
		$this->render_raw_json($data);
	}

}