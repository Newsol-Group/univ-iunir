<?php
/**
 *
 * Ejecución de comandos SQL que producen cambios en la base de negocio.
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class act_requisitos_ingreso_nucleo
{
	/**
	 * Inserta un nuevo registro en la tabla de aplanado.
	 * 
	 */
	function insertar_aplanado($requisito_propuesta, $plan_version)
	{
		$sql = "INSERT INTO	sga_requisitos_propuestas_aplanado (
							requisito_propuesta,
							plan_version)
				VALUES (
							$requisito_propuesta,
							$plan_version)
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el registro de aplanado con éxito. ************');
	}

	/**
	 * Elimina de la tabla de aplanado todos los registros correspondientes a un requisito de ingreso dado.
	 * 
	 */
	function eliminar_aplanado($requisito_propuesta)
	{
		$sql = "DELETE
				FROM	sga_requisitos_propuestas_aplanado
				WHERE	requisito_propuesta = $requisito_propuesta
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los registros de aplanado con éxito. ************');
	}
	
	
	/**
	 * Inserta un nuevo registro en la tabla de requisitos presentados
	 *
	 */
	function insertar_requisito_presentado($persona, $requisito, $fecha_presentacion, $fecha_vencimiento=null, $id_digital=null)
	{
		$persona = toba::db()->quote($persona);
		$requisito = toba::db()->quote($requisito);
		$fecha_presentacion = toba::db()->quote($fecha_presentacion);
		$fecha_vencimiento = (is_null($fecha_vencimiento)) ? 'null' : toba::db()->quote($fecha_vencimiento);
		$id_digital = (is_null($id_digital)) ? 'null' : toba::db()->quote($id_digital);
	
		$sql = "INSERT INTO sga_requisitos_presentados
				(
					persona,
					requisito,
					fecha_presentacion,
					fecha_vencimiento,
					requisito_digital
				) 
				VALUES (
					$persona,
					$requisito,
					$fecha_presentacion,
					$fecha_vencimiento,
					$id_digital
				);";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el requisito presentado con éxito. ************');
	}
	
	/**
	 * Inserta un requisito digital para un documento dado de alta en rdi
	 * @param int $persona		id de persona para que la que se está insertando el requisito digital
	 * @param int $requisito	id de requisito para el que se está insertando el requisito digital
	 * @param int $id_rdi		id al que se encuentra asociado el documento en el repositorio digital integrado
	 */
	function insertar_requisito_digital($persona, $requisito, $id_rdi)
	{
		$persona = toba::db()->quote($persona);
		$requisito = toba::db()->quote($requisito);
		$id_rdi = toba::db()->quote($id_rdi);
	
		$sql = "INSERT INTO sga_requisitos_digitales
				(
					persona,
					requisito,
					id_documento
				) 
				VALUES (
					$persona,
					$requisito,
					$id_rdi
				);";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el requisito digital con éxito. ************');
		return toba::db()->recuperar_secuencia('sga_requisitos_digitales_seq');
	}
	
	/**
	 * Asocia un requisito digital a un requisito ya presentado.
	 * @param int $persona		id de persona a la que corresponde el requisito presentado
	 * @param int $requisito	id de requisito al que corresponde el requisito presentado
	 * @param int $id_digital	id de requisito digital a asociar al requisito presentado
	 */
	function actualizar_req_digital_de_req_presentado($persona, $requisito, $id_digital)
	{
		$persona = toba::db()->quote($persona);
		$requisito = toba::db()->quote($requisito);
		$id_digital = toba::db()->quote($id_digital);
	
		$sql = "UPDATE	sga_requisitos_presentados
				SET		requisito_digital = $id_digital
				WHERE	persona = $persona		AND
						requisito = $requisito
				;";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se asoció con éxito el requisito digital al requisito presentado. ************');
	}
	
	/**
	 * Elimina draft de requisito digital (sea porque se aceptó, en cuyo caso se
	 * copió a sga_requisitos_digitales, o se rechazó)
	 * 
	 * @param int $requisito_digital
	 */
	function eliminar_draft_requisito_digital($requisito_digital)
	{
		$requisito_digital = toba::db()->quote($requisito_digital);
	
		$sql = "DELETE 
				FROM	sga_requisitos_digitales_draft 
				WHERE	requisito_digital = $requisito_digital;";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info("************ Se eliminó draft de requisito digital $requisito_digital. ************");
	}

	/**
	 * Inserta un requisito de egreso para un trámite de solicitud de certificación.
	 *
	 * @param int $nro_solicitud
	 * @param int $requisito
	 */
	function insertar_requisito_certificado($nro_solicitud, $requisito)
	{
		$nro_solicitud = toba::db()->quote($nro_solicitud);
		$requisito = toba::db()->quote($requisito);
		$sql = "INSERT INTO sga_requisitos_certificado
				(
					nro_solicitud,
					requisito
				) 
				VALUES (
					$nro_solicitud,
					$requisito
				);
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó requisito certificado con éxito. ************');
	}
}