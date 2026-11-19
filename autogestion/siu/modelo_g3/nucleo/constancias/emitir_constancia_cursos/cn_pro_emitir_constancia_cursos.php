<?php
class cn_pro_emitir_constancia_cursos extends cn_procesos_bk
{
	protected $s__persona;
	protected $s__persona_datos;
	protected $s__propuesta;
	protected $s__plan;
	protected $s__plan_version;
	protected $s__comision;
	protected $s__comision_datos;
	protected $s__alumno;
	protected $s__alumno_datos;

	function cargar_atributos_parametros($propuesta)
	{
		$operacion = guarani::get_id_operacion();

		$propuestas = array($propuesta);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);

		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuestas);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_ra, $ras);
	}
	
	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_emitir_constancia_cursos_nucleo::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}

	//--------------------------------------------------------------------------
	//---- Manejo de curso
	//--------------------------------------------------------------------------
	
	/**
	 * Establece la propuesta seleccionada al filtrar.
	 * Obtiene el plan y plan_version asociados a la misma.
	 */
	function set_propuesta($propuesta)
	{
		$this->s__propuesta = $propuesta;

		$datos_plan = toba::consulta_php('co_propuestas')->get_plan_y_plan_version_propuesta_cursos($propuesta);
		if (empty($datos_plan['plan']) || empty($datos_plan['plan_version'])) {
			throw guarani::error('No hay un plan activo para el tipo de cursos seleccionado.');
		}
		$this->s__plan = $datos_plan['plan'];
		$this->s__plan_version = $datos_plan['plan_version'];
	}
	
	function hay_propuesta_seteada()
	{
		return isset($this->s__propuesta);
	}

	function resetear_propuesta()
	{
		unset($this->s__propuesta);
		unset($this->s__plan);
		unset($this->s__plan_version);
	}

	/**
	 * Establece la comisión asociada al curso elegido.
	 * Obtiene un conjunto de datos básicos de la misma.
	 */
	function set_comision($comision)
	{
		$this->s__comision = $comision;
		$where = 'sga_comisiones.comision = ' . toba::db()->quote($comision);
		$this->s__comision_datos = current($this->get_listado($where));
	}

	function get_datos_comision()
	{
		return $this->s__comision_datos;
	}

	function get_plan_version()
	{
		return $this->s__plan_version;
	}

	function get_propuesta()
	{
		return $this->s__propuesta;
	}

	function resetear_comision()
	{
		unset($this->s__comision);
		unset($this->s__comision_datos);
	}

	function etiquetas($concepto)
	{
		if (!isset($this->etiquetas[$concepto])) {
			$this->etiquetas[$concepto] = guarani_etiquetas::get_concepto($concepto);
		}
		return $this->etiquetas[$concepto];
	}

	function get_listado($where = '')
	{
		$params_sistema = array('sq_usa_sanaviron' => guarani::parametros()->get_valor('sq_usa_sanaviron'));
		// Sólo se listan los cursos que ya finalizaron
		$where .= ' AND COALESCE(sga_comisiones_excep_perlect.fecha_fin_dictado, sga_periodos_lectivos.fecha_fin_dictado) <= ' . guarani_sql::fecha_actual;
		return toba::consulta_php('co_comisiones')->get_cursos($this->s__propuesta, $this->s__plan, $this->s__plan_version, $where, false, false, 'comision', false, $params_sistema);
	}

	function get_listado_alumnos($exige_aprobacion, $where = '')
	{
		// Además del filtro de alumnos, se debe verificar si la constancia 
		// exige aprobar, entonces sólo deben mostrarse los alumnos aprobados.			
		if ($exige_aprobacion) {
			if ($where) {
				$where .= ' AND ';
			}
			$where .= 'vw_hist_academica.resultado = ' . quote(escalas_notas::resultado_aprobado);
		}
		return toba::consulta_php('co_constancias')->get_alumnos_inscriptos_a_curso($this->s__comision, $exige_aprobacion, $where);
	}

	function get_datos_constancia($constancia)
	{
		$where = ' sga_constancias.constancia = ' . $constancia;
		return current(toba::consulta_php('co_constancias')->get_listado_constancias($where));
	}

}
?>