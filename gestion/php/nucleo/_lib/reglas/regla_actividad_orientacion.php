<?php
class regla_actividad_orientacion extends regla
{
	function validar()
	{
		// Parámetros de contexto
		$alumno = $this->get_parametro('alumno');
		$elemento = $this->get_parametro('elemento');
		$fecha = $this->get_parametro('fecha');

		// $datos_alumno = current(guarani::alumno()->get_datos_alumno($alumno));
		
		// Obtengo propuesta y version de plan de estudios del alumno
		$datos_alumno = guarani::alumno()->get_datos_alumno_reglas($alumno);
				
		$propuesta		= $datos_alumno['propuesta'];
		$plan_version	= $datos_alumno['plan_version'];

		// Recupero parametro si debe el alumno elegir orientación.
		$mat_elegir_orientacion = guarani::parametros()->get_valor_en_reglas('mat_elegir_orientacion', $propuesta);

		if ($mat_elegir_orientacion == comunes::no) {
			$resultado = true;
		} else {
			$orientaciones_actividad = guarani::planes()->get_orientaciones_actividad($plan_version, $elemento);

			if (empty($orientaciones_actividad)) {
				$resultado = true;
			} else {
				$orientaciones_alumno = guarani::alumno()->get_orientaciones_alumno($alumno);
				if (empty($orientaciones_alumno)) {
					$resultado = false;
				} else {
					$resultado = $this->orientaciones_alumno_en_orientaciones_actividad($orientaciones_alumno, $orientaciones_actividad);
				}
			}
		}
		
		$resultado_final = $this->resultado_final($resultado);
		if (!$resultado_final) {
			//$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$persona = $datos_alumno['persona'];
			$nombre_alumno = guarani::personas()->get_nombre_persona($persona);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno), true);
		}
		return $resultado_final;
	}

	/**
	 * Determina si el alumno está inscripto en alguna orientación coincidente
	 * con las orientaciones que contienen a la actividad
	 * 
	 * @param array $orientaciones_alumno
	 * @param array $orientaciones_actividad
	 * @return boolean
	 */
	protected function orientaciones_alumno_en_orientaciones_actividad($orientaciones_alumno, $orientaciones_actividad)
	{
		foreach ($orientaciones_alumno as $orient_alumno) {
			foreach ($orientaciones_actividad as $orient_actividad) {
				if ($orient_alumno['orientacion'] == $orient_actividad['orientacion']) {
					return true;
				}
			}
		}
		return false;
	}

}
?>