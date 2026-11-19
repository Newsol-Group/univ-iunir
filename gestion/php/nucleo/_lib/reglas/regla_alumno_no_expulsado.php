<?php
class regla_alumno_no_expulsado extends regla
{
	function validar()
	{
		$persona = $this->get_parametro('persona');
		$es_persona_expulsada = guarani::co('co_personas')->es_persona_expulsada($persona);
		$resultado = $this->resultado_final(!$es_persona_expulsada);
		if (!$resultado) {
			$nombre_persona = guarani::personas()->get_nombre_persona($persona);
			$this->set_parametros_mensaje_validacion(array($nombre_persona), true);
		}
		return $resultado;
	}

}