<?php
/**
 *
 *
 * @package Responsables_Academicas
 * @subpackage Edificios
 */
class cn_man_edificios extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'edificio';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_edificios')->get_listado($filtro);
	}	
}

?>