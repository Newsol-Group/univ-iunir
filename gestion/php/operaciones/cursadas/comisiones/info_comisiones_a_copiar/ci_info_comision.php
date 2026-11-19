<?php
class ci_info_comision extends guarani_pers_ci
{
	protected $info_comision;
	protected $ev_info;
	protected $ev_datos_generales;

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		toba::memoria()->desactivar_reciclado();
		$this->info_comision = toba::memoria()->get_parametros();

		if (!isset($this->info_comision['comision'])) {
			return;
		}
		
		$this->ev_info()->set_datos($this->info_comision);
		$datos_generales = toba::consulta_php('co_comisiones')->get_datos_comision($this->info_comision['comision']);
		$this->ev_datos_generales()->set_datos(current($datos_generales));
		
		if (isset($this->info_comision['titulo'])) {
			$this->ev_info()->set_titulo($this->get_titulo());
			$this->set_titulo($this->info_comision['titulo']);
		}
	}

	function ev_info()
	{
		if (! isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('comision_nombre', 'ubicacion_nombre', 'descr', 'modalidades', 'anio_academico', 'periodo_lectivo_nombre'));
			
			$this->ev_info->set_max_columnas_por_fila(3);
			
			$this->ev_info->set_columnas_fila(array('anio_academico', 'periodo_lectivo_nombre', 'ubicacion_nombre'), 1);
			$this->ev_info->set_columnas_fila(array('desc', 'comision_nombre'), 2);
			
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('periodo_lectivo_nombre', 'Período Lectivo');
			$this->ev_info->set_etiqueta('comision_nombre', 'Comisión');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
			$this->ev_info->set_etiqueta('descr', 'Actividad');
			
			$this->ev_info->set_td_etiqueta_width('95px');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- ev_datos_generales -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_datos_generales()
	{
		if (! isset($this->ev_datos_generales)) {
			$this->ev_datos_generales = guarani::ev_info_datos();
			$this->ev_datos_generales->set_columnas_a_mostrar(array('turno_nombre', 'catedra_nombre', 'division_nombre', 'cupo', 'letra_desde', 'letra_hasta', 'modalidad_nombre', 'inscripcion_habilitada'));
			
			$this->ev_datos_generales->set_max_columnas_por_fila(4);
			
			$this->ev_datos_generales->set_columnas_fila(array('turno_nombre', 'catedra_nombre', 'cupo'), 1);
			$this->ev_datos_generales->set_columnas_fila(array('letra_desde', 'letra_hasta', 'modalidad_nombre'), 2);
			$this->ev_datos_generales->set_columnas_fila(array('inscripcion_habilitada', 'division_nombre'), 3);
			
			$this->ev_datos_generales->set_etiqueta('turno_nombre', 'Turno');
			$this->ev_datos_generales->set_etiqueta('catedra_nombre', 'Cátedra');
			$this->ev_datos_generales->set_etiqueta('cupo', 'Cupo');
			$this->ev_datos_generales->set_etiqueta('letra_desde', 'Letra desde');
			$this->ev_datos_generales->set_etiqueta('letra_hasta', 'Letra hasta');
			$this->ev_datos_generales->set_etiqueta('modalidad_nombre', 'Modalidad');
			$this->ev_datos_generales->set_etiqueta('inscripcion_habilitada', 'Habilitada');
			$this->ev_datos_generales->set_etiqueta('division_nombre', 'División');
			
			$this->ev_datos_generales->set_td_etiqueta_width('75px');
		}
		return $this->ev_datos_generales;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_instancias ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_instancias(toba_ei_cuadro $cuadro)
	{
		if (isset($this->info_comision['comision'])) {
			$instancias = toba::consulta_php('co_comisiones')->get_instancias_comision($this->info_comision['comision']);
			$cuadro->set_datos($instancias);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_docentes --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_docentes(toba_ei_cuadro $cuadro)
	{
		if (isset($this->info_comision['comision'])) {
			$docentes = toba::consulta_php('co_comisiones')->get_docentes_comision($this->info_comision['comision']);
			$cuadro->set_datos($docentes);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_horarios --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_horarios(toba_ei_cuadro $cuadro)
	{
		if (isset($this->info_comision['comision'])) {
			$horarios = toba::consulta_php('co_bandas_horarias')->get_listado_asignaciones_comision($this->info_comision['comision']);
			$cuadro->set_datos($horarios);
		}
	}

	//-----------------------------------------------------------------------------------
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