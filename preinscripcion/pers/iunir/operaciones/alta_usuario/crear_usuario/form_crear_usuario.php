<?php

namespace iunir\operaciones\alta_usuario\crear_usuario;

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

class form_crear_usuario extends \siu\operaciones\alta_usuario\crear_usuario\form_crear_usuario
{

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
}