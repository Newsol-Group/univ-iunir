<?php
/**
 * Constantes para Escalas de notas
 *
 * @package Propuestas
 * @subpackage Escalas de Notas
 */
class escalas_notas_nucleo
{	
	//Tipos
	const tipo_continua = 'CONTINUA';
	const tipo_discreta = 'DISCRETA';

	//Es numerica
	const es_numerica		= 'S';
	const no_es_numerica	= 'N';

	// Resultados.
	const resultado_aprobado 			= 'A';
	const resultado_no_promocionado 	= 'N';
	const resultado_promocionado 		= 'P';
	const resultado_reprobado 			= 'R';
	const resultado_ausente 			= 'U';

	// Separadores decimales
	const separador_punto 	= '.';
	const separador_coma 	= ',';
	/**
	 * Retorna el objeto Validador de escalas de notas de la clase validador_escalas_notas
	 *
	 * @param <type> $escalas
	 * @return <type>
	 */

	static function validador_escalas_notas()
	{
		return validador_escalas_notas::instancia();
	}
	
	
	static function get_resultado_descripcion($resultado)
	{
		switch ($resultado) {
			case self::resultado_aprobado:
				return 'Aprobado';
			case self::resultado_no_promocionado:
				return 'No Promocionado';
			case self::resultado_promocionado:
				return 'Aprobado';
			case self::resultado_reprobado:
				return 'Reprobado';
			case self::resultado_ausente:
				return 'Ausente';
			default:
				return '';
		}
	}

	static function get_tipos()
	{
		return array(array('valor' => self::tipo_discreta, 'descr' => 'Discreta'),
					 array('valor' => self::tipo_continua, 'descr' => 'Continua')
					);
	}

}

?>