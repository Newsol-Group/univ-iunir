<?php
class _regla_test_pto_control_9 extends regla
{
	function validar()
	{
		// Regla que falla siempre. Usado para testear la ejecución del Punto de Control 9.
		return $this->resultado_final(false);
	}
}
?>