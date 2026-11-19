<?php
/**
 *
 *
 * @package     Asistencia
 * @subpackage  
 * 
 */
class ci_rep_historial_inasistencias_docente extends ci_rep_sin_listado_inicial
{
	//Selecciones
	protected $s__docente;
	protected $s__datos_docente;
	protected $s__comisiones;
	
	//Filtro comisiones
	protected $s__datos_comisiones;
	protected $s__filtro_comisiones;

	//Componente Visual de Información
	protected $ev_info;
	
	function get_tabla_principal()
	{
		return 'sga_docentes';
	}
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_docentes')->get_listado($filtro);
	}
	
	function set_docente_seleccionado($docente)
	{
		$this->s__docente = $docente;
	}
	
	function get_docente_seleccionado()
	{
		return $this->s__docente;
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
		$this->set_docente_seleccionado(current($seleccion));
		$this->set_pantalla('comisiones');
	}
	
	//-----------------------------------------------------------------------------------
	//---- Pantalla Comisiones ----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__comisiones(toba_ei_pantalla $pantalla)
	{
		$filtro = 'sga_docentes.docente = ' . $this->get_docente_seleccionado();
		$this->s__datos_docente = current($this->get_datos($filtro));
		if (!isset($this->s__datos_docente['legajo'])) {
			$this->s__datos_docente['legajo'] = '';
		}
		$this->ev_info()->set_datos($this->s__datos_docente);
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
		$filtro =			   "EXISTS (SELECT	1
								FROM	sga_clases_ausencias_docentes,
										sga_clases,
										sga_comisiones_bh
								WHERE	sga_clases_ausencias_docentes.clase = sga_clases.clase
									AND sga_clases.banda_horaria = sga_comisiones_bh.banda_horaria
									AND sga_comisiones_bh.comision = sga_comisiones.comision
									AND sga_docentes_comision.comision = sga_comisiones.comision
									AND sga_docentes_comision.docente = sga_clases_ausencias_docentes.docente)";
		
		if (isset($this->s__filtro_comisiones) && !empty($this->s__filtro_comisiones)) {
			$this->indx_msg_eof .= '_filtrado';
			$filtro .= ' AND ' . $this->s__filtro_comisiones;
		}
		$msj = guarani_mensajes::get($this->indx_msg_eof, array('Comisiones asociadas al Docente con ausencias registradas'));
		$cuadro->set_eof_mensaje($msj);
		$datos = toba::consulta_php('co_comisiones')->get_comisiones_docente($this->get_docente_seleccionado(), $filtro);
		$cuadro->set_datos($datos);
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
		$this->ev_info()->set_datos($this->s__datos_docente);
	}
	
	//---- cuadro_inasistencias ---------------------------------------------------------
	
	function conf__cuadro_inasistencias(toba_ei_cuadro $cuadro)
	{
		$etiquetas = guarani_etiquetas::get_tabla('sga_clases_ausencias_docentes');
		$msj = guarani_mensajes::get($this->indx_msg_eof, array($etiquetas->get_enf('plural')));
		$cuadro->set_eof_mensaje($msj);
		$condicion_in = guarani_sql::armar_condicion_in($this->get_comisiones_seleccionadas(), 'sga_comisiones_bh', 'comision');
		
		$datos = toba::consulta_php('co_asistencias')->get_inasistencias_docente($this->get_docente_seleccionado(), $condicion_in);
		$cuadro->set_datos($datos);
	}
	
	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('nombre_persona',  'legajo'));
			$this->ev_info->set_columnas_fila(array('nombre_persona', 'legajo'), 1);
			
			$this->ev_info->set_etiqueta('apellido_y_nombre', 'Docente');
			$this->ev_info->set_etiqueta('legajo', 'Legajo');
		}
		
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__volver()
	{
		unset($this->s__docente);
		unset($this->s__datos_docente);
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
	
	function cuadro() 
	{
		return $this->dep('cuadro_inasistencias');
	}	
}
?>