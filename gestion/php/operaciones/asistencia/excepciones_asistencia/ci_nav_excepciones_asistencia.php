<?php
/**
 *
 *
 * @package Asistencias
 * @subpackage 
 */
class ci_nav_excepciones_asistencia extends ci_nav_con_preseleccion
{
	// Datos del alumno seleccionado.
	protected $s__alumno;
	protected $s__datos_alumno;

	// Datos de la comisión seleccionada.
	protected $s__comision;
	protected $s__datos_comision;

	// Elementos Visuales de Información.
	protected $ev_info_alumno;
	protected $ev_info_comision;

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial_preseleccion();
	}

	function ini()
	{
		parent::set_concepto_preseleccion('Alumno');
	}

	function set_preseleccion($seleccion)
	{
		$this->s__alumno = $seleccion['alumno'];
	}

	function get_preseleccion()
	{
		return $this->s__alumno;
	}

	function get_comision()
	{
		return $this->s__comision;
	}

	function get_datos_comision()
	{
		return $this->s__datos_comision;
	}

	function get_cant_clases_comision()
	{
		return $this->s__datos_comision['cant_clases'];
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla Selección -----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$this->s__datos_alumno = toba::consulta_php('co_alumnos')->get_datos_basicos_alumno($this->s__alumno);
		$this->ev_info_alumno()->set_datos($this->s__datos_alumno);
	}
	
	//---- cuadro -----------------------------------------------------------------------
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_comisiones');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural'))));
		$filtro = "sga_insc_cursada.alumno = $this->s__alumno";
		$datos = $this->cn()->get_listado($filtro);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__comision = $seleccion['id_comision'];
		$this->cn()->cargar(array('comision' => $seleccion['id_comision'], 'alumno' => $this->s__alumno));
		$this->set_pantalla('edicion');
	}
	
	//-----------------------------------------------------------------------------------
	//---- Pantalla Edición -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$filtro = "sga_insc_cursada.alumno = $this->s__alumno";
		$filtro .= " AND sga_insc_cursada.comision = $this->s__comision";
		$this->s__datos_comision = current(toba::consulta_php('co_inscripciones_cursadas')->get_listado($filtro, true));

		$this->ev_info_alumno()->set_datos($this->s__datos_alumno);
		$this->ev_info_comision()->set_datos($this->s__datos_comision);

		parent::conf__edicion($pantalla);
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info_alumno ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info_alumno()
	{
		if (!isset($this->ev_info_alumno)) {
			$this->ev_info_alumno = guarani::ev_info_datos();
			$this->ev_info_alumno->set_controlador($this);
			$this->ev_info_alumno->set_titulo('ALUMNO');
			
			$this->ev_info_alumno->set_columnas_a_mostrar(array('apellido_y_nombre', 'legajo', 'propuesta_nombre_abreviado', 'plan_nombre', 'ubicacion_nombre'));
			$this->ev_info_alumno->set_columnas_fila(array('apellido_y_nombre', 'legajo'), 1);
			$this->ev_info_alumno->set_columnas_fila(array('propuesta_nombre_abreviado', 'plan_nombre', 'ubicacion_nombre'), 2);
			
			$this->ev_info_alumno->set_etiqueta('apellido_y_nombre', 'Nombre');
			$this->ev_info_alumno->set_etiqueta('legajo', 'Legajo');
			$this->ev_info_alumno->set_etiqueta('propuesta_nombre_abreviado', 'Propuesta');
			$this->ev_info_alumno->set_etiqueta('plan_nombre', 'Plan');
			$this->ev_info_alumno->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info_alumno;
	}
	
	//-----------------------------------------------------------------------------------
	//---- ev_info_comision ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info_comision()
	{
		if (!isset($this->ev_info_comision)) {
			$this->ev_info_comision = guarani::ev_info_datos();
			$this->ev_info_comision->set_controlador($this);
			$this->ev_info_comision->set_titulo('COMISIÓN');
			
			$this->ev_info_comision->set_columnas_a_mostrar(array('anio_academico', 'periodo_nombre', 'actividades', 'fecha_inscripcion', 'comision', 'turno', 'ubicacion', 'cant_clases'));
			$this->ev_info_comision->set_columnas_fila(array('anio_academico', 'periodo_nombre'), 1);
			$this->ev_info_comision->set_columnas_fila(array('actividades', 'fecha_inscripcion'), 2);
			$this->ev_info_comision->set_columnas_fila(array('comision', 'turno', 'ubicacion'), 3);
			$this->ev_info_comision->set_columnas_fila(array('cant_clases'), 4);
			
			$this->ev_info_comision->set_etiqueta('anio_academico', 'Año');
			$this->ev_info_comision->set_etiqueta('periodo_nombre', 'P. Lectivo');
			$this->ev_info_comision->set_etiqueta('actividades', 'Actividad');
			$this->ev_info_comision->set_etiqueta('fecha_inscripcion', 'Fecha Inscripción');
			$this->ev_info_comision->set_etiqueta('comision', 'Comisión');
			$this->ev_info_comision->set_etiqueta('turno', 'Turno');
			$this->ev_info_comision->set_etiqueta('ubicacion', 'Ubicación');
			$this->ev_info_comision->set_etiqueta('cant_clases', 'Clases');
		}
		return $this->ev_info_comision;
	}
}
?>