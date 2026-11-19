<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos
 * 
 */
class cn_man_requisitos extends cn_manejador_entidad
{
	
	function get_indice_instancia()
	{
		return 'requisito';
	}
	
	function get_listado($filtro)
	{
		if ($filtro != '') {
			$filtro .= ' AND ';
		}
		$filtro .= " sga_requisitos_tipos.publico IN ('".comunes::si."') ";
		return toba::consulta_php('co_requisitos')->get_listado($filtro);
	}

}
?>