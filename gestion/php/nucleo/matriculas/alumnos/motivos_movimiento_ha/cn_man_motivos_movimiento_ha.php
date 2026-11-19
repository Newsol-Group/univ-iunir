<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Alumnos
 */
class cn_man_motivos_movimiento_ha extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'movimiento_ha_motivo';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_motivos_movimiento_ha')->get_listado($filtro);			
	}
	
}

?>