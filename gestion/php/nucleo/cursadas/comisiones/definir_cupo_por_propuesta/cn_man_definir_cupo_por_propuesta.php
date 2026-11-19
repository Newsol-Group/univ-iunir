<?php
/**
 *
 * @package Cursadas
 * @subpackage Comisiones
 */
class cn_man_definir_cupo_por_propuesta extends cn_manejador_entidad
{
	function etiquetas($dep=null)
	{
		return is_null($dep) ? guarani_etiquetas::get_tabla('sga_comisiones') : parent::etiquetas($dep);
	} 

	function get_listado($filtro)
	{
		$filtro .= " AND sga_comisiones.cupo_tipo = '".comision::cupo_tipo_propuesta."'";
		return toba::consulta_php('co_comisiones')->get_listado($filtro);
	}

	function actualizar_propuestas_cupo($comision, $datos)
	{
		try {
			foreach ($datos as $dato) {
				if ($dato['apex_ei_analisis_fila'] == 'M') {
					guarani::act('act_comisiones')->actualizar_propuesta_cupo($comision, $dato['propuesta'], $dato['cupo']);
				}
			}
		} catch (toba_error $e)	{
			throw $e;
		}
	}
}
?>