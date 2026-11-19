<?php
class regla_vencimiento_residencia extends regla
{
	function validar()
	{
		$persona = $this->get_parametro('persona');
		$fecha   = $this->get_parametro('fecha');
		
		// Verifica si el alumno es extranjero y si tiene la residencia de tipo precaria o temporal vencida a la fecha pasada por parametro.
		$residencia_vencida = guarani::co('co_personas')->tiene_residencia_vencida($persona, $fecha);
		
		$resultado = $this->resultado_final(!$residencia_vencida);
		if (!$resultado) {
			$nombre_persona = guarani::personas()->get_nombre_persona($persona);
			$sql  = "SELECT to_char(vencimiento_residencia,'DD/MM/YYYY') as fecha_vencimiento FROM mdp_personas_extranjeros WHERE persona = $persona";
			$venc = guarani_db::consultar_fila($sql);			
			$this->set_parametros_mensaje_validacion(array($nombre_persona, $venc['fecha_vencimiento']), true);
		}
		return $resultado;
	}

}	