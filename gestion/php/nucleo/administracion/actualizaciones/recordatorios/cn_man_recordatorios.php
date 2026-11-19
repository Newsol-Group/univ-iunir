<?php
/**
 *
 *
 * @package Administracion
 * @subpackage Actualizaciones
 */
class cn_man_recordatorios extends cn_manejador_entidad
{
	
	function get_indice_instancia()
	{
		return 'recordatorio';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_recordatorios')->get_listado($filtro);
	}
	
}

?>