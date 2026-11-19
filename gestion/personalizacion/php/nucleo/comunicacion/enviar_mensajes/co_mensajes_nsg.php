<?php
/**
 *
 *
 * @package 	Comunicación
 * @subpackage 	-
 * 
 */
class co_mensajes_nsg extends co_mensajes
{

	function get_alumnos_morosos($dominio)
	{
		$where = guarani_sql::armar_condicion_in($dominio, 'sga_alumnos', 'propuesta', 'propuesta');

		$sql = "SELECT 	DISTINCT sga_alumnos.persona
					FROM 	int_sq_morosos
					JOIN 	sga_alumnos ON sga_alumnos.alumno = int_sq_morosos.alumno
					JOIN	mdp_personas ON mdp_personas.persona = sga_alumnos.persona
					WHERE	sga_alumnos.calidad = '" . comunes::estado_activo . "'
					AND $where
				";
		return guarani::db()->consultar($sql);
	}

}
?>
