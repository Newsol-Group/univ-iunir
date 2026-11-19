<?php
/**
 *
 *
 * @package 	Matricula
 * @subpackage 	Alumnos
 * 
 */
class cn_man_orientaciones extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'orientacion';
	}
	
	function etiquetas($dep=null)
	{
		return guarani_etiquetas::get_tabla('sga_alumnos');
	}
	
	function get_listado($filtro)
	{
		// Sólo deberán listarse alumnos activos.
		$where = "sga_alumnos.calidad = '".alumno::calidad_activo."'";
		if ($filtro) {
			$where .= "	AND $filtro";
		}
		return toba::consulta_php('co_alumnos')->get_alumnos_perfil_de_datos($where);
	}
}
?>