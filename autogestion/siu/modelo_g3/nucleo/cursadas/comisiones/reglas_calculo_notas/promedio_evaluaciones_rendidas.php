<?php
class promedio_evaluaciones_rendidas extends promedio_evaluaciones
{
	function calcular_promedio($alumno, $evaluaciones)
	{
		return guarani::co('co_evaluaciones')->calcular_promedio_evaluaciones_rendidas($alumno, $evaluaciones);
	}	
}
?>