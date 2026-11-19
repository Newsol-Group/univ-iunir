<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Comisiones
 */
class ci_nav_exceptuar_periodos_inscripcion extends ci_navegacion_wizard
{
	// Filtro Actividades y Comisiones.
	protected $s__datos_filtro;
	protected $s__filtro_comisiones;
	protected $s__filtro_actividades;

	// Componente Visual de Información.
	protected $s__datos_ev_info;
	protected $ev_info;

	// Datos de la Excepción.
	protected $datos_excepcion;

	protected $indx_msg_eof = '800_eof_cuadro';
	protected $mje_confirma_excepcion = '800CUR_confirma_excepcion_per_insc';

	//-----------------------------------------------------------------------------------
	//---- PANTALLA ACTIVIDADES ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	//---- filtro_actividades -----------------------------------------------------------

	function conf__filtro_actividades(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro)) {
			$filtro->set_datos($this->s__datos_filtro);
		}
		$filtro->columna('modalidad')->set_condicion_fija('es_igual_a');
		$filtro->columna('nombre_actividad')->set_condicion_fija('contiene');
	}

	function evt__filtro_actividades__filtrar($datos)
	{
		$this->s__filtro_actividades = '';
		$this->s__filtro_comisiones = '';
		if (isset($datos)) {
			/**
			 * El filtro de actividades tiene dos tipos de campos, los que filtran actividades
			 * y los que filtran comisiones. Se arma entonces en primer lugar el filtro para las
			 * actividades reseteando sus campos, y luego con los demás campos se arma el filtro
			 * para las comisiones.
			 */
			$this->s__datos_filtro = $datos;
			$sql_clausulas = $this->dep('filtro_actividades')->get_sql_clausulas();

			if (isset($datos['nombre_actividad'])) {
				$this->s__filtro_actividades = $sql_clausulas['nombre_actividad'];
				$this->dep('filtro_actividades')->columna('nombre_actividad')->resetear_estado();
			}

			if (isset($datos['entidad_subtipo'])) {
				$this->s__filtro_actividades .= empty($this->s__filtro_actividades) ? $sql_clausulas['entidad_subtipo'] : " AND {$sql_clausulas['entidad_subtipo']}";
				$this->dep('filtro_actividades')->columna('entidad_subtipo')->resetear_estado();
			}

			if (isset($datos['propuesta'])) {
				$this->s__filtro_actividades .= empty($this->s__filtro_actividades) ? $sql_clausulas['propuesta'] : " AND {$sql_clausulas['propuesta']}";
				$this->dep('filtro_actividades')->columna('propuesta')->resetear_estado();
			}

			if (isset($datos['anio_de_cursada'])) {
				$this->s__filtro_actividades .= empty($this->s__filtro_actividades) ? $sql_clausulas['anio_de_cursada'] : " AND {$sql_clausulas['anio_de_cursada']}";
				$this->dep('filtro_actividades')->columna('anio_de_cursada')->resetear_estado();
			}

			$filtro_modalidad = '';
			if (isset($datos['modalidad'])) {
				$filtro_modalidad .= " AND EXISTS (SELECT * FROM sga_comisiones_modalidad WHERE sga_comisiones_modalidad.comision = sga_comisiones.comision AND sga_comisiones_modalidad.modalidad = '{$datos['modalidad']['valor']}')";
				$this->dep('filtro_actividades')->columna('modalidad')->resetear_estado();
			}

			$this->s__filtro_comisiones = "{$this->dep('filtro_actividades')->get_sql_where()} $filtro_modalidad";
			$this->s__datos_ev_info = toba::consulta_php('co_periodos_lectivos')->get_datos_periodo_lectivo($datos['periodo_lectivo']['valor']);
		} else {
			unset($this->s__datos_filtro);
		}
	}

	function evt__filtro_actividades__cancelar()
	{
		unset($this->s__datos_filtro);
		unset($this->s__filtro_actividades);
		unset($this->s__filtro_comisiones);
	}

	//---- cuadro_actividades -----------------------------------------------------------
	
	function conf__cuadro_actividades(cuadro_seleccion_multiple $cuadro)
	{
		if (!isset($this->s__filtro_actividades) && !isset($this->s__filtro_comisiones)) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			$order_by = 'vw_actividades.nombre';
			$datos = $this->cn()->get_listado_exceptuar_periodos_insc($this->s__filtro_comisiones, $this->s__filtro_actividades, $order_by);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}

	function evt__cuadro_actividades__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function conf_evt__cuadro_actividades__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_actividades', $fila));
	}

	function validar_actividades()
	{
		if ($this->falta_seleccionar('elemento')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una actividad'));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA COMISIONES ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__comisiones(toba_ei_pantalla $pantalla)
	{
		if (isset($this->s__datos_filtro['ubicacion'])) {
			$datos_ubicacion = guarani::co('co_ubicaciones')->get_ubicacion_info($this->s__datos_filtro['ubicacion']['valor']);
			$this->s__datos_ev_info['ubicacion_nombre'] = $datos_ubicacion['ubicacion_nombre'];
		}
		if (isset($this->s__datos_filtro['modalidad'])) {
			$datos_modalidad = current(guarani::co('co_planes')->get_info_modalidades($this->s__datos_filtro['modalidad']['valor']));
			$this->s__datos_ev_info['modalidad_nombre'] = $datos_modalidad['modalidad_nombre'];
		}
		$this->s__datos_ev_info['actividad_nombre'] = current($this->get_seleccion('actividad_nombre'));
		$this->ev_info()->set_datos($this->s__datos_ev_info);
	}

	//---- cuadro_comisiones ------------------------------------------------------------

	function conf__cuadro_comisiones(cuadro_seleccion_multiple $cuadro)
	{
		$filtro = $this->s__filtro_comisiones.' AND '.guarani_sql::armar_condicion_in($this->get_seleccion_valor('elemento'), 'sga_comisiones', 'elemento');
		$order_by = 'sga_elementos.nombre, sga_comisiones.nombre';
		$datos = toba::consulta_php('co_comisiones')->get_listado($filtro, '', $order_by);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_comisiones__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function conf_evt__cuadro_comisiones__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_comisiones', $fila));
	}

	function validar_comisiones()
	{
		if ($this->falta_seleccionar('comision')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una comisión'));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA EXCEPCION -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__excepcion(toba_ei_pantalla $pantalla)
	{
		$mensaje = guarani_mensajes::get($this->mje_confirma_excepcion);
		$this->evento('procesar')->set_msg_confirmacion($mensaje);
		$this->ev_info()->set_datos($this->s__datos_ev_info);
	}

	//---- form_excepcion ---------------------------------------------------------------

	function conf__form_excepcion(toba_ei_formulario $form)
	{
		if (isset($this->datos_excepcion)) {
			$form->set_datos($this->datos_excepcion);
		}
	}

	function evt__form_excepcion__modificacion($datos)
	{
		$this->datos_excepcion = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_max_datos_por_columna(10);
			$this->ev_info->set_max_chars_por_columna(110);
			$this->ev_info->set_td_etiqueta_width('100px');
			$this->ev_info->set_columnas_a_mostrar(array('periodo_anio_academico', 'periodo_nombre', 'ubicacion_nombre', 'modalidad_nombre', 'actividad_nombre'));
			$this->ev_info->set_columnas_fila(array('periodo_anio_academico', 'periodo_nombre'), 1);
			$this->ev_info->set_columnas_fila(array('ubicacion_nombre', 'modalidad_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('actividad_nombre'), 3);
			$this->ev_info->set_max_columnas_por_fila(2);
			$this->ev_info->set_etiqueta('periodo_anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('periodo_nombre', 'Período Lectivo');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
			$this->ev_info->set_etiqueta('modalidad_nombre', 'Modalidad');
			$this->ev_info->set_etiqueta('actividad_nombre', 'Actividades');
		}
		return $this->ev_info;
	}

	function get_fecha_fin_periodo_lectivo()
	{
		return $this->s__datos_ev_info['periodo_fecha_fin'];
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__procesar()
	{
		$this->cn()->exceptuar_periodos_inscripcion($this->datos_excepcion['fecha_inicio'], $this->datos_excepcion['fecha_fin'], $this->datos_excepcion['fecha_tope_bajas'], $this->s__datos_ev_info['periodo_fecha_fin'], $this->get_seleccion_valor('comision'));
		$this->limpiar_seleccion();
		$this->set_pantalla('actividades');
	}
}
?>