<?php
/**
 *
 *
 * @package Docentes
 * @subpackage Actualizaciones
 */
class cn_man_catedras_equivalentes extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'catedra_eq';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_catedras_equivalentes')->get_catedras_equivalentes_perfil_de_datos($filtro);
	}
}
?>