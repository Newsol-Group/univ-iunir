<?php
class ci_recuperar_contrasenia extends guarani_pers_ci
{
	protected $s__datos;
	protected $s__token;
	protected $msj_confirmacion_email;
	protected $msj_confirmacion_contrasenia;

	function evt__form_recuperar__modificacion($datos)
	{
		$this->s__datos = $datos;
	}

	function ini()
	{
		$token = toba::memoria()->get_parametro('token');
		if ($token) {
			$this->s__token = $token;
			$this->set_pantalla('pant_contrasenia');
		}
	}

	function evt__form_contrasenia__procesar($datos)
	{
		if (!$datos["captcha"]) {
			//Si el captcha es incorrecto
			$mensaje = guarani_mensajes::get('800_codigo_seguridad_invalido');
			throw guarani::error($mensaje);
		}
		unset($datos["captcha"]);
		$datos['token'] = $this->s__token;
		$this->cn()->actualizar_contrasenia($datos);
		unset($this->s__token);
		$this->msj_confirmacion_contrasenia = $this->get_mensaje('800_contrasenia_restablecida_exitosamente');
		$this->set_pantalla('pant_mensaje_bis');
	}

	protected function validar_nro_documento($tipo_documento, $nro_documento)
	{
		$tipos = persona::get_datos_tipo_documento();

		if (trim($nro_documento) == "") {
			$msg = guarani_mensajes::get('800_error_nro_documento_requerido');
			throw guarani::error($msg);
		}
		$patt = '/' . $tipos[$tipo_documento]['exp_regular'] . '/';
		$msg = $tipos[$tipo_documento]['mensaje'];

		if (!preg_match($patt, $nro_documento)) {
			// Documento es invalido
			throw guarani::error($msg);
		}
	}

	function evt__procesar()
	{
		if (!$this->s__datos["captcha"]) {
			// El captcha es incorrecto
			$mensaje = guarani_mensajes::get('800_codigo_seguridad_invalido');
			throw guarani::error($mensaje);
		}
		unset($this->s__datos["captcha"]);

		$this->validar_nro_documento($this->s__datos["tipo_documento"], $this->s__datos["nro_documento"]);		
		$email = $this->cn()->recuperar_contrasenia($this->s__datos);
		
		// Se ofusca el email
		$email = persona::obfuscate_mail($email);
		unset($this->s__datos);
		$this->msj_confirmacion_email = $this->get_mensaje('800_msj_email_recuperar_contrasenia_enviado', array($email));
		$this->set_pantalla('pant_mensaje');
	}

	function conf__form_recuperar(guarani_ei_formulario $formulario)
	{
		$formulario->set_datos($this->s__datos);
	}

	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		print "$('#mensaje-confirmacion-envio-email-recuperacion').html('{$this->msj_confirmacion_email}');";
		print "$('#mensaje-confirmacion-contrasenia-reestablecida').html('{$this->msj_confirmacion_contrasenia}');";
	}

}