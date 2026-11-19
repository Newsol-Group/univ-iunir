<?php
class regla_alumno_existe_en_otra_mesa_examen_misma_fecha extends regla
{
	function validar()
	{
		// -------------------------------------------------------------------------------------
		// Verifica que el alumno NO este inscripto en otra mesa de examen (de cualquier actividad) en la misma fecha.
		// -------------------------------------------------------------------------------------
		$alumno       = $this->get_parametro('alumno');
		$llamado_mesa = $this->get_parametro('llamado_mesa');

		
		// Verifica inscripciones a examen en la misma fecha de la mesa en que se esta inscribiendo.
		// En cualquiera de las propuestas del alumno.
		$sql = "SELECT to_char(vw_mesas_examen.mesa_examen_fecha,'DD/MM/YYYY') as fecha
		          FROM sga_alumnos
				  JOIN vw_insc_examen ON vw_insc_examen.alumno = sga_alumnos.alumno
                  JOIN vw_mesas_examen ON vw_mesas_examen.llamado_mesa = vw_insc_examen.llamado_mesa				  
				WHERE sga_alumnos.persona IN (SELECT a.persona FROM sga_alumnos as a WHERE a.alumno = $alumno) 
				  AND vw_mesas_examen.mesa_examen_fecha IN (SELECT fecha FROM sga_llamados_mesa WHERE llamado_mesa = $llamado_mesa)
				LIMIT 1;";
		$rs  = guarani_db::consultar_fila($sql, null, false);
		
		$resultado = $this->resultado_final(empty($rs));

		if (!$resultado) {
			// Obtengo la fecha de la mesa de examen.
			$fecha_examen = $rs['fecha'];
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $fecha_examen), true);
		}
		return $resultado;
	}

}
?>