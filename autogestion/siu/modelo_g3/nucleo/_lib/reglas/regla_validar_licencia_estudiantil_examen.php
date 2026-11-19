<?php
class regla_validar_licencia_estudiantil_examen extends regla
{
	
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$llamado_mesa = $this->get_parametro('llamado_mesa');

		// Controla que el alumno no tenga una licencia estudiantil en la fecha del examen
		// Solo busca en licencias marcadas como que no permite rendir examen final.
		// Si lo encuentra falla el control y devuelve el dato de cuando tiene una licencia..
		$datos_llamado_mesa = toba::consulta_php('co_mesas_de_examen')->get_datos_llamado_mesa($llamado_mesa);
		
		// Verifico si tiene una licencia para la fecha del examen.
		$rtn = toba::consulta_php('co_alumnos')->tiene_licencia_estudiantil_examen($alumno, $datos_llamado_mesa['fecha'], $datos_llamado_mesa['anio_academico']);
		$resultado = $this->resultado_final(empty($rtn));

		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $rtn['licencia_desde_hasta']), true);
		}
		return $resultado;
	}

}
?>