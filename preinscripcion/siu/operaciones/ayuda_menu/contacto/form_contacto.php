<?php

namespace siu\operaciones\ayuda_menu\contacto;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\guarani;
use siu\modelo\g3\g3;
use siu\modelo\transacciones\usuario_cursos;

//use siu\modelo\entidades\parametro;

class form_contacto extends form
{
	/**
	 * @var \siu\modelo\transacciones\usuario_cursos
	 */
	protected $modelo;

	const FILAS_TEXTAREA = 5;
	const LARGO_TEXTAREA = 2500;

	function __construct()
	{
		
	}

	function get_id_html()
	{
		return 'form_contacto';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('ayuda_menu', 'carga_datos_contacto');
	}

	protected function generar_definicion()
	{
		//---- Datos personales ------------------------------------------------

		$this->set_campo('nombres', [
			form::label => 'ayuda_menu.nombres',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60,
		]);

		$this->set_campo('email', [
			form::label => 'ayuda_menu.email',
			form::obligatorio => true,
			form::filtro => validador::TIPO_MAIL,
			form::elemento => array('tipo' => 'text'),
			form::largo => 100,
		]);

		$this->set_campo('nro_documento', [
			form::label => 'ayuda_menu.documento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::largo => 20,
			form::ayuda => kernel::traductor()->trans('ayuda_menu.ayuda_dni')
		]);

		$this->set_campo('mensaje', [
			form::label => 'ayuda_menu.mensaje',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => ['tipo' => 'textarea'],
			form::rows => static::FILAS_TEXTAREA,
			form::largo => static::LARGO_TEXTAREA,
			form::clase_css => 'js_auditiva_forma_habitual_comunicacion',
		]);
	}

	//--------------------------------------------------------------------------
	//---- Generación de html --------------------------------------------------
	//--------------------------------------------------------------------------

	function control_group($nombre, $clase_control_group = '')
	{
		if (!$this->check_activo($nombre)) {
			return '';
		}
		if ($this->get_elemento_html_tipo($nombre) == 'hidden') {
			return $this->elemento($nombre);
		}

		$label = $this->label($nombre);
		if ($this->get_obligatorio($nombre)) {
			$label = str_replace('class="optional"', 'class="label-obligatorio"', $label);
			$label = str_replace('</label>', ' (*)</label>', $label);
		}

		$elemento = $this->elemento($nombre);
		$tiene_error = $this->tiene_error($nombre);
		$clase_error = ($tiene_error) ? 'error' : '';


		$html = "<div class='control-group $clase_control_group $clase_error' id=form_id_$nombre>";
		$html .= $label;
		$html .= $elemento;
		$html .= $this->ayuda($nombre);
		if ($tiene_error) {
			$html .= "<span class='help-inline'>{$this->error($nombre)}</span>";
		}
		$html .= "</div>";

		return $html;
	}

	//--------------------------------------------------------------------------
	//---- Validaciones --------------------------------------------------------
	//--------------------------------------------------------------------------

	function validar()
	{
		// Si la carga de datos es correcta, se procede a realizar las validaciones particulares
		//$usuario_valido = $this->validar_usuario();
		$mail_valido = $this->validar_email();
		$nro_documento_valido = $this->validar_nro_documento();

		return parent::validar() && $mail_valido && $nro_documento_valido;
	}

	/**
	 * Se valida que el mail ingresado y su repetición sean idénticos
	 * 
	 * @return boolean
	 */
	protected function validar_email()
	{
		$email_ok = true;

		if ((empty($this->datos['email']))) {
			$email = trim($this->datos['email']);
			$mensaje = kernel::traductor()->trans('ayuda_menu.error_emails');
			$this->agregar_error('email', $mensaje);
			$email_ok = false;
		}

		return $email_ok;
	}

	function validar_nro_documento()
	{
		$tipo_doc = 0;
		$nro_doc = $this->datos['nro_documento'];
		$tipos_documento = g3::entidad('tipos_documentos')->get_entidad($tipo_doc);
		$expresion = $tipos_documento['exp_regular_validacion'];
		$mensaje = $tipos_documento['exp_regular_mensaje'];

		$es_valido = true;

		$patt = '/' . $expresion . '/';
		if (!preg_match($patt, $nro_doc)) {
			//Si el documento es invalido
			$es_valido = false;
			$this->agregar_error('nro_documento', $mensaje);
		}
		return $es_valido;
	}

}