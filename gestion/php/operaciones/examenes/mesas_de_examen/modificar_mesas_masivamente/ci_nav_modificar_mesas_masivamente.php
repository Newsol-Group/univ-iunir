<?php
/**
 *
 *
 * @package Examenes
 * @subpackage Modificar Mesas Masivamente
 */

class ci_nav_modificar_mesas_masivamente extends ci_nav_procesos_bk_wizzard 
{
	//Filtros
	protected $s__filtro_mesas;
	//Datos Filtros
	protected $s__datos_filtro_mesas;
	//Flags para eliminar la selección al filtrar
	protected $seleccion_mesas_filtrada = false;
	//Temporales
	protected $datos_modificacion_temp;
	//Componentes Visuales de información de selecciones previas.
	protected $ev_info;
	
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $mje_confirma_modificacion = '800EXA_modificar_mesas_confirmar';
	protected $mje_listado_mesas = '800EXA_modificar_mesas_sin_actas';
	
	//Se resetean todas las variables de sesión de la clase. 
	//Utilizado en el evento volver al inicio de la operación.
	function resetear_variables()
	{
		unset($this->s__filtro_mesas);
		unset($this->s__datos_filtro_mesas);
		//Elimina las selecciones realizadas en todas las pantallas del wizard.
		$this->eliminar_seleccion_completa();
	}
	
	//-----------------------------------------------------------------------------------
	//---- TIPO MODIFICACIÓN ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	//---- form_tipo_modificacion -------------------------------------------------------
	
	function conf__form_datos_modificacion(toba_ei_formulario $form)
	{
		if (isset($this->datos_modificacion_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_modificacion_temp);
		} elseif (($this->existe_seleccion('info_modificacion'))) {
			$datos = $this->get_seleccion_valor('info_modificacion');
			$form->set_datos($datos);
		}
	}

	function evt__form_datos_modificacion__modificacion($datos)
	{
		$this->datos_modificacion_temp = $datos;
		//Seteo los datos
		switch ($datos['tipo_modificacion']) {
			case mesa_de_examen::cambio_suspension:
				$datos['tipo_modificacion_desc'] = mesa_de_examen::cambio_suspension_desc;
				break;
			case mesa_de_examen::cambio_fecha:
				$datos['tipo_modificacion_desc'] = mesa_de_examen::cambio_fecha_desc;
				break;
			case mesa_de_examen::cambio_horario:
				$datos['tipo_modificacion_desc'] = mesa_de_examen::cambio_horario_desc;
				break;
		}
		$datos['motivo_desc'] = toba::consulta_php('co_motivos_ausencia')->get_motivo_ausencia_nombre($datos['motivo']);
		$formateo = new toba_formateo('html');
		$datos['fecha_formato_visual'] = $formateo->formato_fecha($datos['fecha']);
		$this->set_seleccion(array('info_modificacion' => $datos));
	}
	
	//Validación de salida
	function validar_tipo_modificacion()
	{
		$datos_modificacion = $this->get_seleccion_valor('info_modificacion');
		$tipo	= $datos_modificacion['tipo_modificacion'];
		$fecha = null;
		$hora_inicio = null;
		$hora_fin = null;
		if (isset($datos_modificacion['fecha'])) {
			$fecha = $datos_modificacion['fecha'];	
		}
		if (isset($datos_modificacion['hora_inicio'])) {
			$hora_inicio = $datos_modificacion['hora_inicio'];
		}
		if (isset($datos_modificacion['hora_finalizacion'])) {
			$hora_fin = $datos_modificacion['hora_finalizacion'];
		}
		
		//Se realizan las validaciones correspondientes sobre la información ingresada para la modificación.
		$this->cn()->validar_datos_tipo_modificacion($tipo, $fecha, $hora_inicio, $hora_fin);
	}
	
	//-----------------------------------------------------------------------------------
	//---- PROPUESTAS -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__mesas(toba_ei_pantalla $pantalla)
	{
		$msj = guarani_mensajes::get($this->mje_listado_mesas);
		$pantalla->set_descripcion($msj);
		//Datos para el ev info.
		$datos_modificacion = $this->get_seleccion_valor('info_modificacion');
		$this->ev_info()->set_datos($datos_modificacion);
		//Si se filtró por propuestas se limpia la selección de las mismas.
		if ($this->seleccion_mesas_filtrada) {
			$this->eliminar_seleccion_previa();
		}
		$mensaje = guarani_mensajes::get($this->mje_confirma_modificacion);
		$this->evento('cambiar_tab__siguiente')->set_msg_confirmacion($mensaje);
		$this->evento('cambiar_tab__siguiente')->set_etiqueta('Procesar');
		$this->evento('cambiar_tab__siguiente')->set_imagen('/generar.png');
	}
	
	//---- filtro_propuestas ------------------------------------------------------------

	function conf__filtro_mesas(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_mesas)) {
			$filtro->set_datos($this->s__datos_filtro_mesas);
		} 
	}

	function evt__filtro_mesas__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_mesas = $datos;
			$this->s__filtro_mesas = $this->dep('filtro_mesas')->get_sql_where();
			//Se setea el flag para eliminar la selección del cuadro al filtrar.
			$this->seleccion_mesas_filtrada = true;
		} else {
			unset($this->s__datos_filtro_mesas);
			$this->s__filtro_mesas = '';
		}
	}

	function evt__filtro_mesas__cancelar()
	{
		unset($this->s__datos_filtro_mesas);
		$this->s__filtro_mesas = '';
	}
	
	//---- cuadro_propuestas ------------------------------------------------------------
	
	function conf__cuadro_mesas(toba_ei_cuadro $cuadro)
	{
		// Sin listado inicial, debe filtrar para obtener datos.
		if (!isset($this->s__filtro_mesas)) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			if (!empty($this->s__filtro_mesas)) {
				$this->indx_msg_eof .= '_filtrado';
			} 
			//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			// Se retornan los llamados mesas sin actas abiertas ni cerradas y que no han sido suspendidos.
			$cond_no_suspendida = "sga_llamados_mesa.estado <> '" . mesa_de_examen::mesa_estado_suspendido . "'";
			$filtro_mesas = ($this->s__filtro_mesas == '') ? $cond_no_suspendida : $cond_no_suspendida . ' AND ' . $this->s__filtro_mesas;
			$orden ='	vw_actividades.nombre, 
						periodos_turnos_examen.nombre,
						sga_mesas_examen.nombre
					';
			$datos = toba::consulta_php('co_mesas_de_examen')->get_listado_reporte_mesas_examen_sin_acta_generada($filtro_mesas, $orden);
			$cuadro->set_datos($datos);
		}
		$etiqueta = guarani_etiquetas::get_tabla('sga_mesas_examen');
		$msj = guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural')));
		$cuadro->set_eof_mensaje($msj);
	}

	function evt__cuadro_mesas__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion(array('mesas' => $seleccion));
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function conf_evt__cuadro_mesas__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_mesas', $fila));
	}
	
	//Validación de salida
	function validar_mesas()
	{
		//Valido que se haya seleccionado alguna Propuesta
		if ($this->falta_seleccionar('mesas')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('un Llamado de Mesa de Examen'));
			throw guarani::error($mje);
		}
		$this->cn()->validar_mesas_a_modificar();
	}
	
	//-----------------------------------------------------------------------------------
	//---- PROCESAR ---------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__procesar(toba_ei_pantalla $pantalla)
	{
		//Oculto el boton Anterior e inhabilito la edición del formulario
		$this->evento('cambiar_tab__anterior')->ocultar();
		$datos_modificacion = $this->get_seleccion_valor('info_modificacion');
		//Agrega el año académico de las Mesas seleccionadas en los datos de la información a modificar
		$datos_modificacion['anio'] = $this->s__datos_filtro_mesas['anio_academico']['valor'];
		$this->ev_info()->set_datos($datos_modificacion);
		//Se inhabilita la edición del formulario que se utiliza solo para mostrar los totales resultantes del proceso.
		$this->dependencia('form_totales')->set_solo_lectura();
	}
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares para la visualización de las selecciones previas ------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_titulo('Modificación a aplicar');
			$this->ev_info->set_style('border-style: solid; border-width: 1px; border-color: #DDD;');
			$this->ev_info->set_style_fila('height: 20px;');
			$this->ev_info->set_td_etiqueta_width('80px');
			$this->ev_info->set_td_valores_width('150px');
			$this->ev_info->set_columnas_a_mostrar(array('anio', 'tipo_modificacion_desc', 'motivo_desc', 'fecha_formato_visual', 'hora_inicio', 'hora_finalizacion'));
			$this->ev_info->set_columnas_fila(array('anio', 'tipo_modificacion_desc', 'motivo_desc'), 1);
			$this->ev_info->set_columnas_fila(array('fecha_formato_visual'), 2);
			$this->ev_info->set_columnas_fila(array('hora_inicio', 'hora_finalizacion'), 3);
			$this->ev_info->set_etiqueta('anio', 'Año Académico');
			$this->ev_info->set_etiqueta('tipo_modificacion_desc', 'Modificación');
			$this->ev_info->set_etiqueta('motivo_desc', 'Motivo');
			$this->ev_info->set_etiqueta('fecha_formato_visual', 'Fecha');
			$this->ev_info->set_etiqueta('hora_inicio', 'Hora Inicio');
			$this->ev_info->set_etiqueta('hora_finalizacion', 'Hora Fin');
		}
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- EVENTOS ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function evt__volver()
	{
		$this->resetear_variables();
		$this->set_pantalla('tipo_modificacion');	
	}
	
	/**
	 * Se maneja el avance de pantalla en el wizard para disparar el proceso de modificación en el último paso.
	 */
	function evt__cambiar_tab__siguiente()
	{
		parent::evt__cambiar_tab__siguiente();
		//Si clickeó el boton 'Procesar' entonces se dispara el proceso de Modificación de las Mesas
		if ($this->s__pantalla_actual == 'mesas') {
			
			//Valores
			$tipo_modificacion	= $this->get_seleccion_valor('info_modificacion');
			$mesas				= $this->get_seleccion_valor('mesas');
			
			//Armo el array con los parámetros a mandar en la creación del proceso
			$parametros = array_merge(	array('datos_modificacion'	=> $tipo_modificacion),
										array('mesas_a_modificar' 	=> $mesas));
			                        
			//Ejecuto el proceso en background.
			$this->cn()->ejecutar_proceso($parametros);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Funciones ajax utilizadas en el proceso de Modificación de mesas -------------
	//-----------------------------------------------------------------------------------
	
	function ajax__estado_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_estado_proceso();
		if (!empty($contenido)) {
			$respuesta->set($contenido);
		}
	}
	
	function ajax__resultados_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		//Se retorna el array de resultados con las cantidades totales como respuesta.
		$contenido_totales = $this->cn()->get_resultado_proceso(pro_modificar_mesas_masivamente::reporte_resultados);
		$resultados = current($contenido_totales['mensajes']);
		if (!empty($resultados)) {
			$respuesta->set($resultados);
		}
	}

	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		/**
		 * Para los evento de Reporte de Mesas modificadas con éxito, y Mesas no modificadas por error
		 * se setean parámetros que le indican a cada item de popup al que se invoca si mostrar o no las
		 * Observaciones y la cantidad de Mesas.
		 */
		switch ($params['evento']) {
			case 'rep_mesas_modificadas':
				$reporte				= pro_modificar_mesas_masivamente::reporte_modificadas;
				$operacion				= '37000126';
				$ind_datos_impresion	= mesa_de_examen::ind_mesas_llamados;
				$mostrar_obs 			= false;
			break;
			case 'rep_mesas_no_modificadas':
				$reporte				= pro_modificar_mesas_masivamente::reporte_no_modificadas;
				$operacion				= '37000126';
				$ind_datos_impresion	= mesa_de_examen::ind_mesas_llamados;
				$mostrar_obs			= true;
			break;
		}

        if ($this->modo_visualizacion()) {
            $datos = $this->controlador()->get_resultado_reporte($reporte);
        } else {
            //Obtengo el Proceso para pedirle el log de resultados
            $datos = $this->cn()->get_resultado_proceso($reporte);
        }
		
		// Obtengo los datos correspondientes al encabezado del reporte.
		$datos_encabezado = $this->get_seleccion_valor('info_modificacion');
		//Agrega el año académico de las Mesas seleccionadas en los datos de la información a modificar
		$datos_encabezado['anio'] = $this->s__datos_filtro_mesas['anio_academico']['valor'];
		
		//Almaceno los datos en la instasncia de impresion para ser consumida luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos(array($ind_datos_impresion => $datos['mensajes']));
		guarani_datos_impresion::instancia()->set_conf($datos_encabezado);
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		$params_popup = array();
		if (isset($mostrar_obs)) {
			$params_popup = array_merge($params_popup, array(mesa_de_examen::mostrar_observaciones => $mostrar_obs));
		}
		
		//Creo el vinculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $operacion, $params_popup, array('celda_memoria'=>'popup'));
		$respuesta->set($vinculo);
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		//Se realiza todo el procesamiento relativo al evento filtrar para las pantallas con cuadros multiseleccion.
		$msj_confirm_filtrar = guarani_mensajes::get('800_confirmar_filtro_pierde_seleccion');
      
        if ($this->get_id_pantalla() == 'mesas') {
        	$id_js_filtro_mesas = $this->dependencia('filtro_mesas')->get_id_objeto_js();
			$id_js_cuadro_mesas = $this->dependencia('cuadro_mesas')->get_id_objeto_js();
			
			echo "
			//Chequea si se encuentra seleccionada alguna fila en el cuadro de mesas.
			{$id_js_filtro_mesas}.evt__filtrar = function() {
				//Obtiene las filas del cuadro multiseleccion relacionado al filtro.
				var seleccion = {$id_js_cuadro_mesas}.get_ids_seleccionados('seleccion');
				if (seleccion.length != 0) {
					return confirmar_filtrado();
				}
			}
			
			function confirmar_filtrado(proyecto, operacion, url, es_popup) {
				if (!es_popup || typeof(es_popup) == 'undefined') {
					resp = confirm('$msj_confirm_filtrar');
					if (resp) {
						return true;
					} else {
						return false;
					}
				}
				return true;
			}
			
			";
        }
	}
}
?>