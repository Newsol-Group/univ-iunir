<?php
/**
 *
 *
 * @package Responsables_Academicas
 * @subpackage Tipos de Aulas
 */
class cn_man_tipo_aula extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'tipoaula';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_aulas')->get_listado_tipos_aulas($filtro);
	}	
}

?>