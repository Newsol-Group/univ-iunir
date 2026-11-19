<?php
/**
 *
 *
 * @package 	Propuestas Formativas
 * @subpackage	Certificados
 * 
 */
class cn_man_escalas_cumplimiento extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'escala_cumplimiento';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_certificados')->get_listado_escalas_cumplimiento($filtro);
	}
}
?>