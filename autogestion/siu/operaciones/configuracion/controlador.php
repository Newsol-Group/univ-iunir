<?php
namespace siu\operaciones\configuracion;

use SIU\Chulupi\acceso\AuthenticationProvider;
use SIU\Chulupi\ErrorKernel;
use SIU\Chulupi\kernel;
use SIU\Chulupi\nucleo\FinalizadorRequest;
use SIU\Chulupi\util\validador;
use siu\guarani;
use SIU\Chulupi\util\mail;
use siu\extension_kernel\controlador_g3w2;
use siu\modelo\datos\catalogo;
use siu\errores\error_guarani_procesar_renglones;
use \siu\modelo\entidades\persona;
use siu\errores\error_guarani;
use siu\errores\error_guarani_usuario;

class controlador extends controlador_g3w2
{
	function modelo()
	{

	}
	
	function accion__index()
	{
		
	}


	function accion__guardar_pass()
	{
		$usuario = guarani::persona()->get_identificacion();
		$actual_pass = kernel::request()->getPost('password_actual');
		$nuevo_pass = kernel::request()->getPost('password');
		try {
			$respuesta = guarani::persona()->actualizar_password($usuario, $actual_pass, $nuevo_pass);
			$respuesta['cont'] = $respuesta['cont'];
		}
		catch (error_guarani_procesar_renglones $e) {
			$respuesta['cod'] = -10;
			$respuesta['cont'] = "";
			foreach ($e->get_renglones() as $reglon) {
				$respuesta['cont'] .= $reglon['msg']."<br>";
			}
		}
		$this->render_ajax($respuesta['cont'], $respuesta);
//		$this->render_ajax($respuesta['cont']);
	}

	function accion__guardar_checks()
	{
		$persona = guarani::persona()->get_id();
        // limpiar cache
        $parametros = array('persona' => $persona);
        catalogo::limpiar_cache('persona', 'configuracion', $parametros);
		$conf = guarani::persona()->get_configuracion();
		$recibe_mail_mensajes = $this->validate_param('recibe_mail_mensajes', 'post', validador::TIPO_INT,array('default' => '0'));
        $email = strtolower($this->validate_param('correo', 'post', validador::TIPO_MAIL));
		
		$respuesta = array('cont' => 'Guardado', 'cod' => 1);
		if($email && ($email != $conf['email'])) {
			$token = persona::refresh_token($persona);
			persona::update_mail_temporal($persona, $email);
			$this->enviar_cambio_mail($email, $token);
            $alert_solicitud_confirmacion_email_enviada = kernel::traductor()->trans('configuracion.mensaje.mail_temporal', array('%mail%' => $email));
			$respuesta = array(
				'cod' => 2, 
				'mail_old' => $conf['email'],
				'mail_new' => $email,
                'alert_solicitud_confirmacion_email_enviada' => $alert_solicitud_confirmacion_email_enviada
			);
			
			if (!empty($conf['email'])){
				$email = $conf['email'];
			}			
		}

        //Si hubo cambios en los checkboxes guardo dichos cambios
        if($recibe_mail_mensajes != $conf['recibe_mail_mensajes']){
            guarani::persona()->actualizar_configuracion_checks($persona, $recibe_mail_mensajes);
        }

		$this->render_ajax('Guardado', $respuesta);
	}
	
	function accion__cambiar_mail() 
	{
		$persona = guarani::persona()->get_id();
		$conf = guarani::persona()->get_configuracion();
		$token = $this->validate_param('t', 'get', validador::TIPO_TEXTO);
        $pagelet = $this->vista()->pagelet('configuraciones');
        
		try {
			$datos_persona = persona::get_persona_from_token($token);
            
            // Si el token expiro le muestro un mensaje
            if ($datos_persona['token_expirado']) {
                $pagelet->set_estado_info(pagelet_configuraciones::TOKEN_EXPIRADO);
            } else if($persona != $datos_persona['persona']) {
                $pagelet->set_estado_info(pagelet_configuraciones::TOKEN_OTRA_PERSONA);
            } else {// Sino le muestro mensaje de exito
                
                $mail = persona::get_mail_from_token($token);
                persona::blanquear_mail_temporal($persona);
                guarani::persona()->actualizar_configuracion_email($persona, $mail);
                persona::limpiar_token($persona);

                $pagelet->set_estado_info(pagelet_configuraciones::CAMBIO_MAIL_EXITOSO);
            }
            
        } catch (error_guarani $e) {
            $pagelet->set_estado_info(pagelet_configuraciones::TOKEN_INCORRECTO);
		}
	}

	function accion__cuentas_externas(){
		kernel::renderer()->add($this->vista()->pagelet('cuentas_externas'));
	}

	function accion__vincular_cuenta_externa(){
		$proveedor = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$manager = $this->get_administrador_cuentas_externas();
		$url_procesar = kernel::vinculador()->crear('configuracion', 'vincular_cuenta_externa', $proveedor);

		try{
			$datos = $manager->obtener_datos_cuenta($proveedor, $url_procesar);
			$datos['persona'] = guarani::persona()->get_id();

			if($exito = catalogo::consultar('persona', 'vincular_login_externo', $datos)){
				$mje = array('f' =>$proveedor, 'vinculada' => true) ;
			}else {
				$mje = array('error' => 'No se pudo vincular la cuenta!') ;
			}
		}catch (ErrorKernel $e){
			kernel::log()->add_error($e);
			$mje = array('error' => 'Error al procesar el pedido!');//que paso?
		}
		$mje = json_encode($mje);

		echo "
			<script>
				window.opener.returnPopup($mje);
				window.close();
			</script>
		";
		throw new FinalizadorRequest();
	}


	function accion__desvincular_cuenta_externa()
	{
		$proveedor = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		if($exito = catalogo::consultar('persona', 'desvincular_login_externo', array(
			'persona' => guarani::persona()->get_id(), 'proveedor' => $proveedor))){
			$respuesta = array('estado' => 'ok', 'proveedor' => $proveedor) ;
		}else {
			$respuesta = array('estado' => 'error', 'error' => 'No se pudo remover la cuenta') ; //no la pongo en mjes porque no debería pasar
		}
		$this->render_ajax('Guardado', $respuesta);
	}


	function accion__guardar_perfil_defecto(){
		$perfil_default = $this->validate_param('perfil_defecto', 'post', validador::TIPO_TEXTO);
		$parametros['persona'] = guarani::persona()->get_id();
		$parametros['perfil_inicial'] = $perfil_default;
		catalogo::consultar('persona', 'set_perfil_defecto', $parametros);
		// Se limpia la cache
		catalogo::limpiar_cache('persona', 'get_tipo_usuario_inicial', ['persona' => $parametros['persona']]);
	}	

	function accion__guardar_propuesta_inicial(){
		$propuesta_inicial = $this->validate_param('propuesta_inicial', 'post', validador::TIPO_INT);
		$parametros['persona'] = guarani::persona()->get_id();
		$parametros['propuesta'] = $propuesta_inicial;
		catalogo::consultar('persona', 'registrar_propuesta_inicial', $parametros);
		// Se limpia la cache
		catalogo::limpiar_cache('persona', 'get_propuesta_inicial', ['persona' => $parametros['persona']]);
	}
	
	/**
	 * @return AuthenticationProvider
	 */
	public function get_administrador_cuentas_externas(){
		$lm = kernel::login_manager();

		foreach ($lm->get_auth_providers() as $auth){
			if($auth->get_id() == 'cuenta_ext'){
				return $auth;
			}
		}
		return null;

	}


	protected function enviar_cambio_mail($dir_mail, $token)
	{
		$asunto = kernel::traductor()->trans('asunto_cambio_mail_notificaciones');
		$tpl = kernel::load_template('configuraciones/mail.twig');
		$link = kernel::vinculador()->crear('configuracion', 'cambiar_mail', array(
			't' => $token
		));
		$cuerpo = $tpl->render(array(
			'mail' => $dir_mail,
			'link' => $link
		));
		
		$mail = new mail($dir_mail, $asunto, $cuerpo);
		$mail->set_html(true);
		
		$mail->enviar();
	}
}