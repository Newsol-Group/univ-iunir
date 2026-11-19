<?php
/**
	*
	*
	* @package     Constancias y Certificados
	* @subpackage     Administrar Solicitudes
	* 
	*/
class ci_edi_administrar_solicitudes_constancias extends guarani_pers_ci
{
	// Datos seteados en la modificación de los formularios.
	protected $s__datos_form_entrega;
	protected $s__datos_form_rechazo;
	
	// Datos de la solicitud seleccionada por el usuario y del usuario que está realizando el proceso.
	protected $s__solicitud;
	protected $s__datos_solicitud;
	protected $s__datos_usuario;
	
	// Flag para indicar cuándo debe abrirse la ventana de impresión en jasper, luego de actualizar los datos en el servidor.
	protected $s__imprimir = false;
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function resetear_vars()
	{
		unset($this->s__datos_form_entrega);
		unset($this->s__datos_form_rechazo);
		unset($this->s__solicitud);
		unset($this->s__datos_solicitud);
		unset($this->s__datos_usuario);
		$this->s__imprimir = false;
	}
	
	function get_datos_usuario()
	{
		return $this->s__datos_usuario;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ini()
	{
		parent::ini();
		/**
		 * En el ci en el que se realiza el evento de Impresión debe realizarse un desactivar reciclado para que
		 * puedan ejecutarse más eventos del Ci sin perder la memoria, luego de abrir el popup con el servicio
		 * vista_jasper_reports.
		 */
		toba::memoria()->desactivar_reciclado();
		// Se recuperan los datos de la solicitud seleccionada para utilizarla en la configuración de componentes del ci.
		$this->s__solicitud = $this->controlador()->get_solicitud_seleccionada();
		$this->s__datos_solicitud = toba::consulta_php('co_constancias')->get_datos_solicitud($this->s__solicitud);    
		$persona = toba::consulta_php('co_personas')->get_persona_x_usuario(toba::usuario()->get_id());
		$this->s__datos_usuario = array('nombre'        => toba::usuario()->get_nombre(),
										'id_persona'    => $persona['persona']);
	}
	
	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_style('width: 75%;');
			$this->ev_info->set_titulo('Detalle de la Solicitud');
			$this->ev_info->set_columnas_a_mostrar(array('apellido_y_nombre', 'documento_principal', 'propuesta_nombre_abreviado', 'plan_nombre', 'ubicacion_nombre', 'constancia_nombre', 'cant_copias_solicitadas', 'estado', 'observaciones','datos_adicionales'));
			
			$this->ev_info->set_columnas_fila(array('apellido_y_nombre', 'documento_principal'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre_abreviado', 'plan_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('ubicacion_nombre'), 3);
			$this->ev_info->set_columnas_fila(array('constancia_nombre', 'cant_copias_solicitadas'), 4);
			$this->ev_info->set_columnas_fila(array('estado'), 5);
			$this->ev_info->set_columnas_fila(array('observaciones'), 7);
			$this->ev_info->set_columnas_fila(array('datos_adicionales'), 6);
			
			$this->ev_info->set_etiqueta('apellido_y_nombre', 'Alumno');
			$this->ev_info->set_etiqueta('documento_principal', 'Identificación');
			$this->ev_info->set_etiqueta('propuesta_nombre_abreviado', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
			$this->ev_info->set_etiqueta('constancia_nombre', 'Constancia');
			$this->ev_info->set_etiqueta('cant_copias_solicitadas', 'Cantidad de Copias');
			$this->ev_info->set_etiqueta('estado', 'Estado');
			$this->ev_info->set_etiqueta('observaciones', 'Observaciones');
			$this->ev_info->set_etiqueta('datos_adicionales', 'Datos adicionales');
		}
		return $this->ev_info;
	}
		
	//-----------------------------------------------------------------------------------
	//---- PANTALLA DE RECHAZO E IMPRESION ----------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__pant_imprimir_rechazar(guarani_ei_pantalla $pantalla)
	{
		// En la pantalla de Imprimir-Rechazar el campo observaciones es editable y no se muestra en el ev.
		$datos = $this->s__datos_solicitud;
		unset($datos['observaciones']);
		$this->ev_info()->set_datos($datos);
		// Se setea el mensaje de confirmación al evento de Rechazar la Solicitud.
		$this->evento('rechazar')->set_msg_confirmacion(guarani_mensajes::get('800CON_confirmar_rechazo'));
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_rechazo_solicitud -------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__form_rechazo_solicitud(toba_ei_formulario $form)
	{
		// Se muestran las observaciones de la solicitud dada de alta y se podrán editar para indicar el Motivo del Rechazo.
		if (isset($this->s__datos_form_rechazo)) {
            $form->set_datos($this->s__datos_form_rechazo);
            if (!$this->s__imprimir) {
                $this->controlador->set_hubo_cambios();
            }
		} else {
			$form->set_datos($this->s__datos_solicitud);
		}
		
		// Cargo el combo de autoridades
		$autoridades = toba::consulta_php('co_autoridades_cargos')->get_autoridades_constancia($this->s__datos_solicitud['constancia']);
		$opciones_autoridades['nopar'] = '-- Seleccione --';
		foreach ($autoridades as $autoridad) {
			$opciones_autoridades[$autoridad['autoridad']] = $autoridad['autoridad_nombre'] . ' - ' . $autoridad['autoridad_calidad'];
		}
		$form->ef('autoridad')->set_opciones($opciones_autoridades);
				
		// Si se presiono el boton imprimir se debe setear como solo lectura el campo Observaciones y Autoridades
		if ($this->s__imprimir) {
			$form->ef('autoridad')->set_solo_lectura();
			$form->ef('observaciones')->set_solo_lectura();
		}    

		$form->evento('ver_historial')->vinculo()->set_parametros(array('alumno' => $this->s__datos_solicitud['alumno'], 'solicitud' => $this->s__datos_solicitud['solicitud'])); 
	}

	function evt__form_rechazo_solicitud__modificacion($datos)
	{
		// Se editan las observaciones del motivo de rechazo de la solicitud.
		if (!empty($datos)) {
			$this->s__datos_form_rechazo = $datos;
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA DE REGISTRAR ENTREGA ------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__pant_registrar_entrega(guarani_ei_pantalla $pantalla)
	{

		$parametros = current( toba::consulta_php('co_constancias')->get_solicitud_params($this->s__solicitud));

		if (isset($parametros['campo']) && $parametros['campo'] == 'evaluacion') {
			//solicito los datos de la evalucion $parametros['valor']
			$condicion = " sga_evaluaciones.evaluacion = '" . $parametros['valor'] . "'";
			$datos_evaluacion = current(toba::consulta_php('co_evaluaciones')->get_evaluaciones_parciales_docentes($condicion));

			$this->datos_adicionales = 'Nombre de la evalucion: ' . $datos_evaluacion['evaluacion_nombre'] . ' comisión ' . $datos_evaluacion['comision_nombre'] . ' Docentes a cargo: ' . $datos_evaluacion['docentes'];
			$this->s__datos_solicitud['datos_adicionales'] = $this->datos_adicionales;
		}

		$this->ev_info()->set_datos($this->s__datos_solicitud);
	}

	//-----------------------------------------------------------------------------------
	//---- form_entrega_solicitud -------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__form_entrega_solicitud(toba_ei_formulario $form)
	{
		// Se setea como fecha y hora de entrega por defecto la fecha y hora actuales.
		$fecha_actual = guarani_fecha::get_ahora();
		// Se formatea la fecha y hora para el ef_editable_fecha_hora.
		$fecha_hora_visual = guarani_fecha::separar_fecha_hora($fecha_actual);
		$form->set_datos_defecto(array('fecha_entrega'=>$fecha_hora_visual));
		if (isset($this->s__datos_form_entrega)) {
			$this->controlador->set_hubo_cambios();
			$fecha_hora_temp = guarani_fecha::separar_fecha_hora($this->s__datos_form_entrega['fecha_entrega']);
			$form->ef('fecha_entrega')->set_estado($fecha_hora_temp);
		}
		$form->evento('ver_historial')->vinculo()->set_parametros(array('alumno' => $this->s__datos_solicitud['alumno'], 'solicitud' => $this->s__datos_solicitud['solicitud'])); 
	}

	function evt__form_entrega_solicitud__modificacion($datos)
	{
		// Se formatean los datos del arreglo del ef fecha hora para guardarlos en la base.
		if (isset($datos['fecha_entrega'])) {
			$datos['fecha_entrega'] = "{$datos['fecha_entrega'][0]} {$datos['fecha_entrega'][1]}";
		}
		// Se setean los datos de la fecha y hora de entrega de la solicitud.
		$this->s__datos_form_entrega = $datos;
	}
	
	//-----------------------------------------------------------------------------------
	//---- EVENTOS ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function evt__rechazar()
	{
		// Se realizan las actualizaciones correspondientes al Rechazo de la Solicitud.
		if (isset($this->s__datos_form_rechazo['observaciones'])) {
			$observaciones = $this->s__datos_form_rechazo['observaciones'];
		} else {
			$observaciones = '';
		}
		$this->cn()->rechazar_solicitud_constancia($this->s__solicitud, $this->s__datos_usuario['id_persona'], $observaciones);
		
		// Se invoca al notificador para enviar mensaje/email al alumno.
		$parametros = ['id_solicitud' => $this->s__solicitud];
		guarani::notificador()->enviar(notificador::administrar_solicitudes, $parametros);

		// Se informa el éxito del Rechazo de la Solicitud.
		$msje = guarani_mensajes::get('800CON_rechazar_ok');
		toba::notificacion()->agregar($msje, comunes::error_informacion);
		$this->controlador()->evt__cancelar();
	}
	
	function evt__registrar_entrega()
	{
		// Se realizan las actualizaciones correspondientes a la Entrega de la Solicitud.
		$this->cn()->registrar_entrega_solicitud_constancia($this->s__solicitud, $this->s__datos_usuario['id_persona'], $this->s__datos_form_entrega['fecha_entrega'], $this->s__datos_solicitud);
		// Se informa el éxito del registro de la Entrega de la Solicitud.
		$msje = guarani_mensajes::get('800CON_entregar_ok');
		toba::notificacion()->agregar($msje, comunes::error_informacion);
		$this->controlador()->evt__cancelar();
	}
	
	// Recibe un parámetro señalando si además de Imprimir la Solicitud de Constancia, el usuario desea Registrar la Entrega de la misma.
	function evt__imprimir()
	{
		// Se realizan las actualizaciones correspondientes a la Impresión 
		if (isset($this->s__datos_form_rechazo['observaciones'])) {
				$observaciones = $this->s__datos_form_rechazo['observaciones'];
		} else {
				$observaciones = '';
		}
		$this->cn()->registrar_impresion_solicitud_constancia($this->s__solicitud, $this->s__datos_usuario['id_persona'], false, $observaciones);
		
		// Se invoca al notificador para enviar mensaje/email al alumno.
		$parametros = ['id_solicitud' => $this->s__solicitud];
		guarani::notificador()->enviar(notificador::administrar_solicitudes, $parametros);
		
		// Se informa el éxito del registro de la Impresión de la Solicitud de Constancia..
		$msje = guarani_mensajes::get('800CON_imprimir_ok');
		toba::notificacion()->agregar($msje, comunes::error_informacion);
		// Se pone el true el flag que permite saber cuando abrir en js la ventana correspondiente al reporte Jasper.
		$this->s__imprimir = true;
		// Se deshabilitan los botones de Imprimir y Rechazar ya que al imprimir se abrirá la vista Jasper y el usuario quedará
		// en la misma pantalla, pero la única acción posible es volver al inicio.
		$this->evento('rechazar')->desactivar();
		$this->evento('imprimir')->desactivar();
		$this->evento('imprimir_entregar')->desactivar();
		$this->controlador()->evento('eliminar')->desactivar();
	}
	
	// Recibe un parámetro señalando si además de Imprimir la Solicitud de Constancia, el usuario desea Registrar la Entrega de la misma.
	function evt__imprimir_entregar()
	{
		// Se realizan las actualizaciones correspondientes a la Impresión y la Entrega de la Solicitud de Constancia.
        if (isset($this->s__datos_form_rechazo['observaciones'])) {
			$observaciones = $this->s__datos_form_rechazo['observaciones'];
		} else {
			$observaciones = '';
		}
		$this->cn()->registrar_impresion_solicitud_constancia($this->s__solicitud, $this->s__datos_usuario['id_persona'], true, $observaciones);
		// Se informa el éxito del registro de la Impresión de la Solicitud de Constancia..
		$msje = guarani_mensajes::get('800CON_imprimir_entregar_ok');
		toba::notificacion()->agregar($msje, comunes::error_informacion);
		// Se pone el true el flag que permite saber cuando abrir en js la ventana correspondiente al reporte Jasper.
		$this->s__imprimir = true;
		// Se deshabilitan los botones de Imprimir y Rechazar ya que al imprimir se abrirá la vista Jasper y el usuario quedará
		// en la misma pantalla, pero la única acción posible es volver al inicio.
		$this->evento('rechazar')->desactivar();
		$this->evento('imprimir')->desactivar();
		$this->evento('imprimir_entregar')->desactivar();
		$this->controlador()->evento('eliminar')->desactivar();
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
                $param_nombre_persona_doc_oficiales = persona::get_param_nombre_persona_doc_oficiales();		
		$report->set_parametro('apellido_y_nombre', 'S', $param_nombre_persona_doc_oficiales);
		if (!is_null($this->s__datos_form_rechazo['autoridad'])) {
			$autoridad = toba::consulta_php('co_autoridades_cargos')->get_datos_basicos_autoridad($this->s__datos_form_rechazo['autoridad']);
			$str = (is_null($autoridad['autoridad_titulo'])) ? $autoridad['autoridad_nombre'] : $autoridad['autoridad_titulo'].' '.$autoridad['autoridad_nombre'];
			$report->set_parametro('autoridad_nombre', 'S', $str);
			$report->set_parametro('autoridad_cargo', 'S', $autoridad['autoridad_cargo']);
		}

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

                if (!is_null($this->s__datos_form_rechazo['autoridad'])) {
			$autoridad = toba::consulta_php('co_autoridades_cargos')->get_datos_basicos_autoridad($this->s__datos_form_rechazo['autoridad']);
			$str = (is_null($autoridad['autoridad_titulo'])) ? $autoridad['autoridad_nombre'] : $autoridad['autoridad_titulo'].' '.$autoridad['autoridad_nombre'];
			$report->set_parametro('autoridad_nombre', 'S', $str);
			$report->set_parametro('autoridad_cargo', 'S', $autoridad['autoridad_cargo']);
		}

		$parametros = toba::consulta_php('co_constancias')->get_solicitud_params($this->s__solicitud);
		foreach ($parametros as $param) {
			$report->set_parametro($param['campo'], $param['tipo_de_dato'], $param['valor']);
		}

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
        }

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		// Abre la ventana que muestra el reporte Jasper para la impresión.
		
		if ($this->s__imprimir) {
			$this->s__imprimir = false;

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
