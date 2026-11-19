<?php
/**
 *
 *
 * @package Egresados
 * @subpackage Juramentos
 */
class cn_man_juramentos extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'juramentos';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_juramentos')->get_listado($filtro);			
	}
}

?>