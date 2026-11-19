<?php
/**
 * Catálogo de Turnos de Exámen
 *
 *
 * @package Calendario Académico
 * @subpackage Turnos de Exámen
 */
class turno_de_examen_nucleo
{
    // Tipos 
    const tipo_normal = 1;
    const tope_fecha_hora_examen = 0;
    const tope_fecha = 1;
    const tope_dias_antes = 2;
    const tope_dias_antes_habiles = 3;
    const tope_hs_antes = 4;
    const tope_hs_antes_habiles = 5;
    const tope_fecha_hora_examen_descr = 'Hasta la fecha y hora del examen';
    const tope_fecha_descr = 'Fecha';
    const tope_dias_antes_descr = 'Días antes de cada mesa';
    const tope_dias_antes_habiles_descr = 'Días hábiles antes de cada mesa';
    const tope_hs_antes_descr = 'Hs. antes de cada mesa';
    const tope_hs_antes_habiles_descr = 'Hs. hábiles antes de cada mesa';
    
	static protected $instancia;

	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new turno_de_examen();
		}
		return self::$instancia;	
	}

	private function __construct()
	{
	
	}

	static function esta_finalizado($turno)
	{
		return guarani::co('co_turnos_de_examen')->es_turno_finalizado($turno);
	}

	static function llamado_esta_finalizado($llamado)
	{
		return guarani::co('co_turnos_de_examen')->es_llamado_finalizado($llamado);
	}

	static function get_llamado_id_de_periodo($periodo)
	{
		return guarani::co('co_turnos_de_examen')->get_llamado_id_de_periodo($periodo);
	}
}

?>