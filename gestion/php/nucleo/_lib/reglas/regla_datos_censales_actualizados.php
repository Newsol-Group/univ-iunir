<?php
class regla_datos_censales_actualizados extends regla
{
	function validar()
	{
		$persona = $this->get_parametro('persona');
		$fecha = $this->get_parametro('fecha');
		$cant_meses = guarani::parametros()->get_valor_en_reglas('cant_meses_actualizar_datos_censales');
		$tiene_datos_censales_actualizados = guarani::co('co_personas')->tiene_datos_censales_actualizados($persona, $fecha, $cant_meses);
		$resultado = $this->resultado_final($tiene_datos_censales_actualizados);
		if (!$resultado) {
			$nombre_persona = guarani::personas()->get_nombre_persona($persona);
			$this->set_parametros_mensaje_validacion(array($nombre_persona), true);
		}
		return $resultado;
	}

}