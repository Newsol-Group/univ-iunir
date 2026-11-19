<?php
/**
 * Ejecución de comandos SQL que producen cambios en la base de negocio.
 * 
 * @package 	Matrículas
 * @subpackage 	Personas
 * 
 */
class act_alumnos_nucleo
{
	//------------------------------------------------------------------------
	//-- Alumno
	//------------------------------------------------------------------------

	/**
	 * Actualiza la calidad de un alumno.
	 *
	 * @param integer $alumno
	 * @param integer $calidad
	 */
	function actualizar_calidad_alumno($alumno, $calidad)
	{
		$alumno = toba::db()->quote($alumno);
		$calidad = toba::db()->quote($calidad);

		$sql = "UPDATE	sga_alumnos
				SET		calidad = $calidad
				WHERE	alumno = $alumno
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó la calidad del alumno con éxito. ************');
	}

	/**
	 * Actualiza el plan de un alumno.
	 *
	 * @param integer $alumno
	 * @param integer $plan_version
	 */
	function actualizar_plan_alumno($alumno, $plan_version)
	{
		if (is_array($alumno)) {
			$where = guarani_sql::armar_condicion_in($alumno, 'sga_alumnos', 'alumno');
		} else {
			$alumno = toba::db()->quote($alumno);
			$where = "alumno = $alumno";
		}
		$plan_version = toba::db()->quote($plan_version);

		$sql = "UPDATE	sga_alumnos
				SET		plan_version = $plan_version
				WHERE	$where
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó el plan del alumno con éxito. ************');
	}
	
	/**
	 * Actualiza la modalidad de un alumno.
	 *
	 * @param integer $alumno
	 * @param integer $modalidad
	 */
	function actualizar_modalidad_alumno($alumno, $modalidad)
	{
		$alumno = toba::db()->quote($alumno);
		$modalidad = toba::db()->quote($modalidad);

		$sql = "UPDATE	sga_alumnos
				SET		modalidad = $modalidad
				WHERE	alumno = $alumno
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó la modalidad del alumno con éxito. ************');
	}

	/**
	 * Actualiza la ubicación de un alumno.
	 *
	 * @param integer $alumno
	 * @param integer $ubicacion
	 */
	function actualizar_ubicacion_alumno($alumno, $ubicacion)
	{
		$alumno = toba::db()->quote($alumno);
		$ubicacion = toba::db()->quote($ubicacion);

		$sql = "UPDATE	sga_alumnos
				SET		ubicacion = $ubicacion
				WHERE	alumno = $alumno
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó la ubicación del alumno con éxito. ************');
	}

	/**
	 * Elimina un alumno.
	 *
	 * @param integer	$alumno
	 */
	function eliminar_alumno($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "DELETE
				FROM	sga_alumnos
				WHERE	alumno = $alumno
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminó el alumno con éxito. ************');
	}	

	/**
	 * Inserta un alumno.
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
								 plan_version,
								 ubicacion,
								 calidad,
								 modalidad)
				VALUES		($persona,
							 $propuesta,
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

	/**
	 * Actualiza los datos de un alumno.
	 *
	 * @param integer $alumno ID. del alumno
	 * @param integer $cnt_readmisiones
	 * @param integer $regular
	 */
	function actualizar_alumno($alumno, $cnt_readmisiones, $regular)
	{
		$alumno = toba::db()->quote($alumno);
		$cnt_readmisiones = toba::db()->quote($cnt_readmisiones);
		$regular = toba::db()->quote($regular);

		$sql = "UPDATE	sga_alumnos
				SET		cnt_readmisiones = $cnt_readmisiones , regular = $regular 
				WHERE	alumno = $alumno
		";
		guarani_db::ejecutar($sql);
		
		toba::logger()->info('************ Se actualizaron los datos del alumno con éxito. ************');
	}	
	
	//------------------------------------------------------------------------
	//-- Cambios de Calidad
	//------------------------------------------------------------------------

	/**
	 * Inserta un cambio de calidad para un alumno.
	 *
	 * @param integer $alumno
	 * @param integer $calidad
	 * @param str $motivo_calidad
	 */
	function insertar_cambio_calidad($alumno, $calidad, $motivo_calidad, $fecha='now()', $observaciones=null, $documento=null)
	{
		$alumno	 = toba::db()->quote($alumno);
		$calidad = toba::db()->quote($calidad);
		$motivo_calidad = toba::db()->quote($motivo_calidad);
		$fecha = toba::db()->quote($fecha);
		$observaciones = is_null($observaciones) ? 'null' : toba::db()->quote($observaciones);
		$documento = is_null($documento) ? 'null' : toba::db()->quote($documento);

		$sql = "INSERT INTO	sga_alumnos_hist_calidad
							(alumno,calidad,motivo_calidad,fecha,observaciones,documento)
				VALUES		($alumno,$calidad,$motivo_calidad,$fecha,$observaciones,$documento)
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el cambio de calidad del alumno con éxito. ************');
	}

	/**
	 * Elimina los cambios de calidad de un alumno.
	 * Opcionalmente se puede indicar que considere sólo aquellos iguales o posteriores a una fecha dada.
	 *
	 * @param integer	$alumno
	 * @param date		$fecha_desde
	 */
	function eliminar_cambios_calidad($alumno, $fecha_desde=null)
	{
		$alumno = toba::db()->quote($alumno);
		$where = '';
		if (! empty($fecha_desde)) {
			$fecha_desde = toba::db()->quote($fecha_desde);
			$where = " AND fecha >= $fecha_desde";
		}
		$sql = "DELETE
				FROM	sga_alumnos_hist_calidad
				WHERE	alumno = $alumno
						$where
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los cambios de calidad del alumno con éxito. ************');
	}

	//------------------------------------------------------------------------
	//-- Cambios de Plan
	//------------------------------------------------------------------------

	/**
	 * Inserta un cambio de plan para un alumno.
	 *
	 * @param integer $alumno
	 * @param integer $plan_version
	 * @param str $motivo_plan
	 */
	function insertar_cambio_plan($alumno, $plan_version, $motivo_plan, $fecha='now()', $tipo=alumno::cambio_plan_tipo_inscripcion, $documento=null, $observaciones='')
	{
		$alumno	 = toba::db()->quote($alumno);
		$plan_version = toba::db()->quote($plan_version);
		$motivo_plan = toba::db()->quote($motivo_plan);
		$fecha = toba::db()->quote($fecha);
		$tipo = toba::db()->quote($tipo);
		$documento = is_null($documento) ? 'null' : toba::db()->quote($documento);
		$observaciones = toba::db()->quote($observaciones);

		$sql = "INSERT INTO	sga_alumnos_hist_planes
							(alumno,
							 plan_version,
							 motivo_plan,
							 fecha,
							 tipo,
							 documento,
							 observaciones)
				VALUES		($alumno,$plan_version, $motivo_plan, $fecha, $tipo, $documento, $observaciones)
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el cambio de plan del alumno con éxito. ************');
	}

	/**
	 * Elimina los cambios de plan de un alumno.
	 * Opcionalmente se puede indicar que considere sólo aquellos iguales o posteriores a una fecha dada.
	 *
	 * @param integer	$alumno
	 * @param date		$fecha_desde
	 */
	function eliminar_cambios_plan($alumno, $fecha_desde=null)
	{
		$alumno = toba::db()->quote($alumno);
		$where = '';
		if (! empty($fecha_desde)) {
			$fecha_desde = toba::db()->quote($fecha_desde);
			$where = " AND fecha >= $fecha_desde";
		}
		$sql = "DELETE
				FROM	sga_alumnos_hist_planes
				WHERE	alumno = $alumno
						$where
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los cambios de plan del alumno con éxito. ************');
	}

	//------------------------------------------------------------------------
	//-- Cambios de Modalidad
	//------------------------------------------------------------------------

	/**
	 * Inserta un cambio de modalidad para un alumno.
	 *
	 * @param integer $alumno
	 * @param integer $plan_version
	 * @param integer $modalidad
	 * @param str $motivo_modalidad
	 */
	function insertar_cambio_modalidad($alumno, $modalidad, $plan_version, $motivo_modalidad, $fecha='now()')
	{
		$alumno	 = toba::db()->quote($alumno);
		$plan_version = toba::db()->quote($plan_version);
		$modalidad = toba::db()->quote($modalidad);
		$motivo_modalidad = toba::db()->quote($motivo_modalidad);
		$fecha = toba::db()->quote($fecha);

		$sql = "INSERT INTO	sga_alumnos_hist_modalidad
							(alumno,
							 modalidad,
							 plan_version,
							 motivo_modalidad,
							 fecha)
				VALUES		($alumno,$modalidad,$plan_version,$motivo_modalidad,$fecha)
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el cambio de modalidad del alumno con éxito. ************');
	}

	/**
	 * Elimina los cambios de modalidad de un alumno.
	 * Opcionalmente se puede indicar que considere sólo aquellos iguales o posteriores a una fecha dada.
	 *
	 * @param integer	$alumno
	 * @param date		$fecha_desde
	 */
	function eliminar_cambios_modalidad($alumno, $fecha_desde=null)
	{
		$alumno = toba::db()->quote($alumno);
		$where = '';
		if (! empty($fecha_desde)) {
			$fecha_desde = toba::db()->quote($fecha_desde);
			$where = " AND fecha >= $fecha_desde";
		}
		$sql = "DELETE
				FROM	sga_alumnos_hist_modalidad
				WHERE	alumno = $alumno
						$where
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los cambios de modalidad del alumno con éxito. ************');
	}

	//------------------------------------------------------------------------
	//-- Cambios de Ubicación
	//------------------------------------------------------------------------

	/**
	 * Inserta un cambio de ubicacion para un alumno.
	 *
	 * @param integer $alumno
	 * @param integer $ubicacion
	 * @param str $motivo_ubicacion
	 */
	function insertar_cambio_ubicacion($alumno, $ubicacion, $motivo_ubicacion, $fecha='now()')
	{
		$alumno	 = toba::db()->quote($alumno);
		$plan_version = toba::db()->quote($ubicacion);
		$motivo_plan = toba::db()->quote($motivo_ubicacion);
		$fecha = toba::db()->quote($fecha);

		$sql = "INSERT INTO	sga_alumnos_hist_ubicacion
							(alumno,
							 ubicacion,
							 motivo_ubicacion,
							 fecha)
				VALUES		($alumno,$ubicacion,$motivo_ubicacion,$fecha)
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el cambio de ubicacion del alumno con éxito. ************');
	}

	/**
	 * Elimina los cambios de ubicación de un alumno.
	 * Opcionalmente se puede indicar que considere sólo aquellos iguales o posteriores a una fecha dada.
	 *
	 * @param integer	$alumno
	 * @param date		$fecha_desde
	 */
	function eliminar_cambios_ubicacion($alumno, $fecha_desde=null)
	{
		$alumno = toba::db()->quote($alumno);
		$where = '';
		if (! empty($fecha_desde)) {
			$fecha_desde = toba::db()->quote($fecha_desde);
			$where = " AND fecha >= $fecha_desde";
		}
		$sql = "DELETE
				FROM	sga_alumnos_hist_ubicacion
				WHERE	alumno = $alumno
						$where
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los cambios de ubicación del alumno con éxito. ************');
	}
	
	//------------------------------------------------------------------------
	//-- Actualización de legajo
	//------------------------------------------------------------------------
	
	function actualizar_legajo($alumno, $legajo)
	{
		$alumno = toba::db()->quote($alumno);
		$legajo = toba::db()->quote($legajo);
		
		$sql = "UPDATE	sga_alumnos
				SET		legajo = $legajo
				WHERE	alumno = $alumno
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó el legajo del alumno con éxito. ************');
	}
	
	//------------------------------------------------------------------------
	//-- Actualización de la regularidad
	//------------------------------------------------------------------------
	
	function actualizar_regularidad($alumno, $regular)
	{
		$alumno = toba::db()->quote($alumno);
		$regular = toba::db()->quote($regular);

		$sql = "UPDATE	sga_alumnos
				SET		regular = $regular 
				WHERE	alumno = $alumno
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó la regularidad del alumno con éxito. ************');
	}
	
	//------------------------------------------------------------------------
	//-- Inserción de Readmisión
	//------------------------------------------------------------------------
	
	function insertar_readmision($perdida_regularidad, $anio_academico, $tipo, $contabiliza, $fecha, $fecha_vencimiento)
	{
		$perdida_regularidad = toba::db()->quote($perdida_regularidad);
		$anio_academico = toba::db()->quote($anio_academico);
		$tipo = toba::db()->quote($tipo);
		$contabiliza = toba::db()->quote($contabiliza);
		$fecha = toba::db()->quote($fecha);
		$fecha_vencimiento = toba::db()->quote($fecha_vencimiento);
		//$estado = toba::db()->quote($estado);
		
		$sql = "INSERT INTO sga_readmisiones
				(
					perdida_regularidad,
					anio_academico,
					tipo,
					contabiliza,
					fecha,
					fecha_vencimiento
				) 
				VALUES 
				(
					$perdida_regularidad,
					$anio_academico,
					$tipo,
					$contabiliza,
					$fecha,
					$fecha_vencimiento
				)";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó la readmisión del alumno con éxito. ************');
	}
	
	//------------------------------------------------------------------------
	//-- Actualización de cantidad Readmisiones
	//------------------------------------------------------------------------
	
	function actualizar_cant_readmisiones($alumno, $cnt_readmisiones)
	{
		$alumno = toba::db()->quote($alumno);
		$cnt_readmisiones = toba::db()->quote($cnt_readmisiones);
		
		$sql = "UPDATE	sga_alumnos
				SET		cnt_readmisiones = $cnt_readmisiones
				WHERE	alumno = $alumno
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó la cantidad de readmisiones del alumno con éxito. ************');
	}
	
	//------------------------------------------------------------------------
	//-- Movimientos de Historia Académica
	//------------------------------------------------------------------------
	
	function insertar_movimiento_ha($alumno, $motivo_movimiento, $documento=null, $id_acta=null, $equivalencia=null, $reconocimiento_act, $evaluacion=null, $observaciones=null, $usuario_responsable=null)
	{
		if (is_null($usuario_responsable)) {
			$usuario_responsable = toba::usuario()->get_id();
		}
		$datos_persona = toba::consulta_php('co_personas')->get_persona_x_usuario($usuario_responsable);
		$responsable = toba::db()->quote($datos_persona['persona']);

		$alumno = toba::db()->quote($alumno);
		$motivo_movimiento = toba::db()->quote($motivo_movimiento);

		$documento = is_null($documento) ? 'null' : toba::db()->quote($documento);
		$id_acta = is_null($id_acta) ? 'null' : toba::db()->quote($id_acta);
		$equivalencia =  is_null($equivalencia) ? 'null' : toba::db()->quote($equivalencia);
		$reconocimiento_act =  is_null($reconocimiento_act) ? 'null' : toba::db()->quote($reconocimiento_act);
		$evaluacion =  is_null($evaluacion) ? 'null' : toba::db()->quote($evaluacion);
		$observaciones =  is_null($observaciones) ? 'null' : toba::db()->quote($observaciones);

		$sql = "INSERT INTO sga_movimientos_ha
				(
					motivo_movimiento,
					equivalencia,
					id_acta,
					reconocimiento_act,
					evaluacion,
					alumno,
					documento,
					responsable,
					observaciones
				) 
				VALUES 
				(
					$motivo_movimiento,
					$equivalencia,
					$id_acta,
					$reconocimiento_act,
					$evaluacion,
					$alumno,
					$documento,
					$responsable,
					$observaciones
				);";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el movimiento de historia académica del alumno con éxito. ************');
	}

	//------------------------------------------------------------------------
	//-- Optativas
	//------------------------------------------------------------------------

	function insertar_optativa($alumno, $plan_version, $optativa, $generica)
	{
		$alumno = toba::db()->quote($alumno);
		$plan_version = toba::db()->quote($plan_version);
		$optativa = toba::db()->quote($optativa);
		$generica = toba::db()->quote($generica);
		
		$sql = "
			INSERT INTO sga_alumnos_optativas (alumno, plan_version, optativa, generica) 
			VALUES ($alumno, $plan_version, $optativa, $generica)
        ";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se asigno la optativa a la genérica con éxito con éxito. ************');
		
	}
	
	function actualizar_optativa($alumno, $plan_version, $optativa, $generica)
	{
		$alumno = toba::db()->quote($alumno);
		$plan_version = toba::db()->quote($plan_version);
		$optativa = toba::db()->quote($optativa);
		$generica = toba::db()->quote($generica);
		
		$sql = "
				UPDATE 
					sga_alumnos_optativas 
				SET 
					generica = $generica
				WHERE 
					sga_alumnos_optativas.alumno = $alumno
					AND sga_alumnos_optativas.plan_version = $plan_version
					AND sga_alumnos_optativas.optativa = $optativa
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó la optativa a la genérica con éxito con éxito. ************');
	}
	
	function eliminar_optativa($alumno, $plan_version, $optativa)
	{
		$alumno = toba::db()->quote($alumno);
		$plan_version = toba::db()->quote($plan_version);
		$optativa = toba::db()->quote($optativa);
		$sql = "
				DELETE FROM 
					sga_alumnos_optativas 
				WHERE
					alumno = $alumno
					AND plan_version = $plan_version
					AND optativa = $optativa
				";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminó la optativa a la genérica con éxito con éxito. ************');
	}

	//------------------------------------------------------------------------
	//-- Coeficiente de inscripción
	//------------------------------------------------------------------------

	/**
	 * Actualiza el coeficiente de inscripción de un alumno.
	 *
	 * @param int $alumno
	 * @param decimal $coeficiente
	 */
	function actualizar_coeficiente_inscripcion($alumno, $coeficiente=null)
	{
		$alumno = toba::db()->quote($alumno);
		$coeficiente = is_null($coeficiente) ? 'null' : toba::db()->quote($coeficiente);
		$sql = "UPDATE	sga_alumnos
				SET		coeficiente = $coeficiente
				WHERE	alumno = $alumno
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó la calidad del alumno con éxito. ************');
	}
	
	//------------------------------------------------------------------------
	//-- Suplemento al título
	//------------------------------------------------------------------------

	function insertar_suplemento($nro_solicitud, $tipo_suplemento, $texto, $nro_disposicion)
	{
		$sql = "
			INSERT INTO sga_certificados_otorg_anexo (nro_solicitud, tipo_suplemento, texto, nro_disposicion, orden) 
				VALUES ($nro_solicitud, $tipo_suplemento, '$texto', $nro_disposicion, 1)
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info("************ Se insertó un suplemento al título de tipo $tipo_suplemento con éxito. ************");
	}
}
?>