<?php
class regla_cant_max_solicitud_constancias_anio_academico extends regla
{
	function validar()
	{	
		// Parámetros de contexto.
		$alumno = $this->get_parametro('alumno');
		$constancia = $this->get_parametro('constancia');
		$fecha = $this->get_parametro('fecha');
		
		$anio_academico	= toba::consulta_php('co_anios_academicos')->get_anio_academico_actual($alumno);
		$datos_constancia = toba::consulta_php('co_constancias')->get_datos_constancia($constancia);
		$max_solicitudes_constancia = $datos_constancia['max_cant_solicitudes'];
		
		$resultado = true;
		
		if (is_null($anio_academico)) {
			
			throw new Exception("No se pudo recuperar el Año Académico actual, consulte con el area de Alumnado.");
			
		} else {
			
			$cant_solicitudes_constancia = toba::consulta_php('co_constancias')->get_cant_solicitudes_constancia_anio_academico($constancia, $alumno, $anio_academico);
			if ($cant_solicitudes_constancia + 1 > $max_solicitudes_constancia) {
				$resultado = false;
			}
		}
		
		$resultado = $this->resultado_final($resultado);
		if (!$resultado) {
			$this->set_parametros_mensaje_validacion([$max_solicitudes_constancia, $anio_academico], true);
		}
		
		return $resultado;
	}
}