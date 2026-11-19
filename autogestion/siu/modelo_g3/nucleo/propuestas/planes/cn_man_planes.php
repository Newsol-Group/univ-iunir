<?php
/**
 * Manejador de Planes de Estudios
 *
 *
 * @package Propuestas
 * @subpackage Plan
 */
class cn_man_planes extends cn_man_zona_planes
{
	function cargar($id, $parametros = null)
	{
		$salida = parent::cargar($id, $parametros);
		$this->entidad()->cargar_atributos_parametros();
		return $salida;
	}

	function get_indice_instancia()
	{
		return 'plan';
	}

	function get_clase_consulta()
	{
		return 'co_planes';
	}

	function get_proceso_activacion()
	{
		return 'proceso_activar_plan';
	}

	function get_proceso_versionador()
	{
		return 'proceso_versionar_plan';
	}

	function purgar_elementos_huerfanos()
	{
		guarani::act('act_planes')->purgar_elementos_huerfanos();
	}

	/**
	 * Dispara la eliminacion de plan cargado y corre el porceso depurador de elementos Huerfanos en
	 * la tabla sga_elementos
	 * @param $id
	 */
	function eliminar($id = null)
	{
		/**
		 * Ticket #1733: 
		 * 	AGREGADO DE ANALISIS Lo que tiene que hacer es que cuando se borra una version 
		 * 	en estado NUEVO, se deben eliminar tambien todas las matrices que tengan como 
		 * 	destino esa version de ese plan de estudios.
		 */
		$indice = isset($id) ? $id : $this->s__cursor;
		$plan_version = $this->entidad($indice)->get_id_plan_version();
		$estado = $this->entidad($indice)->get_estado_version();
		if ($estado == planes::estado_nuevo) {
			// Si es plan pers se desactivan las transacciones anidadas.
			$desactivar_trans = ($this->entidad($indice)->es_plan_personalizado()) ? true : false;
			guarani::act('act_planes')->borrar_matrices_plan_version_nuevo($plan_version, $desactivar_trans);
		}

		parent::eliminar();
		$this->purgar_elementos_huerfanos();
	}

	function get_datos_sugeridos_plan_version($plan_version)
	{
		$datos = current(toba::consulta_php('co_planes')->get_datos_plan_version($plan_version));
		$salida = array();

		// Nombre sugerido
		$salida['nombre'] = (strlen($datos['version_nombre']) <= 92) ? $datos['version_nombre'] . ' | COPIA' : '';

		// Versión sugerida
		$version_anterior = $datos['version'];

		$posicion_separador = strripos($version_anterior, '_');
		if (!$posicion_separador) {
			$version_sugerida = $version_anterior . '_1';
		} else {
			$nro = substr($version_anterior, $posicion_separador + 1);
			$nro++;
			$version_sugerida = substr($version_anterior, 0, $posicion_separador + 1) . $nro;
		}

		$salida['version'] = (strlen($version_sugerida) <= 10) ? $version_sugerida : '';

		return $salida;
	}

}
?>