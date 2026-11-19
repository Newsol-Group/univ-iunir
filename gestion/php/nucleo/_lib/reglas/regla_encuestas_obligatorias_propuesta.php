<?php
class regla_encuestas_obligatorias_propuesta extends regla
{
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		
		// Verifico si tiene encuestas relacionadas con la propuesta sin contestar.
		$cant_encuestas = toba::consulta_php('co_encuestas')->get_cant_encuestas_no_contestadas_propuesta($alumno);

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