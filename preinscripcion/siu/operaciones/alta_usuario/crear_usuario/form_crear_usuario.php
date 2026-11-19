<?php

namespace siu\operaciones\alta_usuario\crear_usuario;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\errores\error_preinscripcion;
use siu\errores\error_preinscripcion_clave;
use siu\preinscripcion;
use siu\modelo\g3\g3;
use siu\modelo\util;
use siu\modelo\opciones;
use siu\modelo\comunes;

class form_crear_usuario extends form
{
	function get_id_html()
	{
		return 'form_crear_usuario';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('alta_usuario', 'crear');
	}

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

		$html = "<div class='control-group $clase_control_group $clase_error'>";
		$html .= $label;
		$html .= $elemento;
		$html .= $this->ayuda($nombre);
		if ($tiene_error) {
			$html .= "<span class='help-inline'>{$this->error($nombre)}</span>";
		}
		$html .= "</div>";

		return $html;
	}

	protected function generar_definicion()
	{
		if (kernel::sesion()->esta_seteada('alta_se_id')) {
			$e_mail_readonly = true;
		} else {
			$e_mail_readonly = false;
		}

		//---- Datos del usuario -----------------------------------------------
		$this->set_campo('telefono', array(
			form::label => 'Celular',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 20
		));	
	$this->set_campo('pregunta_secreta', array(
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::filtro => validador::TIPO_TEXTO,
			form::multi_options => util::opciones()->get_preguntas_secretas(),
			form::valor_default => opciones::NO_SELECCION,
			form::label => 'pregunta_secreta',
			form::obligatorio => true,
		));	
	$this->set_campo('respuesta_secreta', array(
			form::label => 'respuesta_secreta',
			form::obligatorio => true,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'password'),
			form::ayuda => 'Te recomendamos que tu respuesta secreta sea una sola palabra, que NO contenga información personal como: e-mail, nombre, apellido, DNI, ni fecha de nacimiento.',
			form::largo => 50
		));		
		$this->set_campo('e_mail', array(
			form::label => 'email_principal',
			form::obligatorio => true,
			form::filtro => validador::TIPO_MAIL,
			form::elemento => array('tipo' => 'text'),
			form::readonly => $e_mail_readonly,
			form::largo => 100
		));

		$this->set_campo('e_mail_rep', array(
			form::label => 'email_rep',
			form::obligatorio => true,
			form::filtro => validador::TIPO_MAIL,
			form::elemento => array('tipo' => 'text'),
			form::readonly => $e_mail_readonly,
			form::largo => 100
		));

		$this->set_campo('password', array(
			form::label => 'clave',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'password'),
			form::ayuda => 'Te recomendamos que tu clave posea números, letras, caracteres especiales. Que NO contenga información personal como: e-mail, nombre, apellido, DNI, ni fecha de nacimiento.',
			form::largo => 30
		));

		$this->set_campo('rpassword', array(
			form::label => 'repetir_clave',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'password'),
			form::largo => 30
		));
		/*---- nombre apellido elegido ------------------------------------------------
		$this->set_campo('apellido_elegido', array(
			form::label => 'apellido_elegido',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60
		));*/

		$this->set_campo('nombres_elegido', array(
			form::label => 'nombres_elegido',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::ayuda => kernel::traductor()->trans('nombres_elegido_ayuda'),
			form::largo => 60
		));
		//---- Datos personales ------------------------------------------------
		$this->set_campo('apellido', array(
			form::label => 'apellido',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60
		));

		$this->set_campo('nombres', array(
			form::label => 'nombres',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60
		));

		$this->set_campo('nacionalidad', array(
			form::label => 'nacionalidad',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_nacionalidades(),
			form::valor_default => opciones::NO_SELECCION,
		));

		$this->set_campo('pais_documento', array(
			form::label => 'pais_documento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_paises(),
		));

		$this->set_campo('tipo_documento', array(
			form::label => 'tipo_documento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => false,
			form::multi_options => [],
			form::valor_default => opciones::NO_SELECCION,
		));

		$this->set_campo('nro_documento', array(
			form::label => 'nro_documento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::largo => 20
		));
                
		$this->set_campo('nro_documento_rep', array(
			form::label => 'nro_documento_rep',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::largo => 20
		));
        
        $this->set_campo('sexo', array(
			form::label => 'sexo',
			form::obligatorio => true,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_generos(),
		));
        
        if (kernel::proyecto()->get_mat_validacion_renaper() == comunes::si){
            
            $this->set_campo('nro_tramite_dni', array(
                form::label => 'nro_tramite_dni',
                form::filtro => validador::TIPO_TEXTO,
                form::obligatorio => false,
                form::elemento => array('tipo' => 'text'),
                form::largo => 11
            ));
           
            //---- Datos de nacimiento ---------------------------------------------
            $this->set_campo('fecha_nacimiento', array(
                form::label => 'fecha_nacimiento',
                form::filtro => validador::TIPO_DATE,
                form::filtro_params => array('format' => 'd/m/Y',
                    'allowempty' => true),
                form::clase_css => 'date input-small',
                form::obligatorio => false,
                form::elemento => array('tipo' => 'hidden')
            ));
            //---- Domicilio de procedencia ----------------------------------------
            $this->set_campo('calle_proc', array(
                form::label => 'calle_proc',
                form::filtro => validador::TIPO_TEXTO,
                form::obligatorio => false,
                form::elemento => array('tipo' => 'hidden'),
                form::largo => 50
            ));

            $this->set_campo('numero_proc', array(
                form::label => 'numero_proc',
                form::filtro => validador::TIPO_TEXTO,
                form::obligatorio => false,
                form::elemento => array('tipo' => 'hidden'),
                form::largo => 10,
                form::clase_css => 'input-medium',
            ));

            $this->set_campo('piso_proc', array(
                form::label => 'piso_proc',
                form::filtro => validador::TIPO_TEXTO,
                form::obligatorio => false,
                form::elemento => array('tipo' => 'hidden'),
                form::largo => 3,
                form::clase_css => 'input-medium',
            ));

            $this->set_campo('dpto_proc', array(
                form::label => 'dpto_proc',
                form::filtro => validador::TIPO_TEXTO,
                form::obligatorio => false,
                form::elemento => array('tipo' => 'hidden'),
                form::largo => 5,
                form::clase_css => 'input-medium',
            ));

            $this->set_campo('cp_proc', array(
                form::label => 'cp_proc',
                form::filtro => validador::TIPO_TEXTO,
                form::obligatorio => false,
                form::elemento => array('tipo' => 'hidden'),
                form::readonly => false,
                form::clase_css => 'input-small',
                form::largo => 15
            ));

            $this->set_campo('barrio_proc', array(
                form::label => 'barrio_proc',
                form::filtro => validador::TIPO_TEXTO,
                form::obligatorio => false,
                form::elemento => array('tipo' => 'hidden'),
                form::largo => 60
            ));
            
            $this->set_campo('validado_con_renaper', array(
                form::label => 'validado_con_renaper',
                form::filtro => validador::TIPO_TEXTO,
                form::obligatorio => false,
                form::elemento => array('tipo' => 'hidden'),
                form::largo => 1
            ));
            //Lo defino para guardar el dato de renaper
            $this->set_campo('numero_cuil', array(
                form::label => 'numero_cuil',
                form::filtro => validador::TIPO_INT,
                form::filtro_params => array('allowempty' => true),
                form::obligatorio => false,
                form::elemento => array('tipo' => 'hidden'),
                form::largo => 15
            ));
		}else{
			$this->set_campo('fecha_nacimiento', array(
				form::label => 'fecha_nacimiento',
				form::filtro => validador::TIPO_DATE,
				form::filtro_params => array('format' => 'd/m/Y',
					'allowempty' => true),
				form::clase_css => 'date input-small',
				form::obligatorio => true,
				form::elemento => array('tipo' => 'text')
			));
		}
	}

	//--------------------------------------------------------------------------
	//---- Validaciones --------------------------------------------------------
	//--------------------------------------------------------------------------

	function validar()
	{
		$this->datos['e_mail'] = a_minusculas($this->datos['e_mail']);
		$this->datos['e_mail_rep'] = a_minusculas($this->datos['e_mail_rep']);
        $esta_seteada = kernel::sesion()->esta_seteada('validado_con_renaper');
        if($esta_seteada == comunes::si && kernel::sesion()->get('validado_con_renaper') == comunes::si){
            $apellido = kernel::sesion()->get('renaper_persona')['lastNames'];
            $nombres = kernel::sesion()->get('renaper_persona')['names'];
            $this->set_dato('apellido', $apellido);
            $this->set_dato('nombres', $nombres);
        }
        
		$form_valido = parent::validar();
		$mail_repetido_valido = $this->validar_emails();
		$clave_valida = $this->validar_clave();
		$documento_valido = $this->validar_documento();
        $nro_documento_valido = $this->validar_nro_documento();
        kernel::sesion()->borrar('validado_con_renaper');
        kernel::sesion()->borrar('renaper_persona');  
		return $form_valido && $mail_repetido_valido && $clave_valida && $documento_valido && $nro_documento_valido;
	}

	protected function validar_emails()
	{
		$email_ok = true;

		if ((!empty($this->datos['e_mail'])) && (!empty($this->datos['e_mail_rep']))) {
			$usuario = trim($this->datos['e_mail']);
			$usuario_rep = trim($this->datos['e_mail_rep']);

			if ($usuario != $usuario_rep) {
				// Se valida que las claves sean iguales
				$mensaje = kernel::traductor()->trans('error_emails_no_coinciden');
				$this->agregar_error('e_mail', $mensaje);
				$this->agregar_error('e_mail_rep', $mensaje);
				$email_ok = false;
			}
		}

		return $email_ok;
	}

	protected function validar_clave()
	{
		$clave_valida = true;

		if ((!empty($this->datos['password'])) && (!empty($this->datos['rpassword']))) {
			$clave = trim($this->datos['password']);
			$rclave = trim($this->datos['rpassword']);

			if ($clave != $rclave) {
				// Se valida que las claves sean iguales
				$mensaje = kernel::traductor()->trans('error_clave_no_coinciden');
				$this->agregar_error('password', $mensaje);
				$this->agregar_error('rpassword', $mensaje);
				$clave_valida = false;
			} else {
				if (!empty($this->datos['e_mail'])) {
					$e_mail = $this->datos['e_mail'];
					try {
						// Se realizan el resto de las validaciones de clave
						preinscripcion::usuario()->clave_permitida($clave, $e_mail);
					} catch (error_preinscripcion_clave $e) {
						$clave_valida = false;
						$this->agregar_error('password', $e->get_mensaje());
					} catch (error_preinscripcion $e) {
						$clave_valida = false;
						$this->agregar_error('password', $e->get_mensaje());
					}
				}
			}
		}

		return $clave_valida;
	}

	protected function validar_documento()
	{
		$documento_valido = true;

		if ((!$this->tiene_error('nacionalidad')) && (!$this->tiene_error('pais_documento')) && (!$this->tiene_error('tipo_documento'))) {
			$nacion = $this->datos['nacionalidad'];			
			$paisdoc = $this->datos['pais_documento'];
			$tipodoc = $this->datos['tipo_documento'];
			// Validaciones de País del Documento
			if ($nacion != 2 && $paisdoc != 54) {
				$mensaje = kernel::traductor()->trans('error_pais_doc_1');
				$this->agregar_error('pais_documento', $mensaje);
				$documento_valido = false;
			}// Validaciones de País del Documento$documentos_pais mdp_tipo_documento_pais.pais|| '_' || mdp_tipo_documento.tipo_documento
			$documentos_pais = g3::entidad('tipos_documentos_pais')->get_entidad($paisdoc.'_'.$tipodoc);
			// Validaciones de Tipo de Documento
			if (empty($documentos_pais)){
				$mensaje = kernel::traductor()->trans('error_pais_doc_0');
				$this->agregar_error('pais_documento', $mensaje);
				$this->agregar_error('tipo_documento', $mensaje);
				$documento_valido = false;
			}
			
		}

		return $documento_valido;
	}

	function validar_nro_documento()
	{
		$entidad_tipo_doc = g3::entidad('tipos_documentos_pais')->get_entidad($this->datos['pais_documento']."_".$this->datos['tipo_documento']);
		
		// Validación en base a la expresión regular
		$valido = true;
		$exp_reg= '/' . $entidad_tipo_doc['exp_regular_validacion'] . '/';
		if (!preg_match($exp_reg, $this->datos['nro_documento'])) {
			// Documento inválido según la expresión regular
			$valido = false;
			$this->agregar_error('nro_documento', $entidad_tipo_doc['exp_regular_mensaje']);
		}
		
		switch ($this->datos['tipo_documento']) {
			case comunes::TIPO_DOCUMENTO_DNI:
				// Valida que el DNI sea mayor a 1 millón y menor a 100 millones.
				if (($this->datos['nro_documento'] < comunes::NRO_DOCUMENTO_DNI_DNT_MIN) || ($this->datos['nro_documento'] > comunes::NRO_DOCUMENTO_DNI_DNT_MAX)) {
					$mensaje = kernel::traductor()->trans('nro_documento.dni.error');
					$this->agregar_error('nro_documento', $mensaje);
					$valido = false;
				}
				break;
			case comunes::TIPO_DOCUMENTO_CUIL_CUIT:
				// Valida que el CUIL/CUIT sea valido.
				if (!comunes::validarCUIT($this->datos['nro_documento'])) {
					$mensaje = kernel::traductor()->trans('nro_documento.cuil.error');
					$this->agregar_error('nro_documento', $mensaje);
					$valido = false;
				}
				break;
		}
		
        if ($this->datos['nro_documento'] != $this->datos['nro_documento_rep']) {
				// Se valida que las claves sean iguales
				$mensaje = kernel::traductor()->trans('error_documento_no_coinciden');
				$this->agregar_error('nro_documento', $mensaje);
				$this->agregar_error('nro_documento_rep', $mensaje);
				$valido = false;
		}
		return $valido;
	}
    

}