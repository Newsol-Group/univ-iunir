<?php
/**
 *
 *
 * @package		Matrícula
 * @subpackage 	Alumnos
 * 
 */
class ci_nav_cambiar_masivamente_plan_alumnos extends ci_nav_procesos_bk_wizzard
{
	protected $indx_msj_confirmacion = '800MAT_confirmar_cambio_plan';
	protected $indx_msj_sin_matrices = '800EQU_no_matriz_automatica';
	
	// Componente Visual de información.
	protected $ev_info;
	protected $s__datos_ev;
	
	// Datos temporales
	protected $datos_params_temp;
	
	function ini()
	{
		parent::ini();
		$this->dep('filtro_propuestas')->set_sin_filtrado_inicial();
	}
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		/**
		 * Permite eliminar todas las dependencias relacionadas con la configuración de destino al cual se quieren cambiar
		 * los alumnos, y agregar el cuadro de destinos posibles en caso de cambio a Planes diferentes Activos o Vigentes.
		 */
		if ($this->pantalla()->existe_dependencia('cuadro_destinos_disponibles')) {
			$this->pantalla()->eliminar_dep('cuadro_destinos_disponibles');
		}
		
		$destino = $this->dep('form_parametros')->ef('hacia')->get_estado();
		if (isset($destino) && $destino == alumno::dest_planes_activos_vigentes) {
			$this->pantalla()->agregar_dep('cuadro_destinos_disponibles');
		} 
	}
	

	//-----------------------------------------------------------------------------------
	//---- Pantalla Responsables Académicas ---------------------------------------------
	//-----------------------------------------------------------------------------------

	//---- filtro_ras ------------------------------------------------------------

	function conf__filtro_ras(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_ras', false);
	}

	function evt__filtro_ras__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_ras', $datos);
	}

	function evt__filtro_ras__cancelar()
	{
		$this->filtro_limpiar('filtro_ras');
	}

	//---- cuadro_ras ------------------------------------------------------------

	function conf__cuadro_ras(cuadro_seleccion_diferida $cuadro)
	{
		$filtro = $this->get_filtro('filtro_ras');
		$etiquetas = guarani_etiquetas::get_tabla('sga_responsables_academicas');
		$etiqueta = $etiquetas->get_enf('plural');
		$indx_msj = $this->indx_msg_eof . '_filtrado';
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array($etiqueta)));
		$datos = toba::consulta_php('co_responsables_academicas')->get_ras_institucion_instalacion($filtro);
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

	function validar_responsables_academicas()
	{
		if ($this->falta_seleccionar('valor')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una Responsable Académica'));
			throw guarani::error($mje);
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- Pantalla Propuestas ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__propuestas(toba_ei_pantalla $pantalla)
	{	
		$ra = $this->get_seleccion();
		$this->s__datos_ev = current(toba::consulta_php('co_responsables_academicas')->get_datos_responsable_academica($ra['valor']));
		$this->ev_info()->set_datos($this->s__datos_ev);
	}

	//---- filtro_propuestas ------------------------------------------------------------

	function conf__filtro_propuestas(guarani_ei_filtro $filtro)
	{
		$filtro->columna('propuesta')->set_condicion_fija('es_igual_a');
		$filtro->columna('plan')->set_condicion_fija('es_igual_a');
		$filtro->columna('version')->set_condicion_fija('es_igual_a');
		$this->filtro_configurar('filtro_propuestas', false);
	}

	function evt__filtro_propuestas__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_propuestas', $datos);
	}

	function evt__filtro_propuestas__cancelar()
	{
		$this->filtro_limpiar('filtro_propuestas');
	}

	//---- cuadro_propuestas ------------------------------------------------------------

	function conf__cuadro_propuestas(cuadro_seleccion_diferida $cuadro)
	{
		$filtro = $this->get_filtro('filtro_propuestas');
		$etiquetas = guarani_etiquetas::get_tabla('sga_propuestas');
		$etiqueta = $etiquetas->get_enf('plural');
		
		if (!isset($filtro)) {
			$indx_msj = $this->indx_msg_eof . '_sinfiltro';
		} else {
			$indx_msj = $this->indx_msg_eof . '_filtrado';
			 $filtro_vigentes = ' AND ' . "	sga_planes.estado IN ('". planes::estado_activo . "', '" . planes::estado_activo_vigente . "') AND
											sga_planes_versiones.estado IN ('". planes::estado_activo . "', '" . planes::estado_activo_vigente . "')";
			// Propuestas ofertadas por las Responsables Académicas previamente seleccionadas, según el filtro dado.
			$ras = $this->get_seleccion();
			$filtro_ras = guarani_sql::armar_condicion_in($ras, 'sga_responsables_academicas', 'responsable_academica', 'valor');
			$filtro = empty($filtro) ? $filtro_ras . $filtro_vigentes : $filtro . ' AND ' . $filtro_ras . $filtro_vigentes;
			$datos = toba::consulta_php('co_planes')->get_datos_plan_version(null, $filtro);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array($etiqueta)));
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

	function validar_propuestas()
	{
		if ($this->falta_seleccionar('plan_version')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una propuesta'));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla Parámetros Generales ------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__parametros_generales(toba_ei_pantalla $pantalla)
	{
		$plan = $this->get_seleccion();
		$nombre_plan = current(toba::consulta_php('co_planes')->get_datos_plan_version($plan['plan_version']));
		$this->s__datos_ev = array_merge($this->s__datos_ev, $nombre_plan);
		$this->ev_info()->set_datos($this->s__datos_ev);
		// Se cambia la etiqueta del botón Siguiente >
		$this->evento('cambiar_tab__siguiente')->set_etiqueta('Confirmar');
		$this->evento('cambiar_tab__siguiente')->set_imagen('aplicar.png');
		$this->evento('cambiar_tab__siguiente')->set_msg_confirmacion(guarani_mensajes::get($this->indx_msj_confirmacion));
	}

	/**
        Retorna las opciones de Período para aplicar filtro por Cohorte.
    */
	function get_tipos_cohorte()
	{
		return array(	array('valor' => 'D','descr' => 'Desde'),
						array('valor' => 'E','descr' => 'Entre'),
						array('valor' => 'H','descr' => 'Hasta')
					);
	}
	
	/**
        Retorna las opciones del Tipo de Cambio que puede seleccionar hacer el usuario.
    */
	function get_categoria_cambio()
	{
		// Si el plan versión origen seleccionado corresponde a la última versión vigente, no se incluye la opción para 
		// Cambiar de Versión (a la última versión vigente).
		$seleccion = $this->get_seleccion();
		if (toba::consulta_php('co_planes')->plan_version_es_actual($seleccion['plan_version'])) {
			return array(	array('valor' => alumno::cat_cambio_plan, 'descr' => 'Cambiar de Plan'),
						);
		} else {
			return array(	array('valor' => alumno::cat_cambio_plan, 'descr' => 'Cambiar de Plan'),
							array('valor' => alumno::cat_cambio_version, 'descr' => 'Cambiar de Versión')
						);
		}
	}
	
	/**
        Retorna los posibles destinos hacia donde se realiza el cambio.
    */
	function get_destinos_cambio($categoria)
	{
		// Se cargan los posibles destinos dependiendo de si se seleccionó Cambio de Plan o Cambio de Versión.
		if ($categoria == alumno::cat_cambio_plan) {
			return array(	array('valor' => apex_ef_no_seteado, 'descr' => '-- Seleccione --'),
							array('valor' => alumno::dest_planes_activos_vigentes, 'descr' => 'Planes Activos y Vigentes'),
							array('valor' => alumno::dest_plan_anterior, 'descr' => 'Plan anterior del Alumno')
						);
		} else {
			return array(	array('valor' => alumno::dest_ultima_version, 'descr' => 'Última Versíón Vigente')
						);
		}
	}
	
    //---- form_parametros -----------------------------------------------------------------

	function conf__form_parametros(toba_ei_formulario $form)
	{
		if (isset($this->datos_params_temp)) {
			$form->set_datos($this->datos_params_temp);
		}
		
		if ($form->ef('hacia')->tiene_estado())  {
			if ($form->ef('hacia')->get_estado() == alumno::dest_ultima_version) {
				// En caso de que el destino no sea Última Versión Vigente recupera los datos relativos a la misma.
				$datos_version_destino = current(toba::consulta_php('co_planes')->get_datos_plan_version($this->s__datos_ev['version_actual']));
				$form->ef('ultima_version_nombre')->set_estado($datos_version_destino['version_nombre']);
				$form->ef('ultima_version_plan_nombre')->set_estado($datos_version_destino['plan_nombre']);
			} 
		}
	}

	function evt__form_parametros__modificacion($datos)
	{
		$this->datos_params_temp = $datos;
		$this->set_seleccion($datos);
	}

	//---- cuadro_destinos_disponibles -----------------------------------------------------------------

	function conf__cuadro_destinos_disponibles(cuadro_seleccion_diferida $cuadro)
	{
		/** Planes versiones Activos o Vigentes, a los que se puede cambiar el alumno, correspondientes a la misma propuesta, 
		 *  y diferentes al plan versión actual de los alumnos, y cuya fecha de entrada en vigencia de mayor a la del plan
		 *  versión origen. Para la opción "Cambio de Plan-Planes Nuevos Activos y Vigentes".
		 */
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array('Planes Activos o Vigentes')));
	 	$plan_version = $this->get_seleccion();
		$datos_plan_version_origen = current(toba::consulta_php('co_planes')->get_datos_plan_version($plan_version['plan_version']));
		$filtro_propuesta = "sga_propuestas.propuesta = " . $plan_version['propuesta'];
		$filtro_version = "sga_planes_versiones.plan_version <> " . $plan_version['plan_version'];
		$filtro_vigentes = "sga_planes.estado IN ('". planes::estado_activo . "', '" . planes::estado_activo_vigente . "') AND
							sga_planes_versiones.estado IN ('". planes::estado_activo . "', '" . planes::estado_activo_vigente . "')";
		if (isset($datos_plan_version_origen['fecha_entrada_vigencia'])) {
			$fecha_vigencia_origen = toba::db()->quote($datos_plan_version_origen['fecha_entrada_vigencia']);
			$filtro_fecha_vigencia = "sga_planes_versiones.fecha_entrada_vigencia > DATE $fecha_vigencia_origen";
		} else {
			$filtro_fecha_vigencia = "true";
		}
		$filtro = $filtro_propuesta . ' AND ' . $filtro_version . ' AND ' . $filtro_vigentes . ' AND ' . $filtro_fecha_vigencia;
		$datos = toba::consulta_php('co_planes')->get_planes_versiones_destino_cambio_alumnos($filtro);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_destinos_disponibles__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function conf_evt__cuadro_destinos_disponibles__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_destinos_disponibles', $fila));
	}

	function validar_parametros_generales()
	{
		$params = $this->get_seleccion();
		if ($params['hacia'] == alumno::dest_planes_activos_vigentes && $this->falta_seleccionar('plan_version_destino')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('un Plan Versión destino'));
			throw guarani::error($mje);
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- Pantalla Resultados ----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__resultados(toba_ei_pantalla $pantalla)
	{
		$datos = $this->get_seleccion();
		$this->s__datos_ev['tipo_cambio_nombre'] = 'Forzado';
		if (isset($datos['documento'])) {
			$this->s__datos_ev['documento_nro'] = toba::consulta_php('co_documentos')->get_documento($datos['documento']);
		}
		if ($datos['clasificacion'] == alumno::cat_cambio_plan) {
			$clas_nombre = 'Cambio de Plan';
		} else {
			$clas_nombre = 'Cambio de Versión';
		}
		if ($datos['hacia'] == alumno::dest_planes_activos_vigentes) {
			$hacia_nombre = 'Planes Activos y Vigentes';
		} elseif ($datos['hacia'] == alumno::dest_plan_anterior) {
			$hacia_nombre = 'Plan anterior del Alumno';
		} else {
			$hacia_nombre = 'Última Versión Vigente';
		}
		$this->s__datos_ev['clasificacion_nombre'] = $clas_nombre . ' - ' . $hacia_nombre;
		$this->ev_info()->set_datos($this->s__datos_ev);
		
		// Se oculta el botón "< Anterior".
		$this->evento('cambiar_tab__anterior')->ocultar();
		
		/**
		 * Se agrega el form ml de matrices de equivalencia aplicadas, si corresponde.
		 * Puede mostrarse cuando el plan versión destino es el mismo para todos los alumnos, es decir en los casos de
		 * cambio a Plan Activo-Vigente o a Última Versión Vigente.
		 */
		if ($datos['hacia'] <> alumno::dest_plan_anterior) {
			$pantalla->agregar_dep('cuadro_matrices_equiv_aplicadas');
		}
	}
	
	//---- form_totales -----------------------------------------------------------------
	
	function conf__form_totales(toba_ei_formulario $form)
	{
		$form->set_solo_lectura();
	}
	
	//---- form_ml_matrices_equiv_aplicadas ---------------------------------------------

	function conf__cuadro_matrices_equiv_aplicadas(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msj_sin_matrices));
		// Se conoce el plan versión destino y es único
		$datos = $this->get_seleccion();
		$plan_version_origen = toba::db()->quote($datos['plan_version']);
		if ($datos['hacia'] == alumno::dest_planes_activos_vigentes) {
			$plan_version_destino = toba::db()->quote($datos['plan_version_destino']);
		} elseif ($datos['hacia'] == alumno::dest_ultima_version) {
			$plan_version_destino = toba::db()->quote($this->s__datos_ev['version_actual']);
		}
		/**
		 * Se recuperan matrices de tipo automáticas y activas, cuyo plan versión destino sea al cual se cambiaron los
		 * alumnos, y cuyo orígen coincida con el de los alumnos dependiendo del Alcance de la matriz.
		 */
		$filtro = "	sga_equiv_matrices.plan_version_destino = $plan_version_destino
					AND sga_equiv_matrices.aplicacion_automatica = '" . comunes::si . "'
					AND sga_equiv_matrices.activo = '" . comunes::si . "'
					AND (sga_equiv_matrices.alcance = '" . equivalencia::matriz_alcance_general . "' OR
						(sga_equiv_matrices.alcance = '" . equivalencia::matriz_alcance_entre_propuestas . "'	AND sga_equiv_matrices.propuesta_origen = (	SELECT	vw_planes.propuesta
																																							FROM	vw_planes
																																							WHERE	vw_planes.plan_version = $plan_version_origen)) OR
                        (sga_equiv_matrices.alcance = '" . equivalencia::matriz_alcance_entre_planes_otra_prop . "' AND sga_equiv_matrices.plan_origen = (	SELECT vw_planes.plan
																																							FROM	vw_planes
																																							WHERE	vw_planes.plan_version = $plan_version_origen)) OR
						(sga_equiv_matrices.alcance = '" . equivalencia::matriz_alcance_entre_planes_misma_prop . "' AND sga_equiv_matrices.plan_origen = ( SELECT vw_planes.plan
																																							FROM	vw_planes
																																							WHERE	vw_planes.plan_version = $plan_version_origen)) OR
						(sga_equiv_matrices.alcance = '" . equivalencia::matriz_alcance_entre_planes_misma_prop . "' AND sga_equiv_matrices.propuesta_origen = ( SELECT vw_planes.propuesta
																																								 FROM	vw_planes
																																								 WHERE	vw_planes.plan_version = $plan_version_origen)
																													 AND sga_equiv_matrices.plan_origen IS NULL)		OR
						(sga_equiv_matrices.alcance = '" . equivalencia::matriz_alcance_entre_versiones . "' AND sga_equiv_matrices.plan_version_origen = $plan_version_origen) OR
																													
						(sga_equiv_matrices.alcance = '" . equivalencia::matriz_alcance_entre_versiones . "' AND  sga_equiv_matrices.plan_origen = (	SELECT	vw_planes.plan
																																						FROM	vw_planes
																																						WHERE	vw_planes.plan_version = $plan_version_origen)
																												  AND sga_equiv_matrices.plan_version_origen IS NULL)	
						)";
		$matrices = toba::consulta_php('co_matrices_de_equivalencia')->get_listado_con_prop_plan_version_origen_y_destino($filtro);
		foreach ($matrices as $clave => $valor) {
			$matrices[$clave]['icono_info_grupos'] = $this->get_html_icono_info_grupos_equivalencia($matrices[$clave]['matriz']);
		}
		$cuadro->set_datos($matrices);
	}
	
	//-----------------------------------------------------------------------------------
	//---- operaciones auxiliares -------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Retorna el HTML de un vínculo (con imagen) a la operación que muestra el detalle de
	 * las Actividades de los Grupos Origen y Destino de la Matriz de Equivalencia.
	 */
	function get_html_icono_info_grupos_equivalencia($matriz_equivalencia)
	{
		$vinculo = toba::vinculador()->get_url(toba_proyecto::get_id(), '38000104', array('matriz_equivalencia'=>$matriz_equivalencia), array('celda_memoria'=>'grupos_equivalencia'));
		$javascript = "abrir_popup('info','$vinculo',{'width':'850','height':'630'})";

		$salida	 = "<a class='icono-utileria' href='#' onclick=\"$javascript\">";
		$salida .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, 'Ver detalles de grupos');
		$salida .= '</a>';

		return $salida;
	}
	
	//---- ev_info ----------------------------------------------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_max_datos_por_columna(3);
			$this->ev_info->set_max_chars_por_columna(110);
			$this->ev_info->set_td_etiqueta_width('80px');
   			
			$this->ev_info->set_columnas_a_mostrar(array('resp_acad_nombre', 'propuesta_nombre', 'plan_nombre', 'version_nombre', 'tipo_cambio_nombre', 'documento_nro', 'clasificacion_nombre', 'hasta_nombre'));
			$this->ev_info->set_columnas_fila(array('resp_acad_nombre'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre', 'plan_nombre', 'version_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('tipo_cambio_nombre', 'documento_nro'), 3);
			$this->ev_info->set_columnas_fila(array('clasificacion_nombre'), 4);
			$this->ev_info->set_etiqueta('resp_acad_nombre', 'Responsable Académica');
			$this->ev_info->set_etiqueta('propuesta_nombre', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
			$this->ev_info->set_etiqueta('version_nombre', 'Versión');
			$this->ev_info->set_etiqueta('tipo_cambio_nombre', 'Tipo de Cambio');
			$this->ev_info->set_etiqueta('documento_nro', 'Documento Respaldatorio');
			$this->ev_info->set_etiqueta('clasificacion_nombre', 'Destino');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cambiar_tab__anterior()
	{
		parent::evt__cambiar_tab__anterior();
		if ($this->s__pantalla_actual == 'propuestas') {
			$this->filtro_limpiar('filtro_propuestas');
		}
	}
	
	function evt__cambiar_tab__siguiente()
	{
		parent::evt__cambiar_tab__siguiente();

		if ($this->s__pantalla_actual == 'parametros_generales') { // Si clickeó el boton 'Siguiente' desde la pantalla de parametros generales...
			$datos = $this->get_seleccion();
			// Recupera los alumnos a cambiar de plan a partir del plan versión origen y filtros de cohorte si existen.
			$filtro_origen = "sga_alumnos.calidad = '" . alumno::calidad_activo . "'";
			$filtro_origen .= " AND sga_alumnos.plan_version = " . toba::db()->quote($datos['plan_version']);
			if (isset($datos['periodo'])) {
				//filtros desde y hasta dependiendo del período.
				switch ($datos['periodo']) {
					case 'D':
						$año = toba::db()->quote($datos['cohorte_desde']);
						$filtro_origen .= " AND sga_propuestas_aspira.anio_academico >= $año"; 
						break;
					case 'E':
						$año_desde = toba::db()->quote($datos['cohorte_desde']);
						$año_hasta = toba::db()->quote($datos['cohorte_hasta']);
						$filtro_origen .= " AND sga_propuestas_aspira.anio_academico >= $año_desde
											AND sga_propuestas_aspira.anio_academico <= $año_hasta"; 
						break;
					case 'H':
						$año = $datos['cohorte_hasta'];
						$filtro_origen .= " AND sga_propuestas_aspira.anio_academico <= $año"; 
						break;
				}
			}
			$filtro_origen .=	" AND sga_situacion_aspirante.resultado_asp IN ('". propuesta::inscripcion_aceptada. 
								"' , '".propuesta::inscripcion_pendiente. "')";	
			
			$alumnos_origen = toba::consulta_php('co_alumnos')-> get_alumnos_inscriptos($filtro_origen);
			$parametros = array_merge(	array('alumnos' => $alumnos_origen),
										array('destino_clasificacion' => $datos['hacia']));
			// Para el caso de Cambio de Plan a Planes Nuevos Activos y Vigentes se pasa el plan versión destino seleccionado.
			if ($datos['hacia'] == alumno::dest_planes_activos_vigentes) {
				$datos_plan_version_destino = current(toba::consulta_php('co_planes')->get_datos_plan_version($datos['plan_version_destino']));
				$parametros = array_merge(	array(	'plan_version_destino' => $datos['plan_version_destino'],
													'plan_destino_nombre' => $datos_plan_version_destino['plan_nombre'], 
													'version_destino_nombre' => $datos_plan_version_destino['version_nombre']), 
											$parametros);
			} elseif ($datos['hacia'] == alumno::dest_ultima_version) {
				$datos_version_destino = current(toba::consulta_php('co_planes')->get_datos_plan_version($this->s__datos_ev['version_actual']));
				$parametros = array_merge(	array(	'plan_version_destino' => $this->s__datos_ev['version_actual'],
													'plan_destino_nombre' => $datos_version_destino['plan_nombre'], 
													'version_destino_nombre' => $datos_version_destino['version_nombre']), 
											$parametros);
			}
			if (isset($datos['documento'])) {
				$parametros = array_merge(array('documento' => $datos['documento']), $parametros);
			}	
			if (isset($datos['observaciones'])) {
				$parametros = array_merge(array('observaciones' => $datos['observaciones']), $parametros);
			}
			// Se ejecuta el proceso background.
			$this->cn()->ejecutar_proceso($parametros);
		}
	}

	function evt__finalizar()
	{
		$this->limpiar_memoria();
		$this->set_pantalla('responsables_academicas');
	}

	//-----------------------------------------------------------------------------------
	//---- AJAX -------------------------------------------------------------------------
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
		$resultados = $this->cn()->get_resultado_proceso(pro_cambiar_masivamente_plan_alumnos::reporte_resultados);
		
		if (!empty($resultados['mensajes'])) {
			$respuesta->set(current($resultados['mensajes']));
		}
	}
	
	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		switch ($params['evento']){
			case 'rep_cant_modificados':
				$reporte		 = pro_cambiar_masivamente_plan_alumnos::reporte_cant_modificados;
				$operacion		 = '37000112';
				$parametro_popup = 'alumnos';
			break;
			case 'rep_cant_no_modificados':
				$reporte		 = pro_cambiar_masivamente_plan_alumnos::reporte_cant_no_modificados;
				$operacion		 = '37000112';
				$parametro_popup = 'alumnos_no_copiados';
			break;
			case 'rep_equiv_por_alumno':
				$reporte		 = pro_cambiar_masivamente_plan_alumnos::reporte_equiv_otorgadas_alumno;
				$operacion		 = '37000113';
				$parametro_popup = 'equiv_otorgadas_alumno';
			break;
			case 'rep_equiv_por_actividad':
				$reporte		 = pro_cambiar_masivamente_plan_alumnos::reporte_equiv_otorgadas_actividad;
				$operacion		 = '37000113';
				$parametro_popup = 'equiv_otorgadas_actividad';
			break;
		}
		
		// Obtengo los datos correspondientes al encabezado del reporte (Propuesta-Plan-Versión origen de los Alumnos).
		$datos_conf	= array('propuesta'	=> $this->s__datos_ev['propuesta_nombre'],
							'plan'		=> $this->s__datos_ev['plan_nombre'],
				            'version'	=> $this->s__datos_ev['version_nombre']);

        if ($this->modo_visualizacion()) {
            $datos = $this->controlador()->get_resultado_reporte($reporte);
        } else {
            //Obtengo el Proceso para pedirle el log de resultados
            $datos = $this->cn()->get_resultado_proceso($reporte);
        }
		
		//Almaceno los datos en la instancia de impresion para ser consumida luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos(array($parametro_popup => $datos['mensajes']));
		guarani_datos_impresion::instancia()->set_conf($datos_conf);
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		//Creo el vinculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $operacion, null, array('celda_memoria' => 'popup'));
		$respuesta->set($vinculo);
	}
}
?>