<?php
/**
 * Constantes para Trámites de Solicitud de Certificado.
 *
 * @package Egresados
 * @subpackage Actualizaciones
 */
class tramite_certificacion_nucleo
{
	// Estados de un trámite.
	const estado_adelantado = 2;
	const estado_observado = 3;

	// Estados de presentación de requisitos de egreso para un trámite.
	const estado_requisitos_abierto = 'B';
	const estado_requisitos_abierto_descr = 'Abierto';
	const estado_requisitos_aceptado = 'A';
	const estado_requisitos_aceptado_descr = 'Aceptado';
	const estado_requisitos_pendiente = 'P';
	const estado_requisitos_pendiente_descr = 'Pendiente';

	// Niveles de un título.
	const nivel_titulo_final = 'FINAL';
	const nivel_titulo_intermedio = 'INTERMEDIO';
	const nivel_titulo_todos = 'TODOS';

	// Variables que tienen seteado el .jasper
	const plantillaAnalitico = 'CertificadoAnalitico.jasper';
	const plantillaDiploma = 'Diploma.jasper';

	static protected $instancia;

	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new tramite_certificacion();
		}
		return self::$instancia;
	}

	function __construct()
	{
		
	}

	/**
	 * Controla el pago de una cuota.
	 *
	 * @return boolean
	 */
	static function verificar_pago()
	{
		return true;
	}
	
	/**
	 * Frase Rector/a y Decano/a del Diploma.
	 *
	 * @return string
	 */
	static function frase_rector_decano_diploma()
	{
		return "El/la Rector/a de la Universidad y el/la Decano/a de la ";
	}
	
	/**
	 * Fecha actual del Diploma.
	 *
	 * @return string
	 */
	static function fecha_actual_diploma()
	{
		$meses = comunes::get_meses_anio();
		$dia = date("d");
		$mes = date("n");
		$anio = date("Y");
		return "{$dia} de {$meses[$mes]} de {$anio}";
	}
}