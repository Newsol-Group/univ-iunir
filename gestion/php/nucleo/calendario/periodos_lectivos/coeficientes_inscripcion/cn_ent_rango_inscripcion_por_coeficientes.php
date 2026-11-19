<?php
/**
 * Representa un rango de inscripción por coeficiente
 *
 * @package Calendario
 * @subpackage Periodos Lectivos
 */
class cn_ent_rango_inscripcion_por_coeficientes extends cn_entidad
{
	/**
	 * Se redefine la función sincronizar del cn_entidad ya que este DR tiene una único DT.
	 * No puede realizarse la sincronización de la relación por no tener un único registro en la tabla cabecera.
	 *
	 */
	function sincronizar()
	{
		$this->dep('datos')->tabla('per_insc_coeficientes')->sincronizar();
	}

	//-----------------------------------------------------------------------------------
	//---- datos de coeficientes --------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_datos_coeficientes()
	{
		$datos = $this->tabla('per_insc_coeficientes')->get_filas();
		return rs_ordenar_por_columna($datos, 'fecha_inicio');
	}

	function set_datos_coeficientes($datos)
	{
		foreach ($datos as $dato) {
			if ($dato['apex_ei_analisis_fila'] != 'B') {
				$this->validar_datos($dato);
			}
		}
		$this->tabla('per_insc_coeficientes')->procesar_filas($datos);
	}

	function agregar_datos_coeficientes($datos)
	{
		foreach ($datos as $dato) {
			$this->validar_datos($dato);
			$this->dep('datos')->tabla('per_insc_coeficientes')->nueva_fila($dato);
		}
	}
	
	function validar_datos($datos) 
	{
		$datos_periodo_inscripcion = toba::consulta_php('co_periodos_de_inscripcion')->get_datos_periodo_insc_fechas($datos['periodo_insc']);

		$formateo = new toba_formateo('html');
		$fecha_ini_format = $formateo->formato_fecha($datos_periodo_inscripcion['fecha_inicio']);
		$fecha_fin_format = $formateo->formato_fecha($datos_periodo_inscripcion['fecha_fin']);
		$fecha_tope_bajas_format = $formateo->formato_fecha($datos_periodo_inscripcion['fecha_tope_bajas']);

		// El coeficiente desde debe ser menor o igual el coeficiente hasta.
		if ($datos['coeficiente_desde'] > $datos['coeficiente_hasta']) {
			$this->set_error('800_valor_mayor', array("coeficiente desde ({$datos['coeficiente_desde']})", "coeficiente hasta ({$datos['coeficiente_hasta']})"));
		}
		// La fecha y hora de inicio debe ser menor o igual a la fecha y hora de finalización.
		if (guarani_fecha::es_menor($datos['fecha_fin'], $datos['fecha_inicio'], false, true)) {
			$this->set_error('800_control_fechas', array('de fin del rango de coeficientes', 'menor', 'de inicio.'));
		}
		// Tanto la fecha y hora de inicio como la fecha y hora de finalización deben estar comprendidas dentro del rango definido en el período de inscripción.
		if (!guarani_fecha::esta_entre_fechas_horas($datos['fecha_inicio'], $datos_periodo_inscripcion['fecha_inicio'], $datos_periodo_inscripcion['fecha_fin'])) {
			$this->set_error('800_control_fecha_entre', array('inicio del rango de coeficientes', "inicio período inscripción: $fecha_ini_format", "fin período inscripción: $fecha_fin_format"));
		}
		if (!guarani_fecha::esta_entre_fechas_horas($datos['fecha_fin'], $datos_periodo_inscripcion['fecha_inicio'], $datos_periodo_inscripcion['fecha_fin'])) {
			$this->set_error('800_control_fecha_entre', array('fin del rango de coeficientes', "inicio período inscripción: $fecha_ini_format", "fin período inscripción: $fecha_fin_format"));
		}
		// La fecha tope de bajas debe estar comprendida en el rango [fecha de inicio, fecha tope de bajas del período de inscripción].
		if (!guarani_fecha::esta_entre_fechas_horas($datos['fecha_tope_bajas'], $datos['fecha_inicio'], $datos_periodo_inscripcion['fecha_tope_bajas'])) {
			$this->set_error('800_control_fecha_entre', array('de tope de bajas', 'inicio', "tope de bajas período inscripción: $fecha_tope_bajas_format"));
		}

		// Los rangos de coeficientes definidos no pueden superponerse.
		$rangos = $this->get_datos_coeficientes();
		foreach ($rangos as $rango) {
			if (!isset($datos['x_dbr_clave']) || $datos['x_dbr_clave'] != $rango['x_dbr_clave']) {
				if ($rango['coeficiente_desde'] >= $datos['coeficiente_desde'] && $rango['coeficiente_desde'] <= $datos['coeficiente_hasta'] ||
					$rango['coeficiente_hasta'] >= $datos['coeficiente_desde'] && $rango['coeficiente_hasta'] <= $datos['coeficiente_hasta']) {
					$this->set_error('800CAL_rango_coeficientes_erroneo', array($datos['coeficiente_desde'], $datos['coeficiente_hasta']));
				}
			}
		}

		if ($this->hay_errores) {
			throw guarani::error($this->errores);
		}
	}

}