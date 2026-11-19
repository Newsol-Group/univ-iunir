<?php
class ci_edi_rangos_inscripcion_por_coeficientes extends guarani_pers_ci
{
	protected $ev_info;
	protected $s__periodo_insc;
	protected $s__propuesta;
	protected $datos_coeficientes_temp;

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		$datos_periodo = $this->controlador()->get_seleccion();
		$this->s__periodo_insc = $datos_periodo['periodo_insc'];
		$this->s__propuesta = $datos_periodo['propuesta'];
	}

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		$datos_periodo = $this->controlador()->get_datos_periodo();

		$fecha_inicio = guarani_fecha::convertir_fecha_formato_frontend(substr($datos_periodo['fecha_inicio'], 0, 10));
		$hora_inicio = substr($datos_periodo['fecha_inicio'], 11, 8);
		$fecha_fin = guarani_fecha::convertir_fecha_formato_frontend(substr($datos_periodo['fecha_fin'], 0, 10));
		$hora_fin = substr($datos_periodo['fecha_fin'], 11, 8);

		$datos_periodo['fecha_inicio'] = $fecha_inicio . ' ' . $hora_inicio;
		$datos_periodo['fecha_fin'] = $fecha_fin . ' ' . $hora_fin;

		$this->ev_info()->set_datos($datos_periodo);

		$this->controlador()->evento('cancelar')->mostrar();
		$this->controlador()->evento('guardar')->mostrar();
	}

	function conf__pantalla_copiar_rangos(toba_ei_pantalla $pantalla)
	{
		$this->controlador()->evento('cancelar')->ocultar();
		$this->controlador()->evento('guardar')->ocultar();
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico', 'periodo_nombre', 'propuesta_nombre', 'ubicaciones', 'fecha_inicio', 'fecha_fin'));

			$this->ev_info->set_columnas_fila(array('anio_academico', 'periodo_nombre'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre', 'fecha_inicio', 'fecha_fin'), 2);
			$this->ev_info->set_columnas_fila(array('ubicaciones'), 3);

			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('periodo_nombre', 'Período Lectivo');
			$this->ev_info->set_etiqueta('propuesta_nombre', 'Propuesta');
			$this->ev_info->set_etiqueta('fecha_inicio', 'Fecha de Inicio de Inscripción');
			$this->ev_info->set_etiqueta('fecha_fin', 'Fecha de Fin de Inscripción');
			$this->ev_info->set_etiqueta('ubicaciones', 'Ubicaciones');
		}

		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- form_ml_rangos ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_rangos(form_ml_rangos $form_ml)
	{
		if (isset($this->datos_coeficientes_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_coeficientes_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_coeficientes();
		}
		foreach ($datos as $clave => $valor) {
			if (isset($valor['fecha_inicio'])) {
				$datos[$clave]['fecha_inicio'] = guarani_fecha::separar_fecha_hora($valor['fecha_inicio']);
			}
			if (isset($valor['fecha_fin'])) {
				$datos[$clave]['fecha_fin'] = guarani_fecha::separar_fecha_hora($valor['fecha_fin']);
			}
			if (isset($valor['fecha_tope_bajas'])) {
				$datos[$clave]['fecha_tope_bajas'] = guarani_fecha::separar_fecha_hora($valor['fecha_tope_bajas']);
			}
		}
		$form_ml->set_datos($datos);
	}

	function evt__form_ml_rangos__modificacion($datos)
	{
		foreach ($datos as $clave => $valor) {
			if ($valor['apex_ei_analisis_fila'] != 'B') {
				$datos[$clave]['periodo_insc'] = $this->s__periodo_insc;
				$datos[$clave]['propuesta'] = $this->s__propuesta;
				$datos[$clave]['fecha_inicio'] = $valor['fecha_inicio'][0] . ' ' . $valor['fecha_inicio'][1] . ':00-03';
				$datos[$clave]['fecha_fin'] = $valor['fecha_fin'][0] . ' ' . $valor['fecha_fin'][1] . ':00-03';
				$datos[$clave]['fecha_tope_bajas'] = $valor['fecha_tope_bajas'][0] . ' ' . $valor['fecha_tope_bajas'][1] . ':00-03';
			}
		}
		try {
			$this->cn()->entidad()->set_datos_coeficientes($datos);
		} catch (guarani_error $e) {
			$this->datos_coeficientes_temp = $datos;
			throw $e;
		}
	}

	function evt__form_ml_rangos__copiar_rangos()
	{
		$this->set_pantalla('pantalla_copiar_rangos');
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(guarani_pers_ei_cuadro $cuadro)
	{
		$cuadro->set_datos(toba::consulta_php('co_periodos_de_inscripcion')->get_propuestas_con_periodos_inscripcion_por_rangos($this->s__periodo_insc, $this->s__propuesta));
	}

	function evt__cuadro__copiar($seleccion)
	{
		$datos = toba::consulta_php('co_periodos_de_inscripcion')->get_rangos_de_coeficientes_por_propuestas($this->s__periodo_insc, $seleccion['propuesta']);
		foreach ($datos as $clave => $valor) {
			$datos[$clave]['propuesta'] = $this->s__propuesta;
		}
		$this->cn()->entidad()->agregar_datos_coeficientes($datos);
		$this->set_pantalla('pant_inicial');
	}

	function evt__cuadro__volver()
	{
		$this->set_pantalla('pant_inicial');
	}
}
?>