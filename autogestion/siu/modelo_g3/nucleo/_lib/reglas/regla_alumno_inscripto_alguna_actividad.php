<?php
class regla_alumno_inscripto_alguna_actividad extends regla
{
	/**
	 * Verifica si el alumno tiene alguna inscripcion a actividad en periodos lectivos vigentes en su propuesta.
	 */
	function validar()
	{
		$alumno = $this->get_parametro('alumno');

		// Busco inscripciones a actividades en periodos lectivos que no finalizaron.
		$sql = "SELECT COUNT(1) as cant
				   FROM sga_insc_cursada
				   JOIN vw_comisiones ON vw_comisiones.comision = sga_insc_cursada.comision
				  WHERE sga_insc_cursada.alumno = $alumno 
				    AND CURRENT_DATE <= vw_comisiones.fecha_fin
				";   
		$datos = guarani_db::consultar_fila($sql);
		
		$tiene_inscripciones = $datos['cant'] > 0;
		$resultado = $this->resultado_final($tiene_inscripciones);
		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno), true);
		}
		return $resultado;
	}

}
?>