<?php
/**
 *
 *
 * @package     Asistencia
 * @subpackage  
 * 
 */
class ci_rep_historial_inasistencias extends ci_rep_sin_listado_inicial
{
	//Selecciones
	protected $s__alumno;
	protected $s__datos_alumno;
	protected $s__comisiones;
	
	//Filtro comisiones
	protected $s__datos_comisiones;
	protected $s__filtro_comisiones;

	//Componente Visual de Información
	protected $ev_info;
	
	function get_tabla_principal()
	{
		return 'sga_alumnos';
	}
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_alumnos')->get_listado($filtro);
	}
	
	function set_alumno_seleccionado($alumno)
	{
		$this->s__alumno = $alumno;
	}
	
	function get_alumno_seleccionado()
	{
		return $this->s__alumno;
	}
	
	function set_comisiones_seleccionadas($comisiones)
	{
		$this->s__comisiones = $comisiones;
	}
	
	function get_comisiones_seleccionadas()
	{
		return $this->s__comisiones;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Pantalla Selección -----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- cuadro -----------------------------------------------------------------------
	
	function evt__cuadro__seleccion($seleccion)
	{
		$this->set_alumno_seleccionado(current($seleccion));
		$this->set_pantalla('comisiones');
	}
	
	//-----------------------------------------------------------------------------------
	//---- Pantalla Comisiones ----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__comisiones(toba_ei_pantalla $pantalla)
	{
		$filtro = 'sga_alumnos.alumno = '.$this->get_alumno_seleccionado();
		$this->s__datos_alumno = current($this->get_datos($filtro));
		if (!isset($this->s__datos_alumno['legajo'])) {
			$this->s__datos_alumno['legajo'] = '';
		}
		$this->ev_info()->set_datos($this->s__datos_alumno);
	}
	
	//---- filtro_comisiones ------------------------------------------------------------
	
	function conf__filtro_comisiones(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_comisiones)) {
			$filtro->set_datos($this->s__datos_comisiones);
		}
	}

	function evt__filtro_comisiones__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos_comisiones = $datos;
			$this->s__filtro_comisiones = $this->dep('filtro_comisiones')->get_sql_where();
		} else {
			unset($this->s__datos_comisiones);
			$this->s__filtro_comisiones = '';
		}
	}

	function evt__filtro_comisiones__cancelar()
	{
		unset($this->s__datos_comisiones);
		unset($this->s__filtro_comisiones);
	}
	
	//---- cuadro_comisiones ------------------------------------------------------------
	
	function conf__cuadro_comisiones(toba_ei_cuadro $cuadro)
	{
		$filtro  = 'sga_alumnos.alumno = ' . $this->get_alumno_seleccionado();
		$filtro .= !empty($this->s__filtro_comisiones) ? ' AND ' . $this->s__filtro_comisiones : '';
		$datos = toba::consulta_php('co_inscripciones_cursadas')->get_listado_inscripciones_a_comision($filtro);
		$cuadro->set_datos($datos);
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_sin_registros'));
	}

	function evt__cuadro_comisiones__seleccion($seleccion)
	{
		$this->set_comisiones_seleccionadas($seleccion);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Pantalla Inasistencias -------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__inasistencias(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->s__datos_alumno);
	}
	
	//---- cuadro_inasistencias ---------------------------------------------------------
	
	function conf__cuadro_inasistencias(toba_ei_cuadro $cuadro)
	{
		$condicion_in = guarani_sql::armar_condicion_in($this->get_comisiones_seleccionadas(), 'sga_comisiones_bh', 'comision');
		$datos = toba::consulta_php('co_asistencias')->get_ausencias_alumno($this->get_alumno_seleccionado(), $condicion_in);
		$alu = $com = $porc = 0;
		if (!empty($datos)) {
			foreach ($datos as $key => $inasistencia) {
				if (($inasistencia['alumno'] != $alu) || ($inasistencia['comision'] != $com)) {
                    $total_inasistencias = asistencia::instancia()->calcular_inasistencias_acumulada_de_un_alumno_x_comision($inasistencia['alumno'], $inasistencia['comision']);
					$descr_total_inasistencias = isset($total_inasistencias['descr_total_inasistencias']) ? $total_inasistencias['descr_total_inasistencias'] : 'Cantidad Total de Inasistencias: 0';
					$descr_total_inasistencias_justificadas = isset($total_inasistencias['descr_total_inasistencias_justificadas']) ? $total_inasistencias['descr_total_inasistencias_justificadas'] : 'Cantidad de Justificaciones: 0';
					$porcentaje = isset($total_inasistencias['descr_porcentaje_asistencia']) ? $total_inasistencias['descr_porcentaje_asistencia'] : 'Porcentaje de Asistencia: 0,00%';
					$alu = $inasistencia['alumno'];
					$com = $inasistencia['comision'];
				}
				$datos[$key]['descr_porcentaje_asistencia'] = $porcentaje;
				$datos[$key]['descr_total_inasistencias'] = $descr_total_inasistencias;
				$datos[$key]['descr_total_inasistencias_justificadas'] = $descr_total_inasistencias_justificadas;
			}
		}

		$cuadro->set_datos($datos);
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_sin_registros'));
	}
	
	function cuadro()
	{
		return $this->dep('cuadro_inasistencias');
	}

	function datos_encabezado() 
	{
		return $this->ev_info->get_datos();
	}
	
	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('apellido_y_nombre',  'legajo', 'propuesta_nombre', 'plan_nombre', 'ubicacion_nombre'));
			$this->ev_info->set_columnas_fila(array('apellido_y_nombre', 'legajo'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre', 'plan_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('ubicacion_nombre'), 3);
			
			$this->ev_info->set_etiqueta('apellido_y_nombre', 'Alumno');
			$this->ev_info->set_etiqueta('legajo', 'Legajo');
			$this->ev_info->set_etiqueta('propuesta_nombre', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__volver()
	{
		unset($this->s__alumno);
		unset($this->s__datos_alumno);
		unset($this->s__comisiones);
		$this->set_pantalla('seleccion');
	}

	function evt__ver_inasistencias()
	{
		if (!isset($this->s__comisiones)) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una Comisión'));
			throw guarani::error($mje);
		}
		$this->set_pantalla('inasistencias');
	}

}
?>