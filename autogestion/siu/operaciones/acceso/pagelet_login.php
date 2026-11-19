<?php

namespace siu\operaciones\acceso;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\entidades\parametro;
use siu\modelo\_comun\comunes;

class pagelet_login extends pagelet {

	function ini() {
		
	}

	function get_nombre() {
		return 'login';
	}

	function prepare() {
		$this->data['url_recuperar'] = kernel::vinculador()->crear('acceso', 'recuperar');

		$lm = kernel::login_manager();

        $metodos_autenticacion_habilitados = kernel::proyecto()->get_metodos_autenticacion_habilitados();
                
		$modelo = array();
		foreach ($lm->get_auth_providers() as $auth) {
            if (in_array($auth->get_id(), $metodos_autenticacion_habilitados)) {
                /** @var $auth AuthenticationProvider */
                $modelo[$auth->get_id()] = $auth->get_modelo();
            }
        }
        $this->data['providers'] = $modelo;

		$this->add_mensaje_js('id_requerido', kernel::traductor()->trans('login.errores.id_requerido'));
		$this->add_mensaje_js('clave_requerida', kernel::traductor()->trans('login.errores.clave_requerida'));

		if (kernel::persona()->perfil()->get_operacion_activa('alta_usuario_cursos')) {
			if (parametro::get_valor(parametro::cur_alta_usuario_cursos_autogestion) == comunes::si) {
				$link = kernel::vinculador()->crear('alta_usuario_cursos');
				$this->data['mensaje_alta_cursos'] = kernel::traductor()->trans('alta_uc.mensaje_acceso', array('%link%' => $link));
			}
		}
        
        // Si la contraseña se recupero con éxito muestro mensaje
        if (kernel::sesion()->esta_seteada('msg_contrasenia_recuparada_exito')) {
            $this->add_mensaje_js('msg_contrasenia_recuparada_exito', kernel::sesion()->get('msg_contrasenia_recuparada_exito'));
            kernel::sesion()->borrar('msg_contrasenia_recuparada_exito');
        }
	}

}