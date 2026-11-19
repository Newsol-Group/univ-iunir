<?php
class form_ml_tramos extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		//Obtengo las Fechas Inicio y Fin por defecto: Inicio y Fin Dictado
		$datos_per = $this->controlador()->get_datos_per_lectivo();
		$fecha_inicio_periodo_lec = $datos_per['fecha_inicio_dictado'];
		$fecha_fin_periodo_lec = $datos_per['fecha_fin_dictado'];
		
		$dia_ini = guarani_fecha::get_parte($fecha_inicio_periodo_lec, 'dia');
		$mes_ini = guarani_fecha::get_parte($fecha_inicio_periodo_lec, 'mes') - 1;
		$anio_ini = guarani_fecha::get_parte($fecha_inicio_periodo_lec, 'año');
		
		$dia_fin = guarani_fecha::get_parte($fecha_fin_periodo_lec, 'dia');
		$mes_fin = guarani_fecha::get_parte($fecha_fin_periodo_lec, 'mes') - 1;
		$anio_fin = guarani_fecha::get_parte($fecha_fin_periodo_lec, 'año');
		
		$formateo  = new toba_formateo('html');
		$fecha_ini_format = $formateo->formato_fecha($fecha_inicio_periodo_lec);
		$fecha_fin_format = $formateo->formato_fecha($fecha_fin_periodo_lec);
		
		//Mensajes de error
		$mje_fecha_fin = guarani_mensajes::get('800_control_fechas', array("de Fin del Tramo de Asistencia", 'menor o igual', 'de Inicio'));
		$mje_fecha_fin_fuera_periodo = guarani_mensajes::get('800_control_fecha_entre', array("Fin del Tramo de Asistencia", "Inicio Período: $fecha_ini_format", "Fin Período: $fecha_fin_format"));
		$mje_fecha_inicio_fuera_periodo = guarani_mensajes::get('800_control_fecha_entre', array("Inicio del Tramo de Asistencia", "Inicio Período: $fecha_ini_format", "Fin Período: $fecha_fin_format"));
		
		echo "
		
		var fecha_ini_per_lectivo = new Date($anio_ini, $mes_ini, $dia_ini);
		var fecha_fin_per_lectivo = new Date($anio_fin, $mes_fin, $dia_fin);
		
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.crear_fila = function()
		{
			ei_formulario_ml.prototype.crear_fila.call(this);
			
			var fecha_ini = '$fecha_ini_format';
			var fecha_fin = '$fecha_fin_format';
			var numero_mayor = 0;
			var filas = this.filas();
			
			for (id_fila in filas) {
				var numero = this.ef('nro_tramo').ir_a_fila(filas[id_fila]).get_estado();
				if (numero > numero_mayor) {
					numero_mayor = numero;
				}
			}
			
			this.ef('nro_tramo').set_estado(numero_mayor + 1);
			this.ef('fecha_inicio').set_estado(fecha_ini);
			this.ef('fecha_fin').set_estado(fecha_fin);
			this.ef('tramo_en_uso').set_estado(0);
		}

		{$this->objeto_js}.eliminar_seleccionada = function()
		{
			var filas = this.filas();
			if ((filas.length) == 1) {
				notificacion.limpiar();
				notificacion.agregar('No se puede eliminar la fila. Debe haber al menos un tramo para el período');
				notificacion.mostrar();
			} else {
				ei_formulario_ml.prototype.eliminar_seleccionada.call(this);
			}
		}

		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__fecha_fin__validar = function(fila)
		{
			if (this.ef('fecha_fin').ir_a_fila(fila).tiene_estado()) {
				fecha_fin = this.ef('fecha_fin').ir_a_fila(fila).fecha();
				
				if (this.ef('fecha_inicio').ir_a_fila(fila).tiene_estado()) {
					fecha_inicio = this.ef('fecha_inicio').ir_a_fila(fila).fecha();
					
					if (fecha_fin <= fecha_inicio) {
						this.ef('fecha_fin').ir_a_fila(fila).set_error('$mje_fecha_fin');
						return false;
					}
				}
				
				if (fecha_fin < fecha_ini_per_lectivo || fecha_fin > fecha_fin_per_lectivo) {
					this.ef('fecha_fin').ir_a_fila(fila).set_error('$mje_fecha_fin_fuera_periodo');
					return false;
				}
			}
			
			return true;
		}
		
		{$this->objeto_js}.evt__fecha_inicio__validar = function(fila)
		{
			if (this.ef('fecha_inicio').ir_a_fila(fila).tiene_estado()) {
				fecha_inicio = this.ef('fecha_inicio').ir_a_fila(fila).fecha();
				
				if (fecha_inicio < fecha_ini_per_lectivo || fecha_inicio > fecha_fin_per_lectivo) {
					this.ef('fecha_inicio').ir_a_fila(fila).set_error('$mje_fecha_inicio_fuera_periodo');
					return false;
				}
			}
			
			return true;
		}
		
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__nro_tramo__procesar = function(es_inicial, fila)
		{
			if (this.ef('tramo_en_uso').ir_a_fila(fila).tiene_estado()) {
				var en_uso = this.ef('tramo_en_uso').ir_a_fila(fila).get_estado();
				if (en_uso == 1) {
					this.ef('nro_tramo').ir_a_fila(fila).set_solo_lectura(true);
				}
			}
		}
		
		{$this->objeto_js}.evt__fecha_inicio__procesar = function(es_inicial, fila)
		{
			if (this.ef('tramo_en_uso').ir_a_fila(fila).tiene_estado()) {
				var en_uso = this.ef('tramo_en_uso').ir_a_fila(fila).get_estado();
				if (en_uso == 1) {
					this.ef('fecha_inicio').ir_a_fila(fila).set_solo_lectura(true);
				}
			}
		}
		
		{$this->objeto_js}.evt__fecha_fin__procesar = function(es_inicial, fila)
		{
			if (this.ef('tramo_en_uso').ir_a_fila(fila).tiene_estado()) {
				var en_uso = this.ef('tramo_en_uso').ir_a_fila(fila).get_estado();
				if (en_uso == 1) {
					this.ef('fecha_fin').ir_a_fila(fila).set_solo_lectura(true);
					$('#js_form_38000320_form_ml_tramos_eliminar'+fila).hide();
				}
			}
		}
		";
		
		if ($this->controlador()->debe_crear_tramo()) {
			echo "{$this->objeto_js}.crear_fila();";
		}
	}


}
?>