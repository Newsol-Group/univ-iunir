<?php
/**
 *
 *
 * @package Asistencias
 * @subpackage -
 */
class ci_nav_registrar_asistencia_docentes extends ci_nav_con_preseleccion
{
	protected $s__comision;
	protected $s__subcomision;
	protected $s__clase;
	protected $s__docente;
	protected $s__datos_comision;
	protected $s__datos_subcomision;
	protected $s__datos_clase;
	protected $s__datos_docente;
	
	//Componentes Visuales de Información.
	protected $ev_info_alumno;
	protected $ev_info_comision;
	protected $ev_info_clase;
	protected $ev_info_docente;

	function ini__operacion()
	{
		parent::set_concepto_preseleccion('Comisión');
	}

	function ini()
	{
		if (toba::zona()->cargada() && empty($this->s__comision)) {
			$entidad = toba::zona()->get_editable();
			$datos_comision = current(toba::consulta_php('co_comisiones')->get_listado("sga_comisiones.entidad = $entidad"));
			$this->seleccionar_preseleccion(array('comision' => $datos_comision['comision']));
		}
	}

	function seleccionar_preseleccion($seleccion)
	{
		parent::evt__cuadro_preseleccion__seleccion($seleccion);
		// Se verifica si la comisión tiene subcomisiones para ver a qué pantalla se debe pasar.
		if (toba::consulta_php('co_comisiones')->comision_tiene_subcomisiones($this->get_preseleccion())) {
			$this->set_pantalla('seleccion_subcomision');
		} else {
			$this->set_pantalla('seleccion_clase');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial_preseleccion();
	}

	function set_preseleccion($seleccion)
	{
		$this->s__comision = $seleccion['comision'];
	}

	function get_preseleccion()
	{
		return $this->s__comision;
	}

	function set_seleccion_subcomision($seleccion)
	{
		$this->s__subcomision = $seleccion['subcomision'];
	}

	function get_seleccion_subcomision()
	{
		return $this->s__subcomision;
	}

	function set_seleccion_clase($seleccion)
	{
		$this->s__clase = $seleccion['clase'];
	}

	function get_seleccion_clase()
	{
		return $this->s__clase;
	}

	function set_seleccion_docente($seleccion)
	{
		$this->s__docente = $seleccion['docente'];
	}

	function get_seleccion_docente()
	{
		return $this->s__docente;
	}

	function get_datos_comision()
	{
		return $this->s__datos_comision;
	}

	function get_datos_subcomision()
	{
		return $this->s__datos_subcomision;
	}

	function get_datos_clase()
	{
		return $this->s__datos_clase;
	}

	function get_datos_docente()
	{
		return $this->s__datos_docente;
	}

	function indica_cantidad_inasistencias()
	{
		return ($this->s__datos_comision['asistencia_cantidad_inasist'] == comunes::si);
	}

	function get_minimo_inasistencias()
	{
		return $this->cn()->entidad()->obtener_valor_minimo_inasistencias();
	}

	function get_maximo_inasistencias()
	{
		return $this->cn()->entidad()->obtener_valor_maximo_inasistencias();
	}

	function obtener_valor_defecto_cant_inasistencias()
	{
		return $this->cn()->entidad()->obtener_valor_maximo_inasistencias();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Selección Comisión 
	//-----------------------------------------------------------------------------------

	function conf__preseleccion(toba_ei_pantalla $pant)
	{
		$pant->set_descripcion('Sólo se listarán comisiones sin acta de cursada/promoción cerrada. Se permitirá seleccionar aquellas para las cuales el parámetro <strong>registrar asistencia en las cursadas (asistencia_registrar_asistencia)</strong> indique que <strong>se registra asistencia en las clases de las cursadas</strong>.');
		$this->cn()->resetear_atributos_parametros();
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_preselección ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_preseleccion(toba_ei_filtro $filtro)
	{
		parent::conf__filtro_preseleccion($filtro);
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('periodo_lectivo')->set_condicion_fija('es_igual_a');
		$filtro->columna('modalidad')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro_preseleccion__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_preseleccion = $datos;
			$filtro_modalidad = '';
			if (isset($datos['modalidad'])) {
				$filtro_modalidad .= " AND EXISTS (SELECT * FROM sga_comisiones_modalidad WHERE sga_comisiones_modalidad.comision = sga_comisiones.comision AND sga_comisiones_modalidad.modalidad = '{$datos['modalidad']['valor']}')";
				$this->dep('filtro_preseleccion')->columna('modalidad')->resetear_estado();
			}
			$this->s__filtro_preseleccion = "{$this->dep('filtro_preseleccion')->get_sql_where()} $filtro_modalidad";
		} else {
			unset($this->s__datos_preseleccion);
			$this->s__filtro_preseleccion = '';
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_preselección ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Se redefine la selección del primer cuadro para pasar a la segunda pantalla
	 */
	function evt__cuadro_preseleccion__seleccion($seleccion)
	{
		$this->seleccionar_preseleccion($seleccion);
		$datos_comision = toba::consulta_php('co_comisiones')->get_datos_basicos_comision($seleccion['comision']);
		toba::zona()->cargar($datos_comision['entidad']);
		$this->cn()->cargar_atributos_parametros($seleccion['comision']);
	}
	
	function conf_evt__cuadro_preseleccion__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_preseleccion')->get_datos();
		$comision = $datos[$fila]['comision'];
		$planes = aplanar_matriz(toba::consulta_php('co_comisiones')->get_planes_comision($comision), 'plan');
		if (guarani::parametros()->get_valor('asistencia_registrar_asistencia', $planes) == comunes::no) {
			$evento->anular();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Selección Subcomisión 
	//-----------------------------------------------------------------------------------

	function conf__seleccion_subcomision(toba_ei_pantalla $pantalla)
	{
		$this->s__datos_comision = current(toba::consulta_php('co_comisiones')->get_datos_comision($this->get_preseleccion()));
		$this->ev_info_comision()->set_datos($this->s__datos_comision);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_subcomisiones(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_listado_subcomisiones($this->s__comision);
		$cuadro->set_datos($datos);
	}

	/**
	 * Se extiende el método de selección del ci nav con preselección porque existe una tercera pantalla de Selección.
	 */
	function evt__cuadro_subcomisiones__seleccion($seleccion)
	{
		$this->set_seleccion_subcomision($seleccion);
		$this->set_pantalla('seleccion_clase');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Selección Clase 
	//-----------------------------------------------------------------------------------

	function conf__seleccion_clase(toba_ei_pantalla $pantalla)
	{
		if (!isset($this->s__subcomision)) {
			$this->s__datos_comision = current(toba::consulta_php('co_comisiones')->get_datos_comision($this->get_preseleccion()));
		} else {
			$this->s__datos_subcomision = current(toba::consulta_php('co_comisiones')->get_datos_subcomision($this->s__subcomision));
			$this->ev_info_subcomision()->set_datos($this->s__datos_subcomision);
		}
		$this->ev_info_comision()->set_datos($this->s__datos_comision);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro clases ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_clases(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array('Clases asociadas a la Comisión/Subcomisión seleccionada')));
		$datos = $this->cn()->get_listado_clases($this->s__comision, $this->s__subcomision);
		$cuadro->set_datos($datos);
	}

	function conf_evt__cuadro_clases__seleccion(toba_evento_usuario $evento, $fila)
	{
		// Sólo se permite seleccionar Clases que ya fueron dictadas (con fecha menor o igual a hoy).
		$datos = $this->dep('cuadro_clases')->get_datos();
		$fecha = $datos[$fila]['fecha_clase'];
		if (guarani_fecha::es_menor_igual_a_fecha_actual($fecha)) {
			$evento->mostrar();
		} else {
			$evento->ocultar();
		}
	}

	function evt__cuadro_clases__seleccion($seleccion)
	{
		$this->set_seleccion_clase($seleccion);
		$this->set_pantalla('seleccion');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Selección Docente 
	//-----------------------------------------------------------------------------------

	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$this->ev_info_comision()->set_datos($this->s__datos_comision);
		if (isset($this->s__subcomision)) {
			$this->ev_info_subcomision()->set_datos($this->s__datos_subcomision);
		}
		$this->s__datos_clase = toba::consulta_php('co_clases')->get_datos_clase_asignacion($this->s__clase);
		$this->ev_info_clase()->set_datos($this->s__datos_clase);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro clases ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_docentes(toba_ei_cuadro $cuadro)
	{
		if (isset($this->s__subcomision)) {
			$tabla = 'sga_docentes_subcomision';
		} else {
			$tabla = 'sga_docentes_comision';
		}
		$etiquetas = guarani_etiquetas::get_tabla($tabla);
		$etiqueta = $etiquetas->get_enf('plural');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta)));
		$datos = $this->cn()->get_listado_docentes_inasistencias($this->s__comision, $this->s__subcomision, $this->s__clase);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_docentes__seleccion($seleccion)
	{
		$this->set_seleccion_docente($seleccion);
		// Debe verificarse si ya existe un registro en la tabla de Ausencias Docentes para la Clase y el Docente
		// seleccionados. De ser así se carga el DR con el mismo, en caso contrario se genera una nueva entidad
		// para dar de alta el registro de inasistencia.
		if (toba::consulta_php('co_asistencias')->existe_registro_inasistencia_docente_a_clase($this->s__docente, $this->s__clase)) {
			$array_ids = array('docente' => $this->s__docente, 'clase' => $this->s__clase);
			$this->cn()->cargar($array_ids);
			$this->set_pantalla('edicion');
		} else {
			parent::evt__agregar();
			$this->cn()->entidad()->set_datos(array('docente' => $this->s__docente, 'clase' => $this->s__clase));
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Edición 
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$this->ev_info_comision()->set_datos($this->s__datos_comision);
		if (isset($this->s__subcomision)) {
			$this->ev_info_subcomision()->set_datos($this->s__datos_subcomision);
		}
		$this->ev_info_clase()->set_datos($this->s__datos_clase);
		$this->s__datos_docente = toba::consulta_php('co_docentes')->get_datos_docente_comision($this->s__docente, $this->s__comision);
		$this->ev_info_docente()->set_datos($this->s__datos_docente);
		$this->cn()->entidad()->set_asistencias_por_horas($this->s__datos_comision['asistencia_por_horas']);
		parent::conf__edicion($pantalla);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar_seleccion_subcomision_comision()
	{
		// En la pantalla de Clases, se retorna a la selección de subcomisión o de comisión según corresponda.
		if (isset($this->s__subcomision)) {
			unset($this->s__subcomision);
			$this->set_pantalla('seleccion_subcomision');
		} else {
			toba::zona()->resetear();
			unset($this->s__comision);
			$this->set_pantalla('preseleccion');
		}
	}

	function evt__cancelar_seleccion_clase()
	{
		unset($this->s__clase);
		$this->set_pantalla('seleccion_clase');
	}

	//-----------------------------------------------------------------------------------
	//---- evs_info ---------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info_comision()
	{
		if (!isset($this->ev_info_comision)) {
			$this->ev_info_comision = guarani::ev_info_datos();
			$this->ev_info_comision->set_controlador($this);
			
			$this->ev_info_comision->set_td_etiqueta_width('100px');
			$this->ev_info_comision->set_td_valores_width('300px');

			$this->ev_info_comision->set_columnas_a_mostrar(array('anio_academico', 'periodo_lectivo_nombre', 'actividad_desc', 'comision_nombre', 'ubicacion_nombre', 'turno_nombre', 'modalidad_nombre'));
			$this->ev_info_comision->set_columnas_fila(array('anio_academico', 'periodo_lectivo_nombre'), 1);
			$this->ev_info_comision->set_columnas_fila(array('actividad_desc', 'comision_nombre'), 2);
			$this->ev_info_comision->set_columnas_fila(array('turno_nombre', 'modalidad_nombre', 'ubicacion_nombre'), 3);

			$this->ev_info_comision->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info_comision->set_etiqueta('periodo_lectivo_nombre', 'P. Lectivo');
			$this->ev_info_comision->set_etiqueta('actividad_desc', 'Actividad');
			$this->ev_info_comision->set_etiqueta('comision_nombre', 'Comisión');
			$this->ev_info_comision->set_etiqueta('turno_nombre', 'Turno');
			$this->ev_info_comision->set_etiqueta('modalidad_nombre', 'Modalidad');
			$this->ev_info_comision->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info_comision;
	}

	function ev_info_subcomision()
	{
		if (!isset($this->ev_info_subcomision)) {
			$this->ev_info_subcomision = guarani::ev_info_datos();
			$this->ev_info_subcomision->set_controlador($this);
			$this->ev_info_subcomision->set_titulo('Datos de la Subcomisión');

			$this->ev_info_subcomision->set_columnas_a_mostrar(array('subcomision_nombre', 'tipo_clase_nombre'));
			$this->ev_info_subcomision->set_columnas_fila(array('subcomision_nombre', 'tipo_clase_nombre'), 1);

			$this->ev_info_subcomision->set_etiqueta('subcomision_nombre', 'Subcomisión');
			$this->ev_info_subcomision->set_etiqueta('tipo_clase_nombre', 'Tipo');
		}
		return $this->ev_info_subcomision;
	}

	function ev_info_clase()
	{
		if (!isset($this->ev_info_clase)) {
			$this->ev_info_clase = guarani::ev_info_datos();
			$this->ev_info_clase->set_controlador($this);
			$this->ev_info_clase->set_titulo('Datos de la Clase');

			$this->ev_info_clase->set_columnas_a_mostrar(array('fecha_formateada', 'dia_semana_nombre', 'hora_inicio_formateada', 'hora_finalizacion_formateada', 'cantidad_horas_dictadas'));
			$this->ev_info_clase->set_columnas_fila(array('fecha_formateada', 'dia_semana_nombre', 'hora_inicio_formateada', 'hora_finalizacion_formateada', 'cantidad_horas_dictadas'), 1);

			$this->ev_info_clase->set_etiqueta('fecha_formateada', 'Fecha');
			$this->ev_info_clase->set_etiqueta('dia_semana_nombre', 'Día');
			$this->ev_info_clase->set_etiqueta('hora_inicio_formateada', 'Hs Inicio');
			$this->ev_info_clase->set_etiqueta('hora_finalizacion_formateada', 'Hs Fin');
			$this->ev_info_clase->set_etiqueta('cantidad_horas_dictadas', 'Cant. horas dictadas');
		}
		return $this->ev_info_clase;
	}

	function ev_info_docente()
	{
		if (!isset($this->ev_info_docente)) {
			$this->ev_info_docente = guarani::ev_info_datos();
			$this->ev_info_docente->set_controlador($this);
			$this->ev_info_docente->set_titulo('Datos del Docente');

			$this->ev_info_docente->set_columnas_a_mostrar(array('docente_nombre', 'legajo', 'responsabilidad_nombre'));
			$this->ev_info_docente->set_columnas_fila(array('docente_nombre', 'legajo', 'responsabilidad_nombre'), 1);

			$this->ev_info_docente->set_etiqueta('docente_nombre', 'Docente');
			$this->ev_info_docente->set_etiqueta('legajo', 'Legajo');
			$this->ev_info_docente->set_etiqueta('responsabilidad_nombre', 'Responsabilidad');
		}
		return $this->ev_info_docente;
	}

}
?>