<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Certificados
 */
class cn_man_certificado extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'certificado';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_certificados')->get_listado($filtro);
	}
	
}	
?>