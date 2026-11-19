<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Actualizaciones
 */
class cn_man_motivos_cambio_mod_cursada extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'motivo_cambio_modalidad_cursada';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_motivos_cambio_mod_cursada')->get_listado($filtro);			
	}
}
?>