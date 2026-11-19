<?php

namespace siu\operaciones\asociar_usuario_se;

use SIU\Chulupi\kernel;
use SIU\Chulupi\ErrorKernel;
use siu\extension_kernel\controlador_preinscripcion;
use siu\preinscripcion;
use siu\errores\error_preinscripcion_login;

class controlador extends controlador_preinscripcion
{
	/**
	 * @return null
	 */
	function modelo()
	{
		return null;
	}

	function ini()
	{
		if (!kernel::sesion()->esta_seteada('medio_se')) {
			die(kernel::traductor()->trans('operacion_invalida'));
		}
	}

	function accion__index()
	{
		$this->vista()->set_pagelet('asociar_usuario');
		$op_defecto = kernel::persona()->perfil()->get_parametro('index');

		if (kernel::sesion()->abierta()) {
			$this->nuevo_request($op_defecto);
		}
	}

	function accion__asociar()
	{
		$exito = false;
		$pagelet = $this->vista()->set_pagelet('asociar_usuario');

		if (kernel::request()->isPost()) {
			// Validación de formulario y alta de usuario
			$form = $pagelet->get_form();
			if ($form->procesar()) {
				$valores = $form->get_datos();

				try {
					$id_se = kernel::sesion()->get('id_se');
					$mail_se = kernel::sesion()->get('mail_se');
					$medio_se = kernel::sesion()->get('medio_se');

					// Se vincula la cuenta externa con la de preinscripción
					preinscripcion::usuario()->vincular_usuario_se($mail_se, $id_se, $medio_se);

					kernel::sesion()->borrar('id_se');
					kernel::sesion()->borrar('mail_se');
					kernel::sesion()->borrar('medio_se');

					$exito = true;
				} catch (error_preinscripcion_login $e) {
					$this->add_error($this->trans('asoc_se.error_clave'));
					$pagelet->set_estado_info(pagelet_asociar_usuario::FORM_ERROR_VALIDACION);
				} catch (ErrorKernel $e) {
					$pagelet->set_estado_info(pagelet_asociar_usuario::FORM_ERROR_VALIDACION);
				}
			} else {
				if ($form->tiene_errores()) {
					$pagelet->set_estado_info(pagelet_asociar_usuario::FORM_ERROR_VALIDACION);
				}
			}
		}

		if ($exito) {
			$mensaje = kernel::traductor()->trans('asoc_se.cuenta_vinculada_ok', array('%1%' => capitalizar($medio_se)));
			kernel::sesion()->set('cuenta_vinculada_ok', $mensaje);
			$this->redirigir();
		} else {
			kernel::renderer()->add($pagelet);
		}
	}

	//--------------------------------------------------------------------------
	//---- Operaciones auxiliares ----------------------------------------------
	//--------------------------------------------------------------------------

	protected function get_login_manager()
	{
		if (!isset($this->login_manager)) {
			$clase = kernel::proyecto()->get_clase_login();
			if (!isset($clase)) {
				throw new ErrorKernel('SESION: no se seteo el manejador de login');
			}
			$this->login_manager = kernel::localizador()->instanciar($clase);
		}
		return $this->login_manager;
	}

	protected function add_error($mensaje, $e = null)
	{
		$mensajes = array();
		$mensajes[] = array(
			preinscripcion::mensaje_cuerpo => $mensaje,
			preinscripcion::mensaje_tipo => preinscripcion::control_error,
		);
		$this->add_var('mensajes', $mensajes);
		if (!is_null($e)) {
			kernel::log()->add_error($e);
		}
	}

	protected function redirigir()
	{
		$this->nuevo_request(kernel::persona()->perfil()->get_parametro('index'));
	}

}
?>