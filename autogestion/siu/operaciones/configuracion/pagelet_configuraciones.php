<?php
namespace siu\operaciones\configuracion;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;
use siu\guarani;
use siu\modelo\entidades\persona;
use siu\modelo\entidades\parametro;
use siu\modelo\_comun\comunes;

class pagelet_configuraciones extends pagelet
{
	const CAMBIO_MAIL_EXITOSO = 'cambio_mail_exito';
    const TOKEN_INCORRECTO = 'token_incorrecto';
    const TOKEN_EXPIRADO = 'token_expirado';
    const TOKEN_OTRA_PERSONA = 'token_otra_persona';

    function ini()
	{
	}
	
	function get_nombre()
	{
		return 'configuraciones';
	}

	function prepare()
	{
		$this->data['checks'] = guarani::persona()->get_configuracion();

        $this->add_var_js('email', $this->data['checks']['email']);
        $this->add_var_js('tiene_algun_email', ($this->data['checks']['email'])? true : false);

		$operacion = kernel::ruteador()->get_id_operacion();        
		$this->data['url_guardar_pass'] = kernel::vinculador()->crear($operacion, 'guardar_pass');
		$this->data['url_guardar_checks'] = kernel::vinculador()->crear($operacion, 'guardar_checks');
		$this->data['url_guardar_perfil_def'] = kernel::vinculador()->crear($operacion, 'guardar_perfil_defecto');
		$this->data['url_guardar_propuesta_inicial'] = kernel::vinculador()->crear($operacion, 'guardar_propuesta_inicial');
		$this->data['es_alumno'] = guarani::persona()->es_alumno();

		$this->add_mensaje_js('mail_token_cambio', kernel::traductor()->trans('configuracion.mensaje.mail_token.cambio'));
		$this->add_mensaje_js('mail_no_concuerda', kernel::traductor()->trans('configuracion.mensaje.mail_no_concuerda'));
		$this->add_mensaje_js('aceptar', kernel::traductor()->trans('aceptar'));
		$this->add_mensaje_js('cancelar', kernel::traductor()->trans('cancelar'));
		$this->add_mensaje_js('revertir', kernel::traductor()->trans('revertir'));
		$this->add_var_js('url_eliminar_token', kernel::vinculador()->crear($operacion, 'eliminar_token'));
		
		$this->add_mensaje_js('password_actual_error', ucfirst(kernel::traductor()->trans('configuracion.label.password_actual_error')));
		$this->add_mensaje_js('passworderror', ucfirst(kernel::traductor()->trans('configuracion.label.password_nuevo_error')));
		$this->add_mensaje_js('password1error', ucfirst(kernel::traductor()->trans('configuracion.label.password_repetir_error')));
        
        switch ($this->estado) {
            case self::TOKEN_INCORRECTO:
            case self::TOKEN_EXPIRADO:
                $this->add_mensaje_js('token_incorrecto_expirado', kernel::traductor()->trans('configuracion.mensaje.token_incorrecto_expirado'));
                break;
            case self::TOKEN_OTRA_PERSONA:
                $this->add_mensaje_js('token_gestionado_otra_persona', kernel::traductor()->trans('configuracion.mensaje.token_gestionado_otra_persona'));
                break;
            case self::CAMBIO_MAIL_EXITOSO:
                $this->add_mensaje_js('cambio_mail_exitoso', kernel::traductor()->trans('configuracion.mensaje.cambio_mail'));
                $this->data['checks']['email_temporal'] = '';
                break;
        }

        $this->data['perfiles'] = guarani::persona()->get_perfiles();
		
		$this->data['perfiles_cant'] = guarani::persona()->get_count_perfiles();
		$tipo_usuario_inicial = guarani::persona()->get_tipo_usuario_inicial();
		$this->data['perfil_sugerido'] = $tipo_usuario_inicial;
	    $this->add_var_js('tipo_usuario_inicial', $tipo_usuario_inicial);
		
		// Ver ticket #34224 (https://redmine.siu.edu.ar/redmine/issues/34224)
		$this->data['propuestas_cant'] = guarani::persona()->get_count_propuestas();
		$this->data['propuestas'] = guarani::persona()->get_lista_propuestas();
		$propuesta_inicial = guarani::persona()->get_propuesta_inicial();
		$this->data['propuesta_inicial'] = $propuesta_inicial;
		$this->add_var_js('propuesta_inicial', $propuesta_inicial);
	}
}