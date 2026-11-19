<?php
/**
 *
 *
 * @package Matricula
 * @subpackage Actualizaciones
 */
class cn_man_motivos_de_cambio_de_calidad extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'motivos_de_cambio_de_calidad';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_motivos_de_cambio_de_calidad')->get_listado($filtro);			
	}
}

?>