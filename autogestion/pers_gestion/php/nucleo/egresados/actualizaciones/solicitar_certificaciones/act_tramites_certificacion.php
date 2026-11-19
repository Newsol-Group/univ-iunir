<?php 
  class act_tramites_certificacion extends act_tramites_certificacion_nucleo { 

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

		$sql = "INSERT	INTO negocio_nsg.sga_certificados_otorg_ext
						(id_solicitud, pago)
				VALUES ($nro_solicitud, 'N')
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se registró la historia académica del alumno con éxito. ************');

	}

  } 
?>