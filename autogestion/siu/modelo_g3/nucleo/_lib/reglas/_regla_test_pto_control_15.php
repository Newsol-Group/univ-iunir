<?php
class _regla_test_pto_control_15 extends regla
{
	function validar()
	{
		// Regla que falla siempre. Usado para testear la ejecución del Punto de Control 15.
		return $this->resultado_final(false);
	}
}
?>