<?php
class regla_controlar_orientacion_elegida extends regla
{
	function validar()
	{
		/*
		  Regla que verifica que el alumno cumpla las orientaaciones elegidas.
		  La regla se cumple ademas si el plan no tiene orientaciones o tiene pero el alumno no eligió orientacion.
		*/
	
		// Parámetros de contexto
		$alumno = $this->get_parametro('alumno');
		$fecha = $this->get_parametro('fecha');

		// Se obtiene la versión del plan del alumno. Se obtiene del parametro y si no viene se obtiene la version de plan actual del alumno.
		//$plan_version = $this->existe_parametro('plan_version') ? $this->get_parametro('plan_version') : guarani::alumno()->get_plan_version($alumno, $fecha);

		// Recupero orientaciones elegidas por el alumno y su forma de cumplimiento.
		$sql = "SELECT sga_alumnos_orient.orientacion as orientacion,
				sga_elementos_plan.nombre as orientacion_nombre,
				sga_elementos_revision.regla as regla,
				sga_elementos_revision.parametros as parametros,
				sga_alumnos.persona as persona,
				sga_alumnos.propuesta as propuesta,
				sga_alumnos.plan_version as plan_version
			FROM sga_alumnos 
			JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = sga_alumnos.plan_version
			JOIN sga_alumnos_orient ON sga_alumnos_orient.alumno = sga_alumnos.alumno AND sga_alumnos_orient.plan = sga_planes_versiones.plan
			JOIN sga_elementos_revision ON sga_elementos_revision.elemento = sga_alumnos_orient.orientacion
			JOIN sga_elementos_plan ON sga_elementos_plan.plan_version = sga_alumnos.plan_version AND sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision
			WHERE sga_alumnos.alumno = $alumno ";

		$orientaciones_elegidas = guarani_db::consultar($sql);

		if (empty($orientaciones_elegidas)) {
		   // El alumno no eligió orientacion. Salgo ok.
		   return true;
		}

		// Recorro las orientaciones elegidas y las evaluo. Si falla alguna salgo sin cumplir la regla.
		$orientacion_cumplida = true;
		foreach ($orientaciones_elegidas as $ori) {

			// Recupero datos de la orientacion para evaluarla.
			$orientacion = $ori['orientacion'];
			$orientacion_nombre = $ori['orientacion_nombre'];
			$operador_not = false;
			$persona = $ori['persona'];
			$propuesta = $ori['propuesta'];
			$plan_version = $ori['plan_version'];

			$aprobacion_por_resolucion = false;
			$aprobado_por_resolucion = toba::consulta_php('co_aprobacion_por_resolucion')->get_reconocimiento_alumno_modulo($alumno, $orientacion);

			if (!empty($aprobado_por_resolucion)) {
				// La orientación fue reconocida por resolucion. Continuo con la proxima orientacion
				continue;
			}
			
			// Evaluo la regla de cumplimiento de la orientacion
			$regla = guarani::regla($ori['regla']);
			$regla->set_parametros($ori['parametros']);
			$regla->set_operador_not($operador_not);
			$regla->set_parametros_contexto(array(
								'alumno' => $alumno,
								'persona' => $persona,
								'propuesta' => $propuesta,
								'plan_version' => $plan_version,
								'elemento' => $orientacion,
								// 'elemento_inscripcion' => ,  
								// 'requisito' => ,
								'fecha' => $fecha));
			// Verifico el cumplimiento del requisito
			$req_valido = $regla->validar();
					
			// ---------------------------------------------------------------------------------------------
			// Si no se cumplio la regla de cumplimiento de la orientacion, salgo.
			// ---------------------------------------------------------------------------------------------
			if (!$req_valido) {
				$orientacion_cumplida = false;
				break;
			}
			
		} 


		// Esta OK si el alumno cumplio con las orientaciones elegidas
		$resultado = $this->resultado_final($orientacion_cumplida);
		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $orientacion_nombre), true);
		}
		return $resultado;
	}

}
?>
