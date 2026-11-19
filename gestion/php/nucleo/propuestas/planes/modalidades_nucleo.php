<?php
/**
 * Catálogo de Modalidades
 *
 * Atencion: esta clase se instancia con: guarani::catalogo_modalidades()
 *
 * @package		Propuestas
 * @subpackage	Planes
 */
class modalidades_nucleo
{
	// Tipos
	const presencial = 'P';
	const a_distancia = 'D';
	const semipresencial = 'S';

	static protected $instancia;

	private function __construct()
	{
		
	}

	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new modalidades();
		}
		return self::$instancia;
	}

}
?>