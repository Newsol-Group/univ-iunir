<?php
class regla_no_adeudar_cuotas_de_propuesta extends regla
{
	function validar()
	{
		// Parámetros de contexto
		$alumno = $this->get_parametro('alumno');

		$filtro_alumno = "sga_alumnos.alumno = $alumno";
		$datos_alumnos_morosos_propuesta = toba::consulta_php('co_cobros')->get_datos_alumnos_morosos_propuesta($filtro_alumno);
		$no_es_moroso_propuesta = empty($datos_alumnos_morosos_propuesta);

		$resultado = $this->resultado_final($no_es_moroso_propuesta);
		if (!$resultado) {
			$datos_alumno_moroso_propuesta = current($datos_alumnos_morosos_propuesta);
			$this->set_parametros_mensaje_validacion(array($datos_alumno_moroso_propuesta['alumno_nombre'], $datos_alumno_moroso_propuesta['propuesta_nombre']), true);
		}
		return $resultado;
	}

}
?>
