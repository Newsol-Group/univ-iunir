<?php
class regla_es_moroso_comision extends regla
{
	function validar()
	{
		// Parámetros de contexto
		$comision = $this->get_parametro('comision');
		$alumno = $this->get_parametro('alumno');

		$filtro_alumno = "sga_alumnos.alumno = $alumno";
		$datos_alumnos_morosos_comision = toba::consulta_php('co_cobros')->get_datos_alumnos_morosos_comision($comision, $filtro_alumno);
		$no_es_moroso_comision = empty($datos_alumnos_morosos_comision);

		$resultado = $this->resultado_final($no_es_moroso_comision);
		if (!$resultado) {
			$datos_alumno_moroso_comision = current($datos_alumnos_morosos_comision);
			$this->set_parametros_mensaje_validacion(array($datos_alumno_moroso_comision['alumno_nombre'], $datos_alumno_moroso_comision['comision_nombre']), true);
		}
		return $resultado;
	}

}
?>