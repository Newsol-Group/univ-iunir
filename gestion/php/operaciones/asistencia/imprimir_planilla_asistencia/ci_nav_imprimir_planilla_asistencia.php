<?php
/**
 *
 *
 * @package 	Asistencia
 * @subpackage 	Imprimir planilla de asistencia
 * 
 */
class ci_nav_imprimir_planilla_asistencia extends ci_rep_sin_listado_inicial
{
	protected $s__sel_comision;
	protected $s__sel_comision_puro;
	protected $s__subcom;
	protected $s__cuadro_coms;
	protected $s__cuadro_coms_sel;
	protected $s__datos_ev;
	protected $s__fecha_desde;
	protected $s__fecha_desde_formateada;
	protected $s__imp_libres;
	protected $s__imprimir = false;
	// Cantidad de fechas a imprimir en la planilla de asistencia.
	protected $s__cant_clases_planilla = 5;
	// Fechas de las clases para las cuales se va a imprimir la planilla de asistencia.
	protected $s__fechas_clases;
	
	// Componentes Visuales de Información.
	protected $ev_info_comision;

	function ini()
	{
		toba::memoria()->desactivar_reciclado();
		parent::ini();
	}
	
	// Funciones abstractas de ci_reporte.
	function get_tabla_principal()
	{
		return 'sga_comisiones';
	}
	
	// Recupera las Comisiones con Períodos Lectivos en curso, según el filtro dado.
	function get_datos($filtro)
	{
		if ($filtro) {
			$filtro .= " AND vw_periodos_lectivos.fecha_fin >= ".guarani_sql::fecha_actual;
		} else {
			$filtro = "vw_periodos_lectivos.fecha_fin >= ".guarani_sql::fecha_actual;
		}
		$datos = toba::consulta_php('co_comisiones')->get_listado_comisiones_subcomisiones_asist($filtro, $this->s__subcom);			
		$this->s__cuadro_coms = $datos;
		return $datos;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Pantalla Selección -----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- filtro -----------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('periodo_lectivo')->set_condicion_fija('es_igual_a');
		$filtro->columna('modalidad')->set_condicion_fija('es_igual_a');
	}

	// Se redefine por el comportamiento del filtro sobre modalidad.
	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$this->s__subcom = false;
			$filtro_modalidad = '';
			if (isset($datos['modalidad'])) {
				$filtro_modalidad .= " AND EXISTS (SELECT * FROM sga_comisiones_modalidad WHERE sga_comisiones_modalidad.comision = sga_comisiones.comision AND sga_comisiones_modalidad.modalidad = '{$datos['modalidad']['valor']}')";
				$this->dep('filtro')->columna('modalidad')->resetear_estado();
			}
			
			$filtro_edif = '';
			if ((isset($datos['edificacion']))) { 
				$filtro_edif     = $datos['edificacion']['condicion'] == 'es_igual_a' ? ' AND ' : ' AND NOT ' ; 
				$filtro_edif .= ' sga_ubicaciones.ubicacion IN (SELECT ubicacion FROM sga_edificaciones WHERE edificacion = ' . $datos['edificacion']['valor'] . ') '; 

				$this->dep('filtro')->columna('edificacion')->resetear_estado(); 
			} 

			if ((isset($datos['tipo_clase']))) { 
				$this->s__subcom = true;
			} 
			
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_modalidad $filtro_edif";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}
	
	function evt__filtro__cancelar()
	{
		parent::evt__filtro__cancelar();
		unset($this->s__sel_comision_puro);
	}	
	
	//---- cuadro -----------------------------------------------------------------------
	
	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__sel_comision_puro = $seleccion;
		$this->s__sel_comision = array();
		if (!empty($seleccion)) {
			foreach ($seleccion as $sel) {
				$datos = explode('-', $sel['com_subcom_codigo']);
				$dg['comision'] = intval($datos[0]);
				if ($datos[1] != '0') {
					$dg['subcomision'] = intval($datos[1]);
				} else {				
					$dg['subcomision'] = '';
				}
				$this->s__sel_comision[] = $dg;

				//Armo los datos para el cuadro_selec conteniendo solo los registros seleccionados
				foreach ($this->s__cuadro_coms as $dato) {
					if ($dato['com_subcom_codigo'] == $sel['com_subcom_codigo']) {
						$sels[] = $dato;
						break;
					}
				}
			}
			unset($this->s__cuadro_coms);
			$this->s__cuadro_coms_sel = $sels;
		}
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_clave_seleccionada($this->dep('cuadro')->get_clave_fila($fila, true)));
	}
	
	function es_clave_seleccionada($clave_fila)
	{
		$claves = array();
		if (isset($this->s__sel_comision_puro)) {
			foreach ($this->s__sel_comision_puro as $valor) {
				$claves[] = implode(apex_qs_separador, $valor);
			}
		}
		return in_array($clave_fila, $claves);
	}
	

	//-----------------------------------------------------------------------------------
	//---- Pantalla Selección Clase -----------------------------------------------------
	//----------------------------------------------------------------------------------- 
	
	function conf__seleccion_clases(toba_ei_pantalla $pantalla)
	{
		$com = $this->s__sel_comision[0];
		$this->s__datos_ev = current(toba::consulta_php('co_comisiones')->get_datos_comision($com['comision']));
		$this->ev_info_comision()->set_datos($this->s__datos_ev);
	}
	
	//---- form_fecha_clases ------------------------------------------------------------
	
	function conf__form_fecha_clases(toba_ei_formulario $form)
	{
		if (isset($this->s__fecha_desde) && isset($this->s__imp_libres)) {
			$form->ef('fecha_desde')->set_estado($this->s__fecha_desde);
			$form->ef('alumnos_libres')->set_estado($this->s__imp_libres);
		}
	}
	
	function evt__form_fecha_clases__modificacion($datos)
	{
		$this->s__fecha_desde = $datos['fecha_desde'];
		$this->s__imp_libres = $datos['alumnos_libres'];
		$formateo = new toba_formateo('html');
		$this->s__fecha_desde_formateada = $formateo->formato_fecha($this->s__fecha_desde);
	}
	
	//---- cuadro -----------------------------------------------------------------------

	function conf__cuadro_selec(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_usar_ordenamiento_con_cortes();
		unset($this->s__cuadro_coms);
		$cuadro->set_datos($this->s__cuadro_coms_sel);
	}

	
	//---- cuadro_asistencias -----------------------------------------------------------
	
	function cuadro_asistencias($cuadro, $com_subcom)
	{
		$etiquetas = guarani_etiquetas::get_tabla('sga_insc_cursada');
		$msj = guarani_mensajes::get($this->indx_msg_eof, array($etiquetas->get_enf('plural')));
		$cuadro->set_eof_mensaje($msj);
		
		$filtro = '';
		// Si se seleccionó no imprimir Alumnos Libres se calcula por el porcentaje de inasistencias al momento de imprimir la planilla
		if (!$this->s__imp_libres) {
			//$filtro = "sga_alumnos.regular = '" . comunes::si . "'";
			$filtro = "  sga_clases_asistencia_acum.libre = 0";
		}
		/**
		 * Se verifica si se seleccionó o no subcomisión para utilizar la sql correspondiente para obtener los alumnos.
		 * Además se obtienen las 5 Fechas de Clases de la comisión o subcomisión a partir de la fecha ingresada por el
		 * usuario, para imprimir en la Planilla el espacio para tomar asistencia en dichas Clases.
		*/ 
		if (!$com_subcom['subcomision']=='') {
			$datos = toba::consulta_php('co_asistencias')->get_alumnos_inasistencias_acum_subcomision($com_subcom['subcomision'], $filtro);
			$this->s__fechas_clases = toba::consulta_php('co_clases')->get_fechas_clases_subcomision_para_asistencia($com_subcom['subcomision'], $this->s__fecha_desde, $this->s__cant_clases_planilla);
		} else {
			$datos = toba::consulta_php('co_asistencias')->get_alumnos_inasistencias_acum_comision($com_subcom['comision'], $filtro);
			$this->s__fechas_clases = toba::consulta_php('co_clases')->get_fechas_clases_comision_para_asistencia($com_subcom['comision'], $this->s__fecha_desde, $this->s__cant_clases_planilla);
		}
		
		/**
		 * Se etiquetan las columnas del cuadro con las fechas correspondientes y se setean los datos para cada alumno.
		 * Debe tenerse en cuenta que pueden devolverse menos de cinco clases de no existir ese nro a partir de la fecha
		 * ingresada.
		*/
		if (isset($this->s__fechas_clases[0])) {
			$cuadro->set_titulo_columna('fecha1', $this->s__fechas_clases[0]['fecha_clase_visual']);
		}
		if (isset($this->s__fechas_clases[1])) {
			$cuadro->set_titulo_columna('fecha2', $this->s__fechas_clases[1]['fecha_clase_visual']);	
		}
		if (isset($this->s__fechas_clases[2])) {
			$cuadro->set_titulo_columna('fecha3', $this->s__fechas_clases[2]['fecha_clase_visual']);	
		}
		if (isset($this->s__fechas_clases[3])) {
			$cuadro->set_titulo_columna('fecha4', $this->s__fechas_clases[3]['fecha_clase_visual']);	
		}
		if (isset($this->s__fechas_clases[4])) {
			$cuadro->set_titulo_columna('fecha5', $this->s__fechas_clases[4]['fecha_clase_visual']);	
		}
		$cuadro->set_datos($datos);
	}
	
	//-----------------------------------------------------------------------------------
	//---- evs_info ---------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info_comision()
	{
		if (!isset($this->ev_info_comision)) {
			$this->ev_info_comision = guarani::ev_info_datos();
			$this->ev_info_comision->set_controlador($this);
			$this->ev_info_comision->set_titulo('Datos del Período');
			
			$this->ev_info_comision->set_columnas_a_mostrar(array('anio_academico', 'periodo_nombre'));
			$this->ev_info_comision->set_columnas_fila(array('anio_academico', 'periodo_nombre'), 1);
			
			$this->ev_info_comision->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info_comision->set_etiqueta('periodo_nombre', 'P. Lectivo');
		}
		return $this->ev_info_comision;
	}
		
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar()
	{
			unset($this->s__fecha_desde);
			unset($this->s__fecha_desde_formateada);
			unset($this->s__imp_libres);
			unset($this->s__datos_ev);
			$this->set_pantalla('seleccion');
	}
	
	function evt__siguiente()
	{
		if (!isset($this->s__sel_comision_puro)) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una comisión/subcomisión'));
			throw guarani::error($mje);
		} else {
			$this->set_pantalla('seleccion_clases');
		}
	}
	
	function evt__pdf()	
	{
		$this->s__imprimir = true;			
	}
	
	//-----------------------------------------------------------------------------------
	//---- IMPRESION JASPER -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function vista_jasperreports(toba_vista_jasperreports $report) 
	{
		$path = guarani::get_path_reporte_jasper('PlanillaAsistencia.jasper');
		$report->set_path_reporte($path);

		// Logo
		$path_logo = guarani::get_path_logo_impresion();

		foreach ($this->s__sel_comision as $sel) {
			$datos_comision = current(toba::consulta_php('co_comisiones')->get_datos_comision($sel['comision']));
			$d_apellido_nombre = '';
			$docentes = explode(',', $datos_comision['comision_docentes']);
			foreach ($docentes as $docente) {
				if (strpos($docente, '(Titular)') !== false) {
					$d_apellido_nombre = $docente;
				}
			}
			// Parámetros para el Ev y las fechas de las clases para Asistencia.
			$report->set_parametro('logo', 'S', $path_logo);
			$report->set_parametro('catedra', 'S', is_null($datos_comision['catedra_nombre'])? '-' : $datos_comision['catedra_nombre']);
			$report->set_parametro('anio_academico', 'S', $datos_comision['anio_academico']);
			$report->set_parametro('per_lectivo', 'S', $datos_comision['periodo_lectivo_nombre']);
			$report->set_parametro('fecha_desde', 'S', $this->s__fecha_desde_formateada);
			$report->set_parametro('actividad', 'S', $datos_comision['actividad_desc']);
			$report->set_parametro('comision_nombre', 'S', $datos_comision['comision_nombre']);
			$str = (is_null($datos_comision['turno_nombre'])) ? ' - ' : $datos_comision['turno_nombre'];
			$report->set_parametro('turno', 'S', $str);
			$report->set_parametro('modalidad', 'S', $datos_comision['modalidad_nombre']);
			$report->set_parametro('ubicacion', 'S', $datos_comision['ubicacion_nombre']);
			$report->set_parametro('d_apellido_nombre', 'S', is_null($d_apellido_nombre)? ' - ' : $d_apellido_nombre);
			$report->set_parametro('horario_comision_clase', 'S', is_null($datos_comision['horario_comision_clase']) ? '-' : $datos_comision['horario_comision_clase']);

			if (!$sel['subcomision']=='') {
				$where=" sga_subcomisiones.subcomision = '".$sel['subcomision']."'";
				$datos_subcomision = current(toba::consulta_php('co_comisiones')->get_datos_subcomisiones($where));
				$report->set_parametro('subcomision_nombre', 'S', $datos_subcomision['nombre']);
				//Si tiene subcomision pisa la de la comision
				
				if(!empty($datos_subcomision['horario']) && !empty($datos_subcomision['tipo_clase_nombre'])){
					$report->set_parametro('horario_comision_clase', 'S',$datos_subcomision['horario'].' - '.$datos_subcomision['tipo_clase_nombre']);
				}
			}else{
				
				$report->set_parametro('subcomision_nombre', 'S', '-');
				$report->set_parametro('tipo_clase', 'S', '-');
			}
			
			$cuadro = $this->dep('cuadro_asistencias');
			$this->cuadro_asistencias($cuadro, $sel);
			if (isset($this->s__fechas_clases[0])) {
				$report->set_parametro('fecha1', 'S', $this->s__fechas_clases[0]['fecha_clase_visual']);
			}
			if (isset($this->s__fechas_clases[1])) {
				$report->set_parametro('fecha2', 'S', $this->s__fechas_clases[1]['fecha_clase_visual']);
			}
			if (isset($this->s__fechas_clases[2])) {
				$report->set_parametro('fecha3', 'S', $this->s__fechas_clases[2]['fecha_clase_visual']);
			}
			if (isset($this->s__fechas_clases[3])) {
				$report->set_parametro('fecha4', 'S', $this->s__fechas_clases[3]['fecha_clase_visual']);
			}
			if (isset($this->s__fechas_clases[4])) {
				$report->set_parametro('fecha5', 'S', $this->s__fechas_clases[4]['fecha_clase_visual']);
			}

			$xml = $cuadro->vista_xml();
			$report->set_xml(utf8_e_seguro($xml));
			
			$report->completar_con_datos();
			$cuadro->set_total_registros(NULL);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		if ($this->s__imprimir) {
			$this->s__imprimir = false;
			echo "
				window.open(vinculador.get_url(null, null, 'vista_jasperreports', null, [{$this->objeto_js}._id])); 
			";
		}
	}
	
}
?>
