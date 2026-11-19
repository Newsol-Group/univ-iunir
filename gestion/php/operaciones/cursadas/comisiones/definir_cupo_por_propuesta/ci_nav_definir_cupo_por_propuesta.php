<?php
/**
 *
 * @package Cursadas
 * @subpackage Comisiones
 */
class ci_nav_definir_cupo_por_propuesta extends ci_nav_sin_listado_inicial
{
	protected $ev_info;

	function get_comision_seleccionada()
	{
		return $this->s__seleccion['comision'];
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCIÓN COMISIÓN --------------------------------------------------
	//-----------------------------------------------------------------------------------

	//---- cuadro -----------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->set_pantalla('edicion');	
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICIÓN -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		/*$datos = $this->get_datos_ev_actividad_comisiones();
		$this->ev_info()->set_datos($datos);*/
	}

	//-----------------------------------------------------------------------------------
	//---- EVENTOS DEL CI ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__guardar()
	{
		try {
			$this->cn()->actualizar_propuestas_cupo($this->get_comision_seleccionada(), $this->dependencia('editor')->get_datos_propuestas_cupo());
			$this->cn()->resetear();
			$this->set_pantalla('seleccion');
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
		} catch (guarani_error $e)	{
			throw $e;
		} 
	}
	
	//-----------------------------------------------------------------------------------
	//---- Elementos de visualización ---------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function get_datos_ev_actividad_comisiones()
	{
		if (!isset($this->s__datos_seleccion)) {
			$this->s__datos_seleccion['anio_academico'] = $this->s__datos_filtro['anio_academico'];
			$this->s__datos_seleccion['periodo_lectivo_nombre'] = toba::consulta_php('co_periodos_lectivos')->get_nombre_periodo_lectivo($this->s__datos_filtro['periodo_lectivo']);
			$datos_ubicacion = toba::consulta_php('co_ubicaciones')->get_nombre_ubicacion($this->s__datos_filtro['ubicacion']);
			$this->s__datos_seleccion['ubicacion_nombre'] = $datos_ubicacion['ubicacion_nombre'];
			$datos_actividad = toba::consulta_php('co_elementos')-> get_actividad_nombre($this->get_actividad_seleccionada());
			$this->s__datos_seleccion['actividad_descr'] = $datos_actividad[0]['codigo_y_nombre'];
			if (isset($this->s__datos_filtro['responsable_academica'])) {
				$datos_ra = toba::consulta_php('co_responsables_academicas')->get_datos_responsable_academica($this->s__datos_filtro['responsable_academica']);				
				$this->s__datos_seleccion['resp_acad_nombre'] = $datos_ra[0]['resp_acad_nombre'];
			}
			// La cantidad de comisiones de la actividad se obtiene de la carga del cuadro a editar.
			$this->s__datos_seleccion['total_comisiones'] = $this->get_cant_comisiones_actividad_seleccionada();
		}
		return $this->s__datos_seleccion;
	}
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_td_etiqueta_width('110px');
			$this->ev_info->set_columnas_a_mostrar(array('nombre', 'nombre_turno', 'nombre_modalidad', 'anio_academico_periodo', 'nombre_periodo', 'codigo_y_nombre_actividad', 'nombre_ubicacion'));
			$this->ev_info->set_columnas_fila(array('nombre', 'nombre_turno', 'nombre_modalidad'), 1);
			$this->ev_info->set_columnas_fila(array('anio_academico_periodo', 'nombre_periodo'), 2);
			$this->ev_info->set_columnas_fila(array('codigo_y_nombre_actividad', 'nombre_ubicacion'), 3);
			$this->ev_info->set_max_columnas_por_fila(3);
			$this->ev_info->set_etiqueta('nombre', 'Comisión');
			$this->ev_info->set_etiqueta('nombre_turno', 'Turno');
			$this->ev_info->set_etiqueta('nombre_modalidad', 'Modalidad');
			$this->ev_info->set_etiqueta('anio_academico_periodo', 'Año Académico');
			$this->ev_info->set_etiqueta('nombre_periodo', 'Período Lectivo');
			$this->ev_info->set_etiqueta('codigo_y_nombre_actividad', 'Actividad');
			$this->ev_info->set_etiqueta('nombre_ubicacion', 'Ubicación');
		}
		return $this->ev_info;
	}}
?>