<?php
/**
 *
 *
 * @package 	Propuestas Formativas
 * @subpackage 	Certificados
 * 
 */
class cn_man_alcances extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'alcance';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_certificados')->get_listado_alcances($filtro);			
	}
}
?>