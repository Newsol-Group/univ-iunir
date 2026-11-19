<?php 

class regla_requisitos_ingreso_restrictivos extends regla
{
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$fecha = $this->get_parametro('fecha');
		$requisitos_no_cumplidos = '';		
		
		$requisitos_doc_no_cumplidos = toba::consulta_php('co_alumnos')->cumple_requisitos_ingreso_documentales_restrictivos($alumno, $fecha);
		$cumple_requisitos_ingreso_documentales = empty($requisitos_doc_no_cumplidos);
		
		foreach ($requisitos_doc_no_cumplidos as $requisito_doc) {
			// Registro los requisitos no cumplidos por el alumno
			if ($requisitos_no_cumplidos <> '') {
				$requisitos_no_cumplidos = $requisitos_no_cumplidos . ', '; 
			}	
			$requisitos_no_cumplidos = $requisitos_no_cumplidos . $requisito_doc['requisito_nombre'];		
		}
		
		$cumple_requisitos_ingreso_tipo_proceso = true;
		
		if ($cumple_requisitos_ingreso_documentales) {
			// Cumple los requisitos documentales, ahora verifico los de tipo proceso.
			$requisitos_ingreso_tipo_proceso = toba::consulta_php('co_alumnos')->get_requisitos_ingreso_tipo_proceso_restrictivos($alumno, $fecha);
			foreach ($requisitos_ingreso_tipo_proceso as $requisito) {
				// Evaluo la regla del requisito de tipo proceso
				$regla = guarani::regla($requisito['regla']);
				$regla->set_parametros($requisito['parametros']);

				$parametros_contexto = $this->get_parametros_contexto();
				$parametros_contexto['persona'] = $requisito['persona'];
				$parametros_contexto['propuesta'] = $requisito['propuesta'];
				$regla->set_parametros_contexto($parametros_contexto);

				$cumple_requisitos_ingreso_tipo_proceso = $regla->validar();
				if (!$cumple_requisitos_ingreso_tipo_proceso) {
					// Registro el requisito no cumplido por el alumno
					if ($requisitos_no_cumplidos <> '') {
						$requisitos_no_cumplidos = $requisitos_no_cumplidos . ', '; 
					}	
		            $requisitos_no_cumplidos = $requisitos_no_cumplidos . $requisito['requisito_nombre'];		
					break;
				}
			}
		}

		$resultado = $this->resultado_final($cumple_requisitos_ingreso_documentales && $cumple_requisitos_ingreso_tipo_proceso);
		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$nombre_propuesta = guarani::alumno()->get_alumno_propuesta_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $nombre_propuesta, $requisitos_no_cumplidos), true);
		}
		return $resultado;
	}
}

?>
