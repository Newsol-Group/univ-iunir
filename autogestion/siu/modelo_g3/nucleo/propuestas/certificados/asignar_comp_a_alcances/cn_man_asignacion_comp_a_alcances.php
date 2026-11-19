<?php
/**
 *
 *
 * @package 	Propuestas Formativas
 * @subpackage	Certificados
 * 
 */
class cn_man_asignacion_comp_a_alcances extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'asignacion_comp_a_alcances';
	}

	function get_listado_preseleccion($filtro)
	{
		return toba::consulta_php('co_certificados')->get_listado($filtro);
	}

	function get_listado_asignacion_comp_a_alcances($certificado, $where='')
	{
		return toba::consulta_php('co_certificados')->get_alcances_x_certif($certificado, $where);
	}
}
?>