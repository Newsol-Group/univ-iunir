<?php
/**
 *
 *
 * @package		Matrícula
 * @subpackage	Inscripciones
 * 
 */
class cn_man_reinscripcion_propuesta extends cn_manejador_entidad
{
	function etiquetas($dep = null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_propuestas_aspira');
		} else {
			parent::etiquetas($dep);
		}
	}

	function get_listado_reinscripcion_propuesta($anio_academico, $filtro)
	{
		return toba::consulta_php('co_reinscripciones')->get_alumnos_reinscripcion_anio_academico($anio_academico, $filtro);
	}

	/**
	 * Reinscribe en la propuesta y año académico al alumno dado
	 *
	 * @param integer $alumno
	 * @param integer $anio_academico
	 * @param integer $nro_transaccion
	 */
	function reinscribir_a_propuesta($parametros, $anio_academico, $nro_transaccion)
	{
		// Si el alumno es ingresante, el valor del parámetro 'mat_reinscripcion_ingresante' para la propuesta debe ser 'S'.
		$param_mat_reinscripcion_ingresante = guarani::parametros()->get_valor('mat_reinscripcion_ingresante', $parametros['propuesta']);
		if (toba::consulta_php('co_alumnos')->es_ingresante($parametros['alumno'], $anio_academico) &&
			$param_mat_reinscripcion_ingresante == comunes::no) {
			$mensaje = guarani_mensajes::get('800MAT_ingresante_sin_resincripcion', array($parametros['apellido_y_nombre']));
			throw guarani::error($mensaje);
		}

		// El valor del parámetro 'mat_reinscripcion_propuesta' para la propuesta del alumno debe ser 'S'.
		$param_mat_reinscripcion_propuesta = guarani::parametros()->get_valor('mat_reinscripcion_propuesta', $parametros['propuesta']);
		if ($param_mat_reinscripcion_propuesta == comunes::no) {
			$mensaje = guarani_mensajes::get('800MAT_propuesta_sin_reinscripcion', array($parametros['propuesta_nombre_abreviado']));
			throw guarani::error($mensaje);
		}

		// Debe existir un período de reinscripción a propuesta vigente a la fecha de hoy para el año académico seleccionado y el plan-versión, modalidad y ubicación del alumno.
		if (!toba::consulta_php('co_reinscripciones')->existe_periodo_reinscripcion($anio_academico, $parametros['plan_version'], $parametros['modalidad'], $parametros['ubicacion'])) {
			$mensaje = guarani_mensajes::get('800MAT_sin_periodo_reinscripcion');
			throw guarani::error($mensaje);
		}

		$this->validar_puntos_de_control($parametros);

		guarani::act('act_inscripciones')->reinscribir_a_propuesta($parametros['alumno'], $anio_academico, $nro_transaccion);
	}

	function validar_puntos_de_control($datos_reinscripcion)
	{
		$puntos_de_control = array(
			guarani_punto_de_control::alumno_entidad,
			guarani_punto_de_control::persona_operacion,
			guarani_punto_de_control::alumno_operacion
		);

		$parametros = array(
			'fecha' => guarani_fecha::get_hoy(),
			'persona' => $datos_reinscripcion['persona'],
			'alumno' => $datos_reinscripcion['alumno'],
			'plan_version' => $datos_reinscripcion['plan_version']
		);

		$retorno = guarani::validador_puntos_de_control($puntos_de_control, $parametros)->controlar();
		guarani::validador_puntos_de_control()->notificar();

		if ($retorno == guarani_punto_de_control::rechazado) {
			throw new guarani_error(null, null, guarani_mensajes::get('800MAT_reinscripcion_fallo_ctrl'));
		}
	}

}
?>