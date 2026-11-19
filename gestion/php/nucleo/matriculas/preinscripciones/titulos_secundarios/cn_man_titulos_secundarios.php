<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Preinscripciones
 */
class cn_man_titulos_secundarios extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'titulo_secundario';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_titulos')->get_listado($filtro);
	}
	
}