<?php
/**
 * Ejecución de funciones o scripts que producen cambios en la base de negocio
 *
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class act_tramites_certificacion_nucleo
{
	/**
	 * Registra una "foto" de la historia académica de un alumno asociada a un trámite de solicitud de certificado.
	 *
	 * @param integer $alumno
	 * @param integer $nro_solicitud
	 * @param array   $excluidas actividades a excluir de la hist. acad.
	 * @param boolean $eliminar_ha_anterior si se debe eliminar una foto anterior de hist. acad. asociada a la solicitud (edición)
	 */
	function registrar_historia_academica($alumno, $nro_solicitud, $excluidas = array(), $eliminar_ha_anterior = false)
	{
		// Si hay actividades configuradas para ser excluidas de la H.A., no se registran
		if (!empty($excluidas)) {
			$where = " AND vw_hist_academica.elemento NOT IN ('" . implode("','", $excluidas) . "')";
		} else {
			$where = '';
		}

		if ($eliminar_ha_anterior) {
			$sql_eliminar = '	DELETE 
								FROM 	sga_certificados_histacad 
								WHERE	nro_solicitud = ' . toba::db()->quote($nro_solicitud);
			guarani_db::ejecutar($sql_eliminar);
			toba::logger()->info("************ Se eliminó la historia académica del alumno asaociado a solicitud '$nro_solicitud' con éxito. ************");
		}

		$sql = "INSERT	INTO sga_certificados_histacad
						(nro_solicitud, elemento, fecha, escala_nota, nota, resultado, tipo)
				SELECT	$nro_solicitud,
						elemento,
						fecha,
						escala_nota,
						nota,
						resultado,
						tipo
				FROM	vw_hist_academica
				WHERE	vw_hist_academica.alumno = $alumno
						$where
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se registró la historia académica del alumno con éxito. ************');
	}

	/**
	 * Elimina la historia académica registrada para un trámite de solicitud de certificado.
	 *
	 * @param integer $nro_solicitud
	 */
	function eliminar_historia_academica($nro_solicitud)
	{
		$nro_solicitud = toba::db()->quote($nro_solicitud);
		$sql = "DELETE
				FROM	sga_certificados_histacad
				WHERE	nro_solicitud = $nro_solicitud
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminó la historia académica con éxito. ************');
	}

	/**
	 * Registra las orientaciones cumplidas relacionadas a un trámite de solicitud de certificación.
	 *
	 * @param integer $nro_solicitud
	 * @param array $orientaciones
	 */
	function registrar_orientaciones_cumplidas($nro_solicitud, $orientaciones)
	{
		$nro_solicitud = toba::db()->quote($nro_solicitud);
		foreach ($orientaciones as $orientacion) {
			$orientacion = toba::db()->quote($orientacion);
			$sql = "INSERT INTO	sga_certificados_otorg_orie (nro_solicitud, orientacion)
					VALUES		($nro_solicitud, $orientacion)
			";
			guarani_db::ejecutar($sql);
			toba::logger()->info('************ Se insertó la orientación cumplida con éxito. ************');
		}
	}

	/**
	 * Elimina las orientaciones cumplidas para un trámite de solicitud de certificación.
	 *
	 * @param integer $nro_solicitud
	 */
	function eliminar_orientaciones_cumplidas($nro_solicitud)
	{
		$nro_solicitud = toba::db()->quote($nro_solicitud);
		$sql = "DELETE
				FROM	sga_certificados_otorg_orie
				WHERE	nro_solicitud = $nro_solicitud
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron las orientaciones cumplidas con éxito. ************');
	}

	//--------------------------------------------------------------------------
	//---- Configuración de Hist. Acad. ----------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Inserta la cabecera de configuración de actividades excluidas de la H.A. para
	 * la obtención de un certificado.
	 * 
	 * @param integer $nro_solicitud
	 * @returns integer certificado_ha (clave de sga_certificados_configurar_ha)
	 */
	function insertar_configuracion_ha($nro_solicitud)
	{
		$nro_solicitud = toba::db()->quote($nro_solicitud);

		$sql = "INSERT INTO sga_certificados_configurar_ha(nro_solicitud) 
				VALUES ($nro_solicitud);
				";
		guarani_db::ejecutar($sql);

		toba::logger()->info('************ Se insertó en sga_certificados_configurar_ha con éxito. ************');
		return toba::db()->recuperar_secuencia('sga_certificados_configurar_ha_seq');
	}

	function insertar_actividad_excluida_ha($certificado_ha, $actividad_excluida)
	{
		$certificado_ha = toba::db()->quote($certificado_ha);
		$actividad_excluida = toba::db()->quote($actividad_excluida);

		$sql = "INSERT INTO sga_certificados_ha_no_elementos
				(
					certificado_ha,
					elemento
				) 
				VALUES (
					$certificado_ha,
					$actividad_excluida
				);";
		guarani_db::ejecutar($sql);

		toba::logger()->info('************ Se insertó en sga_certificados_ha_no_elementos con éxito. ************');
	}

	/**
	 * Elimina la configuración de historia académica registrada para un trámite 
	 * de solicitud de certificado.
	 *
	 * @param integer $nro_solicitud
	 */
	function eliminar_configuracion_ha($nro_solicitud)
	{
		$nro_solicitud = toba::db()->quote($nro_solicitud);

		// Se eliminan las actividades excluidas de la historia académica
		$sql = "DELETE
				FROM	sga_certificados_ha_no_elementos
				WHERE	certificado_ha = (SELECT certificado_ha FROM sga_certificados_configurar_ha WHERE nro_solicitud = $nro_solicitud);
				";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron registros de sga_certificados_ha_no_elementos con éxito. ************');

		// Se elimina el registro cabecera de la configuración de hist. acad.
		$sql2 = "DELETE
				FROM	sga_certificados_configurar_ha
				WHERE	nro_solicitud = $nro_solicitud;
				";
		guarani_db::ejecutar($sql2);
		toba::logger()->info('************ Se eliminaron registros de sga_certificados_configurar_ha_seq con éxito. ************');
	}
	
	/**
	 * Registra el Certificado Analítico o Diploma con sus identificadores del documento digital de SIU-Araí.
	 * 
	 * @param int $nro_solicitud
	 * @param string $tipo_documento
	 * @param string $id_documento_digital
	 * @param int $nro_tramite_sicer
	 * @param string $enviado_a_sicer
	 */
	function insertar_documento_digital_arai($nro_solicitud, $tipo_documento, $id_documento_digital, $nro_tramite_sicer, $enviado_a_sicer = comunes::no)
	{
		$nro_solicitud = toba::db()->quote($nro_solicitud);
		$tipo_documento = toba::db()->quote($tipo_documento);
		$id_documento_digital = toba::db()->quote($id_documento_digital);
		$nro_tramite_sicer = toba::db()->quote($nro_tramite_sicer);
		$enviado_a_sicer = toba::db()->quote($enviado_a_sicer);

		$sql = "INSERT INTO sga_certificados_otorg_documentos(nro_solicitud, tipo_documento, id_documento_digital, nro_tramite_sicer, enviado_a_sicer) 
				VALUES ($nro_solicitud, $tipo_documento, $id_documento_digital, $nro_tramite_sicer, $enviado_a_sicer);
				";
		guarani_db::ejecutar($sql);

		toba::logger()->info('************ Se insertó en sga_certificados_otorg_documentos con éxito. ************');
	}
	
	/**
	 * Modifica la tabla "sga_certificados_otorg_documentos".
	 * 
	 * @param int $nro_solicitud
	 * @param string $tipo_documento
	 * @param string|null $id_documento_digital
	 * @param string|null $nro_tramite_sicer
	 * @param string|null $enviado_a_sicer
	 */
	function actualizar_documento_digital_arai($nro_solicitud, $tipo_documento, $id_documento_digital = null, $nro_tramite_sicer = null, $enviado_a_sicer = null)
	{
		$nro_solicitud = toba::db()->quote($nro_solicitud);
		$tipo_documento = toba::db()->quote($tipo_documento);
		
		$cambios = [];
		
		if (!is_null($id_documento_digital)) {
			$id_documento_digital = toba::db()->quote($id_documento_digital);
			$cambios[] = "id_documento_digital = {$id_documento_digital}";
		}
		
		if (!is_null($nro_tramite_sicer)) {
			$nro_tramite_sicer = toba::db()->quote($nro_tramite_sicer);
			$cambios[] = "nro_tramite_sicer = {$nro_tramite_sicer}";
		}
		
		if (!is_null($enviado_a_sicer)) {
			$enviado_a_sicer = toba::db()->quote($enviado_a_sicer);
			$cambios[] = "enviado_a_sicer = {$enviado_a_sicer}";
		}

		// Si no hay cambios salgo
		if (empty($cambios)) {
			return false;
		} else {
			$cambios_sql = implode(", ", $cambios);
		}
		
		$sql = "UPDATE sga_certificados_otorg_documentos
				SET $cambios_sql
				WHERE nro_solicitud = $nro_solicitud
				AND tipo_documento = $tipo_documento;
				";
		guarani_db::ejecutar($sql);

		toba::logger()->info('************ Se actualizó en sga_certificados_otorg_documentos con éxito. ************');
	}
    
    /**
     * Elimina de la tabla "sga_certificados_otorg_documentos".
     * 
     * @param int $nro_solicitud
     * @param string $tipo_documento
     */
    function desvincular_documento_digital_arai($nro_solicitud, $tipo_documento)
    {
        $nro_solicitud = toba::db()->quote($nro_solicitud);
        $tipo_documento = toba::db()->quote($tipo_documento);

        $sql = "DELETE FROM sga_certificados_otorg_documentos WHERE nro_solicitud = {$nro_solicitud} AND tipo_documento = {$tipo_documento}";
        guarani_db::ejecutar($sql);

        toba::logger()->info('************ Se eliminó en sga_certificados_otorg_documentos con éxito. ************');
    }

    /**
	 * Registra el Expediente Egreso con sus identificadores del documento digital de SIU-Araí.
	 * 
	 * @param int $nro_solicitud
	 * @param int $formulario
	 * @param string $id_documento_digital
	 */
	function actualizar_expediente_egreso_arai($nro_solicitud, $formulario, $id_documento_digital)
	{
		$nro_solicitud = toba::db()->quote($nro_solicitud);
		$formulario = toba::db()->quote($formulario);
		$id_documento_digital = toba::db()->quote($id_documento_digital);

		$sql = "UPDATE sga_certificados_otorg_form
				SET id_documento_digital = $id_documento_digital,
					fecha_envio = CURRENT_DATE
				WHERE nro_solicitud = $nro_solicitud
				AND formulario = $formulario
				";
		guarani_db::ejecutar($sql);

		toba::logger()->info('************ Se actualizó en sga_certificados_otorg_form con éxito. ************');
	}
    
    /**
     * Desvincula el documento del Expediente de Egreso.
     * 
     * @param int $nro_solicitud
     * @param int $formulario
     */
    function desvincular_expediente_egreso_arai($nro_solicitud, $formulario)
    {
        $nro_solicitud = toba::db()->quote($nro_solicitud);
        $formulario = toba::db()->quote($formulario);

        $sql = "UPDATE sga_certificados_otorg_form
				SET id_documento_digital = NULL,
					fecha_envio = NULL
				WHERE nro_solicitud = $nro_solicitud
				AND formulario = $formulario
				";
        guarani_db::ejecutar($sql);

        toba::logger()->info('************ Se desvinculó en sga_certificados_otorg_form con éxito. ************');
    }
    
    /**
     * Actualiza la descripción del Trámite de SUDOCU en el Trámite de Certificación.
     * 
     * @param int $nro_solicitud
     * @param string $nro_expediente_descr
     */
    function actualizar_nro_expediente_descr_tramite_certificacion($nro_solicitud, $nro_expediente_descr)
    {
        $nro_solicitud = toba::db()->quote($nro_solicitud);
        $nro_expediente_descr = toba::db()->quote($nro_expediente_descr);

        $sql = "UPDATE sga_certificados_otorg
				SET nro_expediente_descr = $nro_expediente_descr
				WHERE nro_solicitud = $nro_solicitud
				";
        guarani_db::ejecutar($sql);

        toba::logger()->info('************ Se actualizó en sga_certificados_otorg con éxito. ************');
    }

	/**
	 * Actualiza el estado de presentación de requisitos de egreso para un trámite de solicitud de certificación.
	 * 
	 * @param int $nro_solicitud
	 * @param char $estado_requisitos
	 */
	function actualizar_estado_requisitos($nro_solicitud, $estado_requisitos)
	{
		$nro_solicitud = toba::db()->quote($nro_solicitud);
		$estado_requisitos = toba::db()->quote($estado_requisitos);
		$sql = "UPDATE	sga_certificados_otorg  
				SET		estado_requisitos = $estado_requisitos
				WHERE	nro_solicitud = $nro_solicitud
		";
		guarani_db::ejecutar($sql);		
		toba::logger()->info('************ Se actualizó el estado de requisitos del trámite de certificación con éxito. ************');
	}
}