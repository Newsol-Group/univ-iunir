<?php
class regla_error extends regla
{
	function validar()
	{
		// Regla que falla siempre.
		// Para utilizar en requisitos que se necesita que den error cuando se evalue su cumplimiento.
		return $this->resultado_final(false);
	}

}
?>