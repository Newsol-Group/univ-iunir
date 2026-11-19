<?php
class promedio_mejores_notas extends promedio_evaluaciones
{
	function calcular_promedio($alumno, $evaluaciones)
	{
		return guarani::co('co_evaluaciones')->calcular_promedio_mejores_notas($alumno, $evaluaciones);
	}	
}
?>