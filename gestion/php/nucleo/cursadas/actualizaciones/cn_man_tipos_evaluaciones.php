<?php
/**
 *
 * @package Cursadas
 * @subpackage Actualizaciones
 */
class cn_man_tipos_evaluaciones extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'tipo_evaluacion';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_evaluaciones')->get_tipos_no_automatica($filtro);
	}
}

?>