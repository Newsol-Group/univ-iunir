<?php
/**
 *
 * @package		Propuestas Formativas
 * @subpackage	Actividades
 */
class cn_man_actividad_excepciones_comunes extends guarani_pers_cn
{
	protected $s__excepciones;

	function etiquetas($dep=null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_elementos_no_comunes');
		} else {
			parent::etiquetas($dep);
		}
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_elementos')->get_actividades_varios_planes($filtro);
	}

	function set_excepciones($datos)
	{
		$this->s__excepciones = $datos;
	}

	function guardar_excepciones($elemento, $plan_origen)
	{
		$planes_destino = array();
		foreach ($this->s__excepciones as $excepcion) {
			if ($excepcion['exceptuado'] == 1) {
				$planes_destino[] = $excepcion['plan_destino'];
			}
		}
		return guarani::act('act_elementos')->modificar_elementos_no_comunes($elemento, $plan_origen, $planes_destino);
	}

	function resetear()
	{
		unset($this->s__excepciones);
	}
}