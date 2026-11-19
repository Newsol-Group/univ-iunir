<?php
/**
 *
 *
 * @package Asistencias
 * @subpackage -
 */
class ci_nav_ausencias_examen extends ci_nav_con_preseleccion
{
	protected $s__alumno;
	protected $s__datos_alumno;

	protected $s__datos_acta;

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

	function set_datos_acta($seleccion)
	{
		$this->s__datos_acta = $seleccion;
	}

	function get_datos_acta()
	{
		return $this->s__datos_acta;
	}
	
	function get_datos_alumno()
	{
		return $this->s__datos_alumno;
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla Selección -----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$this->s__datos_alumno = toba::consulta_php('co_alumnos')->get_datos_basicos_alumno($this->get_preseleccion());
		$this->ev_info()->set_datos($this->s__datos_alumno);
	}
	
	//---- cuadro -----------------------------------------------------------------------
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_actas');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural'))));
		$datos = $this->cn()->get_listado($this->get_preseleccion());
		$cuadro->set_datos($datos);
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$this->set_datos_acta($seleccion);
		$array_ids = array('id_acta' => current($seleccion), 'alumno' => $this->get_preseleccion());
		$this->cn()->cargar($array_ids);
		$this->set_pantalla('edicion');
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla Edición -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$datos = array_merge($this->s__datos_alumno, $this->s__datos_acta);
		$this->ev_info()->set_datos($datos);
		parent::conf__edicion($pantalla);
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			
			$this->ev_info->set_columnas_a_mostrar(array('apellido_y_nombre', 'legajo', 'propuesta_nombre_abreviado', 'plan_nombre', 'ubicacion_nombre', 'nro_acta', 'anio_academico', 'turno_nombre', 'mesa_nombre', 'llamado_nombre', 'fecha_formateada', 'actividad_nombre'));
			$this->ev_info->set_columnas_fila(array('apellido_y_nombre', 'legajo'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre_abreviado', 'plan_nombre', 'ubicacion_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('nro_acta', 'anio_academico', 'turno_nombre'), 3);
			$this->ev_info->set_columnas_fila(array('mesa_nombre', 'llamado_nombre', 'fecha_formateada'), 4);
			$this->ev_info->set_columnas_fila(array('actividad_nombre'), 5);
			
			$this->ev_info->set_etiqueta('apellido_y_nombre', 'Alumno');
			$this->ev_info->set_etiqueta('legajo', 'Legajo');
			$this->ev_info->set_etiqueta('propuesta_nombre_abreviado', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
			$this->ev_info->set_etiqueta('nro_acta', 'Acta');
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('turno_nombre', 'Turno');
			$this->ev_info->set_etiqueta('mesa_nombre', 'Mesa');
			$this->ev_info->set_etiqueta('llamado_nombre', 'Llamado');
			$this->ev_info->set_etiqueta('fecha_formateada', 'Fecha');
			$this->ev_info->set_etiqueta('actividad_nombre', 'Actividad');
		}
		return $this->ev_info;
	}
}
?>