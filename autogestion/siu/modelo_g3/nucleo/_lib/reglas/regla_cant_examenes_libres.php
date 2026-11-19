<?php
class regla_cant_examenes_libres extends regla
{
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$actividad = $this->get_parametro('elemento');
		$llamado_mesa = $this->get_parametro('llamado_mesa');

		$datos_alumno = current(guarani::alumno()->get_datos_alumno($alumno));
		$plan = $datos_alumno['plan'];
        
		// Obtengo la fecha del examen para buscar examenes desaprobados anteriores a la fecha del examen.
		$datos_llamado_mesa = toba::consulta_php('co_mesas_de_examen')->get_datos_mesa_llamados($llamado_mesa);
		$fecha_examen = $datos_llamado_mesa['fecha'];
		
		$cant_examenes_desap = guarani::co('co_alumnos')->get_cant_examenes_libre_desaprobados($alumno, $actividad, $fecha_examen);
		$cant_veces_permitido = guarani::parametros()->get_valor_en_reglas('exa_cant_veces_rendir_examen_libre', $plan);

		$resultado = $this->resultado_final($cant_examenes_desap < $cant_veces_permitido);

		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $cant_veces_permitido), true);
		}
		return $resultado;
	}

}
?>