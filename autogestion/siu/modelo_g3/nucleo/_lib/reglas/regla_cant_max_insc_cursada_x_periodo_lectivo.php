<?php
class regla_cant_max_insc_cursada_x_periodo_lectivo extends regla
{
	function validar()
	{
		$comision = $this->get_parametro('comision');
		$alumno = $this->get_parametro('alumno');
		$datos_alumno = current(guarani::alumno()->get_datos_alumno($alumno));

		$cant_inscripciones_periodo_lectivo = toba::consulta_php('co_alumnos')->get_cant_inscripciones_periodo_lectivo($alumno, $comision);
		$cant_max_inscripciones_periodo_lectivo = guarani::parametros()->get_valor_en_reglas('cur_cant_max_insc_curs_igual_periodo_lectivo', $datos_alumno['plan']);

		$resultado = $this->resultado_final($cant_inscripciones_periodo_lectivo < $cant_max_inscripciones_periodo_lectivo);

		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$nombre_periodo_lectivo = toba::consulta_php('co_comisiones')->get_descr_per_lectivo_comision($comision);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $cant_max_inscripciones_periodo_lectivo, $nombre_periodo_lectivo), true);
		}
		return $resultado;
	}

}
?>