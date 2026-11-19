<?php
/**
 *
 *
 * @package Examenes
 * @subpackage Copiado Masivo de Mesas de Examen
 */

class ci_nav_copiado_masivo_mesas_de_examen extends ci_nav_procesos_bk_wizzard 
{
	//Filtros
	protected $s__filtro_ras;
	protected $s__filtro_propuestas;
	//Datos Filtros
	protected $s__datos_filtro_ras;
	protected $s__datos_filtro_propuestas;
	//Flags para eliminar la selección al filtrar
	protected $seleccion_ras_filtrada = false;
	protected $seleccion_props_filtrada = false;
	//Temporales
	protected $datos_turno_origen_temp;
	protected $datos_turno_destino_temp;
	//Componentes Visuales de información de selecciones previas.
	protected $ev_info_origen;
	protected $ev_info_filtros;
	protected $ev_info_destino;

	protected $s__anio_academico_origen;
	
	protected $mje_confirma_copiado = '800EXA_copiar_mesas';
	
	//Se resetean todas las variables de sesión de la clase. Utilizado en el evento volver al inicio de la operación.
	function resetear_variables()
	{
		$this->s__filtro_ras = '';
		$this->s__filtro_propuestas = '';
		unset($this->s__datos_filtro_ras);
		unset($this->s__datos_filtro_propuestas);
		//Elimina las selecciones realizadas en todas las pantallas del wizard.
		$this->eliminar_seleccion_completa();
	}
	
	//-----------------------------------------------------------------------------------
	//---- TURNO ORIGEN -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	
	function conf__turno_origen(toba_ei_pantalla $pantalla)
	{
		//Si se filtró por responsables académicas se limpia la selección de las mismas y también la lista descriptiva.
		if ($this->seleccion_ras_filtrada) {
			$this->eliminar_seleccion_previa();
		}
	}
	
	//---- form_turno_origen ----------------------------------------------------------
	
	function conf__form_turno_origen(form_turno_origen $form)
	{
		if (isset($this->datos_turno_origen_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_turno_origen_temp);
		} elseif (($this->existe_seleccion('info_origen'))) {
			$datos = $this->get_seleccion_valor('info_origen');
			$form->set_datos($datos);
		}
	}

	function evt__form_turno_origen__modificacion($datos)
	{
		$this->datos_turno_origen_temp = $datos;
		$this->s__anio_academico_origen = $datos['anio_academico_origen'];
		
		//Hallo datos a mostrar: turno y ubicacion
		$turno_exa	= toba::consulta_php('co_turnos_de_examen')->get_nombre_turno_examen($datos['turno_origen']);
		$datos_ubic	= toba::consulta_php('co_ubicaciones')->get_ubicacion_info($datos['ubicacion']);
		$ubicacion	= $datos_ubic['ubicacion_nombre'];
		
		//Armo el array para setearlo en el ev de información
		$datos['turno_examen_nombre'] = $turno_exa;
		$datos['ubicacion_nombre'] = $ubicacion;
		
		//Seteo los datos
		$this->set_seleccion(array('info_origen' => $datos));
	}
	
	//---- filtro_ras -------------------------------------------------------------------
	
	function conf__filtro_ras(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_ras)) {
			$filtro->set_datos($this->s__datos_filtro_ras);
		} else {
			$filtro->colapsar();
		}
	}

	function evt__filtro_ras__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_ras = $datos;
			$this->s__filtro_ras = $this->dep('filtro_ras')->get_sql_where();
			//Se setea el flag para eliminar la selección del cuadro al filtrar.
			$this->seleccion_ras_filtrada = true;
		} else {
			unset($this->s__datos_filtro_ras);
			$this->s__filtro_ras = '';
		}
	}

	function evt__filtro_ras__cancelar()
	{
		unset($this->s__datos_filtro_ras);
		$this->s__filtro_ras = '';
	}
	
	//---- cuadro_ras -------------------------------------------------------------------
	
	function conf__cuadro_ras(toba_ei_cuadro $cuadro)
	{
		$indx_msg = $this->indx_msg_eof;
		if (isset($this->s__filtro_ras) && ! empty($this->s__filtro_ras)) {
			$indx_msg = $this->indx_msg_eof . '_filtrado';
			$filtro = $this->s__filtro_ras;
		} else {
			$filtro = 'true';
		}
		$etiqueta = guarani_etiquetas::get_tabla('sga_responsables_academicas');
		$msj = guarani_mensajes::get($indx_msg, array($etiqueta->get_enf('plural')));
		$cuadro->set_eof_mensaje($msj);
		$institucion_instalacion = toba::db()->quote(guarani_configuracion::instancia()->get_valor_config('institucion_instalacion'));
		$filtro .= " AND sga_responsables_academicas.institucion = $institucion_instalacion";

		$datos = toba::consulta_php('co_responsables_academicas')->get_listado($filtro);
		$cuadro->set_datos($datos);
	}
	
	function evt__cuadro_ras__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function conf_evt__cuadro_ras__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_ras', $fila));
	}
	
	//Validación de salida
	function validar_turno_origen()
	{
		//Valido selecciones.
		//Valido que se haya seleccionado alguna Responsable Académica
		//No se verifica que se hayan seleccionado un Turno y una Ubicación porque son de carga obligatoria.
		if ($this->falta_seleccionar('responsable_academica')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una Responsable Académica'));
			throw guarani::error($mje);
		}
		//Valido que la Ubicación seleccionada tenga Mesas de Examen definidas en el Turno de Exámen Origen seleccionado.
		$info_origen = $this->get_seleccion_valor('info_origen');
		$ubicacion = $info_origen['ubicacion'];
		$turno_examen = $info_origen['turno_origen'];
		$this->cn()->validar_datos_origen($ubicacion, $turno_examen);
	}

	//-----------------------------------------------------------------------------------
	//---- PROPUESTAS -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__propuestas(toba_ei_pantalla $pantalla)
	{
		//Datos para los evs de información.
		$datos_origen = $this->get_seleccion_valor('info_origen');
		$this->ev_info_origen()->set_datos($datos_origen);
		$datos_filtros = $this->get_seleccion('resp_acad_nombre');
		$this->ev_info_filtros()->set_datos($datos_filtros);
		//Si se filtró por propuestas se limpia la selección de las mismas.
		if ($this->seleccion_props_filtrada) {
			$this->eliminar_seleccion_previa();
		}
	}
	
	//---- filtro_propuestas ------------------------------------------------------------

	function conf__filtro_propuestas(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_propuestas)) {
			$filtro->set_datos($this->s__datos_filtro_propuestas);
		} else {
			$filtro->colapsar();
		}
	}

	function evt__filtro_propuestas__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_propuestas = $datos;
			$this->s__filtro_propuestas = $this->dep('filtro_propuestas')->get_sql_where();
			//Se setea el flag para eliminar la selección del cuadro al filtrar.
			$this->seleccion_props_filtrada = true;
		} else {
			unset($this->s__datos_filtro_propuestas);
			$this->s__filtro_propuestas = '';
		}
	}

	function evt__filtro_propuestas__cancelar()
	{
		unset($this->s__datos_filtro_propuestas);
		$this->s__filtro_propuestas = '';
	}
	
	//---- cuadro_propuestas ------------------------------------------------------------
	
	function conf__cuadro_propuestas(toba_ei_cuadro $cuadro)
	{
		$info_origen = $this->get_seleccion_valor('info_origen');
		$ras = $this->get_seleccion_valor('responsable_academica');
		$cond_ubicacion	= "sga_propuestas.propuesta IN (SELECT	sga_propuestas_oferta.propuesta 
														FROM	sga_propuestas_oferta 
														WHERE	sga_propuestas_oferta.ubicacion = {$info_origen['ubicacion']})";
		
		$filtro_ras = guarani_sql::armar_condicion_in($ras, 'sga_propuestas_ra', 'responsable_academica', 'responsable_academica');
		$cond_ras	= "sga_propuestas.propuesta IN 
							(SELECT sga_propuestas_ra.propuesta 
							 FROM sga_propuestas_ra 
							 WHERE $filtro_ras)";

		$cond_ubic_ra = $cond_ubicacion . ' AND ' . $cond_ras;
		$filtro_prop = ($this->s__filtro_propuestas == '') ? $cond_ubic_ra : $cond_ubic_ra . ' AND ' . $this->s__filtro_propuestas;

		$indx_msg = $this->indx_msg_eof;
		if (isset($this->s__filtro_propuestas) && ! empty($this->s__filtro_propuestas)) {
			$indx_msg = $this->indx_msg_eof . '_filtrado';
		} 
		$etiqueta = guarani_etiquetas::get_tabla('sga_propuestas');
		$msj = guarani_mensajes::get($indx_msg, array($etiqueta->get_enf('plural')));
		$cuadro->set_eof_mensaje($msj);
		
		$datos = toba::consulta_php('co_propuestas')->get_planes_activos_con_propuestas_en_versiones_activas($filtro_prop);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_propuestas__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function conf_evt__cuadro_propuestas__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_propuestas', $fila));
	}
	
	//Validación de salida
	function validar_propuestas()
	{
		//Valido que se haya seleccionado alguna Propuesta
		if ($this->falta_seleccionar('plan')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una Propuesta'));
			throw guarani::error($mje);
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- TURNO DESTINO ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__turno_destino(toba_ei_pantalla $pantalla)
	{
		$datos_origen = $this->get_seleccion_valor('info_origen');
		$this->ev_info_origen()->set_datos($datos_origen);
		$datos_filtros = $this->get_seleccion(array('resp_acad_nombre', 'propuesta_nombre'));
		$this->ev_info_filtros()->set_datos($datos_filtros);
		$mensaje = guarani_mensajes::get($this->mje_confirma_copiado);
		$this->evento('cambiar_tab__siguiente')->set_msg_confirmacion($mensaje);
		$this->evento('cambiar_tab__siguiente')->set_etiqueta('Copiar Mesas');
		$this->evento('cambiar_tab__siguiente')->set_imagen('/generar.png');
	}
	
	//---- form_periodo_destino ---------------------------------------------------------
	
	function conf__form_turno_destino(toba_ei_formulario $form)
	{
		//Setea los valores del año academico y ubicacion origen.
		$datos_origen 	= $this->get_seleccion_valor('info_origen');
		$form->set_datos(array('ubicacion_nombre' => $datos_origen['ubicacion_nombre']));
		$form->set_solo_lectura(array('ubicacion_nombre'));
		if (isset($this->datos_turno_destino_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_turno_destino_temp);
		} elseif (($this->existe_seleccion('info_destino'))) {
			$datos = $this->get_seleccion_valor('info_destino');
			$form->set_datos($datos);
		}
	}

	function evt__form_turno_destino__modificacion($datos)
	{
		$this->datos_turno_destino_temp = $datos;
		
		//Hallo datos a mostrar: turno y criterios
		$turno_exa	= toba::consulta_php('co_turnos_de_examen')->get_nombre_turno_examen($datos['turno_destino']);
		
		//Armo el array para setearlo en el ev de información
		$datos['turno_destino_nombre'] = $turno_exa;
		
		//Seteo los datos
		$this->set_seleccion(array('info_destino' => $datos));
	}
	
	//Validación de salida
	function validar_turno_destino()
	{
		//Valores de las selecciones previas.
		$origen			= $this->get_seleccion_valor('info_origen');
		$destino		= $this->get_seleccion_valor('info_destino');
		$ras			= $this->get_seleccion_valor('responsable_academica');
		$planes			= $this->get_seleccion_valor('plan');
		$turno_origen	= $origen['turno_origen'];
		$turno_destino	= $destino['turno_destino'];
		$ubicacion		= $origen['ubicacion'];

		//Se realizan las validaciones correspondientes sobre la información ingresada para el Destino.
		$this->cn()->validar_datos_destino($turno_origen, $turno_destino, $ras, $ubicacion, $planes);
	}
	
	//-----------------------------------------------------------------------------------
	//---- GENERAR ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__generar(toba_ei_pantalla $pantalla)
	{
		//Oculto el boton Anterior e inhabilito la edición del formulario
		$this->evento('cambiar_tab__anterior')->ocultar();
		$datos_origen = $this->get_seleccion_valor('info_origen');
		$this->ev_info_origen()->set_datos($datos_origen);
		$datos_filtros = $this->get_seleccion(array('resp_acad_nombre', 'propuesta_nombre'));
		$this->ev_info_filtros()->set_datos($datos_filtros);
		$datos_destino = $this->get_seleccion_valor('info_destino');
		$this->ev_info_destino()->set_datos($datos_destino);
		//Se inhabilita la edición del formulario que se utiliza solo para mostrar los totales resultantes del proceso.
		$this->dependencia('form_totales')->set_solo_lectura();
	}
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares para la visualización de las selecciones previas ------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info_origen()
	{
		if (!isset($this->ev_info_origen)) {
			$this->ev_info_origen = guarani::ev_info_datos();
			$this->ev_info_origen->set_controlador($this);
			$this->ev_info_origen->set_titulo('Origen');
			$this->ev_info_origen->set_style('border-style: solid; border-width: 1px; border-color: #DDD;');
			$this->ev_info_origen->set_style_fila('height: 20px;');
			$this->ev_info_origen->set_columnas_a_mostrar(array('anio_academico_origen', 'turno_examen_nombre', 'ubicacion_nombre'));
			$this->ev_info_origen->set_columnas_fila(array('anio_academico_origen', 'turno_examen_nombre', 'ubicacion_nombre'), 1);
			$this->ev_info_origen->set_etiqueta('anio_academico_origen', 'Año Académico');
			$this->ev_info_origen->set_etiqueta('turno_examen_nombre', 'Turno');
			$this->ev_info_origen->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info_origen;
	}
	
	function ev_info_filtros()
	{
		if (!isset($this->ev_info_filtros)) {
			$this->ev_info_filtros = guarani::ev_info_datos();
			$this->ev_info_filtros->set_controlador($this);
			$this->ev_info_filtros->set_titulo('Filtros para las mesas');
			$this->ev_info_filtros->set_style('border-style: solid; border-width: 1px; border-color: #DDD;');
			$this->ev_info_filtros->set_style_fila('height: 20px;');
			$this->ev_info_filtros->set_max_datos_por_columna(10);
			$this->ev_info_filtros->set_max_chars_por_columna(100);
			$this->ev_info_filtros->set_columnas_a_mostrar(array('resp_acad_nombre', 'propuesta_nombre'));
			$this->ev_info_filtros->set_columnas_fila(array('resp_acad_nombre'), 1);
			$this->ev_info_filtros->set_columnas_fila(array('propuesta_nombre'), 2);
			$this->ev_info_filtros->set_etiqueta('resp_acad_nombre', 'Responsables Académicas');
			$this->ev_info_filtros->set_etiqueta('propuesta_nombre', 'Propuestas');
		}
		return $this->ev_info_filtros;
	}
	
	function ev_info_destino()
	{
		if (!isset($this->ev_info_destino)) {
			$this->ev_info_destino = guarani::ev_info_datos();
			$this->ev_info_destino->set_controlador($this);
			$this->ev_info_destino->set_titulo('Destino');
			$this->ev_info_destino->set_style('border-style: solid; border-width: 1px; border-color: #DDD;');
			$this->ev_info_destino->set_style_fila('height: 20px;');
			$this->ev_info_destino->set_columnas_a_mostrar(array('anio_academico_destino', 'turno_destino_nombre', 'ubicacion_nombre'));
			$this->ev_info_destino->set_columnas_fila(array('anio_academico_destino', 'turno_destino_nombre', 'ubicacion_nombre'), 1);
			$this->ev_info_destino->set_etiqueta('anio_academico_destino', 'Año Académico');
			$this->ev_info_destino->set_etiqueta('turno_destino_nombre', 'Turno');
			$this->ev_info_destino->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info_destino;
	}
	
	function cargar_info($pantalla_info)
	{
		//Función de carga del wizard para la información de la selección previa. No se implementa porque se utilizan evs.
	}
	
	//-----------------------------------------------------------------------------------
	//---- EVENTOS ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function evt__volver()
	{
		$this->resetear_variables();
		$this->set_pantalla('turno_origen');	
	}
	
	/**
	 * Se maneja el avance de pantalla en el wizard para disparar el proceso de copiado en el último paso.
	 */
	function evt__cambiar_tab__siguiente()
	{
		parent::evt__cambiar_tab__siguiente();
		//Si clickeó el boton 'Copiar Mesas' entonces se dispara el proceso de Copiado de las Mesas
		if ($this->s__pantalla_actual == 'turno_destino') {
			
			//Valores
			$origen			= $this->get_seleccion_valor('info_origen');
			$destino		= $this->get_seleccion_valor('info_destino');
			
			//Armo el array con los parámetros a mandar en la creación del proceso
			$parametros = array_merge(	array('origen'		=> $origen),
										array('destino' 	=> $destino));
			
			//Ejecuto el proceso en background.
			$this->cn()->ejecutar_proceso($parametros);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares para la carga de combos -------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_anios_academicos_destino()
	{
		return toba::consulta_php('co_anios_academicos')->get_anios_academicos_mayores_o_iguales($this->s__anio_academico_origen);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Funciones ajax utilizadas en el proceso de copiado de mesas ------------------
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
		$contenido_totales = $this->cn()->get_resultado_proceso(pro_copiado_masivo_mesas_de_examen::reporte_resultados);
		$resultados = current($contenido_totales['mensajes']);
		if (!empty($resultados)) {
			$respuesta->set($resultados);
		}
	}

	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		/**
		 * Para los evento de Reporte de Mesas creadas con éxito, creadas con Observaciones y de Actividades con cantidad
		 * de Mesas, se setean parámetros que le indican a cada item de popup al que se invoca si mostrar o no las
		 * Observaciones y la cantidad de Mesas.
		 */
		$mostrar_cant = false;
		switch ($params['evento']) {
			case 'rep_actividades_con_mesas_copiadas':
				$reporte				= pro_copiado_masivo_mesas_de_examen::reporte_activ_con_mesas;
				$operacion				= '38000081';
				$ind_datos_impresion	= mesa_de_examen::ind_actividades_mesas;
				$mostrar_cant 			= true;
			break;
			case 'rep_mesas_copiadas':
				$reporte				= pro_copiado_masivo_mesas_de_examen::reporte_mesas_copiadas;
				$operacion				= '38000097';
				$ind_datos_impresion	= mesa_de_examen::ind_mesas_llamados;
				$mostrar_obs			= false;
			break;
			case 'rep_mesas_con_obs':
				$reporte				= pro_copiado_masivo_mesas_de_examen::reporte_mesas_con_observ;
				$operacion				= '38000097';
				$ind_datos_impresion	= mesa_de_examen::ind_mesas_llamados;
				$mostrar_obs			= true;
			break;
			case 'rep_mesas_no_copiadas':
				$reporte				= pro_copiado_masivo_mesas_de_examen::reporte_mesas_no_copiadas;
				$operacion				= '37000066';
				$ind_datos_impresion	= mesa_de_examen::ind_mesas;
		}

        if ($this->modo_visualizacion()) {
            $datos = $this->controlador()->get_resultado_reporte($reporte);
        } else {
            //Obtengo el Proceso para pedirle el log de resultados
            $datos = $this->cn()->get_resultado_proceso($reporte);
        }
		// Obtengo los datos correspondientes al encabezado del reporte.
		$datos_destino = $this->get_seleccion_valor('info_destino');
		$conf	= array(mesa_de_examen::ind_encab_anio	=> $datos_destino['anio_academico_destino'],
						mesa_de_examen::ind_encab_turno => $datos_destino['turno_destino_nombre']);
		
		//Almaceno los datos en la instasncia de impresion para ser consumida luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos(array($ind_datos_impresion => $datos['mensajes'], mesa_de_examen::mostrar_cant => $mostrar_cant));
		guarani_datos_impresion::instancia()->set_conf($conf);
		guarani_datos_impresion::instancia()->guardar_datos_imp();
		
		//Creo el vinculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $operacion, [], array('celda_memoria'=>'popup'));
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
      
        if ($this->get_id_pantalla() == 'turno_origen') {
        	$id_js_filtro_ras = $this->dependencia('filtro_ras')->get_id_objeto_js();
			$id_js_cuadro_ras = $this->dependencia('cuadro_ras')->get_id_objeto_js();
			
			echo "
			//Chequea si se encuentra seleccionada alguna fila en el cuadro de responsables académicas.
			{$id_js_filtro_ras}.evt__filtrar = function() {
				//Obtiene las filas del cuadro multiseleccion relacionado al filtro.
				var seleccion = {$id_js_cuadro_ras}.get_ids_seleccionados('seleccion');
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
        
        if ($this->get_id_pantalla() == 'propuestas') {
			$id_js_filtro_props = $this->dependencia('filtro_propuestas')->get_id_objeto_js();
			$id_js_cuadro_props = $this->dependencia('cuadro_propuestas')->get_id_objeto_js();
       
        	echo "
			//Chequea si se encuentra seleccionada alguna fila en el cuadro de propuestas.
			{$id_js_filtro_props}.evt__filtrar = function() {
				//Obtiene las filas del cuadro multiseleccion relacionado al filtro.
				var seleccion = {$id_js_cuadro_props}.get_ids_seleccionados('seleccion');
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