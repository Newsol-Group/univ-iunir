<?php 
  class act_alumnos extends act_alumnos_nucleo 
{ 
	/**
	 * Newsol (01/2025) - Se agrega el año de cursada en 1
	 *
	 * @param integer $persona
	 * @param integer $calidad
	 * @param integer $propuesta
	 * @param integer $plan_version
	 * @param integer $ubicacion
	 * @param integer $modalidad
	 * @return integer Id. del alumno recien ingresado
	 */
	function insertar_alumno($persona, $propuesta, $plan_version, $ubicacion, $calidad, $modalidad)
	{
		$persona = toba::db()->quote($persona);
		$calidad = toba::db()->quote($calidad);
		$propuesta = toba::db()->quote($propuesta);
		$plan_version = toba::db()->quote($plan_version);
		$ubicacion = toba::db()->quote($ubicacion);
		$modalidad = toba::db()->quote($modalidad);	

		$sql = "INSERT INTO	sga_alumnos
								(persona,
								 propuesta,
								 anio_cursada,
								 plan_version,
								 ubicacion,
								 calidad,
								 modalidad)
				VALUES		($persona,
							 $propuesta,
							 1,
							 $plan_version,
							 $ubicacion,
							 $calidad,
							 $modalidad)
		";

		guarani_db::ejecutar($sql);
		
		toba::logger()->info('************ Se insertó el alumno con éxito. ************');

		$alumno = toba::db()->recuperar_secuencia('sga_alumnos_seq');
		return $alumno;			
	}

} 
?>