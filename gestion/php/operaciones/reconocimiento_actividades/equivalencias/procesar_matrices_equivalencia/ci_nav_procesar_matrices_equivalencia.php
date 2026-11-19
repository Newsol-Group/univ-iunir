<?php
/**
 *
 * @package Reconocimiento de Actividades
 * @subpackage Equivalencias
 */
class ci_nav_procesar_matrices_equivalencia extends ci_nav_procesos_bk_wizzard
{
	protected $datos_responsable_academica_temp;
	protected $datos_aplicar_a_temp;
	protected $ev_info;
	protected $s__filtro_propuestas;
	protected $s__filtro_planes_versiones;
	protected $s__datos_filtro_propuestas;
	protected $s__datos_filtro_planes_versiones;

	function evt__cambiar_tab__siguiente()
	{
		parent::evt__cambiar_tab__siguiente();

		if ($this->s__pantalla_actual == 'propuestas') {
			$aplicar_a = $this->get_seleccion('responsable_academica');
			$ubicaciones = $this->get_seleccion('ubicacion');
			if ($aplicar_a['responsable_academica']['aplicar_a'] == 'Versiones') {
				$planes = array('plan' => array());
				$versiones = $this->get_seleccion('plan_version');
			} else {
				$planes = $this->get_seleccion('plan');
				$versiones = array('plan_version' => array());
			}
			$parametros = array_merge($aplicar_a, $ubicaciones, $planes, $versiones);
			$this->cn()->ejecutar_proceso($parametros);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- RESPONSABLE ACADÉMICA --------------------------------------------------------
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- form_responsable_academica ---------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_responsable_academica(form_responsable_academica $form)
	{
		$form->set_datos_defecto(array('aplicar_a' => 'Examenes'));
		if (isset($this->datos_responsable_academica_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_responsable_academica_temp);
		} elseif ($this->existe_seleccion('responsable_academica')) {
			$datos = $this->get_seleccion_valor('responsable_academica');
			$form->set_datos($datos);
		}
	}

	function evt__form_responsable_academica__modificacion($datos)
	{
		$this->datos_responsable_academica_temp = $datos;

		$datos_responsable_academica = toba::consulta_php('co_responsables_academicas')->get_datos_resp_academica($datos['responsable_academica']);
		$datos['responsable_academica_nombre'] = $datos_responsable_academica['nombre'];
		$datos['turno_examen_nombre'] = toba::consulta_php('co_turnos_de_examen')->get_nombre_turno_examen($datos['turno_examen']);
		$datos['periodo_lectivo_nombre'] = toba::consulta_php('co_periodos_lectivos')->get_nombre_periodo_lectivo($datos['periodo_lectivo']);

		$this->set_seleccion(array('responsable_academica' => $datos));
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_ubicaciones -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_ubicaciones(toba_ei_cuadro $cuadro)
	{
		$datos = guarani::co('co_ubicaciones')->get_listado();
		$cuadro->set_datos($datos);
		if (guarani_arreglos::count($datos) == 1) {
			$this->set_seleccion($datos);
		}
	}

	function evt__cuadro_ubicaciones__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function conf_evt__cuadro_ubicaciones__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_ubicaciones', $fila));
	}

	function validar_responsable_academica()
	{
		if (guarani_arreglos::count($this->get_seleccion('ubicacion')) == 0) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_ubicaciones');
			$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una')));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PROPUESTAS -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__propuestas(toba_ei_pantalla $pantalla)
	{
		$seleccion = $this->get_seleccion();
		$datos = $seleccion['responsable_academica'];
		$datos['ubicacion_nombre'] = $seleccion['ubicacion_nombre'];
		$this->ev_info()->set_datos($datos);

		if ($datos['aplicar_a'] == 'Versiones') {
			if ($pantalla->existe_dependencia('filtro_propuestas')) {
				$pantalla->eliminar_dep('filtro_propuestas');
			}
			if ($pantalla->existe_dependencia('cuadro_propuestas')) {
				$pantalla->eliminar_dep('cuadro_propuestas');
			}
		} else {
			if ($pantalla->existe_dependencia('filtro_planes_versiones')) {
				$pantalla->eliminar_dep('filtro_planes_versiones');
			}
			if ($pantalla->existe_dependencia('cuadro_planes_versiones')) {
				$pantalla->eliminar_dep('cuadro_planes_versiones');
			}
		}

		$this->evento('cambiar_tab__siguiente')->set_etiqueta('Procesar');
		$this->evento('cambiar_tab__siguiente')->set_imagen('/generar.png');
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro_propuestas ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

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

	//-----------------------------------------------------------------------------------
	//---- filtro_planes_versiones ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_planes_versiones(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_planes_versiones)) {
			$filtro->set_datos($this->s__datos_filtro_planes_versiones);
		} else {
			$filtro->colapsar();
		}
	}

	function evt__filtro_planes_versiones__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_planes_versiones = $datos;
			$this->s__filtro_planes_versiones = $this->dep('filtro_planes_versiones')->get_sql_where();
		} else {
			unset($this->s__datos_filtro_planes_versiones);
			$this->s__filtro_planes_versiones = '';
		}
	}

	function evt__filtro_planes_versiones__cancelar()
	{
		unset($this->s__datos_filtro_planes_versiones);
		$this->s__filtro_planes_versiones = '';
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_propuestas ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_propuestas(toba_ei_cuadro $cuadro)
	{
		$seleccion = $this->get_seleccion_valor('responsable_academica');
		$filtro_responsable_academica = "sga_propuestas.propuesta IN (	SELECT	sga_propuestas_ra.propuesta 
																		FROM	sga_propuestas_ra 
																		WHERE	sga_propuestas_ra.responsable_academica = {$seleccion['responsable_academica']})";
		$filtro_propuestas = $filtro_responsable_academica;
		$filtro_propuestas .= (empty($this->s__filtro_propuestas)) ? '' : " AND $this->s__filtro_propuestas";

		$indx_msg = $this->indx_msg_eof;
		if (isset($this->s__filtro_propuestas) && !empty($this->s__filtro_propuestas)) {
			$indx_msg = $this->indx_msg_eof . '_filtrado';
		}
		$etiqueta = guarani_etiquetas::get_tabla('sga_planes_versiones');
		$msj = guarani_mensajes::get($indx_msg, array($etiqueta->get_enf('plural')));
		$cuadro->set_eof_mensaje($msj);

		$datos = toba::consulta_php('co_propuestas')->get_planes_activos_con_propuestas_en_versiones_activas($filtro_propuestas);
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

	//-----------------------------------------------------------------------------------
	//---- cuadro_planes_versiones ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_planes_versiones(toba_ei_cuadro $cuadro)
	{
		$seleccion = $this->get_seleccion_valor('responsable_academica');
		$filtro_responsable_academica = "sga_propuestas.propuesta IN (	SELECT	sga_propuestas_ra.propuesta 
																		FROM	sga_propuestas_ra 
																		WHERE	sga_propuestas_ra.responsable_academica = {$seleccion['responsable_academica']})";
		$filtro_planes_versiones = $filtro_responsable_academica;
		$filtro_planes_versiones .= (empty($this->s__filtro_planes_versiones)) ? '' : " AND $this->s__filtro_planes_versiones";
		$filtro_planes_versiones .= "	AND sga_planes_versiones.estado IN ('".planes::estado_activo."','".planes::estado_vigente."')
										AND sga_planes.tipo_plan = '".planes::tipo_estructurado."'
										AND sga_planes.estado IN ('".planes::estado_activo."','".planes::estado_vigente."')
										AND	sga_propuestas.estado = '".propuesta::estado_activo."'";

		$indx_msg = $this->indx_msg_eof;
		if (isset($this->s__filtro_planes_versiones) && !empty($this->s__filtro_planes_versiones)) {
			$indx_msg = $this->indx_msg_eof . '_filtrado';
		}
		$etiqueta = guarani_etiquetas::get_tabla('sga_propuestas');
		$msj = guarani_mensajes::get($indx_msg, array($etiqueta->get_enf('plural')));
		$cuadro->set_eof_mensaje($msj);

		$datos = toba::consulta_php('co_planes')->get_lista_planes($filtro_planes_versiones);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_planes_versiones__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function conf_evt__cuadro_planes_versiones__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_planes_versiones', $fila));
	}

	function validar_propuestas()
	{
		$seleccion = $this->get_seleccion();
		if ($seleccion['responsable_academica']['aplicar_a'] == 'Versiones') {
			if (guarani_arreglos::count($this->get_seleccion('plan_version')) == 0) {
				$etiqueta = guarani_etiquetas::get_tabla('sga_planes_versiones');
				$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('un')));
				throw guarani::error($mje);
			}
		} elseif (guarani_arreglos::count($this->get_seleccion('plan')) == 0) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_planes');
			$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('un')));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PROCESAMIENTO ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__procesar(toba_ei_pantalla $pantalla)
	{
		parent::conf__procesar($pantalla);

		$this->evento('cambiar_tab__anterior')->ocultar();

		$seleccion = $this->get_seleccion();
		$datos = $seleccion['responsable_academica'];
		$datos['ubicacion_nombre'] = $seleccion['ubicacion_nombre'];
		if ($datos['aplicar_a'] == 'Versiones') {
			$datos['version_nombre'] = $seleccion['version_nombre'];
		} else {
			$datos['plan_nombre'] = $seleccion['plan_nombre'];
		}
		$this->ev_info()->set_datos($datos);

		$this->dependencia('form_totales')->set_solo_lectura();
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__finalizar()
	{
		$this->limpiar_memoria();
		$this->set_pantalla('responsable_academica');
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares el manejo del avance del proceso ----------------------------------
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
		$resultados = $this->cn()->get_resultado_proceso(pro_procesar_matrices_equivalencia::reporte_resultados);
		if (!empty($resultados['mensajes'])) {
			$respuesta->set(current($resultados['mensajes']));
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares para la visualización de las selecciones previas ------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('responsable_academica_nombre', 'ubicacion_nombre', 'anio_academico', 'turno_examen_nombre', 'periodo_lectivo_nombre', 'plan_nombre', 'version_nombre'));
			$this->ev_info->set_columnas_fila(array('responsable_academica_nombre'), 1);
			$this->ev_info->set_columnas_fila(array('ubicacion_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('anio_academico', 'turno_examen_nombre', 'periodo_lectivo_nombre'), 3);
			$this->ev_info->set_columnas_fila(array('plan_nombre', 'version_nombre'), 4);
			$this->ev_info->set_etiqueta('responsable_academica_nombre', 'Responsable Académica');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicaciones');
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('turno_examen_nombre', 'Turno de Examen');
			$this->ev_info->set_etiqueta('periodo_lectivo_nombre', 'Período Lectivo');
			$this->ev_info->set_etiqueta('plan_nombre', 'Planes');
			$this->ev_info->set_etiqueta('version_nombre', 'Versiones');
			$this->ev_info->set_style('width: 100%');
			$this->ev_info->set_td_etiqueta_width('140px');
			$this->ev_info->set_td_valores_width('300px');
		}
		return $this->ev_info;
	}
}
?>