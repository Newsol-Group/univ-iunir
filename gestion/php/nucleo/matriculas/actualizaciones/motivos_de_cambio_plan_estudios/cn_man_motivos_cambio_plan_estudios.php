<?php
/**
 *
 *
 * @package 	Matricula
 * @subpackage 	Actualizaciones
 * 
 */
class cn_man_motivos_cambio_plan_estudios extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'motivo_cambio_plan_estudios';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_motivos_cambio_plan_estudios')->get_listado($filtro);			
	}
}

?>