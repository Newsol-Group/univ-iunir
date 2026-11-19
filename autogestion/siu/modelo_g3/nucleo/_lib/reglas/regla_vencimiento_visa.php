<?php
class regla_vencimiento_visa extends regla
{
	function validar()
	{
		$persona = $this->get_parametro('persona');
		$fecha = $this->get_parametro('fecha');
		$tiene_visa_vencida = guarani::co('co_personas')->tiene_visa_vencida($persona, $fecha);
		$resultado = $this->resultado_final(!$tiene_visa_vencida);
		if (!$resultado) {
			$nombre_persona = guarani::personas()->get_nombre_persona($persona);
			$this->set_parametros_mensaje_validacion(array($nombre_persona), true);
		}
		return $resultado;
	}

}