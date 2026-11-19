<?php
/**
 *
 *
 * @package Calendario
 * @subpackage Turnos de Examen
 */
class ci_rep_turnos_examen extends ci_rep_sin_listado_inicial
{
	protected $s__llamado_seleccionado;
	protected $s__datos_llamado;

	protected $ev_info;
	protected $popup = false;

	function get_tabla_principal()
	{
		return 'sga_turnos_examen';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_turnos_de_examen')->get_turnos_llamados($filtro);
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA LLAMADOS ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	//---- cuadro -----------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__llamado_seleccionado = $seleccion['periodo'];
		$this->set_pantalla('periodos_inscripcion');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA PERÍODOS DE INSCRIPCIÓN ---------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__periodos_inscripcion()
	{
		$filtro = "sga_llamados_turno.periodo = $this->s__llamado_seleccionado";
		$this->s__datos_llamado = current(toba::consulta_php('co_turnos_de_examen')->get_turnos_llamados($filtro));

		$this->ev_info()->set_datos($this->s__datos_llamado);
	}

	//---- cuadro_detalle ---------------------------------------------------------------

	function conf__cuadro_detalle(toba_ei_cuadro $cuadro)
	{
		$datos = toba::consulta_php('co_periodos_de_inscripcion')->get_periodos_inscripcion_por_periodo($this->s__llamado_seleccionado);
		$cuadro->set_datos($datos);

		$etiqueta = guarani_etiquetas::get_tabla('sga_periodos_inscripcion');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural'))));
	}

	function evt__cuadro_detalle__informacion($seleccion)
	{
		// Datos para el popup detalle del Período de Inscripción.
		$filtro = "sga_periodos_inscripcion_fechas.periodo_insc = {$seleccion['periodo_insc']}";
		$datos_periodo_inscripcion = current(toba::consulta_php('co_periodos_de_inscripcion')->get_periodos_inscripcion_por_periodo($this->s__llamado_seleccionado, $filtro));
		
		// Modalidades.
		$modalidades = toba::consulta_php('co_periodos_de_inscripcion')->get_modalidades_periodo_inscripcion($seleccion['periodo_inscripcion']);
		if (!empty($modalidades)) {
			$datos_periodo_inscripcion['lista_modalidades'] = guarani_texto::armar_lista(guarani_arreglos::aplanar_matriz_sin_nulos($modalidades, 'nombre'), ' - ');
		}

		// Ubicaciones.
		$ubicaciones = toba::consulta_php('co_periodos_de_inscripcion')->get_ubicaciones_periodo_inscripcion($seleccion['periodo_inscripcion']);
		if (!empty($ubicaciones)) {
			$datos_periodo_inscripcion['lista_ubicaciones'] = guarani_texto::armar_lista(guarani_arreglos::aplanar_matriz_sin_nulos($ubicaciones, 'nombre'), ' - ');
		}

		// Subtipos/Entidades.
		if ($datos_periodo_inscripcion['tipo'] == g3entidad::nivel_aplicacion_tipo_subtipo) {
			$subtipos = toba::consulta_php('co_periodos_de_inscripcion')->get_subtipos($seleccion['periodo_insc']);
			$lista_subtipos_entidades = guarani_texto::armar_lista(guarani_arreglos::aplanar_matriz_sin_nulos($subtipos, 'nombre'), ' - ');
		} elseif ($datos_periodo_inscripcion['tipo'] == g3entidad::nivel_aplicacion_tipo_entidad) {
			$entidades = toba::consulta_php('co_periodos_de_inscripcion')->get_entidades($seleccion['periodo_insc']);
			$filtro = guarani_sql::armar_condicion_in($entidades, guarani::catalogo_g3entidades()->get_tabla_tipo($datos_periodo_inscripcion['entidad_tipo']), 'entidad');

			$entidades = guarani::co('co_g3entidades')->get_listado_tipo_detallado($datos_periodo_inscripcion['entidad_tipo'], $filtro);
			$lista_subtipos_entidades = guarani_texto::armar_lista(guarani_arreglos::aplanar_matriz_sin_nulos($entidades, 'entidad_descripcion'), ' - ');
		} else {
			$lista_subtipos_entidades = ' ';
		}
		$datos_periodo_inscripcion['lista_subtipos_entidades'] = $lista_subtipos_entidades;

		$datos = array();
		$datos['llamado'] = $this->s__datos_llamado;
		$datos['periodo_inscripcion'] = $datos_periodo_inscripcion;

		// Se almacenan los datos en memoria para recuperarlos desde el popup detalle del Período de Inscripción.
		guarani_datos_impresion::instancia()->set_datos($datos);
		guarani_datos_impresion::instancia()->guardar_datos_imp();
		$this->popup = true;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__volver()
	{
		unset($this->s__llamado_seleccionado);
		unset($this->s__datos_llamado);
		$this->set_pantalla('llamados');
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico', 'turno_nombre', 'turno_cant_mesas', 'turno_fecha_inicio_visual', 'turno_fecha_fin_visual', 'llamado_nombre', 'llamado_fecha_inicio_visual', 'llamado_fecha_fin_visual'));
			
			$this->ev_info->set_columnas_fila(array('anio_academico', 'turno_nombre', 'turno_cant_mesas'), 1);
			$this->ev_info->set_columnas_fila(array('turno_fecha_inicio_visual', 'turno_fecha_fin_visual'), 2);
			$this->ev_info->set_columnas_fila(array('llamado_nombre', 'llamado_fecha_inicio_visual', 'llamado_fecha_fin_visual'), 3);
			
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('turno_nombre', 'Turno');
			$this->ev_info->set_etiqueta('turno_cant_mesas', 'Mesas');
			
			$this->ev_info->set_etiqueta('turno_fecha_inicio_visual', 'Inicio Turno');
			$this->ev_info->set_etiqueta('turno_fecha_fin_visual', 'Fin Turno');
			
			$this->ev_info->set_etiqueta('llamado_nombre', 'Llamado');
			$this->ev_info->set_etiqueta('llamado_fecha_inicio_visual', 'Inicio Llamado');
			$this->ev_info->set_etiqueta('llamado_fecha_fin_visual', 'Fin Llamado');
		}
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		if ($this->popup) {
			$this->popup = false;
			$url = toba::vinculador()->get_url(toba::proyecto()->get_id(), '38000117', array(), array('celda_memoria' => 'celda_impresion'));
			echo "
				abrir_popup('impresion', '$url', {'width':'700','height':'500'});
			";
		}
	}
	
}
?>