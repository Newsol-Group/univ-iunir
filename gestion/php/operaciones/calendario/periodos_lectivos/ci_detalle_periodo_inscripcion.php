<?php
/**
 * 
 *
 * @package 	Calendario
 * @subpackage 	Períodos Lectivos
 *
 */
class ci_detalle_periodo_inscripcion extends ci_impresion_popup
{
	protected $ev_info_per_lectivo;
	protected $ev_info_per_inscripcion;
	protected $s__etiqueta_entidades_subtipos = null;
	protected $s__datos_periodo_lectivo;
	protected $s__datos_periodo_inscripcion;

	function ini__operacion()
	{
		$periodo_insc = toba::memoria()->get_parametro('periodo_insc');
		$periodo_inscripcion = toba::memoria()->get_parametro('periodo_inscripcion');
		$periodo = toba::memoria()->get_parametro('periodo');

		// Datos del Período de Inscripción
		$filtro = "sga_periodos_inscripcion_fechas.periodo_insc = " . toba::db()->quote($periodo_insc);
		$datos_periodo_inscripcion = current(toba::consulta_php('co_periodos_de_inscripcion')->get_periodos_inscripcion_por_periodo($periodo, $filtro));

		// Modalidades
		$modalidades = toba::consulta_php('co_periodos_de_inscripcion')->get_modalidades_periodo_inscripcion($periodo_inscripcion);
		if (!empty($modalidades)) {
			$datos_periodo_inscripcion['lista_modalidades'] = guarani_texto::armar_lista(guarani_arreglos::aplanar_matriz_sin_nulos($modalidades, 'nombre'), ' - ');
		}

		// Ubicaciones
		$ubicaciones = toba::consulta_php('co_periodos_de_inscripcion')->get_ubicaciones_periodo_inscripcion($periodo_inscripcion);
		if (!empty($ubicaciones)) {
			$datos_periodo_inscripcion['lista_ubicaciones'] = guarani_texto::armar_lista(guarani_arreglos::aplanar_matriz_sin_nulos($ubicaciones, 'nombre'), ' - ');
		}

		// Subtipos / Entidades
		if ($datos_periodo_inscripcion['tipo'] == g3entidad::nivel_aplicacion_tipo_subtipo) {
			$subtipos = toba::consulta_php('co_periodos_de_inscripcion')->get_subtipos($periodo_insc);
			$lista_subtipos_entidades = guarani_texto::armar_lista(guarani_arreglos::aplanar_matriz_sin_nulos($subtipos, 'nombre'), ' - ');
		} elseif ($datos_periodo_inscripcion['tipo'] == g3entidad::nivel_aplicacion_tipo_entidad) {
			$entidades = toba::consulta_php('co_periodos_de_inscripcion')->get_entidades($periodo_insc);
			$filtro = guarani_sql::armar_condicion_in($entidades, guarani::catalogo_g3entidades()->get_tabla_tipo($datos_periodo_inscripcion['entidad_tipo']), 'entidad');

			$entidades = guarani::co('co_g3entidades')->get_listado_tipo_detallado($datos_periodo_inscripcion['entidad_tipo'], $filtro);
			$lista_subtipos_entidades = guarani_texto::armar_lista(guarani_arreglos::aplanar_matriz_sin_nulos($entidades, 'entidad_descripcion'), ' - ');
		} else {
			$lista_subtipos_entidades = ' ';
		}
		$datos_periodo_inscripcion['lista_subtipos_entidades'] = $lista_subtipos_entidades;

		$filtro = "sga_periodos_lectivos.periodo = " . toba::db()->quote($periodo);

		$this->s__datos_periodo_lectivo = current(toba::consulta_php('co_periodos_lectivos')->get_listado($filtro));
		$this->s__datos_periodo_inscripcion = $datos_periodo_inscripcion;
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DETALLE -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_detalle_periodo(toba_ei_pantalla $pantalla)
	{
		$etiquetas = guarani_etiquetas::get_tabla(g3entidad::get_tabla_tipo($this->s__datos_periodo_inscripcion['entidad_tipo']));
		switch ($this->s__datos_periodo_inscripcion['tipo']) {
			case g3entidad::nivel_aplicacion_tipo_entidad:
				$this->s__etiqueta_entidades_subtipos = ($this->s__datos_periodo_inscripcion['entidad_tipo'] == g3entidad::tipo_institucion) ? null : $etiquetas->get_plural();
				break;
			case g3entidad::nivel_aplicacion_tipo_subtipo:
				$this->s__etiqueta_entidades_subtipos = 'Tipos de ' . $etiquetas->get_plural();
		}

		$this->ev_info_per_lectivo()->set_datos($this->s__datos_periodo_lectivo);
		$this->ev_info_per_inscripcion()->set_datos($this->s__datos_periodo_inscripcion);
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info_per_lectivo()
	{
		if (!isset($this->ev_info_per_lectivo)) {
			$this->ev_info_per_lectivo = guarani::ev_info_datos();
			$this->ev_info_per_lectivo->set_controlador($this);
			$this->ev_info_per_lectivo->set_columnas_a_mostrar(array('periodo_anio_academico', 'periodo_nombre', 'cant_comisiones', 'periodo_fecha_inicio_visual', 'periodo_fecha_fin_visual', 'fecha_inicio_dictado_visual', 'fecha_fin_dictado_visual'));

			$this->ev_info_per_lectivo->set_columnas_fila(array('periodo_anio_academico', 'periodo_nombre', 'cant_comisiones'), 1);
			$this->ev_info_per_lectivo->set_columnas_fila(array('periodo_fecha_inicio_visual', 'periodo_fecha_fin_visual'), 2);
			$this->ev_info_per_lectivo->set_columnas_fila(array('fecha_inicio_dictado_visual', 'fecha_fin_dictado_visual'), 3);

			$this->ev_info_per_lectivo->set_etiqueta('periodo_anio_academico', 'Año Académico');
			$this->ev_info_per_lectivo->set_etiqueta('periodo_nombre', 'Período Lectivo');
			$this->ev_info_per_lectivo->set_etiqueta('cant_comisiones', 'Comisiones');

			$this->ev_info_per_lectivo->set_etiqueta('periodo_fecha_inicio_visual', 'Inicio');
			$this->ev_info_per_lectivo->set_etiqueta('periodo_fecha_fin_visual', 'Fin');

			$this->ev_info_per_lectivo->set_etiqueta('fecha_inicio_dictado_visual', 'Inicio Dictado');
			$this->ev_info_per_lectivo->set_etiqueta('fecha_fin_dictado_visual', 'Fin Dictado');
		}
		return $this->ev_info_per_lectivo;
	}

	function ev_info_per_inscripcion()
	{
		if (!isset($this->ev_info_per_inscripcion)) {
			$this->ev_info_per_inscripcion = guarani::ev_info_datos();
			$this->ev_info_per_inscripcion->set_controlador($this);
			$columnas = array('nombre', 'fecha_inicio_visual', 'fecha_fin_visual', 'fecha_tope_bajas_visual', 'lista_modalidades', 'lista_ubicaciones', 'nivel_aplicacion');

			if (isset($this->s__etiqueta_entidades_subtipos)) {
				array_push($columnas, 'lista_subtipos_entidades');
			}

			$this->ev_info_per_inscripcion->set_columnas_a_mostrar($columnas);

			$this->ev_info_per_inscripcion->set_etiqueta('nombre', 'Nombre');
			$this->ev_info_per_inscripcion->set_etiqueta('fecha_inicio_visual', 'Fecha Inicio');
			$this->ev_info_per_inscripcion->set_etiqueta('fecha_fin_visual', 'Fecha Fin');
			$this->ev_info_per_inscripcion->set_etiqueta('fecha_tope_bajas_visual', 'Fecha Tope de Bajas');
			$this->ev_info_per_inscripcion->set_etiqueta('lista_modalidades', 'Modalidad');
			$this->ev_info_per_inscripcion->set_etiqueta('lista_ubicaciones', 'Ubicación');
			$this->ev_info_per_inscripcion->set_etiqueta('nivel_aplicacion', 'Nivel de Aplicación');

			if (isset($this->s__etiqueta_entidades_subtipos)) {
				$this->ev_info_per_inscripcion->set_etiqueta('lista_subtipos_entidades', $this->s__etiqueta_entidades_subtipos);
			}
		}
		return $this->ev_info_per_inscripcion;
	}

	//-----------------------------------------------------------------------------------
	//---- IMPRESION JASPER -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function vista_jasperreports(toba_vista_jasperreports $report)
	{
		$path = guarani::get_path_reporte_jasper('ReportePeriodosLectivos.jasper');
		$report->set_path_reporte($path);

		$path_logo = guarani::get_path_logo_impresion();
		$report->set_parametro('logo', 'S', $path_logo);

		if (isset($this->s__etiqueta_entidades_subtipos)) {
			// Se agrega como parámetro adicional para el reporte la etiqueta de los subtipos de entidades
			$report->set_parametro('subtipo_entidad_nombre', 'S', $this->s__etiqueta_entidades_subtipos);
		}

		$ev1_xml = '';
		$ev2_xml = '';

		if ($this->ev_info_per_lectivo()->get_datos()) {
			$ev1_xml = $this->ev_info_per_lectivo()->vista_xml_jasper();
		}

		if ($this->ev_info_per_inscripcion()->get_datos()) {
			$ev1_xml = str_replace('</fila></datos></tabla>', '', $ev1_xml);
			$ev2_xml = $this->ev_info_per_inscripcion()->vista_xml_jasper();
		}
		if ($ev1_xml) {
			$ev2_xml = str_replace('<tabla><datos><fila>', '', $ev2_xml);
		}
		$xml = $ev1_xml . $ev2_xml;
		$report->set_xml(utf8_e_seguro($xml));
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
?>