<?php
/**
 *
 *
 * @package 	Comunicación
 * @subpackage
 * 
 */
class cn_man_grupos_destinatarios extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'grupo_destinatarios';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_grupos_destinatarios')->get_listado($filtro);
	}
	
	function get_listado_alumnos($filtro_personas, $filtro_alumnos)
	{
		$filtro = $filtro_personas . " AND mdp_personas.persona IN (SELECT sga_alumnos.persona FROM sga_alumnos WHERE sga_alumnos.calidad = '". alumno::calidad_activo ."' AND $filtro_alumnos)";
		return toba::consulta_php('co_personas')->get_listado($filtro);
	}
	
	function get_listado_docentes($filtro)
	{
		return toba::consulta_php('co_docentes')->get_docentes_activos($filtro);
	}
}
?>