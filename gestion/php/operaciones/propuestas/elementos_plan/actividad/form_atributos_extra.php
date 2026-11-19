<?php
class form_atributos_extra extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table width="100%">';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('insc_cursada_por_prioridad');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('prioridad_cant_maxima', '200px');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td colspan=2>';
				$this->generar_html_ef('barra');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td colspan=2>';
				$this->generar_html_ef('dia_examen');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('hora_inicio_examen');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('hora_fin_examen', '200px');
				echo '</td>';
			echo '</tr>';
		echo '</table>'; 
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_hora_inicio_oblig = guarani_mensajes::get('dato_faltante', array($this->ef('hora_inicio_examen')->get_etiqueta()));
		$msj_hora_posterior = guarani_mensajes::get('800_error_rango_horas');

		// En el caso que se seleccione un dia para el examen, validar que se ingrese la Hora de Inicio del Examen.
		// Si se ingresa la Hora de Finalización del exámen, esta debe ser mayor que la Hora de Inicio del Examen.
		echo "
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__insc_cursada_por_prioridad__procesar = function(es_inicial, fila)
		{
			if (this.ef('insc_cursada_por_prioridad').get_estado() == '".comunes::si."') {
				this.ef('prioridad_cant_maxima').set_obligatorio(true);
				this.ef('prioridad_cant_maxima').set_solo_lectura(false);
			} else {
				this.ef('prioridad_cant_maxima').resetear_estado();
				this.ef('prioridad_cant_maxima').set_obligatorio(false);
				this.ef('prioridad_cant_maxima').set_solo_lectura(true);
			}
		}

		{$this->objeto_js}.evt__validar_datos = function()
		{
			var ok = true;

			if (! this.ef('dia_examen').tiene_estado()) {
					this.ef('hora_inicio_examen').set_estado('');
					this.ef('hora_fin_examen').set_estado('');
			} else {
				if (! this.ef('hora_inicio_examen').tiene_estado()) {
					notificacion.agregar('$msj_hora_inicio_oblig');
					ok = false;
				} else {
					if (this.ef('hora_fin_examen').tiene_estado() && this.ef('hora_inicio_examen').hora() > this.ef('hora_fin_examen').hora()) {
						notificacion.agregar('$msj_hora_posterior');
						ok = false;
					}
				}
			}
			return ok;
		}
		";
	}
}
?>