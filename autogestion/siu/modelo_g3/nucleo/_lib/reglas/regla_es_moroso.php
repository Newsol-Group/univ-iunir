<?php
class regla_es_moroso extends regla
{
	function validar()
	{
		// Parámetros de contexto
		$alumno = $this->get_parametro('alumno');

		$filtro_alumno = "sga_alumnos.alumno = $alumno";
		$datos_alumnos_morosos = toba::consulta_php('co_cobros')->get_datos_alumnos_morosos($filtro_alumno);
		$no_es_moroso = empty($datos_alumnos_morosos);

		$resultado = $this->resultado_final($no_es_moroso);
		if (!$resultado) {
			$datos_alumno_moroso = current($datos_alumnos_morosos);
			$this->set_parametros_mensaje_validacion(array($datos_alumno_moroso['alumno_nombre']), true);
		}
		return $resultado;
	}

}
?>