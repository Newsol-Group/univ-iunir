<?php
class regla_encuestas_obligatorias_resp_academica extends regla
{
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		
		// Verifico si tiene encuestas relacionadas con la responsable academica de la propuesta sin contestar.
		$cant_encuestas = toba::consulta_php('co_encuestas')->get_cant_encuestas_no_contestadas_resp_academica($alumno);

		$resultado = $this->resultado_final($cant_encuestas == 0);
		if (!$resultado) {
			$datos_alumno = current(guarani::alumno()->get_datos_alumno($alumno));
			$nombre_alumno = $datos_alumno['apellido_y_nombre'];
			$this->set_parametros_mensaje_validacion(array($nombre_alumno), true);
		}
		return $resultado;
	}

}
?>