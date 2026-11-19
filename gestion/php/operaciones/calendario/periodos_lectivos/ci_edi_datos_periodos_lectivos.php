<?php
class ci_edi_datos_periodos_lectivos extends guarani_pers_ci
{
	//Datos temporales
	protected $datos_princ_temp;
	protected $s__crear_tramo_obligatorio = false;
	//Datos del Período para info
	protected $ev_info;

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__periodos_x_nivel()
	{
		//Seteo los datos al elemento visual de información
		$datos = $this->get_datos_princ_periodo();
		$this->ev_info()->set_datos($this->formatear_fechas($datos));
	}

	function formatear_fechas($datos)
	{
		$formateo = new toba_formateo('html');

		$datos['fecha_inicio'] = $formateo->formato_fecha($datos['fecha_inicio']);
		$datos['fecha_fin'] = $formateo->formato_fecha($datos['fecha_fin']);
		$datos['fecha_inicio_dictado'] = $formateo->formato_fecha($datos['fecha_inicio_dictado']);
		$datos['fecha_fin_dictado'] = $formateo->formato_fecha($datos['fecha_fin_dictado']);

		return $datos;
	}

	function conf__tramos_asistencia()
	{
		//Seteo los datos al elemento visual de información
		$datos = $this->get_datos_princ_periodo();
		$this->ev_info()->set_datos($this->formatear_fechas($datos));
	}

	function debe_crear_tramo()
	{
		if ($this->s__crear_tramo_obligatorio) {
			$this->s__crear_tramo_obligatorio = false;
			return true;
		}
		return $this->s__crear_tramo_obligatorio;
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DATOS PRINCIPALES ---------------------------------------------------
	//-----------------------------------------------------------------------------------
	//---- form_datos_princ -------------------------------------------------------------

	function conf__form_datos_princ(toba_ei_formulario $form)
	{
		if (isset($this->datos_princ_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_princ_temp);
		} else {
			$datos = $this->get_datos_princ_periodo();
			$form->set_datos($datos);
		}

		if (!$this->cn()->entidad_es_nueva()) {

			$periodo = $this->cn()->entidad()->get_datos();
			$comisiones = $this->cn()->entidad()->get_comisiones_periodo($periodo['periodo']);

			if (!empty($comisiones)) {
				$form->set_solo_lectura(array('anio_academico', 'periodo_generico'));
			}
		}
	}

	function evt__form_datos_princ__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos($datos);
			$this->cn()->entidad()->set_datos_per_lectivo($datos);
		} catch (guarani_error $e) {
			$this->datos_princ_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA PERÍODOS POR NIVEL --------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__periodos_x_nivel__salida()
	{
		$this->dep('editor_periodo')->evt__cancelar();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA TRAMOS DE ASISTENCIA ------------------------------------------------
	//-----------------------------------------------------------------------------------
	//---- form_datos_periodo -----------------------------------------------------------

	function conf__form_datos_periodo(toba_ei_formulario $form)
	{
		$datos = $this->get_datos_princ_periodo();
		$form->set_datos($datos);
		$form->set_solo_lectura();
	}

	//---- form_ml_tramos ---------------------------------------------------------------

	function conf__form_ml_tramos(toba_ei_formulario_ml $form_ml)
	{
		$datos = $this->cn()->entidad()->get_datos_per_lectivo_tramos();
		if (empty($datos)) {
			$this->s__crear_tramo_obligatorio = true;
		} else {
			foreach ($datos as $key => $dato) {
				if (!empty($dato['id_tramo'])) {
					$datos[$key]['tramo_en_uso'] = toba::consulta_php('co_periodos_lectivos')->tramo_en_uso($dato['id_tramo']);
				} else {
					$datos[$key]['tramo_en_uso'] = 0;
				}
			}
		}
		$form_ml->set_datos($datos);
	}

	function evt__form_ml_tramos__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_per_lectivo_tramos($datos);
		} catch (guarani_error $e) {
			throw new guarani_error($e->getMessage());
		}
	}

	//---- Auxiliares -------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_style('width: 50%;');
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico', 'nombre', 'fecha_inicio', 'fecha_fin', 'fecha_inicio_dictado', 'fecha_fin_dictado'));

			$this->ev_info->set_columnas_fila(array('anio_academico'));
			$this->ev_info->set_columnas_fila(array('nombre'));
			$this->ev_info->set_columnas_fila(array('fecha_inicio', 'fecha_fin'));
			$this->ev_info->set_columnas_fila(array('fecha_inicio_dictado', 'fecha_fin_dictado'));

			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('nombre', 'Nombre');
			$this->ev_info->set_etiqueta('fecha_inicio', 'Inicio Período');
			$this->ev_info->set_etiqueta('fecha_fin', 'Fin Período');
			$this->ev_info->set_etiqueta('fecha_inicio_dictado', 'Inicio Dictado');
			$this->ev_info->set_etiqueta('fecha_fin_dictado', 'Fin Dictado');
		}
		return $this->ev_info;
	}

	function get_datos_per_lectivo()
	{
		return $this->cn()->entidad()->get_datos_per_lectivo();
	}

	function get_datos_princ_periodo()
	{
		$datos_periodo = $this->cn()->entidad()->get_datos();
		$datos_per_lec = $this->cn()->entidad()->get_datos_per_lectivo();

		$datos_periodo['fecha_inicio_dictado'] = $datos_per_lec['fecha_inicio_dictado'] ?? null;
		$datos_periodo['fecha_fin_dictado'] = $datos_per_lec['fecha_fin_dictado'] ?? null;
		$datos_periodo['fecha_tope_movimientos'] = $datos_per_lec['fecha_tope_movimientos'] ?? null;
		$datos_periodo['fecha_inactivacion'] = $datos_per_lec['fecha_inactivacion'] ?? null;
		$datos_periodo['fecha_publicacion_comisiones'] = $datos_per_lec['fecha_publicacion_comisiones'] ?? null;
		$datos_periodo['fecha_publicacion_comisiones_docente'] = $datos_per_lec['fecha_publicacion_comisiones_docente'] ?? null;

		return $datos_periodo;
	}

}