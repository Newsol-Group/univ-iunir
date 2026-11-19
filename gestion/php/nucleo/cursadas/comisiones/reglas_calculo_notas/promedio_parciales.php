<?php
class promedio_parciales extends promedio_evaluaciones
{
	function calcular_promedio($alumno, $evaluaciones)
	{
		return guarani::co('co_evaluaciones')->calcular_promedio_parciales($alumno, $evaluaciones);
	}
}
?>