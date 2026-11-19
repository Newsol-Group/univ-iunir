<?php
class form_datos_excepcion_periodo extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		$this->generar_html_ef('fecha_inicio');
		echo '<table border="0" style="border-collapse:collapse;"><tr><td>';
		$this->generar_html_ef('tipo_fin');
		echo '</td><td>';
		$this->generar_html_ef('fecha_fin');
		echo '</td><td>';
		$this->generar_html_ef('hs_previas_fin');
		echo '</td><td>';
		$this->generar_html_ef('dias_previos_fin');
		echo '</td></tr></table>';
		echo '<table border="0" style="border-collapse:collapse;"><tr><td>';
		$this->generar_html_ef('tipo_tope_bajas');
		echo '</td><td>';
		$this->generar_html_ef('fecha_tope_bajas');
		echo '</td><td>';
		$this->generar_html_ef('hs_previas_tope_bajas');
		echo '</td><td>';
		$this->generar_html_ef('dias_previos_tope_bajas');
		echo '</td></tr></table>';
		echo '<table border="0" style="border-collapse:collapse;"><tr><td>';
		$this->generar_html_ef('habilitado_interfaz');
		echo '</td></tr></table>';
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$tope_fecha = turno_de_examen::tope_fecha;
		$tope_dias = turno_de_examen::tope_dias_antes;
		$tope_dias_habiles = turno_de_examen::tope_dias_antes_habiles;
		$tope_hs = turno_de_examen::tope_hs_antes;
		$tope_hs_habiles = turno_de_examen::tope_hs_antes_habiles;

		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__tipo_fin__procesar = function(es_inicial)
		{
			if (this.ef('tipo_fin').get_estado() == $tope_fecha) {
				//Muestro fecha y oculto las horas
				this.ef('fecha_fin').mostrar();
				this.ef('hs_previas_fin').ocultar();
				this.ef('hs_previas_fin').resetear_estado();
				this.ef('dias_previos_fin').ocultar();
				this.ef('dias_previos_fin').resetear_estado();				
				this.ef('tipo_tope_bajas').activar();
			} 
            if (this.ef('tipo_fin').get_estado() == $tope_dias ||
                this.ef('tipo_fin').get_estado() == $tope_dias_habiles) {
				//Muestro horas y oculto la fecha
				this.ef('dias_previos_fin').mostrar();
				this.ef('fecha_fin').ocultar();
				this.ef('fecha_fin').resetear_estado();
                this.ef('hs_previas_fin').ocultar();
				this.ef('hs_previas_fin').resetear_estado();
			}
            if (this.ef('tipo_fin').get_estado() == $tope_hs ||
                this.ef('tipo_fin').get_estado() == $tope_hs_habiles) {
                this.ef('hs_previas_fin').mostrar();
				this.ef('fecha_fin').ocultar();
				this.ef('fecha_fin').resetear_estado();
                this.ef('dias_previos_fin').ocultar();
				this.ef('dias_previos_fin').resetear_estado();
            }
		}
		
		{$this->objeto_js}.evt__tipo_tope_bajas__procesar = function(es_inicial)
		{
			if (this.ef('tipo_tope_bajas').get_estado() == $tope_fecha) {
				//Muestro fecha y oculto las horas
				this.ef('fecha_tope_bajas').mostrar();
				this.ef('hs_previas_tope_bajas').ocultar();
				this.ef('hs_previas_tope_bajas').resetear_estado();
				this.ef('dias_previos_tope_bajas').ocultar();
				this.ef('dias_previos_tope_bajas').resetear_estado();				
			} 
            if (this.ef('tipo_tope_bajas').get_estado() == $tope_dias ||
                this.ef('tipo_tope_bajas').get_estado() == $tope_dias_habiles) {
				//Muestro horas y oculto la fecha
				this.ef('dias_previos_tope_bajas').mostrar();
				this.ef('fecha_tope_bajas').ocultar();
				this.ef('fecha_tope_bajas').resetear_estado();
                this.ef('hs_previas_tope_bajas').ocultar();
				this.ef('hs_previas_tope_bajas').resetear_estado();
			}
            if (this.ef('tipo_tope_bajas').get_estado() == $tope_hs ||
                this.ef('tipo_tope_bajas').get_estado() == $tope_hs_habiles) {
                this.ef('hs_previas_tope_bajas').mostrar();
				this.ef('fecha_tope_bajas').ocultar();
				this.ef('fecha_tope_bajas').resetear_estado();
                this.ef('dias_previos_tope_bajas').ocultar();
				this.ef('dias_previos_tope_bajas').resetear_estado();
            }
		}

		//---- Validacion de EFs -----------------------------------
		";

		$msj_falta_fecha_fin = guarani_mensajes::get('dato_faltante', array($this->ef('fecha_fin')->get_etiqueta()));
		$msj_falta_fecha_tope_bajas = guarani_mensajes::get('dato_faltante', array($this->ef('fecha_tope_bajas')->get_etiqueta()));
		$msj_falta_hs_previas_fin = guarani_mensajes::get('dato_faltante', array($this->ef('hs_previas_fin')->get_etiqueta()));
		$msj_falta_hs_previas_tope_bajas = guarani_mensajes::get('dato_faltante', array($this->ef('hs_previas_tope_bajas')->get_etiqueta()));
		$msj_falta_dias_previos_fin = guarani_mensajes::get('dato_faltante', array($this->ef('dias_previos_fin')->get_etiqueta()));
		$msj_falta_dias_previos_tope_bajas = guarani_mensajes::get('dato_faltante', array($this->ef('dias_previos_tope_bajas')->get_etiqueta()));

		echo "
		{$this->objeto_js}.evt__validar_datos = function()
		{
			ok = true;

			var ef = 'fecha_fin';
			if (this.ef(ef) != undefined && ! this.ef(ef).es_oculto() && ! this.ef(ef).tiene_estado()) {
				notificacion.agregar('$msj_falta_fecha_fin');
				ok = false;
			}

			var ef = 'fecha_tope_bajas';
			if (this.ef(ef) != undefined && ! this.ef(ef).es_oculto() && ! this.ef(ef).tiene_estado()) {
				notificacion.agregar('$msj_falta_fecha_tope_bajas');
				ok = false;
			}

			var ef = 'hs_previas_fin';
			if (this.ef(ef) != undefined && ! this.ef(ef).es_oculto() && ! this.ef(ef).tiene_estado()) {
				notificacion.agregar('$msj_falta_hs_previas_fin');
				ok = false;
			}

			var ef = 'hs_previas_tope_bajas';
			if (this.ef(ef) != undefined && ! this.ef(ef).es_oculto() && ! this.ef(ef).tiene_estado()) {
				notificacion.agregar('$msj_falta_hs_previas_tope_bajas');
				ok = false;
			}
            
			var ef = 'dias_previos_tope_bajas';
			if (this.ef(ef) != undefined && ! this.ef(ef).es_oculto() && ! this.ef(ef).tiene_estado()) {
				notificacion.agregar('$msj_falta_dias_previos_tope_bajas');
				ok = false;
			}
            
            var ef = 'dias_previos_fin';
			if (this.ef(ef) != undefined && ! this.ef(ef).es_oculto() && ! this.ef(ef).tiene_estado()) {
				notificacion.agregar('$msj_falta_dias_previos_fin');
				ok = false;
			}

			return ok;
		}
		
		";
	}

}