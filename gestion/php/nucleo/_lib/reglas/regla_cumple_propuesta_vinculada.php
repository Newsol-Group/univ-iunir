<?php
class regla_cumple_propuesta_vinculada extends regla
{
	function validar()
	{
		$persona = $this->get_parametro('persona');
		$propuesta = $this->get_parametro('propuesta');

		$datos_inscripcion = toba::consulta_php('co_alumnos')->get_datos_inscripcion($persona, $propuesta);

		// Se recupera la relación entre propuestas. Este metodo solo recupera una relación en base al año y plan de ingreso del alumno.
		$cumple_propuesta = true;
		$relacion = toba::consulta_php('co_propuestas_relaciones')->get_relacion_por_anio_academico_propuesta_plan_version($datos_inscripcion['anio_academico'], $propuesta, $datos_inscripcion['plan_version']);
		if (! empty($relacion)) {
			list($cumple_propuesta, ,) = propuesta::validar_relacion($persona, $relacion);
		}

		$resultado = $this->resultado_final($cumple_propuesta);
		if (!$resultado) {
			$nombre_persona = guarani::personas()->get_nombre_persona($persona);
			// Si no hay relación definida y el requisito está negado, el nombre de las propuestas es vacío.
			$nombre_propuestas = empty($relacion) ? '' : toba::consulta_php('co_propuestas_relaciones')->get_nombre_grupos_por_relacion($relacion);
			$this->set_parametros_mensaje_validacion(array($nombre_persona, $nombre_propuestas), true);
		}
		return $resultado;
	}
}