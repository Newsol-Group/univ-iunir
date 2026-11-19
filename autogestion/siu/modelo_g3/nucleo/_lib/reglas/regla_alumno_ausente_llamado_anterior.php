<?php
class regla_alumno_ausente_llamado_anterior extends regla
{
	function validar()
	{
		/*
			En el caso de búsqueda en un turno de examen anterior, solo busca sobre turnos de examen Normales (tipo_turno = 1)	
		*/
		
		$alumno = $this->get_parametro('alumno');
		$actividad = $this->get_parametro('elemento');
		$llamado_mesa = $this->get_parametro('llamado_mesa');

		$datos_llamado_mesa = toba::consulta_php('co_mesas_de_examen')->get_datos_mesa_llamados($llamado_mesa);
		$turno_examen       = $datos_llamado_mesa['turno_examen'];
		$tipo_turno_examen  = $datos_llamado_mesa['turno_tipo'];

		$datos_alumno = current(guarani::alumno()->get_datos_alumno($alumno));
		$propuesta = $datos_alumno['propuesta'];

		
		// Por defecto el alumno no esta ausente
		$alumno_ausente_en_examenes = false;
		$turno_examen_normal = turno_de_examen::tipo_normal;
		
		if ($tipo_turno_examen == $turno_examen_normal) {
			$exa_control_ausencia_mesa = guarani::parametros()->get_valor_en_reglas('exa_control_ausencia_mesa', $propuesta);
			switch ($exa_control_ausencia_mesa) {
				case 'A': //El control se hace para la misma actividad.
					$llamado_turno_anterior = toba::consulta_php('co_mesas_de_examen')->get_llamado_anterior($llamado_mesa, $turno_examen_normal, $propuesta, true);
					if (!empty($llamado_turno_anterior)) {
						$alumno_ausente_en_examenes = toba::consulta_php('co_alumnos')->alumno_ausente_en_examenes_de_llamado($alumno, $llamado_turno_anterior, $actividad);
					}
					break;
				case 'L': //El control se hace para el llamado anterior sin importar la actividad.
					$llamado_turno_anterior = toba::consulta_php('co_mesas_de_examen')->get_llamado_anterior($llamado_mesa, $turno_examen_normal, $propuesta, false);
					if (!empty($llamado_turno_anterior)) {
						$alumno_ausente_en_examenes = toba::consulta_php('co_alumnos')->alumno_ausente_en_examenes_de_llamado($alumno, $llamado_turno_anterior);
					}
					break;
				case 'T': //El control se hace para el turno anterior sin importar la actividad.
					$turno_examen_anterior = toba::consulta_php('co_mesas_de_examen')->get_turno_examen_anterior($turno_examen, $turno_examen_normal, $propuesta, $actividad, false);
					if (!empty($turno_examen_anterior)) {
						$alumno_ausente_en_examenes = toba::consulta_php('co_alumnos')->alumno_ausente_en_examenes_de_turno($alumno, $turno_examen_anterior);
					}
					break;
				case 'B': //El control se hace para la misma actividad en el/los llamado/s vinculado/s.
					$alumno_ausente_en_examenes = toba::consulta_php('co_alumnos')->alumno_ausente_en_examenes_de_llamado_vinculado($alumno, $llamado_mesa, $actividad);
					break;
				case 'D': //El control se hace para el/los llamado/s vinculado/s sin importar la actividad.
					$alumno_ausente_en_examenes = toba::consulta_php('co_alumnos')->alumno_ausente_en_examenes_de_llamado_vinculado($alumno, $llamado_mesa);
					break;
			}
		}
		$resultado = $this->resultado_final(!$alumno_ausente_en_examenes);

		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno), true);
		}
		return $resultado;
	}

}
?>