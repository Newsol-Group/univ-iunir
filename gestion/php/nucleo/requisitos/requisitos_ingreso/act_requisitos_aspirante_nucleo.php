<?php
/**
 * 
 * Ejecución de funciones o scripts que producen cambios en la base de negocio
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class act_requisitos_aspirante_nucleo
{
	/**
	 * Registra excepciones a requisitos de ingreso
	 */
	function registrar_excepciones($condicion_in, $parametros)
	{
		$fecha_excepcion 	 = toba::db()->quote($parametros['fecha_excepcion']);
		$motivo_excepcion 	 = $parametros['motivo_excepcion'];
		$documento_excepcion = is_null($parametros['documento_resp']) ? 'null' : $parametros['documento_resp'];
		$observaciones 		 = is_null($parametros['observaciones'])  ? 'null' : toba::db()->quote($parametros['observaciones']);
		
		$sql = "UPDATE 	sga_requisitos_aspirante
		
				SET 	observaciones		= $observaciones,
						fecha_excepcion		= $fecha_excepcion,
						motivo_excepcion	= $motivo_excepcion,
						documento_excepcion = $documento_excepcion,
						exceptuado			= '" . comunes::si . "'
		
				WHERE 	$condicion_in
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó con éxito las excepciones masivas a requisitos de ingreso ************');
	}
	
	/**
	 * Registra prorrogas a requisitos de ingreso
	 */
	function registrar_prorrogas($condicion_in, $parametros)
	{
		$fecha_prorroga 	= toba::db()->quote($parametros['fecha_prorroga']);
		$documento_prorroga = is_null($parametros['documento_prorroga']) ? 'null' : $parametros['documento_prorroga'];
		
		$sql = "UPDATE 	sga_requisitos_aspirante
		
				SET 	fecha_prorroga	   = $fecha_prorroga,
						documento_prorroga = $documento_prorroga
		
				WHERE 	$condicion_in
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó con éxito las excepciones masivas a requisitos de ingreso ************');
	}

	/**
	 * Elimina los requisitos de una inscripción a propuesta.
	 *
	 * @param integer $propuesta_aspira
	 */
	function eliminar_requisitos_aspirante($propuesta_aspira)
	{
		$propuesta_aspira = toba::db()->quote($propuesta_aspira);
		$sql = "DELETE
				FROM	sga_requisitos_aspirante
				WHERE	propuesta_aspira = $propuesta_aspira
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los requisitos de la inscripción a propuesta con éxito. ************');
	}
	
	/**
	 * Inserta un registro de requisito aspirante
	 *
	 * @param int $propuesta_aspira
	 * @param int $requisito
	 * @param int $restrictivo
	 */
	function insertar_requisito_aspirante($propuesta_aspira, $requisito, $restrictivo, $parametros=null)
	{
		$propuesta_aspira = toba::db()->quote($propuesta_aspira);
		$requisito = toba::db()->quote($requisito);
		$restrictivo = toba::db()->quote($restrictivo);
		$parametros = empty($parametros) ? 'null' : toba::db()->quote($parametros);

		$sql = "INSERT INTO sga_requisitos_aspirante
				(
					propuesta_aspira,
					requisito,
					restrictivo,
					parametros
				) 
				VALUES (
					$propuesta_aspira,
					$requisito,
					$restrictivo,
					$parametros
				);
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó requisito de requisito aspirante con éxito. ************');
	}
}
?>