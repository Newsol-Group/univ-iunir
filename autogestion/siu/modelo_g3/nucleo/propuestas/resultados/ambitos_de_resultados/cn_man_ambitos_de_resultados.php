<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Resultados
 */
class cn_man_ambitos_de_resultados extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'ambito_de_resultados';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_ambitos_de_resultados')->get_listado($filtro);			
	}
}

?>