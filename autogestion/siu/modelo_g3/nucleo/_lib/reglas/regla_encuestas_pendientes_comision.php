<?php
class regla_encuestas_pendientes_comision extends regla
{
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$comision = $this->get_parametro('comision');
		$cant_encuestas = toba::consulta_php('co_encuestas')->get_cant_encuestas_no_contestadas_x_comision($alumno, $comision);
		$resultado = $this->resultado_final($cant_encuestas == 0);
		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno), true);
		}
		return $resultado;
	}

}
?>