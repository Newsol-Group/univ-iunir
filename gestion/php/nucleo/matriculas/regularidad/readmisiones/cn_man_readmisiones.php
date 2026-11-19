<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Regularidad
 */
class cn_man_readmisiones extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'readmision';
	}
	
	function get_listado_preseleccion($where='')
	{
		$where .= " AND sga_alumnos.calidad = '".comunes::estado_activo."'";
		return toba::consulta_php('co_alumnos')->get_alumnos_perfil_de_datos($where);
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_readmisiones')->get_perdidas_regularidades($filtro);
	}
	
	function get_listado_readmisiones($where='')
	{
		return toba::consulta_php('co_readmisiones')->get_listado($where);
	}
	
}
?>