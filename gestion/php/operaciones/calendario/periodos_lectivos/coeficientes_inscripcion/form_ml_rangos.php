<?php
class form_ml_rangos extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$datos_periodo_inscripcion = $this->controlador()->controlador()->get_datos_periodo();

		$formateo = new toba_formateo('html');

		$dia_inicio = guarani_fecha::get_parte($datos_periodo_inscripcion['fecha_inicio'], 'dia');
		$mes_inicio = guarani_fecha::get_parte($datos_periodo_inscripcion['fecha_inicio'], 'mes') - 1;
		$anio_inicio = guarani_fecha::get_parte($datos_periodo_inscripcion['fecha_inicio'], 'año');
		$hora_inicio = substr($datos_periodo_inscripcion['fecha_inicio'], 11, 8);
		$hh_inicio = intval(substr($hora_inicio, 0, 2));
		$mm_inicio = intval(substr($hora_inicio, 3, 2));
		$fecha_ini_format = $formateo->formato_fecha($datos_periodo_inscripcion['fecha_inicio']);

		$dia_fin = guarani_fecha::get_parte($datos_periodo_inscripcion['fecha_fin'], 'dia');
		$mes_fin = guarani_fecha::get_parte($datos_periodo_inscripcion['fecha_fin'], 'mes') - 1;
		$anio_fin = guarani_fecha::get_parte($datos_periodo_inscripcion['fecha_fin'], 'año');
		$hora_fin = substr($datos_periodo_inscripcion['fecha_fin'], 11, 8);
		$hh_fin = substr($hora_fin, 0, 2);
		$mm_fin = substr($hora_fin, 3, 2);
		$fecha_fin_format = $formateo->formato_fecha($datos_periodo_inscripcion['fecha_fin']);

		$dia_tope_bajas = guarani_fecha::get_parte($datos_periodo_inscripcion['fecha_tope_bajas'], 'dia');
		$mes_tope_bajas = guarani_fecha::get_parte($datos_periodo_inscripcion['fecha_tope_bajas'], 'mes') - 1;
		$anio_tope_bajas = guarani_fecha::get_parte($datos_periodo_inscripcion['fecha_tope_bajas'], 'año');
		$hora_tope_bajas = substr($datos_periodo_inscripcion['fecha_tope_bajas'], 11, 8);
		$hh_tope_bajas = substr($hora_tope_bajas, 0, 2);
		$mm_tope_bajas = substr($hora_tope_bajas, 3, 2);
		$fecha_tope_bajas_format = $formateo->formato_fecha($datos_periodo_inscripcion['fecha_tope_bajas']);

		$fecha_fin = guarani_fecha::convertir_fecha_formato_frontend(substr($datos_periodo_inscripcion['fecha_fin'], 0, 10));
		$fecha_tope_bajas = guarani_fecha::convertir_fecha_formato_frontend(substr($datos_periodo_inscripcion['fecha_tope_bajas'], 0, 10));

		$mje_error_coeficientes = guarani_mensajes::get('800_valor_mayor', array('coeficiente desde', 'coeficiente hasta'));
		$mje_fecha_fin = guarani_mensajes::get('800_control_fechas', array('de fin del rango de coeficientes', 'menor', 'de inicio.'));
		$mje_fecha_inicio_fuera_periodo = guarani_mensajes::get('800_control_fecha_entre', array('inicio del rango de coeficientes', "inicio período inscripción: $fecha_ini_format", "fin período inscripción: $fecha_fin_format"));
		$mje_fecha_fin_fuera_periodo = guarani_mensajes::get('800_control_fecha_entre', array('fin del rango de coeficientes', "inicio período inscripción: $fecha_ini_format", "fin período inscripción: $fecha_fin_format"));
		$mje_fecha_tope_bajas_fuera_rango = guarani_mensajes::get('800_control_fecha_entre', array('de tope de bajas', 'inicio', "tope de bajas período inscripción: $fecha_tope_bajas_format"));

		echo "
		var fecha_inicio_periodo_inscripcion = new Date($anio_inicio, $mes_inicio, $dia_inicio, $hh_inicio, $mm_inicio);
		var fecha_fin_periodo_inscripcion = new Date($anio_fin, $mes_fin, $dia_fin, $hh_fin, $mm_fin);
		var fecha_tope_bajas_periodo_inscripcion = new Date($anio_tope_bajas, $mes_tope_bajas, $dia_tope_bajas, $hh_tope_bajas, $mm_tope_bajas);

		{$this->objeto_js}.evt__validar_datos = function()
		{
			var ok = true;
			var filas = this.filas();
			for (id_fila in filas) {
				// El coeficiente desde debe ser menor o igual el coeficiente hasta.
				if (this.ef('coeficiente_desde').ir_a_fila(filas[id_fila]).get_estado() > this.ef('coeficiente_hasta').ir_a_fila(filas[id_fila]).get_estado()) {
					notificacion.agregar('$mje_error_coeficientes');
					ok = false;
				}
				fecha_inicio = this.ef('fecha_inicio').ir_a_fila(filas[id_fila]).fecha();
				fecha_fin = this.ef('fecha_fin').ir_a_fila(filas[id_fila]).fecha();
				fecha_tope_bajas = this.ef('fecha_tope_bajas').ir_a_fila(filas[id_fila]).fecha();
				// La fecha y hora de inicio debe ser menor o igual a la fecha y hora de finalización.
				if (fecha_fin < fecha_inicio) {
					notificacion.agregar('$mje_fecha_fin');
					ok = false;
				}
				// Tanto la fecha y hora de inicio como la fecha y hora de finalización deben estar comprendidas dentro del rango definido en el período de inscripción.
				if (fecha_inicio < fecha_inicio_periodo_inscripcion || fecha_inicio > fecha_fin_periodo_inscripcion) {
					notificacion.agregar('$mje_fecha_inicio_fuera_periodo');
					ok = false;
				}
				if (fecha_fin < fecha_inicio_periodo_inscripcion || fecha_fin > fecha_fin_periodo_inscripcion) {
					notificacion.agregar('$mje_fecha_fin_fuera_periodo');
					ok = false;
				}
				// La fecha tope de bajas debe estar comprendida en el rango [fecha de inicio, fecha tope de bajas del período de inscripción].
				if (fecha_tope_bajas < fecha_inicio || fecha_tope_bajas > fecha_tope_bajas_periodo_inscripcion) {
					notificacion.agregar('$mje_fecha_tope_bajas_fuera_rango');
					ok = false;
				}
			}
			return ok;
		}

		{$this->objeto_js}.crear_fila = function()
		{
			ei_formulario_ml.prototype.crear_fila.call(this);
			
			let fecha_fin = ['$fecha_fin', '$hora_fin'];
			this.ef('fecha_fin').set_estado(fecha_fin);
			let fecha_tope_bajas = ['$fecha_tope_bajas', '$hora_tope_bajas'];
			this.ef('fecha_tope_bajas').set_estado(fecha_tope_bajas);
		}
		";
	}
}
?>