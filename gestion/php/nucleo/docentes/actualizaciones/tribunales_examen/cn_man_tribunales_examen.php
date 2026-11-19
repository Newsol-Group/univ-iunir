<?php
/**
 * @package Docentes
 * @subpackage Actualizaciones
 */
class cn_man_tribunales_examen extends cn_man_modulo
{
	
	function get_indice_instancia()
	{
		return 'tribunal_examen';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_tribunales')->get_tribunales_perfil_de_datos($filtro);
	}
	
	function get_listado_docentes($filtro)
	{
		return toba::consulta_php('co_docentes')->get_listado($filtro);			
	}
	
	function get_listado_actividades($filtro)
	{
		return toba::consulta_php('co_elementos')->get_listado(elemento::tipo_actividad, null, $filtro);
	}
	
}

?>