<?php
class form_periodo_insc_fechas extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		$this->generar_html_ef('fecha_inicio');
		echo '<table border="0" style="border-collapse:collapse;"><tr><td>';
		$this->generar_html_ef('tipo_fin');
		echo '</td><td>';
		$this->generar_html_ef('fecha_fin', '45px');
		echo '</td><td>';
		$this->generar_html_ef('hs_previas_fin', '45px');
		echo '</td><td>';
		$this->generar_html_ef('dias_previos_fin', '45px');
		echo '</td></tr></table>';
		echo '<table border="0" style="border-collapse:collapse;"><tr><td>';
		$this->generar_html_ef('tipo_tope_bajas');
		echo '</td><td>';
		$this->generar_html_ef('fecha_tope_bajas', '45px');
		echo '</td><td>';
		$this->generar_html_ef('hs_previas_tope_bajas', '45px');
		echo '</td><td>';
		$this->generar_html_ef('dias_previos_tope_bajas', '45px');
		echo '</td></tr></table>';
		echo "</fieldset>";
		echo '<fieldset><legend>Habilitación</legend><table width=100%><tr><td>';
		$this->generar_html_ef('habilitado');
		echo '</td><td>';
		$this->generar_html_ef('tipo_alumno', '45px');
		echo '</td><td>';
		$this->generar_html_ef('habilitado_interfaz', '45px');
		echo '</td></tr></table>';
		echo "</fieldset>";
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
		$tope_examen = turno_de_examen::tope_fecha_hora_examen;

		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__tipo_fin__procesar = function(es_inicial)
		{
			var tipo_fin = this.ef('tipo_fin').get_estado();
			if (tipo_fin == $tope_fecha) {
				//Muestro fecha y oculto las horas
				this.ef('fecha_fin').mostrar();
				this.ef('hs_previas_fin').ocultar();
				this.ef('hs_previas_fin').resetear_estado();
				this.ef('dias_previos_fin').ocultar();
				this.ef('dias_previos_fin').resetear_estado();				
				this.ef('tipo_tope_bajas').activar();
			} 
            if (tipo_fin == $tope_dias || tipo_fin == $tope_dias_habiles) {
				//Muestro horas y oculto la fecha
				this.ef('dias_previos_fin').mostrar();
				this.ef('fecha_fin').ocultar();
				this.ef('fecha_fin').resetear_estado();
                this.ef('hs_previas_fin').ocultar();
				this.ef('hs_previas_fin').resetear_estado();
				this.ef('tipo_tope_bajas').set_estado(tipo_fin);
                this.ef('tipo_tope_bajas').desactivar();
			}
            if (tipo_fin == $tope_hs || tipo_fin == $tope_hs_habiles) {
                this.ef('hs_previas_fin').mostrar();
				this.ef('fecha_fin').ocultar();
				this.ef('fecha_fin').resetear_estado();
                this.ef('dias_previos_fin').ocultar();
				this.ef('dias_previos_fin').resetear_estado();
				this.ef('tipo_tope_bajas').set_estado(tipo_fin);
				this.ef('tipo_tope_bajas').desactivar();
            }
		}
		
		{$this->objeto_js}.evt__tipo_tope_bajas__procesar = function(es_inicial)
		{
			var tipo_tope_bajas = this.ef('tipo_tope_bajas').get_estado();
                        if(tipo_tope_bajas == $tope_examen){
                            //oculto fecha hora y dias
                                this.ef('fecha_tope_bajas').ocultar();
                                this.ef('fecha_tope_bajas').resetear_estado();
				this.ef('hs_previas_tope_bajas').ocultar();
				this.ef('hs_previas_tope_bajas').resetear_estado();
                                this.ef('dias_previos_tope_bajas').ocultar();
				this.ef('dias_previos_tope_bajas').resetear_estado();	
                        }
			if (tipo_tope_bajas == $tope_fecha) {
				//Muestro fecha y oculto las horas
				this.ef('fecha_tope_bajas').mostrar();
				this.ef('hs_previas_tope_bajas').ocultar();
				this.ef('hs_previas_tope_bajas').resetear_estado();
				this.ef('dias_previos_tope_bajas').ocultar();
				this.ef('dias_previos_tope_bajas').resetear_estado();				
			} 
            if (tipo_tope_bajas == $tope_dias || tipo_tope_bajas == $tope_dias_habiles) {
				//Muestro horas y oculto la fecha
				this.ef('dias_previos_tope_bajas').mostrar();
				this.ef('fecha_tope_bajas').ocultar();
				this.ef('fecha_tope_bajas').resetear_estado();
                this.ef('hs_previas_tope_bajas').ocultar();
				this.ef('hs_previas_tope_bajas').resetear_estado();
			}
            if (tipo_tope_bajas == $tope_hs || tipo_tope_bajas == $tope_hs_habiles) {
                this.ef('hs_previas_tope_bajas').mostrar();
				this.ef('fecha_tope_bajas').ocultar();
				this.ef('fecha_tope_bajas').resetear_estado();
                this.ef('dias_previos_tope_bajas').ocultar();
				this.ef('dias_previos_tope_bajas').resetear_estado();
            }
		}

		{$this->objeto_js}.evt__habilitado__procesar = function(es_inicial)
		{
			if (this.ef('habilitado').get_estado() == '".comunes::si."') {
				this.ef('habilitado_interfaz').activar();
				this.ef('tipo_alumno').activar();
			} else {
				this.ef('habilitado_interfaz').set_estado(".comunes::interfaz_todas.");
				this.ef('habilitado_interfaz').desactivar();
				this.ef('tipo_alumno').set_estado('".periodo_de_inscripcion::tipo_alumno_todos."');
				this.ef('tipo_alumno').desactivar();
			}
		}
		";
	}
}
?>