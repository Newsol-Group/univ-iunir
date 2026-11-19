<?php 
/**
 *
 *
 * @package 	Propuestas Formativas
 * @subpackage 	-
 * 
 */
class cn_man_propuestas extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'propuestas';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_propuestas')->get_listado($filtro);			
	}
	
}
?>