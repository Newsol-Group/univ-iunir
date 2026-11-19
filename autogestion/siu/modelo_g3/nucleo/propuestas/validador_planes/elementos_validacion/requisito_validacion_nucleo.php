<?php
/**
 * 
 *
 *
 * @package Propuestas
 * @subpackage Validador Planes
 */
class requisito_validacion_nucleo extends elemento_validacion
{
	function validar($alumno, $opcion)
	{
		if (isset($this->regla) && array_key_exists($opcion, $this->regla)) {
			/* El conjunto total de posibles parámetros para reglas de tipo "Procesos" o es: alumno, persona, plan_version y fecha.
			 * Para validar reglas en requisitos de tipo "Documental" se agrega el parámetro requisito. */
			$datos_alumno = current(toba::consulta_php('co_alumnos')->get_datos_alumno($alumno, false));
			$this->regla[$opcion]->set_parametros_contexto(array(	'alumno' => $alumno,
																	'persona' => $datos_alumno['persona'],
																	'propuesta' => $datos_alumno['propuesta'],
																	'plan_version' => $datos_alumno['plan_version'],
																	'requisito' => $this->get_prop('requisito'),
																	'fecha' => guarani_fecha::get_hoy()));
			$this->cumplido[$opcion] = $this->regla[$opcion]->validar();
		}
	}

	function validar_caso_test($caso_test, $opcion)
	{
		if (isset($this->regla) && array_key_exists($opcion, $this->regla)) {
			$this->cumplido[$opcion] = $this->regla[$opcion]->validar_caso_test($caso_test);
		}
	}
}
?>