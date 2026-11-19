<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Alumnos
 */
class ci_nav_vencimiento_regularidad_masivo extends ci_nav_procesos_bk_sin_listado_inicial
{
	protected $s__datos_form_fechas;
	protected $s__comisiones;
	protected $s__datos_modificatoria;
	protected $s__regularidades;

	// Componente Visual de Información.
	protected $ev_info;

	function set_nombre_proceso()
	{
		$this->s__nombre_proceso = pro_vencimiento_regularidad_nucleo::nombre;
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA: Fechas o Comisiones 
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- form_fechas ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_fechas(form_fechas $form)
	{
		$form->set_datos_defecto(array('tipo_filtro' => 'F'));
		if (isset($this->s__datos_form_fechas)) {
			$form->set_datos($this->s__datos_form_fechas);
		}
	}

	function evt__form_fechas__modificacion($datos)
	{
		$this->s__datos_form_fechas = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__filtro(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('periodo_lectivo')->set_condicion_fija('es_igual_a');
		$filtro->columna('modalidad')->set_condicion_fija('es_igual_a');
		$filtro->columna('actividad')->set_condicion_fija('contiene');
	}
	
	/**
	 * Redefinición del evento filtrar, para que arme una cadena WHERE acorde con la
	 * nueva estructura de modalidades de cursada de comisión.
	 * 
	 */
	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_modalidad = '';
			if (isset($datos['modalidad'])) {
				$filtro_modalidad .= " AND EXISTS (SELECT * FROM sga_comisiones_modalidad WHERE sga_comisiones_modalidad.comision = sga_comisiones.comision AND sga_comisiones_modalidad.modalidad = '{$datos['modalidad']['valor']}')";
				$this->dep('filtro')->columna('modalidad')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_modalidad";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		if (isset($this->s__datos_form_fechas) && $this->s__datos_form_fechas['tipo_filtro'] == 'C') {
			$this->s__comisiones = $seleccion;
		}
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		if (isset($this->s__comisiones)) {
			$comisiones = aplanar_matriz($this->s__comisiones, 'comision');
			$datos = $this->dep('cuadro')->get_datos();
			$check_activo = in_array($datos[$fila]['comision'], $comisiones);
			$evento->set_check_activo($check_activo);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: Datos Modificatoria 
	//-----------------------------------------------------------------------------------

	function conf__datos_modificatoria($pantalla)
	{
		$datos = $this->get_datos_ev_info();
		$this->ev_info()->set_datos($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{		
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico', 'periodo_lectivo', 'ubicacion', 'comisiones', 'vencimiento'));

			$this->ev_info->set_columnas_fila(array('anio_academico', 'periodo_lectivo'), 1);
			$this->ev_info->set_columnas_fila(array('ubicacion'), 2);
			$this->ev_info->set_columnas_fila(array('comisiones'), 3);
			$this->ev_info->set_columnas_fila(array('vencimiento'), 4);

			$this->ev_info->set_max_columnas_por_fila(2);

			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('periodo_lectivo', 'Período Lectivo');
			
			$this->ev_info->set_etiqueta('comisiones', 'Comisiones');

			$this->ev_info->set_etiqueta('ubicacion', 'Ubicación');

			$this->ev_info->set_etiqueta('vencimiento', 'Fecha Vencimiento Regularidad');

			$this->ev_info->set_td_etiqueta_width('200px');
			$this->ev_info->set_td_valores_width('250px');
		}
		return $this->ev_info;
	}
	
	function get_datos_ev_info()
	{
		$datos = array();
		if ($this->s__datos_form_fechas['tipo_filtro'] == 'C') {
			$datos['anio_academico'] = $this->s__datos['anio_academico']['valor'];
			
			$datos_periodo = guarani::co('co_periodos_lectivos')->get_datos_periodo_lectivo($this->s__datos['periodo_lectivo']['valor']);
			$datos['periodo_lectivo'] = $datos_periodo['periodo_nombre'];
			
			if (isset($this->s__datos['ubicacion'])) {
				$datos_ubicacion = guarani::co('co_ubicaciones')->get_ubicacion_info($this->s__datos['ubicacion']['valor']);
				$datos['ubicacion'] = $datos_ubicacion['ubicacion_nombre'];
			}
			
			$datos['comisiones'] = $this->get_html_icono_info_comisiones_seleccionadas();
		} else {
			if ($this->s__datos_form_fechas['vencimiento'] == 'D') {
				// Condición Desde
				$formateo = new toba_formateo('html');
				$datos['vencimiento'] = 'Desde '.$formateo->formato_fecha($this->s__datos_form_fechas['fecha_desde']);
			} elseif ($this->s__datos_form_fechas['vencimiento'] == 'H') {
				// Condición Hasta
				$formateo = new toba_formateo('html');
				$datos['vencimiento'] = 'Hasta '.$formateo->formato_fecha($this->s__datos_form_fechas['fecha_desde']);
			} elseif ($this->s__datos_form_fechas['vencimiento'] == 'E') {
				// Condición Entre
				$formateo = new toba_formateo('html');
				$datos['vencimiento'] = 'Entre '.$formateo->formato_fecha($this->s__datos_form_fechas['fecha_desde']).' y '.$formateo->formato_fecha($this->s__datos_form_fechas['fecha_hasta']);
			}
		}
		
		return $datos;
	}
	
	function get_html_icono_info_comisiones_seleccionadas()
	{
		$comisiones = aplanar_matriz($this->s__comisiones, 'comision');
		toba::memoria()->set_dato('comisiones', $comisiones);
		
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '41000123', array(), array('celda_memoria'=>'comisiones_seleccionadas'));
		$javascript = "abrir_popup('info','$vinculo',{'width':'600','height':'520'})";

		$salida = "<a class='icono-utileria' href='#' onclick=\"$javascript\">";
		$salida .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, 'Ver comisiones');
		$salida .= '</a>';

		return $salida;
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		$form->set_datos($this->s__datos_modificatoria);
	}

	function evt__formulario__modificacion($datos)
	{
		$this->s__datos_modificatoria = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: resultado (Procesamiento)
	//-----------------------------------------------------------------------------------

	function conf__resultado()
	{
		$datos = $this->get_datos_ev_info();
		$this->ev_info()->set_datos($datos);
	}
	
	function ajax__estado_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_estado_proceso();
		if (!empty($contenido)) {
			$respuesta->set($contenido);
		}
	}

	function ajax__resultados_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_resultado_proceso(pro_vencimiento_regularidad::reporte_resultados);
		$resultados = current($contenido['mensajes']);
		if (!empty($resultados)) {
			$respuesta->set($resultados);
		}
	}

	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		switch ($params['evento']){
			case 'rep_modificados':
				$reporte = pro_vencimiento_regularidad::reporte_modificados;
				$operacion = '41000121';
				$titulo = 'Alumnos a los que se les ha cambiado la fecha de fin de vigencia de la regularidad';
				break;
			case 'rep_no_modificados':
				$reporte = pro_vencimiento_regularidad::reporte_no_modificados;
				$operacion = '41000122';
				$titulo = 'Alumnos a los que NO se les ha cambiado la fecha de fin de vigencia de la regularidad';
				break;
		}
		if ($this->modo_visualizacion()) {
			$datos = $this->controlador()->get_resultado_reporte($reporte);
		} else {
			//Obtengo el Proceso para pedirle el log de resultados
			$datos = $this->cn()->get_resultado_proceso($reporte);			
		}

		// Se almacenan los datos en la instasncia de impresion para ser consumida luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos(array(	'datos' => $datos['mensajes'],
																'titulo' => $titulo));
		
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		// Se crea el vínculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $operacion, null, array('celda_memoria'=>'popup'));
		$respuesta->set($vinculo);
	}
	
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__confirmar()
	{
		if ($this->s__datos_form_fechas['tipo_filtro'] == 'C') { // Filtrar por comisión.
			if ($this->s__datos_form_fechas['aplicacion'] == 'con_venc') {
				$this->validar_vencimiento();
			}
			if (guarani_arreglos::count($this->s__comisiones) == 0) {
				throw guarani::error(guarani_mensajes::get('800_no_seleccion', array('una comisión')));
			}
			// Filtro por comisión. Se usa la vista vw_regularidades que es la vista utilizada en la query del metodo "get_regularidades"
			// La vista vw_comisiones tiene un LEFT JOIN por eso no se usa porque en bases con muchos registros tarda mucho tiempo.
			$where = guarani_sql::armar_condicion_in($this->s__comisiones, 'vw_regularidades', 'comision', 'comision');
			$where .= $this->get_where_aplicacion();
		} else { // Filtrar por fecha.
			$this->validar_vencimiento();
			// Filtro por fecha.
			$where = $this->get_condicion_where_fechas($this->s__datos_form_fechas['vencimiento'], $this->s__datos_form_fechas['fecha_desde'], $this->s__datos_form_fechas['fecha_hasta']);
		}

		$this->cn()->set_filtro($where);
		$this->s__regularidades = $this->cn()->get_regularidades();
		$this->set_pantalla('datos_modificatoria');
	}

	function evt__procesar()
	{
		$parametros = array();
		$parametros['datos'] = $this->s__regularidades;
		$parametros['datos_modificatoria'] = $this->s__datos_modificatoria;
		$this->cn()->ejecutar_proceso($parametros);		
		$this->set_pantalla('resultado');
	}

	function evt__volver()
	{
		unset($this->s__datos_modificatoria);
		$this->set_pantalla('fechas_comisiones');
	}
	
	function evt__finalizar()
	{
		unset($this->s__datos_form_fechas);
		unset($this->s__comisiones);
		unset($this->s__datos_modificatoria);
		$this->set_pantalla('fechas_comisiones');
	}

	function evt__volver_inicio() {
		$this->set_pantalla('fechas_comisiones');
	}
	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_tipos_filtro()
	{
		return array(	array('valor' => 'F','descr' => 'Fecha fin de vigencia'),
						array('valor' => 'C','descr' => 'Comisión'));
	}

	function get_tipos_aplicacion()
	{
		return array(	array('valor' => 'todas', 'descr' => 'Todas las Regularidades'),
						array('valor' => 'sin_venc', 'descr' => 'Regularidades sin Vencimiento'),
						array('valor' => 'con_venc', 'descr' => 'Regularidades con Vencimiento'));
	}

	function get_condicion_where_fechas($vencimiento, $fecha_desde, $fecha_hasta)
	{
		if ($vencimiento == 'D') {
			// Condición Desde
			$filtro_condicion = new toba_filtro_condicion('desde', '>=', '', '', '::date', '::date');
			$where = $filtro_condicion->get_sql('vw_regularidades.fecha_vigencia', $fecha_desde);
			
		} elseif ($vencimiento == 'H') {
			// Condición Hasta
			$filtro_condicion = new toba_filtro_condicion('desde', '<=', '', '', '::date', '::date');
			$where = $filtro_condicion->get_sql('vw_regularidades.fecha_vigencia', $fecha_desde);
			
		} elseif ($vencimiento == 'E') {
			// Condición Entre
			$filtro_condicion = new toba_filtro_condicion_entre('::date', '::date');
			$valores = array();
			$valores['desde'] = $fecha_desde;
			$valores['hasta'] = $fecha_hasta;
			$where = $filtro_condicion->get_sql('vw_regularidades.fecha_vigencia', $valores);
		}
		return $where;
	}

	function get_where_aplicacion()
	{
		$where = '';
		if (!empty($this->s__datos_form_fechas['aplicacion'])) {
			if ($this->s__datos_form_fechas['aplicacion'] == 'sin_venc') {
				$where = ' AND vw_regularidades.fecha_vigencia IS NULL';
			} elseif ($this->s__datos_form_fechas['aplicacion'] == 'con_venc') {
				$where = ' AND '.$this->get_condicion_where_fechas($this->s__datos_form_fechas['vencimiento'], $this->s__datos_form_fechas['fecha_desde'], $this->s__datos_form_fechas['fecha_hasta']);
			}
		}
		return $where;
	}

	function get_cantidad_alumnos_regularidades()
	{
		return guarani_arreglos::count($this->s__regularidades);
	}

	function validar_vencimiento()
	{
		if ($this->s__datos_form_fechas['vencimiento'] == 'E') {
			$errores = array();
			if (empty($this->s__datos_form_fechas['fecha_desde'])) {
				$errores[] = array ('mensaje' 	=> guarani_mensajes::get('800_unica_seleccion', array('una fecha Desde')),
									'tipo'		=> comunes::error_critico 
									);
			}
			if (empty($this->s__datos_form_fechas['fecha_hasta'])) {
				$errores[] = array ('mensaje' 	=> guarani_mensajes::get('800_unica_seleccion', array('una fecha Hasta')),
									'tipo'		=> comunes::error_critico 
									);
			}
			if (guarani_fecha::es_mayor($this->s__datos_form_fechas['fecha_desde'], $this->s__datos_form_fechas['fecha_hasta'])) {
				$errores[] = array ('mensaje' 	=> guarani_mensajes::get('800_control_fechas', array('Desde', 'mayor', 'Hasta')),
									'tipo'		=> comunes::error_critico 
									);
			}
			if (!empty($errores)) {
				throw guarani::error($errores);
			}
		} else {
			if (empty($this->s__datos_form_fechas['fecha_desde'])) {
				throw guarani::error(guarani_mensajes::get('800_unica_seleccion', array('una fecha')));
			}
		}
	}
}