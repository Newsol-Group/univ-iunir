<?php

// Newsol (NUEVO) - Se utiliza para verificar que el alumno no pueda inscribirse a exámenes con superposición horaria
class regla_superposicion_horaria_mesa extends regla
{
	function set_parametros_contexto($parametros_contexto = array())
	{
		parent::set_parametros_contexto($parametros_contexto);
	}

	/**
	 * Verifica la superposición del horario de la mesa de examen respecto a las inscripciones del alumno.
	 * Tanto la mesa como el alumno son datos recibidos como parámetro de contexto.
	 *
	 * @return boolean false si existe superposición y true en caso contrario.
	 */
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$actividad = $this->get_parametro('elemento');
		$llamado_mesa = $this->get_parametro('llamado_mesa');
		$fecha = $this->get_parametro('fecha');

		$filtro = '';
		$datos_actividad = guarani::elementos()->get_datos_basicos_elemento($actividad);

		$hay_superposicion = false;
		$llamados = guarani::co('co_mesas_de_examen')->get_datos_llamado_mesa($llamado_mesa);
		$hora_inicio = $llamados['mesa_examen_hora_inicio'];
		$hora_finalizacion = $llamados['mesa_examen_hora_finalizacion'];
		$fecha_mesa = $llamados['fecha'];

		foreach ($llamados as $llamado) {
			$hay_superposicion = guarani::co('co_mesas_de_examen')->get_inscripciones_superpuestas($alumno, $llamado_mesa, $fecha_mesa, $hora_inicio, $hora_finalizacion, $filtro);
			if (!empty($hay_superposicion)) {
				break;
				
			}
		}

		$resultado = $this->resultado_final(!$hay_superposicion);
		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno), true);
		}

		return $resultado;
	}

}
?>