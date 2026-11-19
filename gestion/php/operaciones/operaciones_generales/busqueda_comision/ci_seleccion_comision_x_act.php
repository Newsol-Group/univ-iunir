<?php
/**
 * Popup para selección de Comisiones que recibe como parámetros Ubicación, Actividad y Plan Versión para filtrar 
 * las mismas.
 *
 * @package Operaciones Generales
 * @subpackage Busqueda de Comisiones 
 */
class ci_seleccion_comision_x_act extends guarani_pers_ci
{
	protected $info_comisiones;
	protected $ev_info;
	protected $indx_msg_eof = '800_eof_cuadro';

	
	function get_datos()
	{
		/**
		 * Se recuperan las comisiones correspondientes a un Elemento y Ubicación dados como parámetro, que estén 
		 * asociadas a la Propuesta del Plan dado, cuyos Períodos Lectivos tengan Fecha de Inicio de Dictado
		 * mayor o igual a la actual y el Año Académico de los mismos sea el del Período de Inscripción dado.
		 */
		$datos = array();
		if (isset($this->info_comisiones['plan']) && isset($this->info_comisiones['actividad']) && isset($this->info_comisiones['ubicacion']) && isset($this->info_comisiones['anio_academico'])) {
			$ubicacion = toba::db()->quote($this->info_comisiones['ubicacion']);
			$filtro = "sga_comisiones.ubicacion = $ubicacion AND COALESCE(sga_comisiones_excep_perlect.fecha_inicio_dictado, sga_periodos_lectivos.fecha_inicio_dictado) >= CURRENT_DATE";
			$datos = toba::consulta_php('co_comisiones')->get_comisiones_actividad_plan_anio_academico($this->info_comisiones['actividad'], $this->info_comisiones['plan'], $this->info_comisiones['anio_academico'], $filtro);
		}
		return $datos; 
	}
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		toba::memoria()->desactivar_reciclado();
		$this->info_comisiones = toba::memoria()->get_parametros();
		if (isset($this->info_comisiones['plan']) && isset($this->info_comisiones['actividad'])) {
			$datos_plan = toba::consulta_php('co_planes')->get_datos_plan($this->info_comisiones['plan']);
			$datos_elemento = toba::consulta_php('co_elementos')->get_actividad_nombre($this->info_comisiones['actividad']);
			$datos_ev = array_merge($datos_plan, $datos_elemento);
			$this->ev_info()->set_datos($datos_ev);
		}
	}

	function ev_info()
	{
		if (! isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('codigo_y_nombre', 'propuesta_nombre', 'nombre'));
			$this->ev_info->set_etiqueta('codigo_y_nombre', 'Actividad');
			$this->ev_info->set_etiqueta('propuesta_nombre', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_comisiones(toba_ei_cuadro $cuadro)
	{
		$cuadro->desactivar_modo_clave_segura();
		$etiqueta = guarani_etiquetas::get_tabla('sga_comisiones');
		$etiqueta = $etiqueta->get_enf('plural');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta)));
		$cuadro->set_datos($this->get_datos());
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		echo "
		{$this->objeto_js}.evt__cancelar = function()
		{
			window.close();
		}
		";
	}
}