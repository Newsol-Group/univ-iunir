<?php
/**
 * 
 *
 * @package 	Requisitos
 * @subpackage 	-
 * 
 */
class ci_nav_excep_req_alumno extends ci_nav_con_preseleccion
{
	protected $s__alumno;
	protected $s__datos_alumno;

	protected $s__excepciones;

	protected $ev_info;

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

	function get_datos_alumno()
	{
		return $this->s__datos_alumno;
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial_preseleccion();
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla Selección -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$this->s__datos_alumno = toba::consulta_php('co_alumnos')->get_datos_basicos_alumno($this->get_preseleccion());
		$this->ev_info()->set_datos($this->s__datos_alumno);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_requisitos_excepciones');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural'))));

		$datos = $this->cn()->get_listado_excep_req_alumno($this->get_preseleccion());
		$cuadro->set_datos($datos);
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro')->get_datos();
		$fecha_hasta = $datos[$fila]['fecha_hasta'];
		$fecha_hoy = guarani_fecha::get_hoy();
		
		if (guarani_fecha::es_menor($fecha_hasta, $fecha_hoy)) {
			$evento->anular();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla Edición -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->s__datos_alumno);
		parent::conf__edicion($pantalla);
	}

	///----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('apellido_y_nombre', 'legajo', 'propuesta_nombre_abreviado', 'plan_nombre', 'ubicacion_nombre'));
			
			$this->ev_info->set_columnas_fila(array('apellido_y_nombre', 'legajo'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre_abreviado', 'plan_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('ubicacion_nombre'), 3);
			
			$this->ev_info->set_max_columnas_por_fila(2);
			
			$this->ev_info->set_etiqueta('apellido_y_nombre', 'Alumno');
			$this->ev_info->set_etiqueta('legajo', 'Legajo');
			
			$this->ev_info->set_etiqueta('propuesta_nombre_abreviado', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
			
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info;
	}
}
?>