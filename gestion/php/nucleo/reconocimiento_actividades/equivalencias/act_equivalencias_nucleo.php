<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Equivalencias
 */
class act_equivalencias_nucleo
{
	/**
	 * Copia una Matriz de Equivalencia a partir de una de origen, y el Plan Versión origen y destino
	 */
	function copiar_matriz_equivalencia($plan_version_origen, $plan_version_destino, $matriz_origen)
	{
		$ejecutar = "	SELECT	*
						FROM	f_copiar_matrices_equivalencia($plan_version_origen, $plan_version_destino, $matriz_origen)
						";
		guarani_db::ejecutar($ejecutar);
	}

	/**
	 * Cierra el trámite de equivalencia, cambiando su estado, según el id dado.
	 */
	function cerrar_tramite_equivalencia_cabecera($id_tramite)
	{
		$id_tramite = toba::db()->quote($id_tramite);
		$estado_cerrado = toba::db()->quote(equivalencia::estado_cerrado);

		$sql = "UPDATE 	sga_equiv_tramite 
				SET 	fecha_cierre = now(),
				        estado = $estado_cerrado
				WHERE 	equivalencia_tramite = $id_tramite";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se cerró el trámite de equivalencia con éxito. ************');
	}

	/**
	 * Cuando el trámite a cerrar rectifica a alguna equivalencia otorgada,
	 * debe modificarse el detalle para marcarlo como rectificado.
	 */
	function rectificar_detalle_tramite_cerrado($id_tramite)
	{
		$si = toba::db()->quote(comunes::si);
		$sql = "UPDATE 	sga_equiv_otorgada 
				SET 	rectificado = $si
				WHERE	sga_equiv_otorgada.equivalencia IN (SELECT	e.rectifica_a
        													FROM	sga_equiv_otorgada as e
                                            				WHERE	e.equivalencia_tramite = $id_tramite)
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se marcó como rectificado el detalle del trámite de equivalencia cerrado. ************');
	}

	/**
	 * Anula el trámite de equivalencia dado.
	 */
	function anular_tramite_equivalencia($id_tramite, $observaciones = '')
	{
		$id_tramite = toba::db()->quote($id_tramite);
		$set = '';
		if (!empty($observaciones)) {
			$observaciones = toba::db()->quote($observaciones);
			$set = ", observaciones = $observaciones";
		}
		$estado_anulado = toba::db()->quote(equivalencia::estado_anulado);

		$sql = "UPDATE 	sga_equiv_tramite 
				SET 	fecha_anulacion = now(),
				        estado = $estado_anulado
						$set
				WHERE 	equivalencia_tramite = $id_tramite";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se anuló el trámite de equivalencia con éxito. ************');
	}

	/**
	 * Actualiza la versión impresa del trámite de acta dado.
	 */
	function actualizar_version_impresa_tramite_equivalencia($id_tramite)
	{
		$id_tramite = toba::db()->quote($id_tramite);

		$sql = "UPDATE 	sga_equiv_tramite  
				SET 	version_impresa = version
				WHERE 	equivalencia_tramite = $id_tramite";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó la versión impresa del trámite de equivalencia ************');
	}

	/**
	 * Invalida un detalle para una equivalencia dada..
	 */
	function invalidar_detalle($equivalencia)
	{
		$equivalencia = toba::db()->quote($equivalencia);
		$estado_invalidado = toba::db()->quote(equivalencia::detalle_estado_invalidado);

		$sql = "UPDATE 	sga_equiv_otorgada  
				SET 	estado = $estado_invalidado
				WHERE 	equivalencia = $equivalencia
				";

		guarani_db::ejecutar($sql);

		toba::logger()->info('************ Se invalidó el detalle con éxito. ************');
	}

	/**
	 * Convalida un detalle para una equivalencia dada.
	 */
	function convalidar_detalle($equivalencia)
	{
		$equivalencia = toba::db()->quote($equivalencia);
		$estado_activo = toba::db()->quote(equivalencia::detalle_estado_activo);

		$sql = "UPDATE 	sga_equiv_otorgada  
				SET 	estado = $estado_activo
				WHERE 	equivalencia = $equivalencia
				";

		guarani_db::ejecutar($sql);

		toba::logger()->info('************ Se convalidó el detalle con éxito. ************');
	}

	/**
	 * Actualiza la fecha de vigencia de un detalle de equivalencia.
	 * 
	 */
	function actualizar_fecha_vigencia_equivalencia($equivalencia, $fecha_vigencia)
	{
		$equivalencia = toba::db()->quote($equivalencia);
		$fecha_vigencia = ($fecha_vigencia != null) ? toba::db()->quote($fecha_vigencia) : 'null';

		$sql = "UPDATE 	sga_equiv_otorgada  
				SET 	fecha_vigencia = $fecha_vigencia
				WHERE 	equivalencia = $equivalencia";

		guarani_db::ejecutar($sql);

		toba::logger()->info('************ Se actualizó la fecha de vigencia de equivalencia con éxito. ************');
	}

	/**
	 * Obtiene las equivalencias que se otorgarían por la aplicación de una Matriz de Equivalencia, para un alumno y plan
	 * versión dados.
	 * @id_interno Id interno para invocar a la función, se utiliza en los registros retornados para relacionar los datos
	 * de las tablas de equiv_otorgadas con equiv_internas.
	 * Datos de la matriz a evaluar, el alumno al que se otorgará la equivalencia, datos origen de la misma, y parámetros
	 * de sistema para evaluar las equivalencias a otorgar
	 * 
	 */
	function evaluar_equivalencias_matriz($id_interno, $id_matriz, $alumno, $plan_version_alumno, $id_persona, $ajustar_nota, $pasar_desaprobados, $vencimiento_origen, $otorgar_regularidades, $regularidad_con_nota, $prop_origen = null, $plan_origen = null, $version_origen = null, $actividad = null)
	{
		// Indica que la matriz no se está evaluando para aplicarse automáticamente (desde trigger),
		$automatica = 'FALSE';
		if (is_null($prop_origen)) {
			$prop_origen = 'null';
		}
		if (is_null($plan_origen)) {
			$plan_origen = 'null';
		}
		if (is_null($version_origen)) {
			$version_origen = 'null';
		}
		if (is_null($actividad)) {
			$actividad = 'null';
		}

		$sql = "	SELECT	*
					FROM	f_equiv_evaluar_matriz($id_interno, $id_matriz, $actividad, $automatica, $alumno, $plan_version_alumno, $id_persona, $prop_origen, $plan_origen, $version_origen, '$ajustar_nota', '$pasar_desaprobados', '$vencimiento_origen', '$otorgar_regularidades', '$regularidad_con_nota')
					";
		return guarani_db::ejecutar($sql, true);
	}

	/**
	 * Genera la cabecera del tramite de equivalencia
	 */
	function generar_tramite_equivalencia_cabecera($alumno, $plan_version, $fecha, $operador, $institucion, $resp_acad)
	{
		$alumno = toba::db()->quote($alumno);
		$plan_version = toba::db()->quote($plan_version);
		$fecha = toba::db()->quote($fecha);
		$operador = toba::db()->quote($operador);
		$institucion = toba::db()->quote($institucion);
		$resp_acad = toba::db()->quote($resp_acad);
		$origen = equivalencia::origen_ext_libre;
		$estado = toba::db()->quote(equivalencia::estado_cerrado);

		//Los datos restantes se completan con los valores default de la base.
		$sql = "INSERT INTO sga_equiv_tramite(
							alumno,
							fecha,
							plan_version,
							operador,
							institucion,
							responsable_academica,
							origen,
							generacion_automatica,
							estado,
							fecha_cierre)
				VALUES		(
							$alumno,
							$fecha,
							$plan_version,
							$operador,
							$institucion,
							$resp_acad,
							$origen,
							'" . comunes::no . "',
							$estado,
							current_date
							)
				;";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se generó la cabecera del tramite de equivalencia. ************');
		return toba::db()->recuperar_secuencia('sga_equiv_tramite_seq');
	}

	/**
	 * Genera la equivalencia otorgada
	 */
	function generar_equiv_otorgada($id_equivalencia, $elemento, $fecha, $escala_nota, $nota, $resultado)
	{
		$id_equivalencia = toba::db()->quote($id_equivalencia);
		$elemento = toba::db()->quote($elemento);
		$fecha = toba::db()->quote($fecha);
		$nota = toba::db()->quote($nota);
		$resultado = toba::db()->quote($resultado);

		//Los datos restantes se completan con los valores default de la base.
		$sql = "INSERT INTO sga_equiv_otorgada(
							equivalencia_tramite, 
							elemento,
							instancia,
							fecha,
							escala_nota,
							nota, 
							resultado)
				VALUES		(
							$id_equivalencia,
							$elemento,
							'" . equivalencia::equiv_instancia_total . "', 
							$fecha,
							$escala_nota,
							$nota,
							$resultado
							)
				;";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se generó la equivalencia otorgada. ************');
		return toba::db()->recuperar_secuencia('sga_equiv_otorgada_seq');
	}

	/**
	 * Genera la equivalencia externa
	 */
	function generar_equiv_externa($equivalencia, $actividad, $fecha, $nota, $acta, $libro, $folio)
	{
		$equivalencia = toba::db()->quote($equivalencia);
		$actividad = toba::db()->quote($actividad);
		$fecha = toba::db()->quote($fecha);
		$nota = toba::db()->quote($nota);

		//Los datos restantes se completan con los valores default de la base.
		$sql = "INSERT INTO sga_equiv_externas(
							equivalencia, 
							actividad,
							acta,
							fecha,
							libro,
							nota, 
							folio)
				VALUES		(
							$equivalencia,
							$actividad,
							$acta, 
							$fecha,
							$libro,
							$nota,
							$folio
							)
				;";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se generó la equivalencia externa. ************');
	}

	function otorgar_equivalencias($accion, $alumno, $propuesta_origen=null, $plan_origen=null, $plan_version_origen=null, $actividad=null)
	{
		$propuesta_origen = empty($propuesta_origen) ? 'null' : $propuesta_origen;
		$plan_origen = empty($plan_origen) ? 'null' : $plan_origen;
		$plan_version_origen = empty($plan_version_origen) ? 'null' : $plan_version_origen;
		$actividad = empty($actividad) ? 'null' : $actividad;
		$sql = "	SELECT	*
					FROM	f_equiv_otorgar_equivalencias('$accion', $alumno, $propuesta_origen, $plan_origen, $plan_version_origen, $actividad)
		";
		$rs = guarani_db::ejecutar($sql, true, true);
		return current($rs);
	}

	function insertar_equiv_actas_procesadas($id_acta)
	{
		$id_acta = toba::db()->quote($id_acta);
		$sql = "INSERT INTO	sga_equiv_actas_procesadas
							(id_acta,
							fecha_proceso)
				VALUES		($id_acta,
							CURRENT_TIMESTAMP);
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se registró el procesamiento del acta con éxito. ************');
	}
	
	/**
	 * Actualiza el documento digital asociado a un trámite de equivalencia.
     * @see https://redmine.siu.edu.ar/redmine/issues/31318 Ticket #31318
	 */
	function actualizar_documento_digital($id_tramite, $documento_digital)
	{
		$documento_digital = toba::db()->quote($documento_digital);
		
		$sql = "UPDATE 	sga_equiv_tramite  
				SET 	id_documento_digital = $documento_digital,
						parametros_documento_digital = NULL
				WHERE 	equivalencia_tramite = $id_tramite";
				
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó el documento digital del trámite de equivalencia ************');
	}

    /**
	 * Actualiza el estado del documento digital asociado a un trámite de equivalencia.
     * @see https://redmine.siu.edu.ar/redmine/issues/31318 Ticket #31318
	 */
	function actualizar_estado_documento_digital($id_tramite, $estado_documento_digital)
	{
		$estado_documento_digital = toba::db()->quote($estado_documento_digital);
		
		$sql = "UPDATE 	sga_equiv_tramite  
				SET 	estado_documento_digital = $estado_documento_digital
				WHERE 	equivalencia_tramite = $id_tramite";
				
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó el estado del documento digital del trámite de equivalencia ************');
	}

    /**
	 * Actualiza los parámetros del documento digital asociado a un trámite de equivalencia.
     * @see https://redmine.siu.edu.ar/redmine/issues/31318 Ticket #31318
	 */
	function actualizar_parametros_documento_digital($id_tramite, $parametros_documento_digital = null)
	{
		if (is_null($parametros_documento_digital)) {
			$parametros_documento_digital = 'NULL';
		} else {
			$parametros_documento_digital = toba::db()->quote(json_encode($parametros_documento_digital));
		}
		
		$sql = "UPDATE 	sga_equiv_tramite  
				SET 	parametros_documento_digital = $parametros_documento_digital
				WHERE 	equivalencia_tramite = $id_tramite";
				
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizaron los parámetros del documento digital del trámite de equivalencia ************');
	}
}