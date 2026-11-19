<?php
/**
 *
 *
 * @package Operaciones Generales
 * @subpackage Búsqueda de Documentos
 */
class cn_man_documento extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'documento';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_documentos')->get_listado($filtro);
	}	
}

?>