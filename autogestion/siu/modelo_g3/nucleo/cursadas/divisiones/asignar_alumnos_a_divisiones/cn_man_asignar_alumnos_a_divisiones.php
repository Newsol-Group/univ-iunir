<?php
/**
 *
 *
 * @package 	Cursadas
 * @subpackage 	Divisiones
 * 
 */
class cn_man_asignar_alumnos_a_divisiones extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'asignar_alumno_a_division';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_divisiones')->get_listado($filtro);
	}
	
}
?>