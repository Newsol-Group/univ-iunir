<?php
/**
 * 
 * 
 * @package 	Calendario Académico
 * @subpackage 	Planificación
 *
 */
class ci_nav_comparacion_planificado_dictado extends guarani_pers_ci
{
	protected $s__datos;
	protected $s__filtro;

	protected $ev_info;
	
	protected $s__opciones;
	protected $s__pantalla_anterior;

	function conf()
	{
		$this->s__pantalla_anterior = $this->get_id_pantalla_anterior();
	}
	
	function ini()
	{
		parent::ini();
		toba::memoria()->desactivar_reciclado();
	}

	//-----------------------------------------------------------------------------------
	//---- constantes -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	const valor_todos = 'todo';	
	const descr_todos = 'Temas Planificados y Dictados';
	
	const valor_planificados = 'planificados';
	const descr_planificados = 'Temas Planificados';
	
	const valor_dictados = 'dictados';
	const descr_dictados = 'Temas Dictados';
	
	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico_periodo', 'nombre_periodo',
														 'nombre', 'nombre_turno', 'nombre_modalidad',
														 'subcomision_nombre', 'tipo_clase_nombre',
														 'nombre_ubicacion', 'codigo_y_nombre_actividad'
														 ));
			
			$this->ev_info->set_columnas_fila(array('anio_academico_periodo', 'nombre_periodo'), 1);
			$this->ev_info->set_columnas_fila(array('nombre', 'nombre_turno', 'nombre_modalidad'), 2);
			$this->ev_info->set_columnas_fila(array('subcomision_nombre', 'tipo_clase_nombre'), 3);
			$this->ev_info->set_columnas_fila(array('nombre_ubicacion', 'codigo_y_nombre_actividad'), 4);
			
			$this->ev_info->set_max_columnas_por_fila(3);
			
			$this->ev_info->set_etiqueta('anio_academico_periodo', 'Año Académico');
			$this->ev_info->set_etiqueta('nombre_periodo', 'P. Lectivo');
			
			$this->ev_info->set_etiqueta('nombre', 'Comisión');
			$this->ev_info->set_etiqueta('nombre_turno', 'Turno');
			$this->ev_info->set_etiqueta('nombre_modalidad', 'Modalidad');
			
			$this->ev_info->set_etiqueta('subcomision_nombre', 'Subcomisión');
			$this->ev_info->set_etiqueta('tipo_clase_nombre', 'Tipo');
			
			$this->ev_info->set_etiqueta('nombre_ubicacion', 'Ubicación');
			$this->ev_info->set_etiqueta('codigo_y_nombre_actividad', 'Actividad');
			
			$this->ev_info->set_td_etiqueta_width('100px');
			$this->ev_info->set_td_valores_width('200px');
		}
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA sel_comision
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- filtro_comision --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_comision(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}
		$filtro->columna('modalidad')->set_condicion_fija('es_igual_a');
		$filtro->columna('actividad')->set_condicion_fija('contiene');
	}

	function evt__filtro_comision__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_modalidad = '';
			if (isset($datos['modalidad'])) {
				$filtro_modalidad .= " AND EXISTS (SELECT * FROM sga_comisiones_modalidad WHERE sga_comisiones_modalidad.comision = sga_comisiones.comision AND sga_comisiones_modalidad.modalidad = '{$datos['modalidad']['valor']}')";
				$this->dep('filtro_comision')->columna('modalidad')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro_comision')->get_sql_where()} $filtro_modalidad";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	function evt__filtro_comision__cancelar()
	{
		unset($this->s__datos);
		unset($this->s__filtro);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_comision --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_comision(toba_ei_cuadro $cuadro)
	{
		$indx_msg_eof = '800_eof_cuadro';
		if (empty($this->s__filtro)) {
			$msg_eof = guarani_mensajes::get($indx_msg_eof.'_sinfiltro');
		} else {
			$etiqueta = guarani_etiquetas::get_tabla('sga_comisiones');
			$msg_eof = guarani_mensajes::get($indx_msg_eof.'_filtrado', array($etiqueta->get_enf('plural')));

			$datos = $this->cn()->get_listado_comisiones($this->s__filtro);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($msg_eof);	
	}

	function evt__cuadro_comision__seleccion($seleccion)
	{
		$this->cn()->seleccionar_comision($seleccion);
		if ($this->cn()->existen_subcomisiones()) {
			$this->set_pantalla('sel_subcomision');
		} else {
			$this->set_pantalla('sel_clase');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA sel_subcomision
	//-----------------------------------------------------------------------------------

	function conf__sel_subcomision()
	{
		$this->ev_info()->set_datos($this->cn()->get_ev_info_datos());
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_subcomision -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_subcomision(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_subcomisiones();
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_subcomision__seleccion($seleccion)
	{
		$this->cn()->seleccionar_subcomision($seleccion);
		$this->set_pantalla('sel_clase');
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA sel_clase
	//-----------------------------------------------------------------------------------

	function conf__sel_clase()
	{
		$this->ev_info()->set_datos($this->cn()->get_ev_info_datos());
	}
	
	//-----------------------------------------------------------------------------------
	//---- opciones ---------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__opciones(toba_ei_formulario $form)
	{
		if (!empty($this->s__opciones)) {
			$form->set_datos($this->s__opciones);
		}
	}
	
	function evt__opciones__modificacion($datos)
	{
		$this->s__opciones = $datos;
	}
	
	function get_opciones_ver()
	{
		return array(array('valor' => self::valor_todos, 'descr' => self::descr_todos),
					 array('valor' => self::valor_planificados, 'descr' => self::descr_planificados),
					 array('valor' => self::valor_dictados, 'descr' => self::descr_dictados)
		            );
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_clases ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_clases(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_ev_info_asociado($this->ev_info());
		
		$datos = $this->cn()->get_clases();
		$cuadro->set_datos($datos);
		
		if (!empty($datos)) {
			if (isset($this->s__opciones['ver'])) {
				if ($this->s__opciones['ver'] == self::valor_planificados) {
					$this->dep('cuadro_clases')->eliminar_columnas(array('tema_dictado'));
				} elseif ($this->s__opciones['ver'] == self::valor_dictados) {
					$this->dep('cuadro_clases')->eliminar_columnas(array('tema_planificado'));
				}
			}
		} else {
			$msg_eof = guarani_mensajes::get('800CAL_sin_clases_comision');
			$cuadro->set_eof_mensaje($msg_eof);
		}
	}

	function evt__cuadro_clases__seleccion($seleccion)
	{
		$this->cn()->seleccionar_clase($seleccion);
		toba::memoria()->desactivar_reciclado();
		$this->set_pantalla('pant_reporte');
	}
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__volver()
	{
		if ($this->s__pantalla_anterior == 'sel_clase') {
			$this->cn()->deseleccionar_clase();
		} elseif ($this->s__pantalla_anterior == 'sel_subcomision') {
			if (! $this->cn()->existen_subcomisiones()) {
				$this->s__pantalla_anterior = 'sel_comision';
				$this->cn()->deseleccionar_comision();
			} else {
				$this->cn()->deseleccionar_subcomision();	
			}
			unset($this->s__opciones);
		} elseif ($this->s__pantalla_anterior == 'sel_comision') {
			$this->cn()->deseleccionar_comision();
		}
		$this->set_pantalla($this->s__pantalla_anterior);
	}

}
?>