<?php
/**
 *
 * 
 * @package		Cursadas
 * @subpackage	Evaluaciones
 * 
 */
class cn_ent_evaluacion_comision extends cn_entidad
{
	function set_datos_evaluacion($datos)
	{
		$this->validar_datos($datos);
		$this->tabla('evaluaciones')->set($datos);
	}

	function get_datos_evaluacion()
	{
		return $this->tabla('evaluaciones')->get();
	}

	function get_codigo_evaluacion()
	{
		return $this->tabla('evaluaciones')->get_columna('evaluacion');
	}

	function set_datos_instancias($datos)
	{
		// Se eliminan las instancias asociadas a la evaluación.
		$this->tabla('evaluaciones_instancias')->eliminar_filas();

		foreach ($datos as $inst) {
			$this->tabla('evaluaciones_instancias')->nueva_fila(array('instancia' => $inst));
		}
	}

	function get_datos_instancias()
	{
		return $this->tabla('evaluaciones_instancias')->get_filas();
	}

	function set_datos_evaluaciones_relacionadas($datos)
	{
		// Se eliminan las evaluaciones relacionadas.
		$this->tabla('evaluaciones_relacion')->eliminar_filas();

		foreach ($datos as $eval) {
			$this->tabla('evaluaciones_relacion')->nueva_fila(array('evaluacion_padre' => $eval));
		}
	}

	function get_datos_evaluaciones_relacionadas()
	{
		return $this->tabla('evaluaciones_relacion')->get_filas();
	}

	//-----------------------------------------------------------------------------------
	//---- VALIDACIONES -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Configura la habilitación para edición de la entidad cargada
	 */
	function verificar_editabilidad()
	{
		if (!$this->tabla('evaluaciones')->esta_cargada()) {
			return true;
		} else {
			$datos = $this->get_datos_evaluacion();
			return $datos['estado'] == comunes::estado_abierto;
		}
	}

	function es_padre_de_evaluacion($evaluacion)
	{
		return toba::consulta_php('co_evaluaciones')->es_padre_de_evaluacion($evaluacion);
	}

	function tiene_notas_cargadas($evaluacion)
	{
		return toba::consulta_php('co_evaluaciones')->tiene_notas_cargadas($evaluacion);
	}

	function tiene_alumnos($evaluacion)
	{
		return toba::consulta_php('co_evaluaciones')->tiene_alumnos($evaluacion);
	}

	function validar_datos($datos)
	{
		// Se valida que la hora de inicio no sea mayor que la hora de finalización.
		if (isset($datos['hora_inicio']) && isset($datos['hora_fin'])) {
			if ($datos['hora_inicio'] >= $datos['hora_fin']) {
				$this->set_error('800_error_rango_horas');
			}
		}

		// Se valida que no se duplique el nombre de una evaluación dentro de una comisión.
		$evaluacion = $this->tabla('evaluaciones')->get_columna('evaluacion');
		$entidad = $datos['entidad'];
		$this->validar_nombre_evaluacion($datos['nombre'], $evaluacion, $entidad);

		// Se valida que la fecha de examen definida sea mayor a la de inicio del período lectivo.
		$comision = current($this->controlador()->get_listado_comisiones_por_entidad($entidad));
		if (guarani_fecha::es_menor($datos['fecha'], $comision['fecha_inicio_periodo_lectivo'])) {
			$this->set_error('800_control_fechas', array('de Evaluación', 'menor', 'de inicio del Período Lectivo de la Comisión'));
		}

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function validar_nombre_evaluacion($nombre, $evaluacion, $entidad)
	{
		if (!toba::consulta_php('co_evaluaciones')->validar_nombre_evaluacion($nombre, $evaluacion, $entidad)) {
			$this->set_error('dato_duplicado', array('nombre', 'Evaluación'));
		}
	}

	function evaluacion_tipo_es_esencial($evaluacion_tipo)
	{
		return toba::consulta_php('co_evaluaciones')->evaluacion_tipo_es_esencial($evaluacion_tipo);
	}

	function validar_tipo_evaluacion()
	{
		$datos_evaluacion = $this->get_datos_evaluacion();
		if (!$this->evaluacion_tipo_es_esencial($datos_evaluacion['evaluacion_tipo'])) {
			// Si el tipo de evaluación es no esencial y no tiene evalaciones relacionadas -> ERROR
			if (guarani_arreglos::count($this->get_datos_evaluaciones_relacionadas()) == 0) {
				throw guarani::error(guarani_mensajes::get('800_no_seleccion', array('una Evaluación Relacionada')));
			}
		}
	}

	function pre_sincronizar()
	{
		$this->validar_tipo_evaluacion();
	}

}
?>