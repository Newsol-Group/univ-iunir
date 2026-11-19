<?php
/**
 * 
 * @package Encuestas
 */
class cn_man_vincular_encuestas extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'encuesta';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_encuestas')->get_listado($filtro);
	}
}
?>