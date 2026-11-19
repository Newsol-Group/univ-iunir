<?php
class regla_regularidad_vigente_catedra extends regla
{
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$elemento = $this->get_parametro('elemento');
		$fecha = $this->get_parametro('fecha');
		$llamado_mesa = $this->get_parametro('llamado_mesa');

		$plan_version = guarani::alumno()->get_plan_version($alumno, $fecha);
		$info_elemento_plan = toba::consulta_php('co_elementos')->get_info_elemento_plan($plan_version, $elemento);
		if (empty($info_elemento_plan) || $info_elemento_plan['requiere_cursada'] == comunes::si) {
			// La actividad no existe en la versión del plan (rinde examen de una actividad regularizada en un plan/versión anterior)
			// o existe y requiere cursada.
			$datos_alumno = current(guarani::alumno()->get_datos_alumno($alumno));
			$propuesta = $datos_alumno['propuesta'];

			$datos_llamado_mesa = toba::consulta_php('co_mesas_de_examen')->get_datos_llamado_mesa($llamado_mesa);
			$regularidad_vigente_actividad = toba::consulta_php('co_alumnos')->get_regularidad_vigente_actividad($alumno, $elemento, $datos_llamado_mesa['fecha']);

			$resultado = true;
			if (empty($regularidad_vigente_actividad)) {
				$resultado = false;
			} elseif (is_null($regularidad_vigente_actividad['equivalencia_tramite']) && guarani::parametros()->get_valor_en_reglas('exa_inscripcion_examen_catedra', $propuesta) == comunes::si) {
				$catedra_llamado = toba::consulta_php('co_mesas_de_examen')->get_catedra_llamado_mesa($llamado_mesa);
				$catedra_comision = toba::consulta_php('co_comisiones')->get_catedra_comision($regularidad_vigente_actividad['comision']);

				if (isset($catedra_llamado) && isset($catedra_comision) && $catedra_comision != $catedra_llamado) {
					$resultado = false;
					if (toba::consulta_php('co_catedras')->get_estado_catedra($catedra_comision) == comunes::estado_baja) {
						$filtro = "	sga_catedras_equivalentes.catedra = $catedra_comision AND
									sga_catedras_equivalentes.catedra_equivalente = $catedra_llamado";
						$catedras_equivalentes = toba::consulta_php('co_catedras_equivalentes')->get_catedras_equivalentes($filtro);
						if (!empty($catedras_equivalentes)) {
							$resultado = true;
						}
					}
				}
			}
		} elseif ($info_elemento_plan['requiere_cursada'] == comunes::no) {
			//La actividad existe en la versión del plan pero no requiere cursada.
			$resultado = true;
		}

		$resultado = $this->resultado_final($resultado);
		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$datos_basicos_elemento = guarani::elementos()->get_datos_basicos_elemento_en_plan_version($elemento, $plan_version); 
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $datos_basicos_elemento['codigo_nombre']), true);
		}
		return $resultado;
	}
}