<?php
class ci_inscribir_propuesta_preinsc extends ci_nav_sin_listado_inicial
{
	protected $ev_info;
	protected $s__datos_inscripciones;
	protected $s__persona;
	protected $s__procesar_ok = false;
	// Mail
	protected $s__datos_mail;
	protected $s__envio_ok = false;
	protected $s__mail_enviado = false;

	//solicitar_informacion
	
	protected $s__solicitar_informacion_ok = false;
	// Navegación a "Administrar Requisitos de una Persona"
	protected $s__navegar;
	protected $s__vinculo_adm_req;

	function get_persona()
	{
		return $this->s__persona;
	}

	function get_pantalla_retorno()
	{
		return $this->get_id_pantalla();
	}

	function ini()
	{
		
		//control de la configuracion del parametro url3w
		guarani_instalacion_conf::get_parametro_instalacion('url3w',true);
		
		// Cuando vuelve de "Administrar Personas"...
		$pantalla_retorno = toba::memoria()->get_parametro('pantalla_retorno');
		if (isset($pantalla_retorno)) {
			$this->set_pantalla($pantalla_retorno);
		}

		// Cuando vuelve de "Administrar Requisitos de una Persona"...
		$propuestas_aspira = toba::memoria()->get_parametro('propuesta_aspira');
		if (isset($propuestas_aspira)) {
			$this->cn()->set_propuestas_procesadas(explode(',', $propuestas_aspira));
			$this->s__persona = toba::memoria()->get_parametro('persona');
			$this->s__procesar_ok = true;
			$this->set_pantalla('pant_comprobante');
		}
	}

	function get_bases_preinscripcion($origen)
	{
		return $this->cn()->get_bases_preinscripcion($origen);
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA: pant_seleccion
	//--------------------------------------------------------------------------

	function conf__pant_seleccion(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
		$this->cn()->resetear();
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro')->get_datos();
		if ($datos[$fila]['duplicada'] == comunes::si_desc) {
			$evento->anular();
		}
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		toba::memoria()->set_dato('fuente_preinscripcion', $this->s__seleccion['fuente_de_datos']);
		$this->set_pantalla('pant_edicion');
	}

	function procesar_desde_preinscripcion($id_preinscripcion, $version_impresa)
	{
		// Se valida que el usuario de gestión identificado tenga acceso a alguna  
		// propuesta en la que el aspirante se preinscribió
		$this->cn()->validar_propuestas_preinscripcion($id_preinscripcion);

		$datos_preinscripcion = $this->cn()->get_datos_preinscripcion($id_preinscripcion, $version_impresa);

		if (!empty($datos_preinscripcion)) {
			// Se valida que la persona que se está inscribiendo no esté expulsada en otra Institución. 
			if (toba::consulta_php('co_expulsados_en_otras_instituciones')->identificacion_registra_expulsiones($datos_preinscripcion['pais_documento'], $datos_preinscripcion['tipo_documento'], $datos_preinscripcion['nro_documento'])) {
				$mje = guarani_mensajes::get('800MAT_persona_expulsada');
				throw guarani::error($mje);
			}

			if ($datos_preinscripcion['estado'] == preinscripcion::estado_pendiente) {
				throw guarani::error(toba::mensajes()->get('800MAT_preinscripcion_pendiente'));
			} elseif ($datos_preinscripcion['version_modificacion'] != $datos_preinscripcion['version_impresa']) {
				$mensaje = ($datos_preinscripcion['version_impresa'] == 0) ? '800MAT_preinscripcion_imprimir' : '800MAT_preinscripcion_reimprimir';
				throw guarani::error(toba::mensajes()->get($mensaje));
			}
			$persona = $this->cn()->procesar_persona($datos_preinscripcion);
			return $persona;
		} else {
			throw guarani::error(toba::mensajes()->get('800MAT_codigo_preinscripcion_invalido'));
		}
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('origen')->set_condicion_fija('es_igual_a');
		$filtro->columna('base')->set_condicion_fija('es_igual_a');
		$filtro->columna('tramite')->set_condicion_fija('es_igual_a');
		$filtro->columna('propuesta')->set_condicion_fija('es_igual_a');
		$filtro->columna('atendido_por')->set_condicion_fija('es_igual_a');
		$filtro->columna('tiene_atendedor')->set_condicion_fija('es_igual_a');
		$filtro->columna('duplicada')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro__filtrar($datos)
	{
		$this->dep('filtro')->columna('anio_academico')->resetear_estado();
		$this->s__datos = $datos;
		//En el cn armo el filtro que necesito.
		$this->s__filtro = $this->cn()->armar_filtro_condicion($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: pant_edicion
	//-----------------------------------------------------------------------------------

	function conf__pant_edicion(toba_ei_pantalla $pantalla)
	{
		$this->s__seleccion['validado_con_renaper'] = $this->s__seleccion['validado_con_renaper']. ' ' . $this->get_html_icono_info_validado_con_renaper();
		$this->ev_info()->set_datos($this->s__seleccion);
		
		$param = [];
		$param['id'] = $this->s__seleccion['id'];
		$param['origen'] = $this->s__seleccion['origen'];
		$param['fuente_de_datos'] = $this->s__seleccion['fuente_de_datos'];
		
		$this->evento('imprimir')->vinculo()->set_parametros($param);
		$this->evento('descargar_requisitos')->vinculo()->set_parametros($param);
		//tiene requisitos digitales
		if ($this->cn()->get_tiene_requisitos_digitales($param['id'], $param['origen'], $param['fuente_de_datos'])) {
			$param['tiene_requisitos'] = comunes::si;
			$this->evento('descargar_requisitos')->vinculo()->set_parametros($param);
		} else {
			$param['tiene_requisitos'] = comunes::no;
			$this->evento('descargar_requisitos')->vinculo()->set_parametros($param);
		}
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();			
			$this->ev_info->set_columnas_a_mostrar(['apellido_nombres', 'identificacion', 'mail','validado_con_renaper']);

			$this->ev_info->set_columnas_fila(array('apellido_nombres', 'validado_con_renaper'), 1);
			$this->ev_info->set_columnas_fila(array('identificacion'), 2);
			$this->ev_info->set_columnas_fila(array('mail'), 3);
			
			$this->ev_info->set_etiqueta('apellido_nombres', 'Aspirante');
			$this->ev_info->set_etiqueta('identificacion', 'Identificación');
			$this->ev_info->set_etiqueta('mail', 'Mail');
			$this->ev_info->set_etiqueta('validado_con_renaper', persona::validado_con_renaper);
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- form_ml_inscripciones --------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_inscripciones(toba_ei_formulario_ml $form_ml)
	{
		if ($this->s__seleccion['origen'] == preinscripcion::origen_preinscripcion) {
			$datos = isset($this->s__datos_inscripciones) ? $this->s__datos_inscripciones : $this->cn()->get_preinscripciones($this->s__seleccion);
		} else {
			$datos = isset($this->s__datos_inscripciones) ? $this->s__datos_inscripciones : $this->cn()->get_preinscripciones_desde_autogestion($this->s__seleccion);
			//borro boton de solicitar informacion 
			$this->evento('solicitar_informacion')->set_en_botonera(false);
			
		}		
		$form_ml->set_datos($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- operaciones auxiliares -------------------------------------------------------
	//-----------------------------------------------------------------------------------
	/**
	 * Retorna el HTML de un icono (con imagen)
	 */
	function get_html_icono_info_validado_con_renaper()
	{
		$salida = "<a class='icono-utileria' href='#'>";
		$salida .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, persona::descr_renaper);
		$salida .= '</a>';

		return $salida;
	}

	function evt__form_ml_inscripciones__modificacion($datos)
	{
		$this->s__datos_inscripciones = $datos;
	}

	function evt__form_ml_inscripciones__historial($seleccion)
	{
		$this->set_pantalla('pant_historial');
	}

	function conf_fila__form_ml_inscripciones($fila)
	{
		$datos_ml = $this->dep('form_ml_inscripciones')->get_datos(true);

		// Si para algunas de las propuestas a las que el alumno se preinscribió, 
		//  ya tiene una inscripción (Pendiente o Aceptada), este registro deberá 
		//  aparecer deshabilitado y deseleccionado.
		if ($this->s__seleccion['origen'] == preinscripcion::origen_autogestion) {
			$filtro = "sga_situacion_aspirante.resultado_asp IN ('" . propuesta::inscripcion_pendiente . "','" . propuesta::inscripcion_aceptada . "')";
			$cant_inscripciones = toba::consulta_php('co_inscripciones')->get_cant_inscripciones($this->s__seleccion['id'], $datos_ml[$fila]['propuesta'], null, $filtro);
			if ($cant_inscripciones != 0) {
				$this->dep('form_ml_inscripciones')->set_solo_lectura(array('procesar', 'plan_version', 'anio_periodo_insc', 'tipo_ingreso'));
			} else {
				$this->dep('form_ml_inscripciones')->set_solo_lectura(array('procesar', 'plan_version', 'anio_periodo_insc', 'tipo_ingreso'), false);
			}
		}

		if ( $datos_ml[$fila]['estado'] == preinscripcion::insc_solicitud_informacion ) {
			$this->dep('form_ml_inscripciones')->set_solo_lectura(array('procesar', 'plan_version', 'anio_periodo_insc', 'tipo_ingreso'));
		}
	}

	function get_planes_para_inscripcion_prop_modalidad($propuesta, $modalidad)
	{
		$params_sistema = array(
			'inscripcion_propuesta_plan' => guarani::parametros()->get_valor('inscripcion_propuesta_plan', $propuesta),
			'inscripcion_propuesta_version' => guarani::parametros()->get_valor('inscripcion_propuesta_version', $propuesta)
		);
		return toba::consulta_php('co_propuestas')->get_planes_para_inscripcion_prop_modalidad($propuesta, $modalidad, $params_sistema);
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: pant_comprobante
	//-----------------------------------------------------------------------------------

	function conf__pant_comprobante(toba_ei_pantalla $pantalla)
	{
		$datos = $this->cn()->get_datos_ev_info($this->s__persona);
		$this->ev_info()->set_datos($datos);

		if ($this->s__procesar_ok) {
			$this->preparar_comprobante($datos);
			// Se elimina el Envío de Mail automatico con el comprobante por el momento, 
			// ya que el parametro esta definido a nivel de RA y en este caso pueden ser varias.
			// Sólo queda implementado el envío manual del mismo					
			if ($this->s__mail_enviado) {
				$this->evento('mail')->ocultar();
			}
		} else {
			$this->evento('pdf')->ocultar();
			$this->evento('mail')->ocultar();
		}
	}

	protected function preparar_comprobante($datos_persona)
	{
		$datos_inscripciones = $this->cn()->get_inscripciones_procesadas();

		guarani_datos_impresion::instancia()->limpiar_datos();
		guarani_datos_impresion::instancia()->set_titulo('Inscripción a Propuesta desde Preinscripción');
		guarani_datos_impresion::instancia()->set_conf(array('propuesta' => '0', 'nro_transaccion' => ''));

		guarani_datos_impresion::instancia()->nueva_fila_datos('Aspirante', $datos_persona['apellido_nombres']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Identificación', $datos_persona['identificacion']);

		foreach ($datos_inscripciones as $datos_inscripcion) {
			// Fila en blanco para separar
			guarani_datos_impresion::instancia()->nueva_fila_datos('', '');

			guarani_datos_impresion::instancia()->nueva_fila_datos('Resp. Académica', $datos_inscripcion['ras_descr']);
			guarani_datos_impresion::instancia()->nueva_fila_datos('Propuesta', $datos_inscripcion['propuesta_descr']);
			guarani_datos_impresion::instancia()->nueva_fila_datos('Plan de Estudios', $datos_inscripcion['plan_descr']);
			guarani_datos_impresion::instancia()->nueva_fila_datos('Modalidad de Cursada', $datos_inscripcion['modalidad_descr']);
			guarani_datos_impresion::instancia()->nueva_fila_datos('Ubicación', $datos_inscripcion['ubicacion_descr']);

			if (!empty($datos_inscripcion['tipo_ingreso'])) {
				guarani_datos_impresion::instancia()->nueva_fila_datos('Tipo de Ingreso', $datos_inscripcion['tipo_ingreso_descr']);
			} else {
				guarani_datos_impresion::instancia()->nueva_fila_datos('Tipo de Ingreso', ' -- ');
			}

			guarani_datos_impresion::instancia()->nueva_fila_datos('Estado Inscripción', $datos_inscripcion['estado_insc_descr']);
			guarani_datos_impresion::instancia()->nueva_fila_datos('Fecha Inscripción', $datos_inscripcion['fecha_inscripcion_visual']);
			guarani_datos_impresion::instancia()->nueva_fila_datos('Nro. Transacción', $datos_inscripcion['nro_transaccion']);
		}

		// Se almacenan los datos en memoria para recuperarlos luego para la impresión de comprobante
		guarani_datos_impresion::instancia()->guardar_datos_imp();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: pant_formulario
	//-----------------------------------------------------------------------------------

	function conf__pant_formulario(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->s__seleccion);	
	}

	//-----------------------------------------------------------------------------------
	//---- form_solicitar_informacion ---------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__form_solicitar_informacion__enviar($datos)
	{	
		$persona=$this->s__seleccion['id'];
		$hacia = $this->s__seleccion['mail'];
		$asunto = $datos['titulo'];
		$mensaje = $datos['mensaje'];
		$usuario_gestion = toba::consulta_php('co_personas')->get_persona_x_usuario(toba::usuario()->get_id());
		$fuente = $this->s__seleccion['origen'];
		
		$this->enviar_solicitud_informacion($persona, $hacia, $asunto, $mensaje);
		if($this->s__solicitar_informacion_ok){
			//grabo cambio de estado y vuelvo a pantalla anterior $id_preinscripcion, $fuente, $usuario_gestion
			foreach ($this->s__datos_inscripciones as $inscripcion)
			{	
				if ($inscripcion['procesar'] == 1  ) {
					$this->cn()->cambio_estado_preinscripcion($inscripcion['preinscripcion_propuesta'], $inscripcion['id_preinscripcion'], $fuente, $usuario_gestion,$mensaje);
				}
			}
		}
		$this->set_pantalla('pant_seleccion');
		$this->unset_vars();	
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA: pant_historial
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- cuadro_historial -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_historial(guarani_pers_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_historial_preinscripcion($this->s__datos_inscripciones);
		$cuadro->set_datos($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__volver_inscripciones()
	{
		$this->evt__finalizar();
	}

	function evt__procesar_insc()
	{
		$this->cn()->validar_seleccion_insc($this->s__datos_inscripciones);

		// Atributos para resolver valor de parámetros
		$propuestas = array();
		$planes = array();
		$ras = array();
		$tipo_propuestas = array();

		foreach ($this->s__datos_inscripciones as $key => $inscripcion) {
			if ($inscripcion['estado'] == preinscripcion::insc_pendiente && $inscripcion['procesar'] == 1) {
				if(!$inscripcion['plan_version']) {
					toba::notificacion()->error('Plan - Versión es obligatorio.');					
					return false;
				}
				if(!$inscripcion['anio_periodo_insc']) {
					toba::notificacion()->error('Período Inscripción es obligatorio.');					
					return false;
				}
				$propuestas[] = $inscripcion['propuesta'];
				$planes[] = toba::consulta_php('co_planes')->get_plan_de_plan_version($inscripcion['plan_version']);
			}
		}

		// Carga de Parámetros
		if ((!empty($propuestas)) && (!empty($planes))) {
			$this->cn()->cargar_atributos_parametros($propuestas, $planes);
		} else {
			$this->cn()->resetear_atributos_parametros();
		}
		
		// Procesamiento
		$persona = ($this->s__seleccion['origen'] == preinscripcion::origen_preinscripcion) ? $this->procesar_desde_preinscripcion($this->s__seleccion['id'], $this->s__seleccion['version_impresa']) : $this->s__seleccion['id'];
		$this->cn()->procesar_inscripciones($this->s__datos_inscripciones, $persona, $this->s__seleccion['id'], $this->s__seleccion['origen']);

		$this->s__persona = $persona;
		$this->s__procesar_ok = true;
		$this->set_pantalla('pant_comprobante');

		$inscripciones = $this->cn()->get_propuestas_aspira();

		//Verifico si tengo el parámetro en si.
		if (guarani::parametros()->get_valor('pre_notifica_aspirante') == comunes::si) {
			$this->cn()->preparar_envio_mail($persona, $propuestas, $this->s__seleccion['origen']);
		}

		$params = array();
		$params['operacion_a_volver'] = guarani::get_id_operacion_toba();
		$params['celda_a_restaurar'] = toba::memoria()->get_celda_memoria_actual_id();
		$params['persona'] = $persona;
		$params['propuesta_aspira'] = implode(',', $inscripciones);
		$this->s__vinculo_adm_req = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '5000111', $params);
		$this->s__navegar = true;
	}

	function enviar_solicitud_informacion($persona, $hacia, $asunto, $cuerpo)
	{
		try {

			$mail = new guarani_mail($persona, $hacia, $asunto, $cuerpo);
			$mail->set_html(true);
			$mail->ejecutar();
			$this->s__solicitar_informacion_ok = true;
		} catch (toba_error $e) {
			toba::logger()->error($e->getMessage());
			$this->s__solicitar_informacion_ok = false;
			throw guarani::error('No se ha podido enviar el mail. Revisar los parámetros de configuración.');
		}
	}

	function evt__finalizar()
	{
		try {
			$this->set_pantalla('pant_seleccion');
			$this->unset_vars();
			$this->limpiar_memoria();
		} catch (guarani_error $e) {
			toba::notificacion()->agregar($e->getMessage(), 'error');
			toba::logger()->error($e->getMessage());
		}
	}

	function unset_vars()
	{
		unset($this->s__datos_inscripciones);
		unset($this->s__persona);
		unset($this->s__datos_mail);
		$this->s__procesar_ok = false;
		$this->s__envio_ok = false;
		$this->s__mail_enviado = false;
	}

	function evt__mail()
	{
		$this->enviar_mail($this->s__persona);
	}

	function evt__solicitar_informacion()
	{
		//valido que tenga propuestas seleccionadas 
		foreach ($this->s__datos_inscripciones as $key => $inscripcion) {
			
			if ($inscripcion['procesar'] == 1) {
				$this->set_pantalla('pant_formulario');
				return;
			}
		}
		$mensaje = propuesta_nucleo::debe_seleccionar_una_propuesta;
		toba::notificacion()->agregar($mensaje, "error");
			

	}

	function evt__volver_preinscripcion()
	{
		$this->set_pantalla('pant_edicion');
	}

	function evt__volver()
	{
		$this->set_pantalla('pant_seleccion');
		$this->unset_vars();
	}

	//-----------------------------------------------------------------------------------
	//---- Envío de Mail ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function enviar_mail($persona)
	{
		$this->s__datos_mail = null;
		$this->s__mail_enviado = true;

		try {
			$mail = new guarani_mail($persona);
			$mail->set_html(true);
			$this->s__datos_mail = $mail->get_datos_mail();

			$pdf = new guarani_pdf(array($this->dep('comprobante')));
			$archivo = $pdf->generar(true);

			$mail->agregar_adjunto("Comprobante.pdf", $archivo, 'base64', 'pdf');
			$mail->ejecutar();
			$this->s__envio_ok = true;
			if (file_exists($archivo)) {
				unlink($archivo);
			}
		} catch (toba_error $e) {
			toba::logger()->error($e->getMessage());
			$this->s__envio_ok = false;
			throw guarani::error('No se ha podido enviar el mail. Revisar los parámetros de configuración.');
		}
	}
	
	function get_datos_mail()
	{
		return $this->s__datos_mail;
	}

	function envio_ok()
	{
		return $this->s__envio_ok;
	}

	function mail_enviado()
	{
		return $this->s__mail_enviado;
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		if ($this->get_id_pantalla() == 'pant_edicion' && $this->s__seleccion['origen'] == preinscripcion::origen_preinscripcion) {
			$datos_preinscripcion = $this->cn()->get_datos_preinscripcion($this->s__seleccion['id'], $this->s__seleccion['version_impresa']);
			$persona = toba::consulta_php('co_personas')->validar_documento($datos_preinscripcion['tipo_documento'], $datos_preinscripcion['nro_documento'], $datos_preinscripcion['pais_documento']);
			$confirmar_persona = "return true;";
			if (!empty($persona)) {
				$tipo_documento = current(toba::consulta_php('co_personas')->get_nombre_tipo_documento($datos_preinscripcion['pais_documento'], $datos_preinscripcion['tipo_documento']));
				$apellido_nombres = toba::consulta_php('co_personas')->get_nombre_persona($persona);
				$msj_confirmar_persona = guarani_mensajes::get('800MAT_existe_persona_documento', array("{$tipo_documento['tipo_nombre']} {$datos_preinscripcion['nro_documento']}", $apellido_nombres));
				$confirmar_persona = "return confirm('$msj_confirmar_persona');";
			}
			echo "
			{$this->objeto_js}.evt__procesar_insc = function() {
				$confirmar_persona
			}";
		}
		$si = toba::db()->quote(comunes::si);
		$msj_alerta = toba::db()->quote(requisito_digital_nucleo::NO_TIENE_REQUISITOS_PARA_DESCARGAR);
		$msj_administrar = 'Inscripción procesada con éxito. ¿Desea gestionar requisitos de la persona?';
		if (!empty($this->s__navegar)) {
			unset($this->s__navegar);
			echo "
				resp = confirm('$msj_administrar');
				if (resp) {
					document.location.href='$this->s__vinculo_adm_req';
				}
			";
		}
        echo "
        {$this->objeto_js}.invocar_vinculo = function(params,rep) {
			if (params === 'descargar_requisitos') {
				var corte = '&tiene_requisitos=';
				//Corto la url para obtener el parametro
				var tiene_requisitos = vinculador._vinculos[rep].url.split(corte);
				tiene_requisitos = tiene_requisitos[1].substr(0,1)
				if (tiene_requisitos === $si) {
					window.open(vinculador._vinculos[rep].url);
				} else {
					var band = notificacion._mensajes.length;
					if (band > 0) {
						notificacion.limpiar();
					}
					notificacion.agregar($msj_alerta, 'info');
					notificacion.mostrar();
					notificacion.limpiar();					
				}
			} else {
				window.open(vinculador._vinculos[rep].url);
			}				
		}";
	}
}