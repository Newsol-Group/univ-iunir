<?php
class regla_encuestas_obligatorias extends regla
{
	function validar()
	{
		$persona = $this->get_parametro('persona');
		$cant_encuestas = toba::consulta_php('co_encuestas')->get_cant_encuestas_no_contestadas($persona);
		$resultado = $this->resultado_final($cant_encuestas == 0);
		if (!$resultado) {
			$nombre_persona = guarani::personas()->get_nombre_persona($persona);
			$this->set_parametros_mensaje_validacion(array($nombre_persona), true);
		}
		return $resultado;
	}

}