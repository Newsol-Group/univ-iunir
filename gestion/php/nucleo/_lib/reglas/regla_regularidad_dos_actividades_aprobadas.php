<?php
class regla_regularidad_dos_actividades_aprobadas extends regla
{
	function validar()
	{
		// Parámetros de contexto.
		$persona = $this->get_parametro('persona');
		$alumno = $this->get_parametro('alumno');
		$valor_parametro = $this->get_parametro('valor_parametro');

		// Parámetros.
		$anio_academico = $this->get_parametro('anio');
		$fecha_desde = $this->get_parametro('fecha_desde');
		$fecha_hasta = $this->get_parametro('fecha_hasta');

		$filtro = " vw_hist_academica_basica.fecha BETWEEN '$fecha_desde' AND '$fecha_hasta'";

		if ($valor_parametro == 'Alumno') {
			// Considera actividades aprobadas en la misma propuesta o actividades comunes aprobadas en otra propuesta.
			$filtro .= " AND sga_alumnos.alumno = $alumno " ;
		} elseif ($valor_parametro == 'Persona') {
			// Solo considera actividades aprobadas en cada propuesta. Las actividades comunes aprobadas en otra propuesta no las considera para no contarlas mas de una vez.
			$filtro .= " AND vw_hist_academica_basica.persona = $persona ";
			$filtro .= " AND vw_hist_academica_basica.origen_otra_propuesta = false ";
			$filtro .= " AND sga_propuestas_tipos.control_regularidad = '" . comunes::si . "'";
		}

		$actividades = guarani::co('co_alumnos')->get_cant_act_aprobadas_ctrl_regularidad($filtro);
		$resultado = $this->resultado_final($actividades >= 2);
		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $anio_academico), true);
		}

		return $resultado;
	}

}
?>