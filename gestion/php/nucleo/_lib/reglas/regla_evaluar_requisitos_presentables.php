<?php
class regla_evaluar_requisitos_presentables extends regla
{
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$requisito = $this->get_parametro('requisito');
		$fecha = $this->get_parametro('fecha');
		
		// Verifica si el alumno tiene el requisito presentado o exceptuado o si depende de otro requisito que esta presentado/exceptuado.
		$cumple_requisito = toba::consulta_php('co_personas')->cumple_requisito_documental($alumno, $requisito, $fecha);

		$resultado = $this->resultado_final($cumple_requisito);
		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$nombre_requisito = toba::consulta_php('co_requisitos')->get_requisito($requisito);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $nombre_requisito), true);
		}
		return $resultado;
	}

}
?>