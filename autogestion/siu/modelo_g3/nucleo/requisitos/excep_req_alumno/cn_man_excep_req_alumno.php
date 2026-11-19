<?php
/**
 * 
 *
 * @package 	Requisitos
 * @subpackage 	-
 * 
 */
class cn_man_excep_req_alumno extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'excep_req_alumno';
	}

	function get_listado_preseleccion($filtro)
	{
		return toba::consulta_php('co_alumnos')->get_listado($filtro);
	}

	function get_listado_excep_req_alumno($alumno)
	{
		$filtro = "sga_requisitos_excepciones.alumno = $alumno";
		return toba::consulta_php('co_requisitos')->get_requisitos_excepciones($filtro);
	}
}
?>