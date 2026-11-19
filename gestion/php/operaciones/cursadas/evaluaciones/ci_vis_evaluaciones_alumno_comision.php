<?php
class ci_vis_evaluaciones_alumno_comision extends guarani_pers_ci 
{
	protected $datos;
	protected $ev_info;

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		toba::memoria()->desactivar_reciclado();
		$this->datos = toba::memoria()->get_parametros();
		
		if (!isset($this->datos['comision'])) {
			return;
		}

		if (!isset($this->datos['alumno'])) {
			return;
		}
		
		$this->ev_info()->set_datos($this->get_datos_ev_info());
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function get_datos_ev_info()
	{
		$datos = current(guarani::co('co_comisiones')->get_datos_comision($this->datos['comision']));
		$datos['alumno_nombre'] = guarani::co('co_alumnos')->get_alumno_nombre($this->datos['alumno']);
		return $datos;
	}
	
	function ev_info()
	{
		if (! isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico', 'periodo_lectivo_nombre', 'actividad_desc', 'comision_nombre', 'alumno_nombre'));
			
			$this->ev_info->set_max_columnas_por_fila(2);
			
			$this->ev_info->set_columnas_fila(array('anio_academico', 'periodo_lectivo_nombre'), 0);
			$this->ev_info->set_columnas_fila(array('actividad_desc', 'comision_nombre'), 1);
			$this->ev_info->set_columnas_fila(array('alumno_nombre'), 2);

			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('periodo_lectivo_nombre', 'P. Lectivo');
			$this->ev_info->set_etiqueta('actividad_desc', 'Actividad');
			$this->ev_info->set_etiqueta('comision_nombre', 'Comisión');
			$this->ev_info->set_etiqueta('alumno_nombre', 'Alumno');
		}
		
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_evaluaciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_evaluaciones(toba_ei_cuadro $cuadro)
	{
		if (isset($this->datos['comision']) && isset($this->datos['alumno'])) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_eval_detalle');
			$msg_eof = guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural')));
			$cuadro->set_eof_mensaje($msg_eof);

			$datos = toba::consulta_php('co_evaluaciones')->get_resultados_evaluaciones_alumno_comision($this->datos['alumno'], $this->datos['comision']);
			$cuadro->set_datos($datos);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Javascript -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.evt__cerrar = function()	{
			window.close();
		}
		";
	}

}
?>