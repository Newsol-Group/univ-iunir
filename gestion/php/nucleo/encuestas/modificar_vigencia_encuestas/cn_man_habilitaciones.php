<?php
/**
 * 
 * @package Encuestas
 */
class cn_man_habilitaciones extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'habilitacion';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_encuestas')->get_listado_grupos($filtro);
	}
}
?>