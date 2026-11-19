<?php
class ci_edi_rangos_inscripcion_por_coeficientes extends guarani_pers_ci
{
	protected $ev_info;
	protected $periodo_inscripcion;
	protected $propuesta;
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		$this->periodo_inscripcion = toba::memoria()->get_dato('periodo_inscripcion');
		$this->propuesta = toba::memoria()->get_dato('propuesta');
		/*
		ei_arbol([
			toba::memoria()->get_dato('a'),
			toba::memoria()->get_dato('b')
		]);*/
	}
	
	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		$datos_periodo = $this->controlador()->get_datos_periodo();
		$this->ev_info()->set_datos($datos_periodo);
		
		toba::memoria()->set_dato('periodo_inscripcion', $datos_periodo['periodo_inscripcion']);
		toba::memoria()->set_dato('propuesta', $datos_periodo['propuesta']);
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
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(guarani_pers_ei_cuadro $cuadro)
	{
		if (isset($this->periodo_inscripcion) && (isset($this->propuesta))) {
			$datos = toba::consulta_php('co_periodos_de_inscripcion')->get_propuestas_con_periodos_inscripcion_por_rangos($this->periodo_inscripcion, $this->propuesta);
			$prueba = array( 
				array(
					'propuesta' => 1001,
					'nombre' => 'Ing. en sistemas',
					'codigo' => '41',
					'cant_rangos_coeficiente' => 4 
				),
				array(
					'propuesta' => 1002,
					'nombre' => 'Ing. civil',
					'codigo' => '42',
					'cant_rangos_coeficiente' => 3 
				),
				array(
					'propuesta' => 1003,
					'nombre' => 'Ing. mecánica',
					'codigo' => '43',
					'cant_rangos_coeficiente' => 2 
				)
			);
			$cuadro->set_datos($prueba);
		}
	}

	function evt__cuadro__copiar($seleccion)
	{
		toba::memoria()->set_dato('a', 'prueba A');
		toba::memoria()->set_dato('b', 'prueba B');
		$this->set_pantalla('pant_inicial');
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_ml_rangos ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__form_ml_rangos__copiar_rangos()
	{
		$this->set_pantalla('pantalla_copiar_rangos');
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
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