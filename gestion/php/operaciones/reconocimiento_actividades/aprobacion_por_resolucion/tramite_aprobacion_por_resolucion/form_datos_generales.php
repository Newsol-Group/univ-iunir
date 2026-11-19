<?php
/**
 *
 * 
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Aprobación por Resolución
 * 
 */
class form_datos_generales extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('documento');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('fecha', '90px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('alumno');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('tipo_reconocimiento');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('observaciones');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}	
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		
		echo "
		var tipo_original;
		var seteo_tipo_original = false;

		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__documento__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				if (this.ef('documento').tiene_estado()) {
			 		var documento = this.ef('documento').get_estado();
					var params= [documento];
					this.controlador.ajax('fecha_tramite_default', params, this, this.respuesta_fecha_tramite_default);
				}
			}
		}

		{$this->objeto_js}.respuesta_fecha_tramite_default = function(respuesta)
		{
			if (typeof respuesta == 'object') {
				fecha_resolucion = respuesta['fecha'];
				// Se setea como valor default de la fecha del Trámite la fecha del documento que lo respalda.
				this.ef('fecha').set_estado(fecha_resolucion);
			}
		}

		{$this->objeto_js}.evt__tipo_reconocimiento__procesar = function(es_inicial)
		{
			var tipo_reconocimiento = this.ef('tipo_reconocimiento').get_estado();

			if (! es_inicial) {
				if (! seteo_tipo_original) {
					this.controlador.ajax('validar_cambio_tipo', [tipo_reconocimiento], this, this.respuesta_cambio_tipo);
				}
				seteo_tipo_original = false;
	      	} else if (this.ef('tipo_reconocimiento').tiene_estado()) {
	      		 tipo_original = tipo_reconocimiento;
	      	}
		}

		{$this->objeto_js}.respuesta_cambio_tipo = function(respuesta)
		{
			if (typeof respuesta == 'object') {
				if (confirm(respuesta['mensaje'])) {
					this.set_evento(new evento_ei('cambio_tipo_reconocimiento'), true); 
	      		} else {
	      			if (tipo_original) {
	      				seteo_tipo_original = true;
	      				this.ef('tipo_reconocimiento').set_estado(tipo_original);
	      			}
	      			return false;
	      		}
			}
		}
		";
	}
}
?>