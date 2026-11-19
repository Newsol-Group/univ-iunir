<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Resultados
 */
class cn_man_resultados_de_aprendizaje extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'resultado_de_aprendizaje';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_resultados_de_aprendizaje')->get_listado($filtro);			
	}
}

?>