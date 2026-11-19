<?php
class regla_alumno_existe_en_acta_examen_abierta extends regla
{
	/**
	 * Se redefine el set de parámetros de contexto de regla_nucleo.php para considerar el caso especial de las 
	 * operaciones de Equivalencias, donde el parámetro llamado mesa no tiene valor, y no es necesario 
	 * considerarlo al evaluar esta regla.
	 */
	function set_parametros_contexto($parametros_contexto = array())
	{
		if (!isset($parametros_contexto['llamado_mesa'])) {
			$parametros_contexto['llamado_mesa'] = 0;
		}
		parent::set_parametros_contexto($parametros_contexto);
	}

	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$actividad = $this->get_parametro('elemento');
		$llamado_mesa = $this->get_parametro('llamado_mesa');

		// Controla si el alumno esta en otra mesa de examen o acta de examen abierta de la misma actividad
		// Si lo encuentra falla el control y devuelve la fecha del examen.
		$fecha_examen = toba::consulta_php('co_alumnos')->get_fecha_examen_alumno_actividad($alumno, $actividad, $llamado_mesa);
		$resultado = $this->resultado_final(empty($fecha_examen));

		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $fecha_examen), true);
		}
		return $resultado;
	}

}
?>