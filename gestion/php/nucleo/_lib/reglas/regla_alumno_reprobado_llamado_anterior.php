<?php
class regla_alumno_reprobado_llamado_anterior extends regla
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
		
		// Por defecto el alumno no esta reprobado
		$alumno_reprobado_en_examenes = false;
		$turno_examen_normal = turno_de_examen::tipo_normal;
		
		if ($tipo_turno_examen == $turno_examen_normal) {
			$exa_control_reprobacion_mesa = guarani::parametros()->get_valor_en_reglas('exa_control_reprobacion_mesa', $propuesta);
			$param_mensaje1 = '';
			$param_mensaje2 = '';
			switch ($exa_control_reprobacion_mesa) {
				case 'A': //El control se hace para la misma actividad.
					$llamado_mesa_anterior = toba::consulta_php('co_mesas_de_examen')->get_llamado_anterior($llamado_mesa, $turno_examen_normal, $propuesta, true);
					if (!empty($llamado_mesa_anterior)) {
						$alumno_reprobado_en_examenes = toba::consulta_php('co_alumnos')->alumno_reprobado_en_examenes_de_llamado($alumno, $llamado_mesa_anterior, $actividad);
						$param_mensaje2 = $this->armar_mensaje($alumno_reprobado_en_examenes);
					}
					$param_mensaje1 = 'el Llamado';
					break;
				case 'L': //El control se hace para el llamado anterior sin importar la actividad.
					$llamado_mesa_anterior = toba::consulta_php('co_mesas_de_examen')->get_llamado_anterior($llamado_mesa, $turno_examen_normal, $propuesta, false);
					if (!empty($llamado_mesa_anterior)) {
						$alumno_reprobado_en_examenes = toba::consulta_php('co_alumnos')->alumno_reprobado_en_examenes_de_llamado($alumno, $llamado_mesa_anterior);
						$param_mensaje2 = $this->armar_mensaje($alumno_reprobado_en_examenes);
					}
					$param_mensaje1 = 'el Llamado';
					break;
				case 'T': //El control se hace para el turno anterior sin importar la actividad.
					$turno_examen_normal = turno_de_examen::tipo_normal;
					$turno_examen_anterior = toba::consulta_php('co_mesas_de_examen')->get_turno_examen_anterior($turno_examen, $turno_examen_normal, $propuesta, $actividad, false);
					if (!empty($turno_examen_anterior)) {
						$alumno_reprobado_en_examenes = toba::consulta_php('co_alumnos')->alumno_reprobado_en_examenes_de_turno($alumno, $turno_examen_anterior);
						$param_mensaje2 = $this->armar_mensaje($alumno_reprobado_en_examenes);
					}
					$param_mensaje1 = 'el Turno';
					break;
				case 'B': //El control se hace para la misma actividad en el/los llamado/s vinculado/s.
					$alumno_reprobado_en_examenes = toba::consulta_php('co_alumnos')->alumno_reprobado_en_examenes_de_llamado_vinculado($alumno, $llamado_mesa, $actividad);
					$param_mensaje2 = $this->armar_mensaje_concatenado($alumno_reprobado_en_examenes);
					$param_mensaje1 = 'el/los Llamado/s';
					break;
				case 'D': //El control se hace para el/los llamado/s vinculado/s sin importar la actividad.
					$alumno_reprobado_en_examenes = toba::consulta_php('co_alumnos')->alumno_reprobado_en_examenes_de_llamado_vinculado($alumno, $llamado_mesa);
					$param_mensaje2 = $this->armar_mensaje_concatenado($alumno_reprobado_en_examenes);
					$param_mensaje1 = 'el/los Llamado/s';
					break;
			}
		}
		$resultado = $this->resultado_final(!$alumno_reprobado_en_examenes);

		if (!$resultado) {
			$this->set_parametros_mensaje_validacion(array($param_mensaje1, $param_mensaje2), true);
		}
		return $resultado;
	}

	function armar_mensaje($array)
	{
		if (isset($array['nombre'])) {
			return $array['nombre'];
		}
		return '';
	}

	function armar_mensaje_concatenado($array)
	{
		if (guarani_arreglos::count($array) > 0) {
			return implode(', ', $array);
		}
		return '';
	}

}
?>