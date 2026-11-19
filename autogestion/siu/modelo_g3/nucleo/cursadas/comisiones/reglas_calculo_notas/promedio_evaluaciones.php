<?php
abstract class promedio_evaluaciones
{
	function get_promedio($alumno, $comision, $instancia)
	{
		$promedio = null;

		// Se obtienen los datos básicos de la escala de notas de la comisión para la instancia dada.
		$escala_comision = toba::consulta_php('co_evaluaciones')->get_datos_escala_nota_comision($comision, $instancia);
		$promedio = -1; //escala de notas de comision no numérica

		if ($escala_comision['es_numerica'] == comunes::si) {
			$tiene_evaluaciones_con_escalas_compatible = toba::consulta_php('co_evaluaciones')->tiene_evaluaciones_escala_compatible($comision, $instancia);
			if ($tiene_evaluaciones_con_escalas_compatible === -4)
			{
				$promedio = -4;
				return $promedio;
			}
			
			if (! $tiene_evaluaciones_con_escalas_compatible)
			{
				$promedio = -3; // Hay evaluaciones que no tienen escalas compatibles.
				return $promedio;
			}

			// Se obtienen las notas inicial y final de la escala de notas.
			$extremos = toba::consulta_php('co_escalas_notas')->get_extremos_escala_notas($escala_comision['escala_nota']);

			$evaluaciones = toba::consulta_php('co_evaluaciones')->get_evaluaciones_coherentes_promediables($comision, $instancia, $extremos['nota_inicial'], $extremos['nota_final']);
			$promedio = -2; //No hay evaluaciones promediables
			if (! empty($evaluaciones)) {
				$promedio = null;
				$promedio = $this->calcular_promedio($alumno, $evaluaciones);
				if (! is_null($promedio)) {
					$promedio = toba::consulta_php('co_escalas_notas')->get_nota_equivalente_escala($promedio, $escala_comision['escala_nota']);
				}
			}
		}
		
		return $promedio;
	}

	abstract function calcular_promedio($alumno, $evaluaciones);
}
?>