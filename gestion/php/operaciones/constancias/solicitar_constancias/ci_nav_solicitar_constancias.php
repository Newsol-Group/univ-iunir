<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Solicitar Constancias
 * 
 */
class ci_nav_solicitar_constancias extends ci_nav_con_preseleccion
{
	protected $s__alumno;
	protected $s__datos_alumno;
	protected $s__navegar;
	protected $s__vinculo_administrar;

	// Flag para indicar cuándo debe abrirse la ventana de impresión en jasper
	protected $s__imprimir = false;
	protected $s__solicitud;
        protected $s__datos_solicitud;
	
	//Para el mail
	protected $s__mail_enviado = false;
	
	function ini__operacion()
	{
		parent::ini__operacion();
		toba::memoria()->desactivar_reciclado();
	}
	
	function ini()
	{
		parent::set_concepto_preseleccion('Alumno');
	}
	
//	protected function es_usuario_alumno($gacc)
//	{
//		foreach ($gacc as $grupo) {
//			if ($grupo['tipo_usuario'] == usuario::tipo_alumno) {
//				return true;
//			}
//		}
//		return false;
//	}
//	
//	protected function es_usuario_gestion($gacc)
//	{
//		foreach ($gacc as $grupo) {
//			if ($grupo['tipo_usuario'] == usuario::tipo_gestion) {
//				return true;
//			}
//		}
//		return false;
//	}

	function set_preseleccion($seleccion)
	{
		$this->s__alumno = $seleccion['alumno'];
	}

	function get_preseleccion()
	{
		return $this->s__alumno;
	}

	function get_datos_alumno()
	{
		return $this->s__datos_alumno;
	}
	
	function get_es_alumno_autogestion()
	{
		return false;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial_preseleccion();
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla Selección -----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$this->s__datos_alumno = toba::consulta_php('co_alumnos')->get_datos_basicos_alumno($this->get_preseleccion());
		$this->ev_info()->set_datos($this->s__datos_alumno);
		$pantalla->set_descripcion('Sólo se listan las Solicitudes que no han sido entregadas, rechazadas, impresas ni son online');
	}
	
	//-----------------------------------------------------------------------------------
	//---- Pantalla Edición -----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		if ($this->get_es_alumno_autogestion()) {
			$pantalla->eliminar_evento('eliminar');
			$pantalla->eliminar_evento('volver_a_seleccion');
		}else{
			$pantalla->eliminar_evento('imprimir');
		}
		
		if ($this->cn()->entidad()->es_nueva() && $pantalla->existe_evento('eliminar')) {
			$pantalla->eliminar_evento('eliminar');
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_constancias_solicitud');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural'))));
		
		$cuadro->eliminar_columnas(array('interfaz_nombre','estado','fecha_fin_vigencia'));
		$cuadro->eliminar_evento('imprimir');
		$this->solicitudes = $this->cn()->get_listado_solicitar_constancias($this->get_preseleccion());			
		
		$cuadro->set_datos($this->solicitudes);
	}

	//---- ev_info ----------------------------------------------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_style('width: 75%;');
			$this->ev_info->set_columnas_a_mostrar(array('apellido_y_nombre', 'documento_principal', 'propuesta_nombre_abreviado', 'plan_nombre', 'ubicacion_nombre'));
			
			$this->ev_info->set_columnas_fila(array('apellido_y_nombre', 'documento_principal'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre_abreviado', 'plan_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('ubicacion_nombre'), 3);
			
			$this->ev_info->set_etiqueta('apellido_y_nombre', 'Alumno');
			$this->ev_info->set_etiqueta('documento_principal', 'Identificación');
			$this->ev_info->set_etiqueta('propuesta_nombre_abreviado', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__agregar()
	{
		$this->cn()->validar_solicitud_alumno($this->s__datos_alumno['persona'], $this->s__alumno, $this->s__datos_alumno['plan_version']);
		parent::evt__agregar();
	}
	
	function evt__guardar()
	{
		/**
		 * Se extiende para validar y almacenar los parámetros de la solicitud según los valores que se obtengan 
		 * del ci externo antes de sincronizar el DR. 
		 */
		try {
			$this->cn()->validar_solicitud_constancia($this->s__alumno, $this->s__datos_alumno['plan_version']);
			
			
			$params = $this->dependencia('editor')->get_params_a_guardar();
			// Si hay parámetros para guardar asociados a la Solicitud de Constancia.
			if (!empty($params)) {
				foreach ($params as $campo=>$valor) {
					campo::validar_campo_valor($campo, $valor);
				}
				$this->cn()->entidad()->set_datos_params($params);
			}
			// No se resetea el cn porque se permanece en la misma pantalla.
			$this->cn()->guardar();
			
			if (!isset($this->s__seleccion)) {
				// Alta de Solicitud.
				$datos_solicitud = $this->cn()->entidad()->get_datos_solicitud();
				$params = array('solicitud' => $datos_solicitud['solicitud']);
				$this->s__solicitud = $datos_solicitud['solicitud'];
			} else {
				// Edición de Solicitud.
				$params = $this->s__seleccion;
			}
			
			if (!$this->get_es_alumno_autogestion()) {	
				// Se genera el vínculo y se setea el flag para consultar al usuario si desea imprimir o rechazar la constancia.				
				$this->s__vinculo_administrar = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '37000080', $params);
				$this->s__navegar = true;
			} else {
				//Envía el mail al alumno si aún no lo envió
				if (!$this->s__mail_enviado){
					$this->s__mail_enviado = true;
					$persona = toba::consulta_php('co_alumnos')->get_persona_alumno($this->s__alumno);
					$this->enviar_mail($persona);
				}
				$this->evt__volver_a_seleccion();
			}
		} catch (guarani_error $e) {
			toba::notificacion()->agregar($e->getMessage(), 'error');
			toba::logger()->error($e->getMessage());
		}
	}
	
	// Se define este evt para poder volver luego de la confirmación js que no navega a la administración de 
	//solicitudes, sin el control de cambios aplicado al cancelar y para emitir el mensaje de guardar ok.
	function evt__volver_a_seleccion()
	{
		$this->dependencia('editor')->resetear_variables();
		$this->cn()->resetear();
		$this->set_pantalla('seleccion');
	}
	
	function evt__cancelar()
	{
		$this->dependencia('editor')->resetear_variables();
		parent::evt__cancelar();
	}
	
	/**
	 * Sólo se usa cuando un alumno de autogestión está logueado y pide una constancia:
	 * Almanena los datos del pedido e imprime la constancia
	 */
	function evt__imprimir()
	{
		/**
		 * Se extiende para validar y almacenar los parámetros de la solicitud según los valores que se obtengan 
		 * del ci externo antes de sincronizar el DR. 
		 */
		try {
			$params = $this->dependencia('editor')->get_params_a_guardar();

			// Si hay parámetros para guardar asociados a la Solicitud de Constancia.
			if (!empty($params)) {
				foreach ($params as $campo=>$valor) {
					campo::validar_campo_valor($campo, $valor);
				}
				$this->cn()->entidad()->set_datos_params($params);
			}
			// No se resetea el cn porque se permanece en la misma pantalla.
			$this->cn()->guardar();
			
			if (!isset($this->s__seleccion)) {
				// Alta de Solicitud.
				$datos_solicitud = $this->cn()->entidad()->get_datos_solicitud();
				$this->s__solicitud = $datos_solicitud['solicitud'];
			}else{
				$this->s__solicitud = $this->s__seleccion['solicitud'];
			}
			// Se pone el true el flag que permite saber cuando abrir en js la ventana correspondiente al reporte Jasper.
			$this->s__imprimir = true;
			
			//Envía el mail al alumno si aún no lo envió
			if (!$this->s__mail_enviado){
				$this->s__mail_enviado = true;
				$persona = toba::consulta_php('co_alumnos')->get_persona_alumno($this->s__alumno);
				$this->enviar_mail($persona);
			}
			$this->evt__volver_a_seleccion();	
		} catch (guarani_error $e) {
			toba::notificacion()->agregar($e->getMessage(), 'error');
			toba::logger()->error($e->getMessage());
		}
	}
	
	function evt__cuadro__imprimir($seleccion)
	{
		// Se pone el true el flag que permite saber cuando abrir en js la ventana correspondiente al reporte Jasper.
		$this->s__imprimir = true;
		$this->s__solicitud = $seleccion['solicitud'];
	}
	
	function conf_evt__cuadro__imprimir(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dependencia('cuadro')->get_datos();
		
		if (($datos[$fila]['estado'] != constancias::estado_solicitud_online) ||
			 (isset($datos[$fila]['fecha_fin_vigencia']) && guarani_fecha::es_menor_a_fecha_actual($datos[$fila]['fecha_fin_vigencia'])))
		{
			$evento->anular();
		}
	}
	
	//Mail
	function enviar_mail($persona)
	{		
		$datos_solicitud = toba::consulta_php('co_constancias')->get_datos_solicitud($this->s__solicitud);
		$nombre_constancia = $datos_solicitud['constancia_nombre'];
		$nro_transaccion = $datos_solicitud['nro_transaccion'];
		
		$asunto = 'Solicitud de Constancia';
		$cuerpo = 'Se ha registrado su solicitud de '.$nombre_constancia.' con el número de transacción '.$nro_transaccion;
		
		$mail = new guarani_mail($persona,null,$asunto,$cuerpo);
		$mail->set_html(true);
		
		try {
			$mail->ejecutar();
		} catch (toba_error $e) {
			$this->s__mail_enviado = false;
		}
	}
	
	
	//-----------------------------------------------------------------------------------
	//---- IMPRESION JASPER -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function vista_jasperreports(toba_vista_jasperreports $report) 
	{		
		$plantilla = toba::consulta_php('co_constancias')->get_constancia_archivo_plantilla($this->s__datos_solicitud['constancia']);
		$path = guarani::get_path_reporte_jasper($plantilla);
		$report->set_path_reporte($path);
		
		//Seteo el logo de la Institución correspondiente, si no existe seteo el logo de Guarani
		$path_logo = guarani::get_path_logo_impresion();
		$report->set_parametro('logo', 'S', $path_logo);
		$report->set_parametro('solicitud', 'E', $this->s__solicitud);

		//Genero el QR en una carpeta temporal
                $path_carpeta_qr = toba::proyecto()->get_path_temp()."/constancias_qr";
                if (!file_exists($path_carpeta_qr)){
                    mkdir($path_carpeta_qr);
                }
		$path_qr = $path_carpeta_qr.'/'.$this->s__datos_solicitud['solicitud'].".png";
		
		$url_3w = guarani_instalacion_conf::get_parametro_instalacion('url3w',true);
 		
                $param_nombre_persona_doc_oficiales = persona::get_param_nombre_persona_doc_oficiales();		
		$report->set_parametro('apellido_y_nombre', 'S', $param_nombre_persona_doc_oficiales);		
		$url_verificacion = "{$url_3w}/".constancias::operacion_validador_3w;
		$url_verificacion_con_parametros = "{$url_verificacion}/{$this->s__datos_solicitud['nro_documento']}/{$this->s__datos_solicitud['codigo_verificacion']}";
		
		guarani_qrcode::generar_codigo_qr($path_qr, $url_verificacion_con_parametros);
		 
		//Seteo los parámetros para verificación: QR, URL y código
		$report->set_parametro('verificacion_qr', 'S', $path_qr);
		$report->set_parametro('verificacion_url', 'S', $url_verificacion);
		$report->set_parametro('verificacion_codigo', 'S', $this->s__datos_solicitud['codigo_verificacion']);
		$report->set_parametro('ocultar_firma', 'B', true);
		
		$parametros = toba::consulta_php('co_constancias')->get_solicitud_params($this->s__solicitud);        
		foreach ($parametros as $param) {
			$report->set_parametro($param['campo'], $param['tipo_de_dato'], $param['valor']);            
		}
	}

        function vista_araireportes(toba_vista_araireportes $report)
        {
                // Recupero información del reporte
                $informacion_reporte = arai_reportes::getInformacionReporteById($this->s__datos_solicitud['constancia']);
                // Recupero la URI del reporte
                $uri_reporte    = toba::consulta_php('co_arai_reportes')->get_uri_reporte($informacion_reporte['id']);
                $report->set_path_reporte($uri_reporte);

                // Recupero la URI del logo
                $uri_logo    = arai_reportes::getUriLogoImpresion();
                $report->set_parametro('uri_logo', 'S', $uri_logo);

                // Seteo el título del reporte
                $report->set_parametro('titulo', 'S', $informacion_reporte['titulo']);

                // Recupero datos para el detalle del reporte si corresponde
                if (isset($informacion_reporte['clase_consulta']) && isset($informacion_reporte['metodo_consulta'])) {
                    $clase_consulta = $informacion_reporte['clase_consulta'];
                    $metodo_consulta = $informacion_reporte['metodo_consulta'];
                    $datos  = toba::consulta_php($clase_consulta)->$metodo_consulta($this->s__solicitud);

                    // Seteo datos para el reporte
                    $report->set_json(rest_encode($datos));
                }

                //Genero el QR en una carpeta temporal
                $path_carpeta_qr = toba::proyecto()->get_path_temp()."/constancias_qr";
                if (!file_exists($path_carpeta_qr)){
                    mkdir($path_carpeta_qr);
                }
		$path_qr = $path_carpeta_qr.'/'.$this->s__datos_solicitud['solicitud'].".png";

		$url_3w = guarani_instalacion_conf::get_parametro_instalacion('url3w',true);

		$url_verificacion = "{$url_3w}/".constancias::operacion_validador_3w;
		$url_verificacion_con_parametros = "{$url_verificacion}/{$this->s__datos_solicitud['nro_documento']}/{$this->s__datos_solicitud['codigo_verificacion']}";

		guarani_qrcode::generar_codigo_qr($path_qr, $url_verificacion_con_parametros);

                // Genero el base64 del QR a partir de la imagen
                $base64_qr = base64_encode(file_get_contents($path_qr));
                // Elimino la imagen del QR
                unlink($path_qr);

		//Seteo los parámetros para verificación: QR, URL y código
		$report->set_parametro('verificacion_qr', 'S', $base64_qr);
		$report->set_parametro('verificacion_url', 'S', $url_verificacion);
		$report->set_parametro('verificacion_codigo', 'S', $this->s__datos_solicitud['codigo_verificacion']);
		$report->set_parametro('ocultar_firma', 'B', true);

		$parametros = toba::consulta_php('co_constancias')->get_solicitud_params($this->s__solicitud);
		foreach ($parametros as $param) {
			$report->set_parametro($param['campo'], $param['tipo_de_dato'], $param['valor']);
		}
        }
        
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Se redefine la extensión javascript del ci de navegación para no realizar la confirmación de cambios ante 
	 * el evento Guardar. Esto es debido a que la misma no toma en cuenta los cambios producidos en el ci externo 
	 * que se agrega dinámicamente. De esta forma el evento Guardar se ejecuta se hayan o no realizado cambios.
	 * Se mantiene la confirmación del cancelar si es que hubo cambios en el ci de edición de la solicitud.
	 *
	 */
	function js_evt_guardar() 
	{
	}
	
	// Al Guardar se posibilita al usuario navegar hacia la operación de Administrar Solicitudes con la Solicitud actual.
	function extender_objeto_js()
	{
		$msj_administrar = guarani_mensajes::get('800CON_ir_a_administrar');
		$msj_guardar_ok = guarani_mensajes::get('guardar_ok');
	
		if ($this->s__navegar) {
			unset($this->s__navegar);
			echo "
				resp = confirm('$msj_administrar');
				if (resp) {
					document.location.href='$this->s__vinculo_administrar';
				} else {
					{$this->objeto_js}.set_evento(new evento_ei('volver_a_seleccion'), true);
				} 
			";
		}
		
		// Abre la ventana que muestra el reporte Jasper para la impresión.
		if ($this->s__imprimir) {
			$this->s__imprimir = false;

                        $this->s__datos_solicitud = toba::consulta_php('co_constancias')->get_datos_solicitud($this->s__solicitud);

                        $funcion = 'vista_jasperreports';
                        // Me fijo si esta habilitado para Araí Reportes
                        if (arai_reportes::esReporteHabilitado($this->s__datos_solicitud['constancia'])) {
                            $funcion = 'vista_araireportes';
                        }

			echo "
				window.open(vinculador.get_url(null, null, '{$funcion}', null, [{$this->objeto_js}._id]));
			";
		} 
	}
}