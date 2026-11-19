<?php
/**
 * Catálogo de Períodos Genéricos
 *
 *
 * @package Calendario Académico
 * @subpackage Períodos Genéricos
 */
class periodo_generico_nucleo
{
	// Tipos 
	const tipo_periodo_lectivo = 1;
	const tipo_turno_examen = 2;
	const tipo_llamado = 3;
	const tipo_propuesta = 4;
		
	//Ids de Períodos Genéricos
	const turno_examen = 30;
	const llamado = 31;

	static protected $instancia;

	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new persona();	
		}
		return self::$instancia;	
	}

	private function __construct()
	{
	
	}

}

?>