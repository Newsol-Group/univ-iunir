<?php

namespace siu\operaciones\alta_usuario_cursos;

use moodle;
use moodle_nucleo;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;
use siu\extension_kernel\controlador_g3w2;
use siu\guarani;
use SIU\Chulupi\ErrorKernel;
use SIU\Chulupi\util\mail;
use SIU\Chulupi\util\validador;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\parametro;
use siu\modelo\_comun\comunes;
use SIU\Chulupi\nucleo\FinalizadorRequest;
use siu\modelo\entidades\ws_renaper;

class controlador extends controlador_g3w2
{
	const mensaje_tipo = 'mtipo';
	const mensaje_cuerpo = 'mcuerpo';
	const datos_persona = '__alta.uc_valores__';
	const temp_archivos = '__alta.uc_requisitos_temp__';
	const usuario = '__alta.uc_usuario__';
	const validado_con_renaper = '__alta.uc_validado_con_renaper__';
	const renaper_persona = '__alta.uc_renaper_persona__';

	/**
	 * @return \siu\modelo\transacciones\usuario_cursos
	 */
	function modelo()
	{
		return guarani::usuario_cursos();
	}

	function ini()
	{
		
	}

	//--------------------------------------------------------------------------
	//---- Acciones ------------------------------------------------------------
	//--------------------------------------------------------------------------

	function accion__index()
	{
                if (parametro::get_valor(parametro::cur_alta_usuario_cursos_autogestion) == comunes::si) {
                    $this->vista()->set_pagelet('crear_usuario');
                }
                else {
                    $this->nuevo_request('acceso', 'index');
                }
		
	}

	function accion__carga_datos_personales()
	{
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
					// Formateos
					$valores['nombres'] = $this->modelo()->formatear_nombre($valores['nombres']);
					$valores['apellido'] = $this->modelo()->formatear_nombre($valores['apellido']);
					$valores['fecha_nacimiento'] = convertir_fecha_formato_base($valores['fecha_nacimiento']);

					$this->set_datos_persona($valores);

					// Si la persona ya existe, se muestra un mensaje y no se la deja avanzar
					if ($this->modelo()->info__existe_persona($valores)) {
						// El documento ya está asociado a una persona existente en el sistema -> mensaje de error
						$this->vista()->set_template('template_msj');
						$link = kernel::vinculador()->crear('acceso', 'index');
						$this->vista()->set_contexto(array(
							'mensaje' => kernel::traductor()->trans('alta_uc.msj_info_persona_existe', array('%1%' => $link)),
						));
					} else {
						$propuesta = $valores['propuesta'];
						if ($this->modelo()->carga_documentacion($propuesta)) {
							$requisitos = $this->modelo()->info__requisitos_cursos($propuesta);
							if (!empty($requisitos)) {
								// Se procede a la carga de documentación
								$this->nuevo_request('alta_usuario_cursos', 'documentacion');
							} else {
								// Si la propuesta no tiene definidos requisitos, se procede al alta de usuario
								$this->modelo()->evt__nueva_persona($valores);
								$this->post_creacion($valores);
							}
						} else {
							// Si no carga documentación, se procede al alta de usuario
							$this->modelo()->evt__nueva_persona($valores);
							$this->post_creacion($valores);
						}
					}
				} else {
					if ($form->tiene_errores()) {
						$this->add_error($this->trans('alta_uc.alta_error'));
						$pagelet->set_estado_info(pagelet_crear_usuario::FORM_ERROR_VALIDACION);
						kernel::renderer()->add($pagelet);
					}
				}
			}
	}
    
    //Este metodo me permite actualizar los datos censales de la persona que se validó en renaper
    function post_nuevo_usuario_curso($valores)
    {
//        $datos = kernel::sesion()->get(self::datos_persona);
        $parametro = [];
        $parametro['persona'] = kernel::sesion()->get('id_persona');
        $dato_censal = \toba::consulta_php('co_datos_censales')->get_id_dato_censal($parametro['persona']);
        if (empty($dato_censal['dato_censal'])) {
            $dato_censal = catalogo::consultar('datos_censales', 'insertar_dato_censal', $parametro); 
            //Agrego estos valores censales
            $parametros = array(
                array('campo' => 'periodo_lectivo_calle', 'valor' => $valores['periodo_lectivo_calle']),
                array('campo' => 'periodo_lectivo_numero', 'valor' => $valores['periodo_lectivo_numero']),
                array('campo' => 'periodo_lectivo_piso', 'valor' => $valores['periodo_lectivo_piso']),
                array('campo' => 'periodo_lectivo_departamento', 'valor' => $valores['periodo_lectivo_departamento']),
                array('campo' => 'periodo_lectivo_codigo_postal', 'valor' => $valores['periodo_lectivo_codigo_postal']),
                array('campo' => 'periodo_lectivo_barrio', 'valor' => $valores['periodo_lectivo_barrio']),
            );
            \guarani::act('act_personas')->insertar_datos_personales($dato_censal, $parametros);
        }else {
            //Agrego estos valores censales
            $parametros = array(
                array('campo' => 'periodo_lectivo_calle', 'valor' => $valores['periodo_lectivo_calle']),
                array('campo' => 'periodo_lectivo_numero', 'valor' => $valores['periodo_lectivo_numero']),
                array('campo' => 'periodo_lectivo_piso', 'valor' => $valores['periodo_lectivo_piso']),
                array('campo' => 'periodo_lectivo_departamento', 'valor' => $valores['periodo_lectivo_departamento']),
                array('campo' => 'periodo_lectivo_codigo_postal', 'valor' => $valores['periodo_lectivo_codigo_postal']),
                array('campo' => 'periodo_lectivo_barrio', 'valor' => $valores['periodo_lectivo_barrio']),
            );
            \guarani::act('act_personas')->actualizar_datos_personales($dato_censal['dato_censal'], $parametros);
        }
                
        
    }

	function accion__documentacion()
	{
		$this->vista()->set_pagelet('documentacion');
	}

	function accion__upload_documentacion()
	{
		$clave = $this->validate_param(0, 'get', validador::TIPO_TEXTO);

		$temp_path = kernel::proyecto()->get_www_temp();
		$indice_form = 'archivo';
		try {
			$datos = $this->guardar_archivos_subidos($indice_form, $temp_path);
			$archivo_subido = $datos[0];

			// Si el archivo subido tiene errores lanzo una excepción
			if($archivo_subido['error_subida'] != UPLOAD_ERR_OK){

				// Devuelve 0 cuando no tiene limite
				$upload_max_size = ini_get('upload_max_filesize');
				$upload_max_size_bytes = u::return_bytes($upload_max_size);
				$upload_max_size_formateado = u::formatBytes($upload_max_size_bytes);

				// Obtengo las extensiones permitidas
				$extensiones_permitidas = $this->extensiones_permitidas();

				// @see http://php.net/manual/es/features.file-upload.errors.php
				switch ($archivo_subido['error_subida']) {
                    case UPLOAD_ERR_INI_SIZE:
                        throw new ErrorKernel(kernel::traductor()->trans('upload_err_ini_size', array(
                            '%tam_max_archivo%' => $upload_max_size_formateado
                        )));
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
						throw new ErrorKernel(kernel::traductor()->trans('upload_err_form_size'));
                        break;
                    case UPLOAD_ERR_PARTIAL:
						throw new ErrorKernel(kernel::traductor()->trans('upload_err_partial'));
                        break;
                    case UPLOAD_ERR_NO_FILE:
						throw new ErrorKernel(kernel::traductor()->trans('upload_err_no_file'));
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
						throw new ErrorKernel(kernel::traductor()->trans('upload_err_no_tmp_dir'));
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
						throw new ErrorKernel(kernel::traductor()->trans('upload_err_cant_write'));
                        break;
                    case UPLOAD_ERR_EXTENSION:
						throw new ErrorKernel(kernel::traductor()->trans('upload_err_extension'));
                        break;
					case static::UPLOAD_ERR_EXTENSION_NO_PERMITIDA:
						throw new ErrorKernel(kernel::traductor()->trans('upload_err_extension_no_permitida', array(
							'%tipos%' => implode(', ', $extensiones_permitidas)
						)));
						break;
					case static::UPLOAD_ERR_GENERICO:
						throw new ErrorKernel(kernel::traductor()->trans('upload_err_generico'));
						break;
                }

			}

			$this->agregar_doc($clave, $archivo_subido);
		} catch (ErrorKernel $e) {
			kernel::sesion()->set('error_archivo_subido', $e->getMessage());
		}

		$this->nuevo_request('alta_usuario_cursos', 'documentacion');
	}

	function accion__eliminar_documentacion()
	{
		$clave = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$this->eliminar_doc($clave);
		$this->nuevo_request('alta_usuario_cursos', 'documentacion');
	}

	function accion__finalizar()
	{
		$propuesta = $this->get_propuesta_seleccionada();
		$requisitos_a_presentar = $this->modelo()->info__requisitos_cursos($propuesta);
		$requisitos_presentados = array();

		// Se valida que se hayan cargado todos los requisitos restrictivos
		$valido = true;

		foreach ($requisitos_a_presentar as $key => $requisito) {
			$clave_encriptada = catalogo::generar_id($requisito['requisito']);
			if ($this->requisito_cargado($clave_encriptada)) {
				$req_cargado = $this->get_requisito_cargado($clave_encriptada);
				$requisito['path_fs'] = $req_cargado['path_fs'];
				$requisito['nombre_original'] = $req_cargado['nombre_original'];
				$requisitos_presentados[] = $requisito;
			} else {
				if ($requisito['restrictivo'] == \comunes::si) {
					$valido = false;
				}
			}
		}

		if (!$valido) {
			$pagelet = $this->vista()->set_pagelet('documentacion');
			$this->add_error($this->trans('alta_uc.req.restrictivos_error'));
			kernel::renderer()->add($pagelet);
		} else {

			try {
				$valores = $this->get_datos_persona(false);
				$this->modelo()->evt__nueva_persona($valores, $requisitos_presentados);
				$this->post_creacion($valores);
			} catch (\Exception $e) {

				$pagelet = $this->vista()->set_pagelet('documentacion');
				$this->add_error($this->trans('alta_uc.req.error_carga_documentacion'));
				kernel::renderer()->add($pagelet);
			}
		}
	}

	function accion__reenviar_mail()
	{
		$this->enviar_mail_crear_usuario();
		$this->vista()->set_template('template_msj');
		$this->vista()->set_contexto(array(
			'mensaje' => kernel::traductor()->trans('alta_uc.msj_info_mail_reenviado'),
			'url_retorno' => kernel::vinculador()->crear('acceso'),
		));
	}

	//--------------------------------------------------------------------------
	//---- Auxiliares ----------------------------------------------------------
	//--------------------------------------------------------------------------

	protected function post_creacion($valores)
	{
		// Datos para el envío de mail
		$link = kernel::vinculador()->crear('acceso');
		$datos_mail_alta = array(
			'link' => $link,
			'usuario' => $valores['usuario'],
			'mail' => $valores['email'],
			'nombre' => $valores['nombres'] . ' ' . $valores['apellido'],
		);
		kernel::sesion()->set('__alta_uc.datos_mail__', $datos_mail_alta);

		$mensaje_alta = kernel::traductor()->trans('alta_uc.msj_info_alta_persona_ok');
		$mail_enviado = $this->enviar_mail_crear_usuario();
		if ($mail_enviado) {
			$link = kernel::vinculador()->crear('alta_usuario_cursos', 'reenviar_mail');
			$mensaje_alta .= kernel::traductor()->trans('alta_uc.msj_info_alta_persona_mail_ok', array('%1%' => $link));
		}

		$this->vista()->set_template('template_msj');
		$this->vista()->set_contexto(array(
			'mensaje' => $mensaje_alta,
			'url_retorno' => kernel::vinculador()->crear('acceso'),
		));
        
        $validado_con_renaper = kernel::proyecto()->valida_con_renaper();;
        if($validado_con_renaper === \comunes::si && $valores['validado_con_renaper'] === \comunes::si){
            $this->post_nuevo_usuario_curso($valores);
        }
        
		kernel::sesion()->borrar(self::datos_persona);
		kernel::sesion()->borrar(self::temp_archivos);
		kernel::sesion()->borrar(self::renaper_persona);
		kernel::sesion()->borrar('id_persona');
		kernel::sesion()->borrar(self::validado_con_renaper);
		kernel::sesion()->borrar(self::usuario);

	}

	function set_datos_persona($datos)
	{
		kernel::sesion()->set(self::datos_persona, $datos);
	}

	function get_datos_persona($formatear_fecha_frontend = true)
	{
		if (kernel::sesion()->esta_seteada(self::datos_persona)) {
			$datos = kernel::sesion()->get(self::datos_persona);
			if ($formatear_fecha_frontend) {
				$datos['fecha_nacimiento'] = convertir_fecha_formato_frontend($datos['fecha_nacimiento']);
			}
			return $datos;
		} else {
			return array();
		}
	}

	function get_propuesta_seleccionada()
	{
		$valores_cargados = kernel::sesion()->get(self::datos_persona);
		return $valores_cargados['propuesta'];
	}

	protected function enviar_mail_crear_usuario()
	{
		if (kernel::sesion()->esta_seteada('__alta_uc.datos_mail__')) {
			$datos_mail = kernel::sesion()->get('__alta_uc.datos_mail__');

			$asunto = kernel::traductor()->trans('alta_uc.asunto_email_alta');
			$tpl = kernel::load_template('crear_usuario/mail_crear_usuario.twig');
			$cuerpo = $tpl->render($datos_mail);

			$mail = new mail($datos_mail['mail'], $asunto, $cuerpo);
			$mail->set_html(true);

			try {
				$mail->enviar();
				$mail_enviado = true;
			} catch (\SIU\Chulupi\ErrorKernel $e) {
				$mail_enviado = false;
			}
		} else {
			$mail_enviado = false;
		}

		return $mail_enviado;
	}

	protected function get_usuario_sin_validar()
	{
		return substr($this->get_post('usuario'), 0, 50);
	}

	protected function add_error($mensaje, $e = null)
	{
		$mensajes = array();
		$mensajes[] = array(
			self::mensaje_cuerpo => $mensaje,
			self::mensaje_tipo => guarani::control_error,
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
	//---- Documentación -------------------------------------------------------
	//--------------------------------------------------------------------------

	function get_requisitos_cargados()
	{
		if (kernel::sesion()->esta_seteada(self::temp_archivos)) {
			return kernel::sesion()->get(self::temp_archivos);
		} else {
			return array();
		}
	}

	function requisito_cargado($clave)
	{
		if (kernel::sesion()->esta_seteada(self::temp_archivos)) {
			$requisitos = kernel::sesion()->get(self::temp_archivos);
			return (!empty($requisitos[$clave]));
		} else {
			return false;
		}
	}

	function get_requisito_cargado($clave)
	{
		if (kernel::sesion()->esta_seteada(self::temp_archivos)) {
			$requisitos = kernel::sesion()->get(self::temp_archivos);
			return ($requisitos[$clave]);
		} else {
			return null;
		}
	}

	function get_nombre_archivo_cargado($clave)
	{
		if (kernel::sesion()->esta_seteada(self::temp_archivos)) {
			$requisitos = kernel::sesion()->get(self::temp_archivos);
			return $requisitos[$clave]['nombre_fs'];
		} else {
			return '';
		}
	}

	protected function agregar_doc($clave, $valor)
	{
		if (kernel::sesion()->esta_seteada(self::temp_archivos)) {
			$requisitos = kernel::sesion()->get(self::temp_archivos);
		} else {
			$requisitos = array();
		}
		$requisitos[$clave] = $valor;
		kernel::sesion()->set(self::temp_archivos, $requisitos);
	}

	protected function eliminar_doc($clave)
	{
		if (kernel::sesion()->esta_seteada(self::temp_archivos)) {
			$requisitos = kernel::sesion()->get(self::temp_archivos);
			unlink($requisitos[$clave]['path_fs']);
			unset($requisitos[$clave]);
		}
		kernel::sesion()->set(self::temp_archivos, $requisitos);
	}

	//--------------------------------------------------------------------------
	//---- RECAPTCHA -----------------------------------------------------------
	//--------------------------------------------------------------------------

	function usa_captcha()
	{
		return kernel::proyecto()->get('usa_captcha');
	}

	//--------------------------------------------------------------------------
	//---- Cascadas ------------------------------------------------------------
	//--------------------------------------------------------------------------

	function accion__cascada_nacionalidad()
	{
		$nacionalidad = $this->get_param('nacionalidad', 'post');
		if ($nacionalidad == '2') {
			$datos = $this->modelo()->opciones__paises();
		} else {
			$datos = array('54' => 'Argentina');
		}
		$this->render_raw_json($datos);
	}

	//--------------------------------------------------------------------------
	//---- CASCADAS: Selector de localidades -----------------------------------
	//--------------------------------------------------------------------------

	function accion__get_descr_localidad()
	{
		$localidad = $this->get_param('localidad', 'post');
		$datos = $this->modelo()->info__descr_localidad($localidad);
		$this->render_raw_json($datos);
	}

	function accion__lista_localidades()
	{
		$criterio = $this->getAutocompleteTerm();
		$pais = utf8_decode(($this->get_param('pais', 'get')));
		if ((empty($criterio)) || (empty($pais))) {
			$datos = array();
		} else {
			$datos = $this->modelo()->opciones__localidades_autocomplete($criterio, $pais);
		}
		$this->render_autocomplete($datos, 'localidad', 'localidad_descr');
	}

	function render_autocomplete($datos, $campo_valor, $campo_descr)
	{
		$paquete = [];
		foreach ($datos as $key => $dato) {
			$valor = $dato[$campo_valor];
			$descripcion = capitalizar($dato[$campo_descr]);
			$paquete[] = ["label" => $descripcion, "value" => [$campo_valor => $valor, $campo_descr => $descripcion]];
		}
		echo kernel::renderer()->json_encode($paquete);
		throw new FinalizadorRequest();
	}

	protected function to_autocomplete($values)
	{
		$lista_autocomplete = array();
		foreach ($values as $value) {
			$lista_autocomplete[] = array(
				'localidad' => $value['localidad'],
				'localidad_descr' => $value['localidad_descr'],
				'label' => '[' . $value['localidad_descr'] . '] ',
				'value' => '[' . $value['localidad_descr'] . '] '
			);
		}
		return $lista_autocomplete;
	}

	function accion__cascada_provincias()
	{
		$pais = $this->get_param('pais', 'post');
		$datos = $this->modelo()->opciones__provincias($pais);
		$this->render_raw_json($datos);
	}

	function accion__cascada_departamentos()
	{
		$provincia = $this->get_param('provincia', 'post');
		$datos = $this->modelo()->opciones__departamentos($provincia);
		$this->render_raw_json($datos);
	}

	function accion__cascada_localidades()
	{
		$departamento = $this->get_param('departamento', 'post');
		$datos = $this->modelo()->opciones__localidades($departamento);
		$this->render_raw_json($datos);
	}

	function accion__cascada_tipo_documentos()
	{
		//
		$tipo_doc = $this->get_param('tipo_doc', 'post');
		$pais = $this->get_param('pais', 'post');
		$datos = $this->modelo()->opciones__tipos_documentos_x_pais($pais);
		$this->render_raw_json($datos);
	}

	function accion__datos_tipo_documento_validar()
	{
		$tipo_documento = $this->get_param('tipo_documento', 'post');
		$pais = $this->get_param('pais', 'post');
		$nro_documento = $this->get_param('nro_documento', 'post');
		$tipos_documento = \persona::get_tipos_documentos_x_pais($pais);
		
		$datos = [];
		$datos['exp_regular'] = $tipos_documento[$tipo_documento."_".$pais]['exp_regular_validacion'];
		$datos['mensaje'] = $tipos_documento[$tipo_documento."_".$pais]['exp_regular_mensaje'];
		
		$datos['mensaje_validacion_cuil'] = '';
		// Si el tipo de documento es CUIL/CUIT y falla la validación 
		if (($tipo_documento == \persona::doc_CUIL) && (!\persona::validarCUIT($nro_documento))) {
			$datos['mensaje_validacion_cuil'] = kernel::traductor()->trans('alta_uc.cuil.error');
		}
		
		$this->render_raw_json($datos);
	}
	
	//--------------------------------------------------------------------------
	//---- AJAX sugerencia nombre de usuario -----------------------------------
	//--------------------------------------------------------------------------

	function accion__sugerencia_user()
	{
		$pais_doc = $this->get_param('pais_doc', 'post');
		$tipo_doc = $this->get_param('tipo_doc', 'post');
		$nro_doc = $this->get_param('nro_doc', 'post');
		/**
		 * https://docs.moodle.org/20/en/Upload_users
		 * username can only contain alphabetical lowercase letters , numbers, hypen '-', underscore '_', period '.', or at-sign '@'
		 */
		$datos = array('usuario' => \usuario::get_usuario_default($pais_doc, $tipo_doc, $nro_doc));
		$datos['usuario'] =  preg_replace(moodle_nucleo::exp_regular_usuario, '', $datos['usuario']);
		kernel::sesion()->set(self::usuario, $datos['usuario']);
		$this->render_raw_json($datos);
	}

	/**
	 * El metodo valida que el usuario ingresado sea compatible con moodle
	 */
	function accion__validar_usuario()
	{
		$usuario = $this->get_param('usuario', 'post');
		
		$datos = [];
		$datos['exp_regular'] = moodle_nucleo::exp_regular_usuario;
		$datos['usuario'] =  preg_replace(moodle_nucleo::exp_regular_usuario, '', $usuario);
		//Compara dos strings
		if(strcmp($datos['usuario'],$usuario) !== 0){
			$datos['mensaje'] = moodle_nucleo::msj_error_username;
		}
		kernel::sesion()->set(self::usuario, $datos['usuario']);
		$this->render_raw_json($datos);
	}
    
    function accion__datos_nro_tramite_dni()
	{
        //Cargo los datos en los inputs
		$nro_tramite_dni = $this->get_param('nro_tramite_dni', 'post');
        $nro_documento = $this->get_param('nro_documento', 'post');
		$genero = $this->get_param('genero', 'post');
                      
        try{
            $api_parametros = $this->get_parametros_api();
            $apiKey = $api_parametros['renaper']['parametros']['apiKey_personData'];
            $url= $api_parametros['renaper']['parametros']['base_uri'];
            $url_header = $api_parametros['renaper']['parametros']['url_header'];
            $content_type='application/json';
    		$ws = new ws_renaper($apiKey, $content_type, $url, $url_header);
            
            $response = $ws->post_cliente_rest($nro_documento,$genero,$nro_tramite_dni);
            $res = json_decode($response,true);
            kernel::sesion()->esta_seteada(self::renaper_persona) ? kernel::sesion()->borrar(self::renaper_persona) : null;
            kernel::sesion()->esta_seteada(self::validado_con_renaper) ? kernel::sesion()->borrar(self::validado_con_renaper) : null;
            
            if(!empty($res['person']) && $res['person'] != ''){
                $res = json_decode($res['person'],true);
                kernel::sesion()->set(self::renaper_persona, $res);                
                kernel::sesion()->set(self::validado_con_renaper, \comunes::si);
            }else{
                kernel::sesion()->set(self::validado_con_renaper, \comunes::no);
            }
			
			$this->render_raw_json($response);
            
        } catch (error_preinscripcion_alumno_g3 $e) {
            $this->add_error($e->get_mensaje(), $e);
            $pagelet->set_estado_info(pagelet_crear_usuario::FORM_ERROR_VALIDACION);
            $this->render_raw_json($e->get_mensaje());
        }
        
	}
        
    function get_parametros_api(){
        
        $pathInstalacion = realpath(__DIR__.'/../../../../instalacion');    
        $WebConf = include "$pathInstalacion/servicios_web_config.php";         
        $valor = array_key_exists('renaper',$WebConf['consumidos']);
        if($valor){
            $valor = array_search('renaper', array_keys($WebConf['consumidos']));          
            $valor = array_slice($WebConf['consumidos'], $valor,1);            
        }
        
        return $valor;
    }

	}
