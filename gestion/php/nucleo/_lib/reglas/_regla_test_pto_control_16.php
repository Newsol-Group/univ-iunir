<?php
class _regla_test_pto_control_16 extends regla
{
	function validar()
	{
		// Regla que falla siempre. Usado para testear la ejecución del Punto de Control 16.
		return $this->resultado_final(false);
	}
}
?>