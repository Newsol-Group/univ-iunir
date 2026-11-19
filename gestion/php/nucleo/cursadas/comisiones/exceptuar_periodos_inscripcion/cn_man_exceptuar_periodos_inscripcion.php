<?php
/**
 *
 * @package Cursadas
 * @subpackage Comisiones
 */
class cn_man_exceptuar_periodos_inscripcion extends cn_manejador_entidad
{
	function etiquetas($dep=null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_elementos');
		} else {
			parent::etiquetas($dep);
		}
	}
	
	function get_listado_exceptuar_periodos_insc($filtro_comisiones, $filtro_actividades, $order_by)
	{
		$datos = toba::consulta_php('co_elementos')->get_actividades_propuestas_con_comisiones($filtro_comisiones, $filtro_actividades, $order_by);
		$actividades = array();
		foreach ($datos as $key => $value) {
			if ($value['cantidad_comisiones'] > 0) {
				$actividades[] = $value;
			}
		}
		return $actividades;
	}
	
	function exceptuar_periodos_inscripcion($fecha_inicio, $fecha_fin, $fecha_tope_bajas, $fecha_fin_periodo_lectivo, $comisiones)
	{
		//Formateo tipo timestamp
		$fecha_inicio = "'{$fecha_inicio[0]} {$fecha_inicio[1]}'";
		$fecha_fin = "'{$fecha_fin[0]} {$fecha_fin[1]}'";
		$fecha_tope_bajas = "'{$fecha_tope_bajas[0]} {$fecha_tope_bajas[1]}'";
		
		//Validación de datos
		$this->validar_datos($fecha_inicio, $fecha_fin, $fecha_tope_bajas, $fecha_fin_periodo_lectivo);
		
		try {
			toba::db()->abrir_transaccion();
			
			/**
			 * Para las comisiones seleccionadas, se les elimina las excepciones a todos los períodos
			 * de inscripción, y luego se guardan los datos de la excepción al período de inscripción
			 */
			guarani::act('act_comisiones')->eliminar_excepciones_periodos_inscripcion($comisiones);
			guarani::act('act_comisiones')->insertar_excepciones_periodos_inscripcion($comisiones, $fecha_inicio, $fecha_fin, $fecha_tope_bajas);
			
			toba::db()->cerrar_transaccion();
			$mje = guarani_mensajes::get('800CUR_comisiones_excep_perinsc_ok');
			toba::notificacion()->agregar($mje, comunes::error_informacion);
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}
	
	function validar_datos($fecha_inicio, $fecha_fin, $fecha_tope_bajas, $fecha_fin_periodo_lectivo)
	{
		if (guarani_fecha::es_mayor($fecha_fin, $fecha_fin_periodo_lectivo)) {
			$msj = guarani_mensajes::get('800_control_fechas', array('fin', 'mayor', 'fin del período lectivo'));
			throw guarani::error($msj);
		}
		
		if (guarani_fecha::es_menor_timestamp($fecha_fin, $fecha_inicio)) {
			$msj = guarani_mensajes::get('800_control_fechas', array('fin', 'menor', 'inicio'));
			throw guarani::error($msj);
		}
		
		if (guarani_fecha::es_mayor($fecha_tope_bajas, $fecha_fin_periodo_lectivo)) {
			$msj = guarani_mensajes::get('800_control_fechas', array('tope de bajas', 'mayor', 'fin del período lectivo'));
			throw guarani::error($msj);
		}
		
		if (guarani_fecha::es_menor_timestamp($fecha_tope_bajas, $fecha_fin)) {
			$msj = guarani_mensajes::get('800_control_fechas', array('tope de bajas', 'menor', 'fin'));
			throw guarani::error($msj);
		}
	}
}
?>