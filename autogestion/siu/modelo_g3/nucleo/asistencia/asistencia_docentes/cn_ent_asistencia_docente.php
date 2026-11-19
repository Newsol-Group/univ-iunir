<?php
/**
 *
 *
 * @package		Asistencias
 * @subpackage	-
 * 
 */
class cn_ent_asistencia_docente extends cn_entidad
{
	protected $s__carga_horas;

	function set_asistencias_por_horas($valor)
	{
		// Indica si la inasistencia se carga en horas o en presente/ausente.
		$this->s__carga_horas = $valor;
	}

	function set_datos($datos, $tabla = null, $validar = true)
	{
		$this->validar_datos($datos);
		$this->tabla('asistencia')->set($datos);
	}

	function get_datos($tabla = null)
	{
		return $this->tabla('asistencia')->get();
	}

	//-----------------------------------------------------------------------------------
	//---- Validaciones -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function validar_datos($datos)
	{
		// Control sobre el campo cant_inasistencias.
		if (isset($datos['cant_inasistencias'])) {
			$this->validar_cant_inasistencias($datos);
		}
		// Control sobre el campo cant_justificadas.
		if (isset($datos['cant_justificadas'])) {
			$this->validar_cant_justificadas($datos);
		}

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	/**
	 * Verifica que el valor de cantidad inasistencias esté dentro del rango permitido, de acuerdo al atributo asistencia_por_horas.
	 * Si es N el valor permitido será entre 0.5 a 1.
	 * Si es S el valor permitido será mayor a 0 y menor a la cantidad de horas totales de la clase.
	 */
	function validar_cant_inasistencias($datos)
	{
		$minimo = $this->obtener_valor_minimo_inasistencias();
		$maximo = $this->obtener_valor_maximo_inasistencias();
		if ($datos['cant_inasistencias'] < $minimo) {
			$this->set_error('800_valor_menor', array('Cantidad Inasistencias', $minimo));
		}
		if ($datos['cant_inasistencias'] > $maximo) {
			$this->set_error('800_valor_mayor', array('Cantidad Inasistencias', $maximo));
		}
	}

	/**
	 * Verifica que el valor de cantidad justificadas sea mayor a cero y no supere al del campo cantidad inasistencias.
	 */
	function validar_cant_justificadas($datos)
	{
		$cant_inasistencias = $datos['cant_inasistencias'];
		$cant_justificadas = $datos['cant_justificadas'];
		if ($cant_justificadas > $cant_inasistencias) {
			$this->set_error('800_valor_mayor', array('Cantidad Justificadas', 'Cantidad Inasistencias'));
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function obtener_valor_minimo_inasistencias()
	{
		if ($this->s__carga_horas == comunes::si) {
			return 0;
		} else {
			return 0.5;
		}
	}

	function obtener_valor_maximo_inasistencias()
	{
		if ($this->s__carga_horas == comunes::si) {
			$clase = $this->tabla('asistencia')->get_columna('clase');
			$datos_clase = toba::consulta_php('co_clases')->get_datos_clase($clase);
			return $datos_clase['cantidad_horas_dictadas'];
		} else {
			return 1;
		}
	}

}
?>