<?php
class promedio_ultimas_notas extends promedio_evaluaciones
{
	function calcular_promedio($alumno, $evaluaciones)
	{
		return guarani::co('co_evaluaciones')->calcular_promedio_ultimas_notas($alumno, $evaluaciones);
	}	
}
?>