<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Personas
 * 
 */
class co_capacidades_especiales
{
	
	function get_condicion_discapacidad()
	{
		$sin_discapacidad_descr = (comunes::es_interfaz_gestion())? persona::SIN_DISCAPACIDAD_DESCR_GESTION : persona::SIN_DISCAPACIDAD_DESCR;
		$con_discapacidad_descr = (comunes::es_interfaz_gestion())? persona::CON_DISCAPACIDAD_DESCR_GESTION : persona::CON_DISCAPACIDAD_DESCR;
		
		return [
			['valor' => comunes::no, 'descr' => $sin_discapacidad_descr],
			['valor' => comunes::si, 'descr' => $con_discapacidad_descr],
		];
	}
	
	function get_dificultades()
	{	
		$sql = "SELECT	
		                mdp_dificultad_discapacidad.dificultad AS valor,
						mdp_dificultad_discapacidad.descripcion AS descr
				FROM mdp_dificultad_discapacidad
				ORDER BY mdp_dificultad_discapacidad.dificultad
				";

		return guarani_db::consultar($sql, null, false);
	}
}