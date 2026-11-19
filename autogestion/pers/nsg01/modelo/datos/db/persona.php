<?php

namespace nsg01\modelo\datos\db;

use siu\modelo\datos\db\persona as persona_nsg;

class persona extends persona_nsg
{
	const PRIORIDAD_PROPUESTA_MADRE = 0;
	const PRIORIDAD_CURSO_INGRESO = 1;
	const PRIORIDAD_CURSO = 2;

	/**
	 * parametros: persona
	 * cache: sesion
	 * filas: n
	 */
	function carreras($parametros)
	{
		$carreras = parent::carreras($parametros);

		foreach ($carreras as $orden => &$carrera) {
			$carrera['_orden_default_nsg'] = $orden;
		}
		unset($carrera);

		usort($carreras, array($this, 'comparar_prioridad_default'));

		foreach ($carreras as &$carrera) {
			unset($carrera['_orden_default_nsg']);
		}
		unset($carrera);

		return $carreras;
	}

	/**
	 * parametros: persona
	 * cache: memoria
	 * filas: 1
	 */
	function get_propuesta_inicial($parametros)
	{
		$propuesta_inicial = parent::get_propuesta_inicial($parametros);
		if (is_null($propuesta_inicial) || $propuesta_inicial === '') {
			return null;
		}

		$carreras = $this->carreras($parametros);
		$propuesta = $this->get_propuesta_en_carreras($carreras, $propuesta_inicial);
		if (is_null($propuesta)) {
			return null;
		}

		if ($this->es_propuesta_no_madre($propuesta) && $this->tiene_propuesta_madre($carreras)) {
			return null;
		}

		return $propuesta_inicial;
	}

	protected function comparar_prioridad_default($a, $b)
	{
		$prioridad_a = $this->get_prioridad_default($a);
		$prioridad_b = $this->get_prioridad_default($b);

		if ($prioridad_a != $prioridad_b) {
			return $prioridad_a - $prioridad_b;
		}

		return $a['_orden_default_nsg'] - $b['_orden_default_nsg'];
	}

	protected function get_prioridad_default($propuesta)
	{
		if (!isset($propuesta['propuesta_tipo'])) {
			return self::PRIORIDAD_PROPUESTA_MADRE;
		}

		switch ((int) $propuesta['propuesta_tipo']) {
			case \propuesta::tipo_curso_ingreso:
				return self::PRIORIDAD_CURSO_INGRESO;
			case \propuesta::tipo_curso:
				return self::PRIORIDAD_CURSO;
			default:
				return self::PRIORIDAD_PROPUESTA_MADRE;
		}
	}

	protected function es_propuesta_no_madre($propuesta)
	{
		return $this->get_prioridad_default($propuesta) > self::PRIORIDAD_PROPUESTA_MADRE;
	}

	protected function tiene_propuesta_madre($carreras)
	{
		foreach ($carreras as $carrera) {
			if (!$this->es_propuesta_no_madre($carrera)) {
				return true;
			}
		}

		return false;
	}

	protected function get_propuesta_en_carreras($carreras, $id)
	{
		foreach ($carreras as $carrera) {
			if ((string) $carrera['propuesta'] == (string) $id) {
				return $carrera;
			}
		}

		return null;
	}
}

?>
