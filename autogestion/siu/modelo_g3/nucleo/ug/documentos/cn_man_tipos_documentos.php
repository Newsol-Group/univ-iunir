<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Convenios
 */


class cn_man_tipos_documentos extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'tipo_documento';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_documentos')->get_listado_tipos_documentos($filtro);
	}	
}

?>