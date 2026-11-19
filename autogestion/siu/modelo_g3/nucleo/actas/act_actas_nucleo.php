<?php
/**
 * Ejecución de comandos SQL que producen cambios en la base de negocio.
 *
 *
 * @package Cursadas
 * @subpackage Actas
 */

class act_actas_nucleo
{
	/**
	 * Inserta un nuevo acta.
	 */
	function insertar_acta($nro_acta, $origen, $tipo_acta, $evaluacion, $comision, $renglones_folio, $documento=null, $acta_referencia=null, $observaciones='', $digital=comunes::no)
	{
		$nro_acta = toba::db()->quote($nro_acta);
		$origen = toba::db()->quote($origen);
		$tipo_acta = toba::db()->quote($tipo_acta);
		$observaciones = toba::db()->quote($observaciones);
		$digital = toba::db()->quote($digital);

		$documento = empty($documento) ? 'null' : $documento;
		$acta_referencia = empty($acta_referencia) ? 'null' : $acta_referencia;

		$sql = "INSERT INTO sga_actas
				(
					nro_acta,
					origen,
					tipo_acta,
					evaluacion,
					comision,
					renglones_folio,
					documento,
					acta_referencia,
					observaciones,
					digital
				)
				VALUES
				(
					$nro_acta,
					$origen,
					$tipo_acta,
					$evaluacion,
					$comision,
					$renglones_folio,
					$documento,
					$acta_referencia,
					$observaciones,
					$digital
				)";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el acta con éxito. ************');
	}

	function insertar_acta_instancia($id_acta, $instancia, $escala_nota)
	{
		$sql = "INSERT INTO sga_actas_instancias
				(
					id_acta,
					instancia,
					escala_nota
				)
				VALUES
				(
					$id_acta,
					$instancia,
					$escala_nota
				)";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó la instancia del acta con éxito. ************');
	}

	function actualizar_eval_detalle_examen($evaluacion, $alumno, $id_acta)
	{
		$evaluacion = toba::db()->quote($evaluacion);
		$alumno 	= toba::db()->quote($alumno);
		$id_acta 	= toba::db()->quote($id_acta);
		
		$sql = "UPDATE	sga_eval_detalle_examenes
				SET		id_acta 	= $id_acta
				WHERE	evaluacion  = $evaluacion
	            AND		alumno	 	= $alumno
		";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó el detalle examen con éxito. ************');
	}

	function actualizar_eval_detalle_cursada($evaluacion, $alumno, $instancia, $id_acta)
	{
		$evaluacion = toba::db()->quote($evaluacion);
		$alumno 	= toba::db()->quote($alumno);
		$id_acta 	= toba::db()->quote($id_acta);

		$campo_acta = ($instancia == comision::inst_cursar) ? 'id_acta_cursada' : 'id_acta_promocion';

		$sql = "UPDATE	sga_eval_detalle_cursadas
				SET		$campo_acta = $id_acta
				WHERE	evaluacion  = $evaluacion
	            AND		alumno	 	= $alumno
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó el detalle de cursadas con éxito. ************');
	}

	function cerrar_eval_detalle_cursada($evaluacion, $alumno, $instancia = comision::inst_cursar)
	{
		$evaluacion = toba::db()->quote($evaluacion);
		$alumno = toba::db()->quote($alumno);

		if($instancia == comision::inst_cursar) {
			$columna = 'acta_cursada_cerrada';
		}
		elseif ($instancia == comision::inst_promocionar){
			$columna = 'acta_promocion_cerrada';
		}

		$sql = "UPDATE	sga_eval_detalle_cursadas
				SET		$columna = 1
				WHERE	evaluacion = $evaluacion AND
						alumno = $alumno
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se cerró el detalle de cursadas con éxito. ************');
	}

	/**
	 * Inserta las instancias del acta (id_acta) correspondientes a las instancias de la mesa de examen $mesa_examen
	 *
	 * @param <type> $id_acta
	 * @param <type> $mesa_examen
	 */
	function insertar_acta_instancias_de_mesa($id_acta, $mesa_examen)
	{
		$sql = "INSERT INTO sga_actas_instancias (id_acta, instancia,escala_nota)
				
				SELECT	$id_acta,
						instancia,
						escala_nota
				FROM	sga_mesas_examen_instancias
				WHERE	sga_mesas_examen_instancias.mesa_examen = $mesa_examen

				";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertaron la instancias del acta con éxito. ************');
	}

	/**
	 * Anula un acta.
	 */
	function anular_acta($id_acta, $observaciones='')
	{
		$estado_anulada = toba::db()->quote(acta::estado_anulada);
		$observaciones = toba::db()->quote($observaciones);

		$sql = "UPDATE 	sga_actas
				SET 	fecha_anulacion = now(), 
						estado = $estado_anulada,
						observaciones = $observaciones 
				WHERE 	id_acta = $id_acta
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se anuló el acta con éxito. ************');
	}

	/**
	 * Cierra un acta.
	 */
	function cerrar_acta($id_acta)
	{
		$estado_cerrada = toba::db()->quote(acta::estado_cerrada);

		$sql = "UPDATE 	sga_actas
				SET 	fecha_cierre = now(),
				        estado = $estado_cerrada
				WHERE 	id_acta = $id_acta
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se cerró el acta con éxito. ************');
	}

	/**
	 * Inserta un nuevo detalle de acta.
	 *
	 * @param array $detalle arreglo asociativo con los datos del detalle (algunos pueden ser nulos).
	 */
	function insertar_acta_detalle($detalle)
	{
		$id_acta = $detalle['id_acta'];
		$alumno = $detalle['alumno'];
		$instancia = $detalle['instancia'];
		$plan_version = $detalle['plan_version'];

		$fecha = empty($detalle['fecha']) ? 'null' : toba::db()->quote($detalle['fecha']);
		$fecha_vigencia = empty($detalle['fecha_vigencia']) ? 'null' : toba::db()->quote($detalle['fecha_vigencia']);
		$folio = empty($detalle['folio']) ? 1 : $detalle['folio'];
		$renglon = empty($detalle['renglon']) ? 'null' : $detalle['renglon'];
		$pct_asistencia = empty($detalle['pct_asistencia']) ? 0 : $detalle['pct_asistencia'];
		$escala_nota = empty($detalle['escala_nota']) ? 'null' : $detalle['escala_nota'];
		$nota = !isset($detalle['nota']) ? 'null' : toba::db()->quote($detalle['nota']);
		$resultado = empty($detalle['resultado']) ? 'null' : toba::db()->quote($detalle['resultado']);
		$cond_regularidad = empty($detalle['cond_regularidad']) ? 'null' : $detalle['cond_regularidad'];
		$observaciones = empty($detalle['observaciones']) ? 'null' : toba::db()->quote($detalle['observaciones']);
		$estado = empty($detalle['estado']) ? toba::db()->quote(comunes::estado_activo) : toba::db()->quote($detalle['estado']);
		
		$rectificado = toba::db()->quote(comunes::no);

		$sql = "INSERT INTO sga_actas_detalle
				(
					id_acta,
					alumno,
					instancia,
					plan_version,
					fecha,
					fecha_vigencia,
					folio,
					renglon,
					pct_asistencia,
					escala_nota,
					nota,
					resultado,
					cond_regularidad,
					rectificado,
					observaciones,
					estado
				)
				VALUES
				(
					$id_acta,
					$alumno,
					$instancia,
					$plan_version,
					$fecha,
					$fecha_vigencia,
					$folio,
					$renglon,
					$pct_asistencia,
					$escala_nota,
					$nota,
					$resultado,
					$cond_regularidad,
					$rectificado,
					$observaciones,
					$estado		
				)";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el detalle de acta con éxito. ************');
	}

	/**
	 * Inserta un nuevo detalle de acta de examen
	 *
	 * @param array $detalle arreglo asociativo con los datos del detalle (algunos pueden ser nulos)
	 */
	function insertar_acta_detalle_examen($detalle)
	{
		
		$id_acta			= $detalle['id_acta'];
		$alumno				= $detalle['alumno'];
		$instancia			= $detalle['instancia'];
		$plan_version		= $detalle['plan_version'];
		$rectificado		= toba::db()->quote(comunes::no);
		$estado				= toba::db()->quote(comunes::estado_activo);
		$pct_asistencia		= 'null';
		
		$fecha				= empty($detalle['fecha']) ? 'null' : toba::db()->quote($detalle['fecha']);
		$fecha_vigencia 	= empty($detalle['fecha_vigencia']) ? 'null' : toba::db()->quote($detalle['fecha_vigencia']);
		$folio 				= empty($detalle['folio']) ? 1 : $detalle['folio'];
		$renglon 			= empty($detalle['renglon']) ? 'null' : $detalle['renglon'];
		$escala_nota 		= empty($detalle['escala_nota']) ? 'null' : $detalle['escala_nota'];
		$nota 				= !isset($detalle['nota']) ? 'null' : toba::db()->quote($detalle['nota']);
		$resultado 			= empty($detalle['resultado']) ? 'null' : toba::db()->quote($detalle['resultado']);
		$observaciones		= empty($detalle['observaciones']) ? 'null' : toba::db()->quote($detalle['observaciones']);
		
		$sql = "INSERT INTO sga_actas_detalle
				(
					id_acta,
					alumno,
					instancia,
					plan_version,
					fecha,
					fecha_vigencia,
					folio,
					renglon,
					pct_asistencia,
					escala_nota,
					nota,
					resultado,
					rectificado,
					observaciones,
					estado
				)
				VALUES
				(
					$id_acta,
					$alumno,
					$instancia,
					$plan_version,
					$fecha,
					$fecha_vigencia,
					$folio,
					$renglon,
					$pct_asistencia,
					$escala_nota,
					$nota,
					$resultado,
					$rectificado,
					$observaciones,
					$estado
				)";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el detalle de acta de examen con éxito. ************');
	}
	
	/**
	 * Como parte del proceso de cierre de actas de tipo 'Rectificativa',
	 * para cada alumno del acta se marcan como rectificados los detalles del acta original y sus rectificativas cerradas.
	 *
	 * @param integer $alumno			código de alumno.
	 * @param integer $id_acta_original	código de acta original.
	 */
	function rectificar_detalle($alumno, $id_acta_original)
	{
		$si = toba::db()->quote(comunes::si);

		$sql = "UPDATE	sga_actas_detalle
				SET		rectificado = $si
				FROM	vw_actas
				WHERE	sga_actas_detalle.id_acta = vw_actas.id_acta AND
						sga_actas_detalle.alumno = vw_actas.alumno AND
						sga_actas_detalle.alumno = $alumno AND
						vw_actas.id_acta_original = $id_acta_original
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se rectificaron los detalles del acta con éxito. ************');
	}

	function eliminar_eval_detalle_cursada($evaluacion, $alumno)
	{
		$evaluacion = toba::db()->quote($evaluacion);
		$alumno = toba::db()->quote($alumno);

		$sql = "DELETE
				FROM	sga_eval_detalle_cursadas
				WHERE	evaluacion = $evaluacion AND
						alumno = $alumno
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminó el registro de detalle de cursadas con éxito. ************');
	}

	function eliminar_eval_detalle_examen($evaluacion, $alumno)
	{
		$sql = "DELETE
				FROM	sga_eval_detalle_examenes
				WHERE	evaluacion = $evaluacion
				AND		alumno = $alumno
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminó el registro de detalle de exámen con éxito. ************');
	}

	/**
	 * Elimina los registros de eval detalle examen del acta $id_acta , que estan en actas cerradas
	 *
	 * @param <type> $evaluacion
	 * @param <type> $id_acta
	 */
	function eliminar_eval_detalles_examen_en_acta_cerrada($evaluacion, $id_acta)
	{
		$sql = "DELETE
				FROM	sga_eval_detalle_examenes
				WHERE	evaluacion = $evaluacion
				AND		id_acta = $id_acta
				AND     sga_eval_detalle_examenes.alumno  IN (
																	SELECT  sga_actas_detalle.alumno
																	FROM	sga_actas,sga_actas_detalle
																	WHERE   sga_actas.id_acta = sga_actas_detalle.id_acta
																	AND		sga_actas.evaluacion = $evaluacion
																	AND		sga_actas.estado = '".acta::estado_cerrada."'
																	AND		sga_actas_detalle.alumno = sga_eval_detalle_examenes.alumno
																	AND		sga_actas_detalle.estado = '".acta::detalle_estado_activo."'
				)
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminó el registro de detalle de exámen con éxito. ************');
	}

	/**
	 * Elimina los registros de 'sga_eval_detalle_examenes' para el acta recibida como parámetro.
	 * 
	 * @param int $id_acta
	 */
	function eliminar_eval_detalles_examen_acta($id_acta)
	{
		$sql = "DELETE
				FROM	sga_eval_detalle_examenes
				WHERE	id_acta = $id_acta
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los registros del detalle de exámen con éxito. ************');
	}
	
	function desasociar_eval_detalles_examen($evaluacion, $id_acta)
	{
		$sql = "UPDATE	sga_eval_detalle_examenes
				SET		id_acta = null
				WHERE	evaluacion = $evaluacion
	            AND		id_acta = $id_acta
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se desasoció el registro de detalle de exámen con éxito. ************');
	}
	
	function invalidar_detalle($id_acta, $alumno)
	{
		$id_acta = toba::db()->quote($id_acta);
		$alumno = toba::db()->quote($alumno);
		$estado_invalidado = toba::db()->quote(acta::detalle_estado_invalidado); 
		
		$sql = "UPDATE 	sga_actas_detalle  
				SET 	estado = $estado_invalidado
				WHERE	id_acta = $id_acta
						AND	alumno = $alumno
				";
		
		guarani_db::ejecutar($sql);
		
		toba::logger()->info('************ Se invalidó el detalle con éxito. ************');
	}
	
	function convalidar_detalle($id_acta, $alumno)
	{
		$id_acta = toba::db()->quote($id_acta);
		$alumno = toba::db()->quote($alumno);
		$estado_activo = toba::db()->quote(acta::detalle_estado_activo); 
		
		$sql = "UPDATE 	sga_actas_detalle  
				SET 	estado = $estado_activo
				WHERE	id_acta = $id_acta
						AND	alumno = $alumno
				";
		
		guarani_db::ejecutar($sql);
		
		toba::logger()->info('************ Se convalidó el detalle con éxito. ************');
	}
	
	//------------------  mesa de examen  ----------------------------------------------------------------------

	/**
	 * Cierra una acta de exámen, dado su id.
	 */
	function cerrar_acta_examen($id_acta)
	{
		$estado_cerrada = toba::db()->quote(acta::estado_cerrada);
		
		$sql = "UPDATE 	sga_actas  
				SET 	fecha_cierre = now(),
				        estado = $estado_cerrada
				WHERE 	id_acta = $id_acta";
				
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se cerró el acta con éxito. ************');
	}


	/**
	 * Inserta la cabecera del acta de examen y retorna el id
	 *
	 * @param  $nro_acta
	 * @param  $origen
	 * @param  $evaluacion
	 * @param  $llamado_mesa
	 * @param  $renglones_folio
	 * @param  $tipo_acta
	 * @param  $observaciones
	 * @param  $acta_referencia
	 * @param  $documento
	 * @return id de la cabecera del acta insertada
	 */
	function insertar_acta_examen($nro_acta, $origen, $evaluacion, $llamado_mesa, $renglones_folio, $tipo_acta, $observaciones='', $acta_referencia=null, $documento=null, $digital=comunes::no)
	{
		$nro_acta		= toba::db()->quote($nro_acta);
		$origen			= toba::db()->quote($origen);
		$tipo_acta		= toba::db()->quote($tipo_acta);
		$observaciones	= toba::db()->quote($observaciones);
		$digital		= toba::db()->quote($digital);

		$documento = empty($documento) ? 'null' : toba::db()->quote($documento);
		$acta_referencia = empty($acta_referencia) ? 'null' : $acta_referencia;
		
		$sql = "INSERT INTO sga_actas
				(
					nro_acta, origen, tipo_acta, evaluacion, llamado_mesa, documento, acta_referencia,
					renglones_folio, observaciones, digital
				)
				VALUES
				(
					$nro_acta, $origen, $tipo_acta, $evaluacion, $llamado_mesa,	$documento,	$acta_referencia,
					$renglones_folio, $observaciones, $digital
				)";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el acta con éxito. ************');
		return toba::db()->recuperar_secuencia('sga_actas_seq');
		
	}

	/**
	 * Inserta en el Acta Rectificativa las instancias correspondientes al acta original que se rectificó.
	 *
	 * @param int	$id_rectificativa	Id del acta rectificativa rectificativa para la cual se insertan las instancias.
	 * @param int	$id_acta_orig		Id del acta original que fue rectificada de la cual se copian las instancias.
	 */
	function insertar_rectificativa_instancias_acta_origen($id_rectificativa, $id_acta_orig)
	{
		$sql = "INSERT INTO sga_actas_instancias (id_acta, instancia,escala_nota)
				
				SELECT	$id_rectificativa,
						instancia,
						escala_nota
				FROM	sga_actas_instancias
				WHERE	sga_actas_instancias.id_acta = $id_acta_orig

				";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertaron la instancias de la rectificativa con éxito. ************');
	}
	
	/**
	 * Actualiza la impresion.
	 *
	 */
	function actualizar_version_impresa($id_acta)
	{
		$id_acta = toba::db()->quote($id_acta);
		
		$sql = "UPDATE 	sga_actas  
				SET 	version_impresa = sga_actas.version
				WHERE 	id_acta = $id_acta";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó la versión impresa ************');
	}

	/**
	 * Actualiza la versión del acta por modificaciones en la misma.
	 *
	 */
	function actualizar_version_acta($id_acta, $version_actual)
	{
		$id_acta = toba::db()->quote($id_acta);
		$nueva_version = $version_actual + 1;
		$nueva_version = toba::db()->quote($nueva_version);
		
		$sql = "UPDATE 	sga_actas  
				SET 	version = $nueva_version
				WHERE 	id_acta = $id_acta";
				
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó la versión actual del Acta ************');
	}
	
	/**
	 * Actualiza las observaciones del acta especificada.
	 *
	 */
	function actualizar_observaciones_acta($id_acta, $observaciones)
	{
		$id_acta = toba::db()->quote($id_acta);
		$observaciones = toba::db()->quote($observaciones);
		
		$sql = "UPDATE 
					sga_actas  
				SET 
					observaciones = $observaciones
				WHERE 
					id_acta = $id_acta;";
				
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizaron las observaciones del Acta ************');
	}
	
	/**
	 * Actualiza la fecha de vigencia de un detalle de acta.
	 *
	 */
	function actualizar_fecha_vigencia_detalle($id_acta, $alumno, $fecha_vigencia)
	{
		$id_acta = toba::db()->quote($id_acta);
		$alumno = toba::db()->quote($alumno);
		$fecha_vigencia = ($fecha_vigencia != null) ? toba::db()->quote($fecha_vigencia) : 'null';
		
		$sql = "UPDATE 	sga_actas_detalle  
				SET 	fecha_vigencia = $fecha_vigencia
				WHERE 	id_acta = $id_acta
						AND alumno = $alumno";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó la fecha de vigencia del detalle de Acta ************');
	}
	
	/**
	 * Elimina los registros de evaluaciones instancias para una instancia y una mesa dadas
	 *
	 * @param <integer> $instancia
	 * @param <integer> $mesa
	 */
	function eliminar_instancias_de_evaluaciones_manuales($instancia, $mesa)
	{
		$sql = "DELETE
				FROM	sga_evaluaciones_instancias
				WHERE	instancia = $instancia
				AND		evaluacion IN
						(	
							SELECT	sga_evaluaciones.evaluacion
							FROM	sga_evaluaciones,
									sga_llamados_mesa
							WHERE	sga_evaluaciones.entidad = sga_llamados_mesa.entidad AND
									sga_evaluaciones.evaluacion = sga_evaluaciones_instancias.evaluacion AND
									sga_llamados_mesa.mesa_examen = $mesa
						)
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminó el registro de instancia de evaluacion con éxito. ************');
	}

	/**
	 * Inserta un nuevo folio para un acta dada y libro dados.
	 *
	 * @param integer $id_acta
	 * @param integer $folio
	 * @param integer $folio_fisico
	 * @param integer $libro_tomo
	 */
	function insertar_acta_folio($id_acta, $folio, $folio_fisico, $libro_tomo)
	{
		$id_acta = toba::db()->quote($id_acta);
		$folio = toba::db()->quote($folio);
		$folio_fisico = toba::db()->quote($folio_fisico);
		$libro_tomo = toba::db()->quote($libro_tomo);

		$sql = "INSERT INTO sga_actas_folios
				(
					id_acta,
					folio,
					folio_fisico,
					libro_tomo
				)
				VALUES
				(
					$id_acta,
					$folio,
					$folio_fisico,
					$libro_tomo
				);
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó folio físico con éxito. ************');
	}

	/**
	 * Crea un nuevo libro/tomo de actas a partir de otro de origen.
	 * Retorna el código del libro_tomo creado.
	 *
	 * @param integer $libro_tomo
	 * @return integer
	 */
	function duplicar_libro_tomo_actas($libro_tomo)
	{
		$libro_tomo = toba::db()->quote($libro_tomo);
		$ejecutar = "	SELECT	*
						FROM	f_duplicar_tomo_libro($libro_tomo)
					";
		$rs = current(guarani_db::ejecutar($ejecutar, true, true));
		toba::logger()->info('************ Se duplicó libro_tomo de actas con éxito. ************');
		return $rs['f_duplicar_tomo_libro'];
	}

	/**
	 * Genera los folios físicos para un acta dada.
	 *
	 * @param integer $id_acta
	 */
	function generar_foliado_fisico($id_acta)
	{
		$id_acta = toba::db()->quote($id_acta);
		$ejecutar = "	SELECT	*
						FROM 	f_generar_folio_fisico_acta($id_acta);
		";
		guarani_db::ejecutar($ejecutar, false, true);
		toba::logger()->info('************ Se generó el foliado físico con éxito. ************');
	}

	/**
	 * Actualiza el foliado físico para un acta y folio dados.
	 *
	 * @param integer $id_acta
	 * @param integer $folio
	 * @param integer $folio_fisico
	 * @param integer $libro_tomo
	 */
	function actualizar_foliado_fisico($id_acta, $folio, $folio_fisico, $libro_tomo)
	{
		$id_acta = toba::db()->quote($id_acta);
		$folio = toba::db()->quote($folio);
		$folio_fisico = toba::db()->quote($folio_fisico);
		$libro_tomo = toba::db()->quote($libro_tomo);

		$sql = "UPDATE 	sga_actas_folios
				SET 	folio_fisico = $folio_fisico,
						libro_tomo = $libro_tomo
				WHERE 	id_acta = $id_acta AND
						folio = $folio
		";

		guarani_db::ejecutar($sql, false, true);
		toba::logger()->info('************ Se actualizó el foliado físico con éxito. ************');
	}

	/**
	 * Por cada alumno de un acta de examen que rindió en condición de regular y desaprobó,
	 * si la cantidad de exámenes regulares desaprobados (desde la fecha de su última regularidad aprobada vigente)
	 * supera el valor del parámetro 'exa_cant_veces_rendir_examen_regular' o 'exa_cant_veces_rendir_examen_regular' (dependiendo si la regularidad se obtuvo o no por equivalencia)
	 * se vence su regularidad.
	 *
	 * @param integer $id_acta
	 * @param integer $elemento
	 * @param integer $alumno
	 * @param string $fecha_detalle
	 * @param integer $cant_veces_rendir_examen_regular
	 * @param integer $cant_veces_rendir_examen_regular_equiv
	 */
	function vencer_regularidades($id_acta, $elemento, $alumno, $fecha_detalle, $cant_veces_rendir_examen_regular, $cant_veces_rendir_examen_regular_equiv)
	{
		$ejecutar = "	SELECT	*
						FROM	f_examenes_vencimiento_regularidad_alumno($id_acta, $elemento, $alumno, '$fecha_detalle', $cant_veces_rendir_examen_regular, $cant_veces_rendir_examen_regular_equiv);
		";
		guarani_db::ejecutar($ejecutar, true, true);
		toba::logger()->info("************ Se ejecutó el proceso de vencimiento de regularidades con éxito para el alumno $alumno. ************");
	}

	function insertar_alumno_no_promocional($acta, $alumno, $plan_version, $fecha, $escala_nota, $nota, $resultado, $observaciones)
	{
		$acta = toba::db()->quote($acta);
		$alumno = toba::db()->quote($alumno);
		$plan_version = toba::db()->quote($plan_version);
		$fecha = empty($fecha) ? 'null' : toba::db()->quote($fecha);
		$escala_nota = empty($escala_nota) ? 'null' : toba::db()->quote($escala_nota);
		$nota = empty($nota) ? 'null' : toba::db()->quote($nota);
		$resultado = empty($resultado) ? 'null' : toba::db()->quote($resultado);
		$observaciones = empty($observaciones) ? 'null' : toba::db()->quote($observaciones);
		$sql = "INSERT INTO sga_actas_alumnos_no_prom
				(
					acta,
					alumno,
					plan_version,
					fecha,
					escala_nota,
					nota,
					resultado,
					observaciones
				)
				VALUES
				(	$acta,
					$alumno,
					$plan_version,
					$fecha,
					$escala_nota,
					$nota,
					$resultado,
					$observaciones
				)
		";
		guarani_db::ejecutar($sql, false, true);
		toba::logger()->info('************ Se insertó el alumno no promocional en la tabla de log con éxito. ************');
	}

	function eliminar_alumno_no_promocional($comision, $alumno)
	{
		$comision = toba::db()->quote($comision);
		$alumno = toba::db()->quote($alumno);

		$sql = "DELETE
				FROM	sga_actas_alumnos_no_prom
				WHERE	alumno = $alumno AND
						acta IN (	SELECT	sga_actas.id_acta
									FROM	sga_actas
									WHERE	sga_actas.comision = $comision)
		";

		guarani_db::ejecutar($sql, false, true);
		toba::logger()->info('************ Se eliminó el alumno no promocional de la tabla de log con éxito. ************');
	}
	
	function actualizar_cerrada_por_docente($id_acta, $cerrada_por_docente)
	{
		$id_acta = toba::db()->quote($id_acta);
		$cerrada_por_docente = toba::db()->quote($cerrada_por_docente);

		$sql = "UPDATE 	sga_actas
				SET 	cerrada_por_docente = $cerrada_por_docente
				WHERE 	id_acta = $id_acta
		";
		guarani_db::ejecutar($sql, false, true);
		toba::logger()->info('************ Se actualizó el campo cerrada por docente con éxito. ************');
	}
        
    /**
	 * Actualiza el documento digital asociado a un acta.
     * @see https://redmine.siu.edu.ar/redmine/issues/30921 Ticket #30921
	 */
	function actualizar_documento_digital($id_acta, $documento_digital)
	{
		$documento_digital = toba::db()->quote($documento_digital);
		
		$sql = "UPDATE 	sga_actas  
				SET 	id_documento_digital = $documento_digital,
						parametros_documento_digital = NULL
				WHERE 	id_acta = $id_acta";
				
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó el documento digital del Acta ************');
	}

    /**
	 * Actualiza el estado del documento digital asociado a un acta.
     * @see https://redmine.siu.edu.ar/redmine/issues/31174 Ticket #31174
	 */
	function actualizar_estado_documento_digital($id_acta, $estado_documento_digital)
	{
		$estado_documento_digital = toba::db()->quote($estado_documento_digital);
		
		$sql = "UPDATE 	sga_actas  
				SET 	estado_documento_digital = $estado_documento_digital
				WHERE 	id_acta = $id_acta";
				
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó el estado del documento digital del Acta ************');
	}

    /**
	 * Actualiza los parámetros del documento digital asociado a un acta.
     * @see https://redmine.siu.edu.ar/redmine/issues/32582 Ticket #32582
	 */
	function actualizar_parametros_documento_digital($id_acta, $parametros_documento_digital = null)
	{
		if (is_null($parametros_documento_digital)) {
			$parametros_documento_digital = 'NULL';
		} else {
			$parametros_documento_digital = toba::db()->quote(json_encode($parametros_documento_digital));
		}
		
		$sql = "UPDATE 	sga_actas  
				SET 	parametros_documento_digital = $parametros_documento_digital
				WHERE 	id_acta = $id_acta";
				
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizaron los parámetros del documento digital del Acta ************');
	}
}
?>