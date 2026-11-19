<?php
/**
 *
 *
 * @package Docentes
 * @subpackage Actualizaciones
 */
class cn_man_catedras extends cn_manejador_entidad
{
	
	function get_indice_instancia()
	{
		return 'catedras';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_catedras')->get_catedras_perfil_de_datos($filtro);
	}
		
	function get_listado_docentes($filtro)
	{
		return toba::consulta_php('co_docentes')->get_listado($filtro);			
	}
	
	function get_listado_actividades($filtro)
	{
		return toba::consulta_php('co_elementos')->get_listado(elemento::tipo_actividad, null, $filtro);
	}
	
}

?>