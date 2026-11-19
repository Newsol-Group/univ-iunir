<?php
/**
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Títulos Institucionales
 */
class cn_man_titulos_institucionales extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'titulo_institucional';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_personas')->get_listado_personas_titulos($filtro);
	}
}
?>