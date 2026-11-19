<?php

namespace siu\operaciones\alta_usuario;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\mail;
use SIU\Chulupi\ErrorKernelValidacion as error_kernel_validacion;
use SIU\Chulupi\ErrorKernelDb as error_kernel_db;
use SIU\Chulupi\nucleo\sesion;
use siu\extension_kernel\controlador_preinscripcion;
use siu\preinscripcion;
use siu\modelo\util;
use siu\modelo\g3\g3;
use siu\errores\error_preinscripcion_alumno_g3;
use siu\errores\error_preinscripcion_persona_g3;
use siu\modelo\comunes;
use siu\modelo\ws_renaper;

class controlador extends controlador_preinscripcion
{ 
	/**
	 * 
	 * @return null
	 */
	function modelo()
	{
		return null;
	}

	function ini()
	{
		
	}

	function accion__index()
	{
		$this->vista()->set_pagelet('crear_usuario');
		$op_defecto = kernel::persona()->perfil()->get_parametro('index');

		if (kernel::sesion()->abierta()) {
			$this->nuevo_request($op_defecto);
		}
	}

	function accion__crear()
	{
		$exito = false;
		$pagelet = $this->vista()->set_pagelet('crear_usuario');

		if (kernel::request()->isPost()) {
			
			try {
				// Chequeo de CSRF
				$this->check_csrf();
			} catch (\Exception $e) {
				$this->add_error($this->trans('error_csrf'));
				$pagelet->set_estado_info(pagelet_crear_usuario::FORM_ERROR_CSRF);
				return;
			}
				
			if ($this->usa_captcha()) {
				// Chequeo de recaptcha
				if (!static::check_recaptcha2()) {
					$this->add_error($this->trans('error_recaptcha'));
					$pagelet->set_estado_info(pagelet_crear_usuario::FORM_ERROR_RECAPTCHA);
					return;
				}
			}

			// Validación de formulario y alta de usuario
			$form = $pagelet->get_form();
			if ($form->procesar()) {

				$valores = $form->get_datos();
				$valores['duplicada'] = comunes::no;
				//existe el documento en preinscripcion
				$datos_usuario = preinscripcion::usuario()->documento_no_procesado($valores['pais_documento'],$valores['tipo_documento'], $valores['nro_documento']);
				//controles en gestion
				try {	

				//si ingresa el mismo mail que en al inscripcion lo dejo continuar
				//if (!$valores['usuario']==$datos_usuario['usuario']){
				$this->controlar_email($valores['e_mail']);
				//}
				if (!empty($datos_usuario)) {
					// Ya existe el documento en la base
					$pagelet = $this->vista()->set_pagelet('confirm_doc_existente');
					kernel::sesion()->set('__doc_ex__datos_usuario', $datos_usuario);
					kernel::sesion()->set('__doc_ex__valores_form', $valores);
					kernel::sesion()->set('__doc_ex__datos_ayuda', kernel::proyecto()->get_parametro('datos_ayuda'));
				}else {

					try {
						$this->controlar_perfil_valido_g3($valores);
						// Verificar si el documento pertenece a un alumno de G3
						$this->controlar_alumno_g3($valores);
						
						if (kernel::sesion()->esta_seteada('alta_se_medio')) {
							$medio_se = kernel::sesion()->get('alta_se_medio');
							$valores[$medio_se] = kernel::sesion()->get('alta_se_id');
						} else {
							$medio_se = null;
						}

						$token = preinscripcion::usuario()->nuevo($valores, $medio_se);
						$email = $valores['e_mail'];

						$datos_recuperacion = array(
							'e_mail' => $email,
							'codigo_validacion' => $token
						);
						kernel::sesion()->set('__datos_reenvio_mail__', $datos_recuperacion);

						$this->enviar_mail_crear_usuario($email, $token);
						$pagelet->set_estado_info(pagelet_crear_usuario::MAIL_ENVIADO);
						$this->add_var('email', $email);

						if (kernel::sesion()->esta_seteada('id_facebook')) {
							kernel::sesion()->borrar('id_facebook');
						}
						if (kernel::sesion()->esta_seteada('mail_facebook')) {
							kernel::sesion()->borrar('mail_facebook');
						}

						$exito = true;
					} catch (error_preinscripcion_alumno_g3 $e) {
						$this->add_error($e->get_mensaje(), $e);
						$pagelet->set_estado_info(pagelet_crear_usuario::FORM_ERROR_VALIDACION);
					}catch (error_preinscripcion_persona_g3 $e) {
						$this->add_error($e->get_mensaje(), $e);
						$pagelet->set_estado_info(pagelet_crear_usuario::FORM_ERROR_VALIDACION);
					} catch (error_kernel_validacion $e) {
						$this->add_error($this->trans('login_error_formato_mail'), $e);
						$this->add_var('nombre_usuario', $email_raw);
						$pagelet->set_estado_info(pagelet_crear_usuario::FORM_ERROR_VALIDACION);
					} catch (error_kernel_db $e) {
						if ($e->get_sqlstate() == 23505) {
							$this->add_error($this->trans('error_email_repetido'), $e);
						} else {
							$this->add_error($this->trans('error_alta'), $e);
						}
						$pagelet->set_estado_info(pagelet_crear_usuario::FORM_ERROR_VALIDACION);
					}
				}
				} catch (error_kernel_validacion $e){
					$this->add_error($e->get_mensaje(), $e);
						$pagelet->set_estado_info(pagelet_crear_usuario::FORM_ERROR_VALIDACION);
				}

			} else {
				if ($form->tiene_errores()) {
					$this->add_error($this->trans('alta_error'));
					$pagelet->set_estado_info(pagelet_crear_usuario::FORM_ERROR_VALIDACION);
				}
			}
		}

		if ($exito) {
			kernel::sesion()->set('mensaje_alta_iniciada', 'mensaje_alta_iniciada');
			$this->redirigir();
		} else {
			kernel::renderer()->add($pagelet);
		}
	}
	
	function accion__reenvio_mail()
	{
		if (!kernel::sesion()->esta_seteada('__doc_ex__datos_usuario')) {
			die();
		}
		$datos_usuario = kernel::sesion()->get('__doc_ex__datos_usuario');

		$email = $datos_usuario['e_mail'];
		$token = $datos_usuario['codigo_validacion'];
		$this->enviar_mail_crear_usuario($email, $token);
                kernel::sesion()->set('mensaje_alta_iniciada', 'mensaje_recuperacion');
		$this->redirigir();
	}

	function accion__continuar_preinscripcion()
	{
		if (!kernel::sesion()->esta_seteada('__doc_ex__datos_usuario')) {
			die();
		}
		$valores = kernel::sesion()->get('__doc_ex__valores_form');
		kernel::sesion()->borrar('__doc_ex__valores_form');
		kernel::sesion()->borrar('__doc_ex__datos_usuario');

		$exito = false;
		$pagelet = $this->vista()->set_pagelet('crear_usuario');

		try {
			if (kernel::sesion()->esta_seteada('alta_se_medio')) {
				$medio_se = kernel::sesion()->get('alta_se_medio');
				$valores[$medio_se] = kernel::sesion()->get('alta_se_id');
			} else {
				$medio_se = null;
			}
			//marco como duplicada la preinscripcion
			$valores['duplicada'] = comunes::si;
			$token = preinscripcion::usuario()->nuevo($valores, $medio_se);
			$email = $valores['e_mail'];

			$this->enviar_mail_crear_usuario($email, $token);
			$pagelet->set_estado_info(pagelet_crear_usuario::MAIL_ENVIADO);
			$this->add_var('email', $email);

			if (kernel::sesion()->esta_seteada('id_facebook')) {
				kernel::sesion()->borrar('id_facebook');
			}
			if (kernel::sesion()->esta_seteada('mail_facebook')) {
				kernel::sesion()->borrar('mail_facebook');
			}

			$exito = true;
		} catch (error_kernel_validacion $e) {
			$this->add_error($this->trans('login_error_formato_mail'), $e);
			$this->add_var('nombre_usuario', $email_raw);
			$pagelet->set_estado_info(pagelet_crear_usuario::FORM_ERROR_VALIDACION);
		} catch (error_kernel_db $e) {
			if ($e->get_sqlstate() == 23505) {
				$this->add_error($this->trans('error_email_repetido'), $e);
			} else {
				$this->add_error($this->trans('error_alta'), $e);
			}
			$pagelet->set_estado_info(pagelet_crear_usuario::FORM_ERROR_VALIDACION);
		}

		if ($exito) {
                        kernel::sesion()->set('mensaje_alta_iniciada', 'mensaje_alta_iniciada');
			$this->redirigir();
		} else {
			kernel::renderer()->add($pagelet);
		}
	}

	function accion__datos_tipo_documento()
	{
		$pais = $this->get_param('pais', 'post');
		$tipo_documento = $this->get_param('tipo_documento', 'post');
		$condicion="mdp_tipo_documento_pais.pais = '".$pais."' "; 
		(empty($tipo_documento))?$condicion.="": $condicion.= " AND mdp_tipo_documento_pais.tipo_documento ='".$tipo_documento."'";
		$entidad_pais_tipo_doc = g3::entidad('tipos_documentos_pais')->get_opciones_pais($condicion,true);
		$this->render_raw_json($entidad_pais_tipo_doc);
	}
    
    function accion__datos_nro_tramite_dni()
	{
        //Cargo los datos en los inputs
		$nro_tramite_dni = $this->get_param('nro_tramite_dni', 'post');        
        $nro_documento = $this->get_param('nro_documento', 'post');
		$genero = $this->get_param('genero', 'post');
        switch ($genero) {
            case 1: $genero = 'M';
                break;
            case 2: $genero = 'F';
                break;
            case 3: $genero = 'X';
                break;

            default: $genero = '';
                break;
        }		
        
        try{
            $api_parametros = $this->get_parametros_api();
            $apiKey = $api_parametros['renaper']['parametros']['apiKey_personData'];
            $url= $api_parametros['renaper']['parametros']['base_uri'];
            $url_header = $api_parametros['renaper']['parametros']['url_header'];
            $content_type='application/json';
    		$ws = new ws_renaper($apiKey, $content_type, $url, $url_header);
            
            $response = $ws->post_cliente_rest($nro_documento,$genero,$nro_tramite_dni);
            $res = json_decode($response,true);
            
            if(!empty($res['person']) && $res['person'] != ''){
                $res = json_decode($res['person'],true);
                kernel::sesion()->set('renaper_persona', $res);                
                kernel::sesion()->set('validado_con_renaper', comunes::si);
            }else{
                kernel::sesion()->set('validado_con_renaper', comunes::no);
            }
            
    		$this->render_raw_json($response);
            
        } catch (error_preinscripcion_alumno_g3 $e) {
            $this->add_error($e->get_mensaje(), $e);
            $pagelet->set_estado_info(pagelet_crear_usuario::FORM_ERROR_VALIDACION);
            $this->render_raw_json($e->get_mensaje());
        }
        
	}
        
    function get_parametros_api(){
        $valor = '';
        $pathInstalacion = realpath(__DIR__.'/../../../../instalacion');
        $WebConf = include "$pathInstalacion/servicios_web_config.php";         
        $valor = array_key_exists('renaper',$WebConf['consumidos']);
        if($valor){
            $valor = array_search('renaper', array_keys($WebConf['consumidos']));          
            $valor = array_slice($WebConf['consumidos'], $valor,1);            
        }
        
        return $valor;
    }
	
	function accion__datos_tipo_documento_validar()
	{
		$tipo_documento = $this->get_param('tipo_documento', 'post');
		$pais = $this->get_param('pais', 'post');
		$nro_documento = $this->get_param('nro_documento', 'post');
		
		$entidad_tipo_doc = g3::entidad('tipos_documentos_pais')->get_entidad($pais."_".$tipo_documento);
		
		$entidad_tipo_doc['mensaje_validacion_cuil'] = '';
		// Si el tipo de documento es CUIL/CUIT y falla la validación 
		if (($tipo_documento == comunes::TIPO_DOCUMENTO_CUIL_CUIT) && (!comunes::validarCUIT($nro_documento))) {
			$entidad_tipo_doc['mensaje_validacion_cuil'] = kernel::traductor()->trans('nro_documento.cuil.error');
		}
		
		$this->render_raw_json($entidad_tipo_doc);
	}

	//--------------------------------------------------------------------------
	//---- Operaciones auxiliares ----------------------------------------------
	//--------------------------------------------------------------------------

	protected function controlar_email($valores)
	{
		if (!empty(preinscripcion::usuario()->get_datos_usuario($valores))) {		
			$mensaje = kernel::traductor()->trans('alta.error.email_duplicado');
			throw new error_kernel_validacion($mensaje);
		}
	}
	protected function controlar_alumno_g3($valores)
	{	
		$datos_ayuda = kernel::proyecto()->get_parametro('datos_ayuda');
		if (g3::personas()->es_alumno($valores['pais_documento'], $valores['tipo_documento'], $valores['nro_documento'])) {
			$mensaje = kernel::traductor()->trans('alta.error.alumno_g3');
			$mensaje .= kernel::traductor()->trans('alta.error.alumno_g3_ayuda',['%link%' => $datos_ayuda['datos_ayuda_url_ayuda']] );
		
			throw new error_preinscripcion_alumno_g3($mensaje);
		}
	}

	protected function controlar_perfil_valido_g3($valores)
	{
		if (g3::personas()->existe_persona_en_base($valores['pais_documento'], $valores['tipo_documento'], $valores['nro_documento'])) {
			$mensaje = kernel::traductor()->trans('alta.error.perfil_valido_g3');
			throw new error_preinscripcion_alumno_g3($mensaje);
		}
	}
	
	protected function controlar_docente_g3($valores)
	{
		if (g3::personas()->es_docente($valores['pais_documento'], $valores['tipo_documento'], $valores['nro_documento'])) {
			$mensaje = kernel::traductor()->trans('alta.error.docente_g3');
			throw new error_preinscripcion_alumno_g3($mensaje);
		}
	}
	
	protected function controlar_persona_g3($valores)
	{
		if (g3::personas()->existe_persona_enbase($valores['pais_documento'], $valores['tipo_documento'], $valores['nro_documento'])) {
			$mensaje = kernel::traductor()->trans('alta.error.persona_g3');
			throw new error_preinscripcion_persona_g3($mensaje);
		}
	}
	
	protected function enviar_mail_crear_usuario($dir_mail, $token)
	{
		$datos = util::consultas()->get_datos_basicos_cuenta($dir_mail);

		$asunto = $this->trans('asunto_email_crear_usuario');
		$tpl = kernel::load_template('crear_usuario/mail_crear_usuario.twig');
		$link = kernel::vinculador()->crear('acceso', 'login', array(
			'auth' => 'token',
			't' => $token
		));
		$cuerpo = $tpl->render(array(
			'mail' => $dir_mail,
			'link' => $link,
			'apellido' => $datos['apellido'],
			'nombres' => $datos['nombres'],
			'nacionalidad' => $datos['nacionalidad_desc'],
			'pais_documento' => $datos['pais_documento_desc'],
			'tipo_documento' => $datos['tipo_documento_desc'],
			'nro_documento' => $datos['nro_documento'],
			'id_preinscripcion' => $datos['id_preinscripcion'],
		));

		$mail = new mail($dir_mail, $asunto, $cuerpo, null, '', preinscripcion::charset);
		$mail->set_html(true);

		$mail->enviar();
	}

	protected function get_usuario_sin_validar()
	{
		return substr($this->get_post('e_mail'), 0, 100);
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

	//--------------------------------------------------------------------------
	//---- Cascadas ------------------------------------------------------------
	//--------------------------------------------------------------------------

	function accion__cascada_nacionalidad()
	{
		$nacionalidad = $this->get_param('nacionalidad', 'post');
		if ($nacionalidad == comunes::NACIONALIDAD_EXTRANJERO) {
			$datos = util::opciones()->get_paises();
		} else {
			$datos = array('54' => 'Argentina');
		}
		$this->render_raw_json($datos);
	}

	function accion__tipo_documento()
	{
		$nacionalidad = $this->get_param('nacionalidad', 'post');
		$pais = $this->get_param('pais', 'post');
		if ($nacionalidad == comunes::NACIONALIDAD_EXTRANJERO) {
			$condicion = "pais <> '54'";
			$datos = util::opciones()->get_paises(null, $condicion);
		} else {
			$datos = array('54' => 'Argentina');
		}
		$this->render_raw_json($datos);
	}
	
}