<?php
class regla_validar_licencia_estudiantil_cursada extends regla
{
	
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$comision = $this->get_parametro('comision');


		// Verifico si tiene una licencia que se solape con el periodo lectivo de la comision que se esta intentando inscribir.
		$rtn = toba::consulta_php('co_alumnos')->tiene_licencia_estudiantil_cursada($alumno, $comision);
		$resultado = $this->resultado_final(empty($rtn));

		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $rtn['licencia_desde_hasta']), true);
		}
		return $resultado;
	}

}
?>