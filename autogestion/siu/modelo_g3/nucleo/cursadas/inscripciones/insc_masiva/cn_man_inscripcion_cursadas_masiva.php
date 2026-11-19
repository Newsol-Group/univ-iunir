<?php
class cn_man_inscripcion_cursadas_masiva extends cn_man_inscripcion_cursadas
{
	protected $s__proceso;
	
	protected $s__propuesta;
	protected $s__plan;
	protected $s__plan_version;
	protected $s__ubicacion;
	
	// Descripciones de selecciones para el ev_info
	protected $s__selecciones_descr;
	
	protected $s__alumnos = array();
	
	function cargar_atributos_parametros($propuesta = null, $plan = null)
	{
		$operacion = guarani::get_id_operacion();
		
		$this->s__atrib_propuestas = array($this->s__propuesta);
		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($this->s__atrib_propuestas);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_ra, $ras);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $this->s__atrib_propuestas);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_plan, array($this->s__plan));
	}
	
	function resetear()
	{
		parent::resetear();
		unset($this->s__propuesta);
		unset($this->s__plan);
		unset($this->s__plan_version);
		unset($this->s__alumnos);
		unset($this->s__ubicacion);
		$this->resetear_alumnos_seleccionados();
	}
	
	function get_selecciones_descr()
	{
		return $this->s__selecciones_descr;
	}
	
	//--------------------------------------------------------------------------
	//---- Proceso -------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function get_estado_proceso()
	{
		return $this->s__proceso->get_estado();
	}

	function get_resultado_proceso($reporte)
	{
		return $this->s__proceso->get_resultado($reporte);
	}
	
	function abortar_proceso()
	{
		$this->s__proceso->detener_procesamiento();
	}
	
	function ejecutar_proceso()
	{
		$parametros = array();
		$parametros['propuesta'] = $this->s__propuesta;
		$parametros['plan'] = $this->s__plan;
		$parametros['plan_version'] = $this->s__plan_version;
		$parametros['comision'] = current($this->s__comisiones);
		$parametros['subcomisiones'] = (!empty($this->s__subcomisiones)) ? $this->s__subcomisiones : null;
		$parametros['alumnos'] = $this->s__alumnos;
		$parametros['actividad'] = $this->s__actividad;
		$parametros['ubicacion'] = $this->s__ubicacion;
		$parametros['operacion'] = guarani::get_id_operacion();

		$this->s__proceso = guarani::proceso_bk(pro_inscripcion_cursadas_masiva::nombre);
		$this->s__proceso->set_datos($parametros);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}
	
	//--------------------------------------------------------------------------
	//---- Manejo de actividad -------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Retorna las actividades disponibles para inscripción.
	 */
	function get_actividades_disponibles($filtro = null)
	{
		$param_filtro_actividades_con_comision = guarani::parametros()->get_valor('cur_filtro_actividades_con_comision');
		$param_cursa_en_cualquier_ubicacion = guarani::parametros()->get_valor('cur_cursa_en_cualquier_ubicacion');
		return $this->co()->get_actividades_disponibles_inscripcion_masiva($param_filtro_actividades_con_comision, $param_cursa_en_cualquier_ubicacion,
								$this->s__propuesta, $this->s__plan, $this->s__plan_version, $this->s__ubicacion, $filtro);
	}
	
	function set_selecciones_propuesta($datos_filtro)
	{
		$this->s__propuesta = $datos_filtro['propuesta']['valor'];
		$this->s__plan = $datos_filtro['plan']['valor'];
		$this->s__plan_version = $datos_filtro['plan_version']['valor'];
		$this->s__ubicacion = $datos_filtro['ubicacion']['valor'];
		
		$this->s__selecciones_descr = array();
		$this->s__selecciones_descr['propuesta_descr'] = toba::consulta_php('co_propuestas')->get_propuesta_descr($this->s__propuesta);
		$this->s__selecciones_descr['plan_descr'] = toba::consulta_php('co_planes')->get_descripcion_plan($this->s__plan);
		$datos_plan_version = toba::consulta_php('co_planes')->get_datos_plan_version($this->s__plan_version);
		$this->s__selecciones_descr['plan_version_descr'] = $datos_plan_version[0]['version_nombre'];
		$this->s__selecciones_descr['ubicacion_descr'] = toba::consulta_php('co_ubicaciones')->get_ubicacion_nombre($this->s__ubicacion);
				
		$this->cargar_atributos_parametros();
	}
	
	/**
	 * Determina si ya se filtró por actividad
	 * 
	 * @return boolean
	 */
	function hay_filtro_actividad()
	{
		return !empty($this->s__propuesta);
	}
	
	/**
	 * Setea la actividad a la cual se inscribirá a cursar.
	 */
	function set_actividad($actividad)
	{
		$this->s__actividad = $actividad;
		$comisiones_disponibles = $this->get_comisiones_disponibles($actividad);
		if ($this->get_cant_comisiones_disponibles() == 0) {
			throw guarani::error('No hay comisiones disponibles la actividad seleccionada.');
		}
	}
	
	//--------------------------------------------------------------------------
	//---- Manejo de comisión --------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Devuelve las comisiones disponibles para inscribirse para el alumno y actividad elegidas
	 */
	function get_comisiones_disponibles($actividad=null)
	{
		if ($this->s__comisiones_disponibles) {
			return $this->s__comisiones_disponibles;
		}

		$param_usa_subcomisiones = guarani::parametros()->get_valor('cur_usa_subcomisiones');
		$param_cursa_en_cualquier_ubicacion = guarani::parametros()->get_valor('cur_cursa_en_cualquier_ubicacion');
		
		$comisiones = guarani::co('co_comisiones')->get_comisiones_disponibles_para_inscripcion_masiva($param_usa_subcomisiones, 
			$param_cursa_en_cualquier_ubicacion, $this->s__actividad, $this->s__propuesta, $this->s__plan, $this->s__plan_version, $this->s__ubicacion);
		$this->s__comisiones_disponibles = $comisiones;
		return $comisiones;
	}
	
	protected function validar_comisiones($comisiones)
	{
		if (!$this->s__actividad) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('alguna Actividad'));
			throw guarani::error($mje);
		}

		if (guarani_arreglos::count($comisiones) == 0) {
			throw guarani::error("No hay ninguna Comisión seleccionada.");
		}
	}
	
	//--------------------------------------------------------------------------
	//---- Manejo de subccomisiones --------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Retorna las subcomisiones disponibles para inscribirse para el alumno y actividad elegidas
	 */
	function get_subcomisiones_disponibles()
	{
		if ($this->s__subcomisiones_disponibles) {
			return $this->s__subcomisiones_disponibles;
		}
		$filtro = guarani_sql::armar_condicion_in($this->s__comisiones, 'sga_subcomisiones', 'comision');
		$filtro .= " AND sga_subcomisiones.inscripcion_habilitada = '" . comunes::si . "'";
		$subcomisiones = guarani::co('co_comisiones')->get_subcomisiones($filtro);
		$this->set_subcomisiones_disponibles($subcomisiones);
		return $subcomisiones;
	}
	
	protected function validar_subcomisiones($subcomisiones)
	{
		// Se controla que se haya seleccionado una subcomisión por cada uno
		// de los tipos que haya disponibles en la comisión.
		// Se determinan los distintos tipos por comision disponibles
		$tipos_disponibles = array();
		foreach ($this->s__subcomisiones_disponibles as $subcomision) {
			if (!isset($tipos_disponibles[$subcomision['comision']][$subcomision['tipo_clase']])) {
				$tipos_disponibles[$subcomision['comision']][$subcomision['tipo_clase']] = 0;
			}
		}
		// Se determinan los distintos tipos por comision seleccionados
		if ($subcomisiones) {
			foreach ($subcomisiones as $subcomision) {
				$tipos_disponibles[$subcomision['comision']][$subcomision['tipo_clase']] ++;
			}
		}
		// Se determina si faltó seleccionar alguno o si se seleccionó más de uno de algún tipo.
		foreach ($tipos_disponibles as $comision => $tipos_clases) {
			foreach ($tipos_clases as $tipo_clase => $valor) {
				if ($valor == 0) {
					throw guarani::error('Debe seleccionar una opción por cada tipo de clase disponible por comisión.');
				}
				if ($valor > 1) {
					throw guarani::error('Debe seleccionar sólo una opción por cada tipo de clase disponible por comisión.');
				}
			}
		}
	}
	
	//--------------------------------------------------------------------------
	//---- Manejo de alumnos ---------------------------------------------------
	//--------------------------------------------------------------------------
	
	function get_alumnos_disponibles($filtro = '')
	{
		$param_ingresantes_oferta_actividades = guarani::parametros()->get_valor('cur_ingresantes_oferta_actividades');
		$param_recursa_regularizadas = guarani::parametros()->get_valor('cur_recursar_regularizadas');
		$param_filtra_rango_apellido_comision = guarani::parametros()->get_valor('cur_filtra_rango_apellido_comision');
		$param_cursa_en_cualquier_ubicacion = guarani::parametros()->get_valor('cur_cursa_en_cualquier_ubicacion');
		
		$comision = $this->s__comisiones[0]['comision'];
		
		return toba::consulta_php('co_alumnos')->get_alumnos_para_insc_cursada_masiva($param_ingresantes_oferta_actividades, $param_filtra_rango_apellido_comision,
			$param_recursa_regularizadas, $param_cursa_en_cualquier_ubicacion, $this->s__actividad, $comision, $this->s__plan_version, $this->s__ubicacion, $filtro);
	}
	
	function agregar_alumnos_seleccionados($seleccionados)
	{
		if (empty($this->s__alumnos)) {
			$this->s__alumnos = array();
		}
		$this->s__alumnos = array_merge($this->s__alumnos, $seleccionados);
	}
	
	function eliminar_alumno_seleccionado($seleccion)
	{
		foreach ($this->s__alumnos as $key => $alumno) {
			if ($alumno['alumno'] == $seleccion['alumno']) {
				unset($this->s__alumnos[$key]);
				break;
			}
		}
	}
	
	function resetear_alumnos_seleccionados()
	{
		$this->s__alumnos = array();
	}
	
	function get_alumnos_seleccionados()
	{
		return $this->s__alumnos;
	}
	
	function get_cant_alumnos_seleccionados()
	{
		return guarani_arreglos::count($this->s__alumnos);
	}
	
	function hay_alumnos_seleccionados()
	{
		return guarani_arreglos::count($this->s__alumnos) > 0;
	}
}
?>