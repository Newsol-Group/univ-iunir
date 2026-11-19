<?php
/**
 *
 * @package 	Propuestas 
 * @subpackage 	Actualizaciones
 * 
 */
class cn_man_niveles_formacion extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'niveles_formacion';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_planes')->get_niveles_formacion($filtro);			
	}
}
?>