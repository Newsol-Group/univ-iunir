<?php
class cn_ent_acta_examen_basica extends cn_entidad
{
	protected $s__llamado_mesa_entidad;
	protected $s__acta;
	protected $s__evaluacion_auto;
	protected $s__detalles_acta;
	protected $s__datos_basicos_acta;

	function cargar_atributos_parametros($mesa_examen)
	{
		$operacion = guarani::get_id_operacion();

		$propuestas = aplanar_matriz(toba::consulta_php('co_mesas_de_examen')->get_propuestas_mesa_examen($mesa_examen), 'propuesta');
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);

		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuestas);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_ra, $ras);
	}

	function entidad_cargada()
	{
		return isset($this->s__llamado_mesa_entidad);
	}

	function get_listado($filtro)
	{
		// Para todas las operaciones de Actas de Examen, se listan los Llamados de Mesas de Examen que 
		// estén en Estado Activo (no Suspendido).
		$where = "sga_llamados_mesa.estado = '" . mesa_de_examen::mesa_estado_activo . "'";
		$filtro = !empty($filtro) ? $filtro . ' AND ' . $where : $where;
		return toba::consulta_php('co_mesas_de_examen')->get_listado_con_cant_actas_sin_anular($filtro);
	}

	function cargar($id, $parametros = array())
	{
		$this->s__llamado_mesa_entidad = $parametros['entidad'];
		$this->s__evaluacion_auto = $parametros['evaluacion'];
		if (isset($parametros['acta'])) {
			$this->s__acta = $parametros['acta']['id_acta'];
		}
	}

	function resetear()
	{
		unset($this->s__llamado_mesa_entidad);
		unset($this->s__acta);
		unset($this->s__evaluacion_auto);
		unset($this->s__detalles_acta);
		unset($this->s__datos_basicos_acta);
	}

	function get_llamado_mesa_entidad()
	{
		return $this->s__llamado_mesa_entidad;
	}

	function get_evaluacion()
	{
		return $this->s__evaluacion_auto;
	}

	function get_acta()
	{
		return $this->s__acta;
	}

	function get_detalle_acta_abierta($id_acta)
	{
		$datos_basicos = $this->get_datos_basicos_acta_examen($id_acta);

		if (!isset($this->s__detalles_acta)) {
			$params_sistema = array('exa_orden_detalle_actas' => guarani::parametros()->get_valor('exa_orden_detalle_actas'));
			$this->s__detalles_acta = toba::consulta_php('co_actas')->get_detalle_acta_examen_abierta_renglones($id_acta, $datos_basicos['renglones_folio'], $params_sistema);
		}
		return $this->s__detalles_acta;
	}

	/**
	 * Retorna los datos basicos de un acta de examen , si no estan cargados en la entidad, los trae de la BD
	 * 
	 * @return array
	 */
	function get_datos_basicos_acta_examen($id_acta)
	{
		if (!isset($this->s__datos_basicos_acta)) {
			$this->s__datos_basicos_acta = toba::consulta_php('co_actas')->get_datos_basicos_acta_examen($id_acta);
		}
		return $this->s__datos_basicos_acta;
	}

	/**
	 *  Retorna los alumnos en el detalle del acta actual, este abierta o cerrada
	 *
	 */
	function get_alumnos_acta_examen($id_acta = null, $estado = null)
	{
		$datos_basicos = $this->get_datos_basicos_acta_examen();
		$params_sistema = array('exa_orden_detalle_actas' => guarani::parametros()->get_valor('exa_orden_detalle_actas'));
		$datos = toba::consulta_php('co_actas')->get_alumnos_acta_examen($this->get_acta(), $datos_basicos['estado'], $params_sistema);
		return $datos;
	}

}
?>