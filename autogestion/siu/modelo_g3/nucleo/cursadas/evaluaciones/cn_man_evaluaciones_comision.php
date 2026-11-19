<?php
/**
 *
 * 
 * @package		Cursadas
 * @subpackage	Evaluaciones
 * 
 */
class cn_man_evaluaciones_comision extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'evaluacion_comision';
	}

	function get_listado_comisiones($filtro)
	{
		$filtro_activas = "sga_comisiones.estado = '" . comunes::estado_activo . "'";
		$filtro = empty($filtro) ? $filtro_activas : $filtro . ' AND ' . $filtro_activas;
		$datos = toba::consulta_php('co_comisiones')->get_listado($filtro);
		return $datos;
	}

	function get_listado_comisiones_por_entidad($entidad)
	{
		$condicion = "sga_comisiones.entidad = $entidad";
		$datos = toba::consulta_php('co_comisiones')->get_listado($condicion);
		return $datos;
	}

	function get_listado_evaluaciones_por_entidad($entidad)
	{
		$datos = toba::consulta_php('co_evaluaciones')->get_evaluaciones_por_entidad($entidad);
		return $datos;
	}

	function get_instancias_comision($comision)
	{
		return toba::consulta_php('co_comisiones')->get_instancias_comision($comision);
	}
	
	function reabrir_evaluacion($datos)
	{
		guarani::act('act_evaluaciones_nucleo')->cambio_estado_evaluacion($datos);
	}
}
?>