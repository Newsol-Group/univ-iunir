<?php
/**
 *
 *
 * @package Matricula
 * @subpackage Actualizaciones
 */
class cn_man_expulsados_en_otras_instituciones extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'expulsado_en_otra_institucion';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_expulsados_en_otras_instituciones')->get_listado($filtro);			
	}
}

?>