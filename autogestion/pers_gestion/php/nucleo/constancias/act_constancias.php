<?php
class act_constancias extends act_constancias_nucleo
{

	/**
	 *  29/12/2025
	 *  Newsol - Joaquin
	 *  Se borra también el documento asociado a la solicitud de constancia
	 *  Se borra el registro en sga_constancias_documentos_solicitudes y sga_constancias_documentos
	 *  Registra la Eliminaci�n de una Solicitud de Constancia (en estado Impresa o Pendiente).
	 */
	function eliminar_solicitud($id_solicitud)
	{
		$id_solicitud		= toba::db()->quote($id_solicitud);

		$sql = "SELECT id_constancia_documento
				FROM	negocio_nsg.sga_constancias_documentos_solicitudes
				WHERE	solicitud = $id_solicitud
		";
		$constancia_documento = guarani_db::consultar_fila($sql)['id_constancia_documento'];

		if(isset($constancia_documento)) {

			$sql = "DELETE
				FROM 	negocio_nsg.sga_constancias_documentos_solicitudes
				WHERE	solicitud = $id_solicitud
			";
			guarani_db::ejecutar($sql);

			$sql = "DELETE
				FROM	negocio_nsg.sga_constancias_documentos
				WHERE	constancia_documento = '$constancia_documento'
			";
			guarani_db::ejecutar($sql);

		}

		$sql = "DELETE
				FROM	negocio_nsg.sga_constancias_solicitud_ext
				WHERE	id_solicitud = $id_solicitud
		";
		guarani_db::ejecutar($sql);

		$sql = "DELETE
				FROM	sga_constancias_solicitud
				WHERE	solicitud = $id_solicitud
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se elimin� la solicitud con �xito. ************');
	}

	/**
	 * 23/12/2025
	 * Newsol - Joaquin
	 * insert_constancia_documento
	 * Inserta un nuevo registro en la tabla sga_constancias_documentos
	 */
	function insert_constancia_documento($pid, $cid, $did)
	{
		$sql = "INSERT INTO negocio_nsg.sga_constancias_documentos 
							(persona, constancia, id_documento, estado, descripcion, fecha_subida)
				VALUES
                			(
							$pid, 
							$cid, 
							'" . $did . "',
							0, 
							'', 
							'" . date('Y-m-d') . "');";
		guarani_db::ejecutar($sql);
	}

	/**
	 * 23/12/2025
	 * Newsol - Joaquin
	 * insert_constancia_documento_solicitud
	 * Inserta un nuevo registro en la tabla sga_constancias_documentos_solicitud
	 */

	function insert_constancia_documento_solicitud($sid, $dcid, $cid)
	{
		$sql = "INSERT INTO negocio_nsg.sga_constancias_documentos_solicitudes 
							(solicitud, id_constancia_documento, constancia)
				VALUES
							(
							$sid, 
							$dcid,
							$cid);";
		guarani_db::ejecutar($sql);
	}
}
