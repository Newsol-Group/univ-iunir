<?php
/**
 *	Representa una Sanción
 *
 * @package Matrícula
 * @subpackage Actualizaciones
 */
class cn_ent_sancion extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('sanciones')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('sanciones')->get();
	}

	//-------------------------------------------------------------------

	function validar_datos($datos)
	{
		$datos['sancion'] = $this->tabla('sanciones')->get_columna('sancion');

		$this->validar_sanciones_persona($datos['sancion'], $datos['persona'], $datos['fecha_desde'], $datos['fecha_hasta']);
		$this->validar_fechas_desde_hasta($datos['fecha_desde'], $datos['fecha_hasta']);
		$this->validar_fecha_cancelacion($datos['fecha_desde'], $datos['fecha_hasta'], $datos['fecha_cancelacion']);
		$this->informar_fecha_hasta_menor_que_actual($datos['fecha_hasta']);

		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	/**
	 *  Valida si la Persona no tiene otra sanción que se superponga con la actual,
	 * 	o si la misma fue cancelada y no se superpone
    */
	function validar_sanciones_persona($sancion, $persona, $fecha_desde, $fecha_hasta)
	{		
		$filtro = "sga_sanciones.persona = $persona ";
		if (!is_null($sancion)) {
			$filtro .= "AND sga_sanciones.sancion <> $sancion ";
		}
		$sanciones = toba::consulta_php('co_sanciones')->get_listado($filtro);
		foreach ($sanciones as $clave => $valor) {
			$fecha_hasta2 = !empty($valor['fecha_cancelacion']) ? $valor['fecha_cancelacion'] : $valor['fecha_hasta'];
			if (guarani_fecha::rango_solapado_en_rango($fecha_desde, $fecha_hasta, $valor['fecha_desde'], $fecha_hasta2)) {
				$this->set_error('800_fecha_solapada', array('Fecha Desde', 'Fecha Hasta', 'Persona'));
				break;
			}
		}
	}
			
	/**
	 *  Valida si la Fecha Desde es menor o igual que la Fecha Hasta
    */
	function validar_fechas_desde_hasta($fecha_desde, $fecha_hasta)
	{
		if (!empty($fecha_hasta) && guarani_fecha::es_mayor($fecha_desde, $fecha_hasta)) {
			$this->set_error('800_control_fechas', array('Desde', 'mayor', 'Hasta.'));
		}
	}
	
	/**
	 *  Valida si la Fecha Cancelación se encuentra dentro del rango [Fecha Desde, Fecha Hasta]
    */
	function validar_fecha_cancelacion($fecha_desde, $fecha_hasta, $fecha_cancelacion)
	{
		if (!empty($fecha_cancelacion)) {
			if (guarani_fecha::es_menor($fecha_cancelacion, $fecha_desde) || (!empty($fecha_hasta) && guarani_fecha::es_mayor($fecha_cancelacion, $fecha_hasta)) ) {
				$this->set_error('800_control_fecha_entre', array('de Cancelación', 'Desde', 'Hasta'));
			}
		}
	}
	
	/**
	 *  En caso de que la Fecha Hasta sea menor que la Fecha Actual lo informa
    */
	function informar_fecha_hasta_menor_que_actual($fecha_hasta)
	{
		$hoy = guarani_fecha::get_hoy();
		if (!empty($fecha_hasta) && guarani_fecha::es_menor($fecha_hasta, $hoy)) {
			$mensaje = guarani_mensajes::get('800_fecha_menor_que_actual', array('Hasta'));
			toba::notificacion()->agregar($mensaje,'info');
		}
	}
}
?>