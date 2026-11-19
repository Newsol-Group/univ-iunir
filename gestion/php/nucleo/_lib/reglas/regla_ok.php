<?php
class regla_ok extends regla
{
	function validar()
	{
		// Regla que nunca falla.
		// Para utilizar en requisitos que se necesita que nunca fallen al validarse su regla de cumplimiento.
		return $this->resultado_final(true);
	}

}
?>