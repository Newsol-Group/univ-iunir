<?php
/**
 *
 *
 * @package     Asistencia
 * @subpackage  
 */
class ci_rep_listado_asistencias extends ci_rep_sin_listado_inicial
{
	//Selección
	protected $s__comision;
	
	//Componente Visual de Información
	protected $ev_info;
	
	protected $s__xcuadro;
	
	function get_tabla_principal()
	{
		return 'sga_comisiones';
	}
	
	function get_datos($filtro, $select='')
	{
		return toba::consulta_php('co_comisiones')->get_listado($filtro, $select);
	}
	
	function ini()
	{
		parent::ini();
		toba::memoria()->desactivar_reciclado();
	}
	
	function conf()
	{
		
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA COMISIONES ----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- filtro -----------------------------------------------------------------------
	
	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('modalidad')->set_condicion_fija('es_igual_a');
	}
	
	function evt__filtro__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos = $datos;
			$filtro_modalidad = '';
			
			if (isset($datos['modalidad'])) {
				$filtro_modalidad .= " AND EXISTS (	SELECT	*
													FROM	sga_comisiones_modalidad
													WHERE	sga_comisiones_modalidad.comision  = sga_comisiones.comision
													AND		sga_comisiones_modalidad.modalidad = '{$datos['modalidad']['valor']}')";
				$this->dep('filtro')->columna('modalidad')->resetear_estado();
			}
			
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_modalidad";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	//---- cuadro -----------------------------------------------------------------------
	
	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__comision = $seleccion;
		$this->set_pantalla('asistencias');
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA ASISTENCIAS ---------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__asistencias(toba_ei_pantalla $pantalla)
	{
		$where = 'sga_comisiones.comision = '.current($this->s__comision);
		$select = "'(' || sga_elementos.codigo || ') ' || sga_elementos.nombre AS actividad_codigo_y_nombre";
		$datos = $this->get_datos($where, $select);
		$cantidades = toba::consulta_php('co_comisiones')->get_cantidades_horas_y_clases_comision(current($this->s__comision));
		$datos[0]['cantidad_clases'] = $cantidades['cant_clases'];
		$datos[0]['cantidad_horas'] = $cantidades['total_horas_clases'];
		$this->ev_info()->set_datos($datos[0]);
		
		if (toba::consulta_php('co_comisiones')->comision_tiene_subcomisiones(current($this->s__comision))) {
			$pantalla->agregar_dep('cuadro_alumnos_subcomisiones');
			$this->s__xcuadro = 'cuadro_alumnos_subcomisiones';			
		} else {
			$pantalla->agregar_dep('cuadro_alumnos_comision');
			$this->s__xcuadro = 'cuadro_alumnos_comision';
		}
	}
	
	//---- cuadro_alumnos_comision ------------------------------------------------------
	
	function conf__cuadro_alumnos_comision(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_ev_info_asociado($this->ev_info());
		$datos = toba::consulta_php('co_asistencias')->get_alumnos_inasistencias_acum_comision(current($this->s__comision));
		$cuadro->set_datos($datos);
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_sin_registros'));
	}
	
	//---- cuadro_alumnos_subcomisiones -------------------------------------------------
	
	function conf__cuadro_alumnos_subcomisiones(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_ev_info_asociado($this->ev_info());
		$cuadro->set_datos($this->get_datos_cuadro());
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_sin_registros'));
	}
	
	function get_datos_cuadro()
	{
		$where = current($this->s__comision);
		return toba::consulta_php('co_asistencias')->get_asistencias_alumnos_subc($where);
	}
	
	function cuadro() 
	{
		return $this->dep($this->s__xcuadro);
	}
	
	function datos_encabezado() 
	{
		if (isset($this->ev_info)) {
			$datos = $this->ev_info->get_datos();
		} else {
			$datos = array();                            
		}
		return $datos;
	}

	function ancho_etiquetas_encabezado()
	{
		return 120;
	}
	
	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_td_etiqueta_width('120px');
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico_periodo', 'nombre_periodo', 'actividad_codigo_y_nombre', 'nombre', 'nombre_turno', 'nombre_modalidad', 'nombre_ubicacion', 'cantidad_clases', 'cantidad_horas'));
			$this->ev_info->set_columnas_fila(array('anio_academico_periodo', 'nombre_periodo'), 1);
			$this->ev_info->set_columnas_fila(array('actividad_codigo_y_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('nombre', 'nombre_turno', 'nombre_modalidad'), 3);
			$this->ev_info->set_columnas_fila(array('nombre_ubicacion', 'cantidad_clases', 'cantidad_horas'), 4);
			
			$this->ev_info->set_etiqueta('anio_academico_periodo', 'Año Académico');
			$this->ev_info->set_etiqueta('nombre_periodo', 'Período Lectivo');
			$this->ev_info->set_etiqueta('actividad_codigo_y_nombre', 'Actividad');
			$this->ev_info->set_etiqueta('nombre', 'Comisión');
			$this->ev_info->set_etiqueta('nombre_turno', 'Turno');
			$this->ev_info->set_etiqueta('nombre_modalidad', 'Modalidad');
			$this->ev_info->set_etiqueta('nombre_ubicacion', 'Ubicación');
			$this->ev_info->set_etiqueta('cantidad_clases', 'Cantidad de clases');
			$this->ev_info->set_etiqueta('cantidad_horas', 'Cantidad de horas');
		}
		
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__volver()
	{
		unset($this->s__comision);
		$this->set_pantalla('comisiones');
	}

}
?>
