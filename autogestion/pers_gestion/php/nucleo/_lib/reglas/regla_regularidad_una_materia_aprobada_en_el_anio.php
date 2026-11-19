<?php

// Newsol (NUEVO) - Se utiliza como control de regularidad, el alumno debe tener una materia aprobada en el año (por regularidad o por examen)
class regla_regularidad_una_materia_aprobada_en_el_anio extends regla
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
		$cant_regulares = 0;
		$cant_examen = 0;

		$filtro = " vw_regularidades_basica.fecha BETWEEN '$fecha_desde' AND '$fecha_hasta'";

		if ($valor_parametro == 'Alumno') {
			$filtro .= ' AND sga_alumnos.alumno = ' . toba::db()->quote($alumno) . ' AND vw_regularidades_basica.origen_otra_propuesta = false';
		} elseif ($valor_parametro == 'Persona') {
			$filtro .= ' AND vw_regularidades_basica.persona = ' . toba::db()->quote($persona);
			$filtro .= " AND vw_regularidades_basica.origen_otra_propuesta = false ";
			$filtro .= " AND sga_propuestas_tipos.control_regularidad = '" . comunes::si . "'";
		}

		$cant_regulares = guarani::co('co_alumnos')->get_cant_cur_aprobadas_ctrl_regularidad($filtro);
		
		$filtro = " vw_hist_academica_basica.fecha BETWEEN '$fecha_desde' AND '$fecha_hasta'";

		if ($valor_parametro == 'Alumno') {
			$filtro .= ' AND sga_alumnos.alumno = ' . toba::db()->quote($alumno) . ' AND vw_hist_academica_basica.origen_otra_propuesta = false';
		} elseif ($valor_parametro == 'Persona') {
			$filtro .= ' AND vw_hist_academica_basica.persona = ' . toba::db()->quote($persona);
			$filtro .= " AND vw_hist_academica_basica.origen_otra_propuesta = false ";
			$filtro .= " AND sga_propuestas_tipos.control_regularidad = '" . comunes::si . "'";
		}

		$cant_examen = guarani::co('co_alumnos')->get_cant_exa_aprobadas_ctrl_regularidad($filtro);
		
		$actividades = $cant_regulares + $cant_examen;

		$resultado = $this->resultado_final($actividades >= 1);
		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $anio_academico), true);
		}

		return $resultado;
	}

}
?>