<?php
/**
 *
 *
 * @package 	Administración
 * @subpackage 	Parámetros
 * 
 */
class cn_man_parametros extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'parametros';
	}

	function get_listado($filtro = '', $operacion = '')
	{
		$order_by = 'parametro';
		if (!empty($operacion)) {
			if (!empty($filtro)) {
				$filtro .= ' AND ';
			}
			$filtro .= "par_parametros_sistema.parametro IN (
							SELECT	par_parametros_etiquetas.parametro
							FROM 	par_parametros_etiquetas
							WHERE 	par_parametros_etiquetas.etiqueta = " . toba::db()->quote($operacion);
			$filtro .= ')';
		}
		return toba::consulta_php('co_parametros')->get_listado($filtro, $order_by);
	}
	
	function get_operaciones_con_parametros($filtro = '')
	{
		return toba::consulta_php('co_parametros')->get_operaciones_con_parametros($filtro);
	}
	
	function get_modulos_con_parametros($filtro = '')
	{
		return toba::consulta_php('co_parametros')->get_modulos_con_parametros($filtro);
	}

}
?>